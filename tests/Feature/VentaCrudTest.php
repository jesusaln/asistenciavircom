<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\User;
use App\Models\Venta;
use App\Models\CuentaBancaria;
use App\Services\InventarioService;
use App\Support\EmpresaResolver;

use Tests\TestCase;
use Spatie\Permission\Models\Role;

class VentaCrudTest extends TestCase
{

    protected $empresa;
    protected $almacen;
    protected $admin;
    protected $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        // Limpieza agresiva de tablas
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        \Illuminate\Support\Facades\DB::table('ventas')->truncate();
        \Illuminate\Support\Facades\DB::table('venta_items')->truncate();
        \Illuminate\Support\Facades\DB::table('cuentas_por_cobrar')->truncate();
        \Illuminate\Support\Facades\DB::table('inventarios')->truncate();
        \Illuminate\Support\Facades\DB::table('productos')->truncate();
        \Illuminate\Support\Facades\DB::table('clientes')->truncate();
        \Illuminate\Support\Facades\DB::table('almacenes')->truncate();
        \Illuminate\Support\Facades\DB::table('users')->truncate();
        \Illuminate\Support\Facades\DB::table('empresas')->truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        // Crear empresa única para el test con ID 1
        $this->empresa = Empresa::updateOrCreate(
            ['id' => 1],
            [
                'nombre_razon_social' => 'Empresa Test S.A.',
                'tipo_persona' => 'moral',
                'regimen_fiscal' => '601',
                'uso_cfdi' => 'G03',
                'estado' => 'activo',
                'rfc' => 'XAXX010101000',
                'email' => 'admin@test.com',
                'telefono' => '1234567890',
                'calle' => 'Calle Falsa 123',
                'codigo_postal' => '12345',
                'municipio' => 'Municipio Test',
                'numero_exterior' => '100',
                'colonia' => 'Colonia Test',
                'pais' => 'México'
            ]
        );

        // Forzar contexto de empresa a ID 1 inmediatamente
        EmpresaResolver::setContext(1);

        // Inyectar configuración de IVA al 16%
        \App\Models\EmpresaConfiguracion::updateOrCreate(
            ['empresa_id' => 1],
            [
                'nombre_empresa' => 'Empresa Test S.A.',
                'iva_porcentaje' => 16.00,
                'moneda' => 'MXN',
                'enable_retencion_iva' => false,
                'enable_retencion_isr' => false,
                'color_principal' => '#3B82F6',
                'color_secundario' => '#1E40AF',
                'formato_numeros' => 'es-ES',
                'formato_fecha' => 'd/m/Y',
                'formato_hora' => 'H:i:s',
            ]
        );

        // Crear usuario admin asociado a esa empresa
        $this->admin = User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'empresa_id' => 1
            ]
        );
        $this->admin->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));

        // Configurar almacén por defecto
        $this->almacen = Almacen::updateOrCreate(
            ['id' => 1],
            [
                'nombre' => 'Almacén Principal Test',
                'empresa_id' => 1,
                'estado' => 'activo'
            ]
        );

        $this->admin->update([
            'almacen_id' => $this->almacen->id,
            'almacen_venta_id' => $this->almacen->id
        ]);

        $this->cliente = Cliente::factory()->create(['empresa_id' => 1]);

        $this->actingAs($this->admin);

        // Disable model events/observers that may block in testing
        // Venta::unsetEventDispatcher();
        // \App\Models\CuentasPorCobrar::unsetEventDispatcher();
        // \App\Models\ProductoSerie::unsetEventDispatcher();
    }

    public function test_can_list_ventas()
    {
        $venta = $this->createVentaGeneric($this->empresa, $this->almacen);

        $response = $this->get(route('ventas.index'));

        $response->assertStatus(200);
        $response->assertSee($venta->numero_venta);
    }

    public function test_can_create_venta_with_products_and_services()
    {
        $producto = Producto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'precio_venta' => 500,
            'stock' => 10,
            'estado' => 'activo'
        ]);

        // Crear inventario manual
        \Illuminate\Support\Facades\DB::table('inventarios')->insert([
            'producto_id' => $producto->id,
            'almacen_id' => $this->almacen->id,
            'cantidad' => 10,
            'empresa_id' => $this->empresa->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $servicio = Servicio::factory()->create([
            'empresa_id' => $this->empresa->id,
            'precio' => 200,
            'estado' => 'activo'
        ]);

        $payload = [
            'cliente_id' => $this->cliente->id,
            'almacen_id' => $this->almacen->id,
            'metodo_pago' => 'efectivo',
            'productos' => [
                [
                    'id' => $producto->id,
                    'cantidad' => 2,
                    'precio' => 500,
                    'descuento' => 0
                ]
            ],
            'servicios' => [
                [
                    'id' => $servicio->id,
                    'cantidad' => 1,
                    'precio' => 200,
                    'descuento' => 0
                ]
            ],
            'descuento_general' => 0,
            'notas' => 'Venta de prueba'
        ];

        $response = $this->from(route('ventas.create'))
            ->post(route('ventas.store'), $payload);

        $response->assertRedirect(route('ventas.index'));
        $response->assertSessionHasNoErrors();

        // Verificar registro en BD
        $this->assertDatabaseHas('ventas', [
            'cliente_id' => $this->cliente->id,
            'metodo_pago' => 'efectivo',
            'total' => 1392, // (500*2 + 200) * 1.16 = 1200 * 1.16 = 1392
        ]);

        // Verificar reducción de stock
        $this->assertEquals(8, $producto->fresh()->stock);

        // Verificar que se creó la cuenta por cobrar y que está pagada (por ser efectivo)
        $venta = Venta::latest()->first();

        $this->assertNotNull($venta->cuentaPorCobrar);
        $this->assertEquals(0, $venta->cuentaPorCobrar->monto_pendiente);
        $this->assertTrue($venta->pagado);
    }

    public function test_validate_credit_limit()
    {
        $cliente = Cliente::factory()->create([
            'empresa_id' => $this->empresa->id,
            'credito_activo' => true,
            'limite_credito' => 100 // Límite muy bajo
        ]);

        $producto = Producto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'precio_venta' => 500,
            'stock' => 10,
            'estado' => 'activo'
        ]);

        \Illuminate\Support\Facades\DB::table('inventarios')->insert([
            'producto_id' => $producto->id,
            'almacen_id' => $this->almacen->id,
            'cantidad' => 10,
            'empresa_id' => $this->empresa->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'cliente_id' => $cliente->id,
            'almacen_id' => $this->almacen->id,
            'metodo_pago' => 'credito',
            'productos' => [
                [
                    'id' => $producto->id,
                    'cantidad' => 1,
                    'precio' => 500,
                    'descuento' => 0
                ]
            ],
            'descuento_general' => 0
        ];

        $response = $this->from(route('ventas.create'))
            ->post(route('ventas.store'), $payload);

        $response->assertSessionHasErrors(['message']);
        $this->assertDatabaseMissing('ventas', ['cliente_id' => $cliente->id]);
    }

    public function test_validate_insufficient_stock()
    {
        $producto = Producto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'precio_venta' => 100,
            'stock' => 1,
            'estado' => 'activo'
        ]);

        \Illuminate\Support\Facades\DB::table('inventarios')->insert([
            'producto_id' => $producto->id,
            'almacen_id' => $this->almacen->id,
            'cantidad' => 1,
            'empresa_id' => $this->empresa->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'cliente_id' => $this->cliente->id,
            'almacen_id' => $this->almacen->id,
            'metodo_pago' => 'efectivo',
            'productos' => [
                [
                    'id' => $producto->id,
                    'cantidad' => 10, // Intentar vender 10
                    'precio' => 100,
                    'descuento' => 0
                ]
            ],
            'descuento_general' => 0
        ];

        $response = $this->from(route('ventas.create'))
            ->post(route('ventas.store'), $payload);

        $response->assertSessionHasErrors(['message']);
        $this->assertDatabaseMissing('ventas', ['cliente_id' => $this->cliente->id]);
    }

    public function test_can_update_venta_client_and_items()
    {
        $producto1 = Producto::factory()->create(['empresa_id' => $this->empresa->id, 'precio_venta' => 100, 'stock' => 10]);
        $cliente1 = Cliente::factory()->create([
            'empresa_id' => $this->empresa->id,
            'credito_activo' => true,
            'limite_credito' => 10000
        ]);

        \Illuminate\Support\Facades\DB::table('inventarios')->insert([
            'producto_id' => $producto1->id,
            'almacen_id' => $this->almacen->id,
            'cantidad' => 10,
            'empresa_id' => $this->empresa->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crear venta inicial como CRÉDITO para que permita edición
        $payloadCreate = [
            'cliente_id' => $cliente1->id,
            'almacen_id' => $this->almacen->id,
            'metodo_pago' => 'credito',
            'productos' => [['id' => $producto1->id, 'cantidad' => 2, 'precio' => 100, 'descuento' => 0]],
            'descuento_general' => 0
        ];

        $this->post(route('ventas.store'), $payloadCreate);
        $venta = Venta::latest()->first();

        $this->assertNotNull($venta, 'La venta no se creó. Errores: ' . json_encode(session('errors') ? session('errors')->getMessages() : []));

        // Nuevos datos para actualización
        $cliente2 = Cliente::factory()->create([
            'empresa_id' => $this->empresa->id,
            'credito_activo' => true,
            'limite_credito' => 10000
        ]);
        $producto2 = Producto::factory()->create(['empresa_id' => $this->empresa->id, 'precio_venta' => 200, 'stock' => 10]);

        \Illuminate\Support\Facades\DB::table('inventarios')->insert([
            'producto_id' => $producto2->id,
            'almacen_id' => $this->almacen->id,
            'cantidad' => 10,
            'empresa_id' => $this->empresa->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payloadUpdate = [
            'cliente_id' => $cliente2->id,
            'numero_venta' => $venta->numero_venta,
            'fecha' => $venta->fecha->format('Y-m-d'),
            'estado' => $venta->estado->value,
            'metodo_pago' => 'credito',
            'productos' => [['id' => $producto2->id, 'cantidad' => 1, 'precio' => 200, 'descuento' => 0]],
            'descuento_general' => 0
        ];

        $response = $this->put(route('ventas.update', $venta), $payloadUpdate);

        $response->assertRedirect(route('ventas.index'));
        $this->assertDatabaseHas('ventas', [
            'id' => $venta->id,
            'cliente_id' => $cliente2->id,
            'total' => 232.00 // 200 * 1.16
        ]);

        // Verificar que el producto viejo devolvió stock y el nuevo redujo
        $this->assertEquals(10, $producto1->fresh()->stock);
        $this->assertEquals(9, $producto2->fresh()->stock);
    }

    public function test_can_cancel_venta()
    {
        $producto = Producto::factory()->create(['empresa_id' => $this->empresa->id, 'precio_venta' => 100, 'stock' => 5]);

        \Illuminate\Support\Facades\DB::table('inventarios')->insert([
            'producto_id' => $producto->id,
            'almacen_id' => $this->almacen->id,
            'cantidad' => 5,
            'empresa_id' => $this->empresa->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cliente = Cliente::factory()->create([
            'empresa_id' => $this->empresa->id,
            'credito_activo' => true,
            'limite_credito' => 10000
        ]);

        $payload = [
            'cliente_id' => $cliente->id,
            'almacen_id' => $this->almacen->id,
            'metodo_pago' => 'credito',
            'productos' => [['id' => $producto->id, 'cantidad' => 2, 'precio' => 100, 'descuento' => 0]],
        ];

        $this->post(route('ventas.store'), $payload);
        $venta = Venta::latest()->first();
        $this->assertEquals(3, $producto->fresh()->stock);

        // Debug: Check existing inventories
        // $invs = \Illuminate\Support\Facades\DB::table('inventarios')->get();
        // dump($invs);

        $response = $this->post(route('ventas.cancel', $venta->id), ['motivo' => 'Error de captura']);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(\App\Enums\EstadoVenta::Cancelada, $venta->fresh()->estado);
        $this->assertEquals(5, $producto->fresh()->stock); // Stock restaurado
    }

    public function test_can_delete_cancelled_venta()
    {
        $venta = $this->createVentaGeneric($this->empresa, $this->almacen);

        // Forzar estado cancelada y crear movimiento de inventario simulado si es necesario, 
        // pero mejor usar el flujo real de cancelación para que los movimientos existan

        // Primero aseguramos que la venta tiene un producto para que el cancel() genere movimientos
        $producto = Producto::factory()->create(['empresa_id' => $this->empresa->id, 'stock' => 10]);

        \Illuminate\Support\Facades\DB::table('inventarios')->insert([
            'producto_id' => $producto->id,
            'almacen_id' => $this->almacen->id,
            'cantidad' => 10,
            'empresa_id' => $this->empresa->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \App\Models\VentaItem::create([
            'venta_id' => $venta->id,
            'ventable_id' => $producto->id,
            'ventable_type' => Producto::class,
            'cantidad' => 1,
            'precio' => 100,
            'subtotal' => 100,
            'total' => 116
        ]);

        $this->post(route('ventas.cancel', $venta->id), ['motivo' => 'Test delete']);

        $response = $this->delete(route('ventas.destroy', $venta->id));

        $response->assertRedirect(route('ventas.index'));
        $this->assertSoftDeleted('ventas', ['id' => $venta->id]);
    }

    public function test_cannot_delete_active_venta()
    {
        $venta = $this->createVentaGeneric($this->empresa, $this->almacen);
        $venta->update(['estado' => \App\Enums\EstadoVenta::Aprobada]);

        $response = $this->from(route('ventas.index'))
            ->delete(route('ventas.destroy', $venta->id));

        $response->assertRedirect(route('ventas.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('ventas', ['id' => $venta->id, 'deleted_at' => null]);
    }

    public function test_can_create_venta_with_serialized_products()
    {
        $producto = Producto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Laptop Serializada',
            'requiere_serie' => true,
            'stock' => 5,
            'estado' => 'activo'
        ]);

        \Illuminate\Support\Facades\DB::table('inventarios')->insert([
            'producto_id' => $producto->id,
            'almacen_id' => $this->almacen->id,
            'cantidad' => 5,
            'empresa_id' => $this->empresa->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crear series en el almacén
        $serie1 = \App\Models\ProductoSerie::create([
            'empresa_id' => $this->empresa->id,
            'producto_id' => $producto->id,
            'almacen_id' => $this->almacen->id,
            'numero_serie' => 'SN-LAPTOP-001',
            'estado' => 'en_stock'
        ]);

        $serie2 = \App\Models\ProductoSerie::create([
            'empresa_id' => $this->empresa->id,
            'producto_id' => $producto->id,
            'almacen_id' => $this->almacen->id,
            'numero_serie' => 'SN-LAPTOP-002',
            'estado' => 'en_stock'
        ]);

        $payload = [
            'cliente_id' => $this->cliente->id,
            'almacen_id' => $this->almacen->id,
            'metodo_pago' => 'efectivo',
            'productos' => [
                [
                    'id' => $producto->id,
                    'cantidad' => 2,
                    'precio' => 1000,
                    'descuento' => 0,
                    'series' => ['SN-LAPTOP-001', 'SN-LAPTOP-002']
                ]
            ],
            'descuento_general' => 0
        ];

        $response = $this->post(route('ventas.store'), $payload);

        $response->assertRedirect(route('ventas.index'));
        $this->assertDatabaseHas('producto_series', [
            'numero_serie' => 'SN-LAPTOP-001',
            'estado' => 'vendido'
        ]);
        $this->assertDatabaseHas('producto_series', [
            'numero_serie' => 'SN-LAPTOP-002',
            'estado' => 'vendido'
        ]);
    }

    public function test_can_create_venta_with_kit()
    {
        // Componentes
        $comp1 = Producto::factory()->create(['empresa_id' => $this->empresa->id, 'stock' => 10, 'nombre' => 'Mouse']);
        $comp2 = Producto::factory()->create(['empresa_id' => $this->empresa->id, 'stock' => 10, 'nombre' => 'Teclado']);

        // Asegurar stock de componentes MANUAL
        \Illuminate\Support\Facades\DB::table('inventarios')->insert([
            ['producto_id' => $comp1->id, 'almacen_id' => $this->almacen->id, 'cantidad' => 10, 'empresa_id' => $this->empresa->id, 'created_at' => now(), 'updated_at' => now()],
            ['producto_id' => $comp2->id, 'almacen_id' => $this->almacen->id, 'cantidad' => 10, 'empresa_id' => $this->empresa->id, 'created_at' => now(), 'updated_at' => now()]
        ]);

        // Kit
        $kit = Producto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Combo Perifericos',
            'tipo_producto' => 'kit',
            'estado' => 'activo'
        ]);

        \App\Models\KitItem::create([
            'kit_id' => $kit->id,
            'item_type' => 'producto',
            'item_id' => $comp1->id,
            'cantidad' => 1
        ]);
        \App\Models\KitItem::create([
            'kit_id' => $kit->id,
            'item_type' => 'producto',
            'item_id' => $comp2->id,
            'cantidad' => 1
        ]);

        $payload = [
            'cliente_id' => $this->cliente->id,
            'almacen_id' => $this->almacen->id,
            'metodo_pago' => 'efectivo',
            'productos' => [
                [
                    'id' => $kit->id,
                    'cantidad' => 2,
                    'precio' => 500,
                    'descuento' => 0
                ]
            ],
            'descuento_general' => 0
        ];

        $response = $this->post(route('ventas.store'), $payload);

        $response->assertRedirect(route('ventas.index'));

        // El stock de los componentes debe haber bajado (2 kits * 1 item cada uno = 2 unidades menos)
        $this->assertEquals(8, $comp1->fresh()->stock);
        $this->assertEquals(8, $comp2->fresh()->stock);
    }

    protected function createVentaGeneric($empresa, $almacen)
    {
        // Forzar obtención del admin que ya tenemos en $this->admin
        $admin = $this->admin;
        $cliente = Cliente::factory()->create(['empresa_id' => $empresa->id]);

        // Usar DB directamente para evitar el observer de Venta
        $id = \Illuminate\Support\Facades\DB::table('ventas')->insertGetId([
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'numero_venta' => 'V-TEST-' . \Illuminate\Support\Str::uuid(),
            'fecha' => now(),
            'estado' => 'aprobada',
            'subtotal' => 1000.00,
            'total' => 1160.00,
            'iva' => 160.00,
            'metodo_pago' => 'efectivo',
            'almacen_id' => $almacen->id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
            'pagado' => false,
            'descuento_general' => 0.00,
            'isr' => 0.00,
            'retencion_iva' => 0.00,
            'retencion_isr' => 0.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Venta::find($id);
    }
}

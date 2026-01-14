<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\User;
use App\Models\Cotizacion;
use App\Models\PriceList;
use App\Enums\EstadoCotizacion;
use App\Support\EmpresaResolver;

use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CotizacionCrudTest extends TestCase
{
    

    protected $admin;
    protected $empresa;
    protected $almacen;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles si no existen
        if (Role::where('name', 'admin')->doesntExist()) {
            Role::create(['name' => 'admin']);
        }

        // Crear empresa de prueba
        $this->empresa = Empresa::create([
            'nombre_razon_social' => 'Empresa Test ' . uniqid(),
            'tipo_persona' => 'moral',
            'rfc' => 'ABC' . rand(100000, 999999) . 'XYZ',
            'regimen_fiscal' => '601',
            'uso_cfdi' => 'G01',
            'email' => 'test@empresa.com',
            'telefono' => '1234567890',
            'calle' => 'Calle Falsa',
            'numero_exterior' => '123',
            'colonia' => 'Centro',
            'codigo_postal' => '83000',
            'municipio' => 'Hermosillo',
            'estado' => 'Sonora',
            'pais' => 'México'
        ]);

        EmpresaResolver::setContext($this->empresa->id);

        $this->admin = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'email' => 'admin_' . uniqid() . '@test.com'
        ]);
        $this->admin->assignRole('admin');

        $this->almacen = Almacen::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Almacén Central',
            'codigo' => 'ALM-C',
            'estado' => 'activo'
        ]);

        // Asignar almacén al usuario para conversiones
        $this->admin->update(['almacen_venta_id' => $this->almacen->id]);
    }

    public function test_can_list_cotizaciones()
    {
        $this->actingAs($this->admin);

        $cliente = Cliente::factory()->create(['empresa_id' => $this->empresa->id]);

        // Crear una cotización manualmente
        $cotizacion = Cotizacion::create([
            'empresa_id' => $this->empresa->id,
            'cliente_id' => $cliente->id,
            'total' => 100,
            'estado' => EstadoCotizacion::Pendiente,
            'numero_cotizacion' => 'COT-TEST-001'
        ]);

        // Agregar un item (requerido por el controlador para mostrar en index)
        $producto = Producto::factory()->create(['empresa_id' => $this->empresa->id]);
        $cotizacion->items()->create([
            'cotizable_id' => $producto->id,
            'cotizable_type' => Producto::class,
            'cantidad' => 1,
            'precio' => 100,
            'subtotal' => 100,
            'descuento_monto' => 0
        ]);

        $response = $this->get(route('cotizaciones.index'));

        $response->assertStatus(200);
        $response->assertSee($cotizacion->numero_cotizacion);
    }

    public function test_can_create_cotizacion_with_products_and_services()
    {
        $this->actingAs($this->admin);

        $cliente = Cliente::factory()->create(['empresa_id' => $this->empresa->id]);
        $producto = Producto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'precio_venta' => 1000,
            'precio_compra' => 500, // Margen suficiente (50%)
            'estado' => 'activo'
        ]);
        $servicio = Servicio::factory()->create([
            'empresa_id' => $this->empresa->id,
            'precio' => 500,
            'estado' => 'activo'
        ]);

        $payload = [
            'cliente_id' => $cliente->id,
            'productos' => [
                [
                    'id' => $producto->id,
                    'tipo' => 'producto',
                    'cantidad' => 2,
                    'precio' => 1000,
                    'descuento' => 0
                ],
                [
                    'id' => $servicio->id,
                    'tipo' => 'servicio',
                    'cantidad' => 1,
                    'precio' => 500,
                    'descuento' => 10 // 10% de descuento
                ]
            ],
            'descuento_general' => 5, // 5% descuento adicional
            'notas' => 'Test nota'
        ];

        $response = $this->post(route('cotizaciones.store'), $payload);

        $response->assertRedirect(route('cotizaciones.index'));

        $this->assertDatabaseHas('cotizaciones', [
            'cliente_id' => $cliente->id,
            'empresa_id' => $this->empresa->id
        ]);

        $cotizacion = Cotizacion::latest()->first();
        $this->assertEquals(2, $cotizacion->items()->count());

        // Validar cálculos (aprox)
        // Prod: 2 * 1000 = 2000
        // Serv: 1 * 500 = 500 - 10% = 450
        // Subtotal items = 2450
        // Desc Gral 5% = 2450 * 0.05 = 122.5
        // Base = 2327.5
        // IVA 16% = 372.4
        // Total = 2699.9
        $this->assertGreaterThan(0, $cotizacion->total);
    }

    public function test_margin_validation_prevents_creation()
    {
        $this->actingAs($this->admin);

        $cliente = Cliente::factory()->create(['empresa_id' => $this->empresa->id]);
        $producto = Producto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'precio_venta' => 1000,
            'precio_compra' => 960, // Margen insuficiente (5% de 960 es 48, min precio 1008)
            'estado' => 'activo'
        ]);

        $payload = [
            'cliente_id' => $cliente->id,
            'productos' => [
                [
                    'id' => $producto->id,
                    'tipo' => 'producto',
                    'cantidad' => 1,
                    'precio' => 1000,
                    'descuento' => 0
                ]
            ]
        ];

        $response = $this->from(route('cotizaciones.create'))->post(route('cotizaciones.store'), $payload);

        // Debería redirigir con warning de margen
        $response->assertRedirect(route('cotizaciones.create'));
        $response->assertSessionHas('requiere_confirmacion_margen', true);
    }

    public function test_can_force_create_with_low_margin()
    {
        $this->actingAs($this->admin);

        $cliente = Cliente::factory()->create(['empresa_id' => $this->empresa->id]);
        $producto = Producto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'precio_venta' => 1000,
            'precio_compra' => 950,
            'estado' => 'activo'
        ]);

        $payload = [
            'cliente_id' => $cliente->id,
            'ajustar_margen' => true, // Bandera para forzar/ajustar
            'productos' => [
                [
                    'id' => $producto->id,
                    'tipo' => 'producto',
                    'cantidad' => 1,
                    'precio' => 1000,
                    'descuento' => 0
                ]
            ]
        ];

        $response = $this->post(route('cotizaciones.store'), $payload);

        $response->assertRedirect(route('cotizaciones.index'));
        $this->assertDatabaseHas('cotizaciones', ['cliente_id' => $cliente->id]);
    }

    public function test_can_duplicate_cotizacion()
    {
        $this->actingAs($this->admin);

        $cliente = Cliente::factory()->create(['empresa_id' => $this->empresa->id]);
        $cotizacion = Cotizacion::create([
            'empresa_id' => $this->empresa->id,
            'cliente_id' => $cliente->id,
            'total' => 1000,
            'estado' => EstadoCotizacion::Pendiente,
            'numero_cotizacion' => 'COT-ORIGINAL'
        ]);

        $response = $this->post(route('cotizaciones.duplicate', $cotizacion->id));

        $response->assertRedirect(route('cotizaciones.index'));

        $this->assertEquals(2, Cotizacion::count());
        $nueva = Cotizacion::where('numero_cotizacion', '!=', 'COT-ORIGINAL')->first();
        $this->assertEquals(EstadoCotizacion::Borrador, $nueva->estado);
    }

    public function test_can_convert_cotizacion_to_venta()
    {
        $this->actingAs($this->admin);

        $cliente = Cliente::factory()->create(['empresa_id' => $this->empresa->id]);
        $producto = Producto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'stock' => 10,
            'precio_venta' => 100,
            'estado' => 'activo'
        ]);

        // Inyectar stock inicial
        DB::table('inventarios')->insert([
            'empresa_id' => $this->empresa->id,
            'almacen_id' => $this->almacen->id,
            'producto_id' => $producto->id,
            'cantidad' => 10,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $cotizacion = Cotizacion::create([
            'empresa_id' => $this->empresa->id,
            'cliente_id' => $cliente->id,
            'total' => 100,
            'estado' => EstadoCotizacion::Aprobada,
            'numero_cotizacion' => 'COT-TO-VENTA'
        ]);

        $cotizacion->items()->create([
            'cotizable_id' => $producto->id,
            'cotizable_type' => Producto::class,
            'cantidad' => 2,
            'precio' => 100,
            'subtotal' => 200,
            'descuento_monto' => 0
        ]);

        $response = $this->post(route('cotizaciones.convertir-a-venta', $cotizacion->id));

        $response->assertRedirect();

        $this->assertDatabaseHas('ventas', [
            'cotizacion_id' => $cotizacion->id,
            'cliente_id' => $cliente->id
        ]);

        $this->assertEquals(EstadoCotizacion::Convertida, $cotizacion->fresh()->estado);
    }

    public function test_convert_to_pedido_creates_automatic_purchase_order_on_low_stock()
    {
        $this->actingAs($this->admin);

        $cliente = Cliente::factory()->create(['empresa_id' => $this->empresa->id]);
        $proveedor = \App\Models\Proveedor::create([
            'empresa_id' => $this->empresa->id,
            'nombre_razon_social' => 'Proveedor Test',
            'rfc' => 'PRV' . rand(1000, 9999) . 'TAX',
            'regimen_fiscal' => '601'
        ]);

        $producto = Producto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'proveedor_id' => $proveedor->id,
            'stock' => 0, // SIN STOCK
            'precio_venta' => 100,
            'precio_compra' => 50,
            'estado' => 'activo'
        ]);

        $cotizacion = Cotizacion::create([
            'empresa_id' => $this->empresa->id,
            'cliente_id' => $cliente->id,
            'total' => 500,
            'estado' => EstadoCotizacion::Aprobada,
            'numero_cotizacion' => 'COT-TO-PEDIDO'
        ]);

        $cotizacion->items()->create([
            'cotizable_id' => $producto->id,
            'cotizable_type' => Producto::class,
            'cantidad' => 5,
            'precio' => 100,
            'subtotal' => 500,
            'descuento_monto' => 0
        ]);

        $response = $this->post(route('cotizaciones.enviar-pedido', $cotizacion->id));

        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('pedidos', [
            'cotizacion_id' => $cotizacion->id
        ]);

        $pedido = \App\Models\Pedido::where('cotizacion_id', $cotizacion->id)->first();

        // 🚨 Verificar que se creó una Orden de Compra automáticamente debido a stock 0
        $this->assertDatabaseHas('orden_compras', [
            'pedido_id' => $pedido->id,
            'proveedor_id' => $proveedor->id
        ]);

        $this->assertEquals(EstadoCotizacion::EnviadoAPedido, $cotizacion->fresh()->estado);
    }

    public function test_can_generate_pdf()
    {
        $this->actingAs($this->admin);

        $cliente = Cliente::factory()->create(['empresa_id' => $this->empresa->id]);
        $cotizacion = Cotizacion::create([
            'empresa_id' => $this->empresa->id,
            'cliente_id' => $cliente->id,
            'total' => 1000,
            'estado' => EstadoCotizacion::Pendiente,
            'numero_cotizacion' => 'COT-PDF'
        ]);

        $response = $this->get(route('cotizaciones.pdf', $cotizacion->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_can_cancel_and_delete_cotizacion()
    {
        $this->actingAs($this->admin);

        $cliente = Cliente::factory()->create(['empresa_id' => $this->empresa->id]);
        $cotizacion = Cotizacion::create([
            'empresa_id' => $this->empresa->id,
            'cliente_id' => $cliente->id,
            'total' => 1000,
            'estado' => EstadoCotizacion::Pendiente,
            'numero_cotizacion' => 'COT-DELETE'
        ]);

        // Cancelar
        $response = $this->post(route('cotizaciones.cancel', $cotizacion->id));
        $this->assertEquals(EstadoCotizacion::Cancelado, $cotizacion->fresh()->estado);

        // Eliminar permanentemente
        $response = $this->delete(route('cotizaciones.destroy', $cotizacion->id));
        $response->assertRedirect(route('cotizaciones.index'));

        $this->assertDatabaseMissing('cotizaciones', ['id' => $cotizacion->id]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\Almacen;
use App\Models\CuentasPorCobrar;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Proveedor;
use App\Enums\EstadoPedido;
use App\Enums\EstadoVenta;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VentaFlowTest extends TestCase
{


    protected function setUp(): void
    {
        parent::setUp();

        // Limpiar tablas manualmente para evitar contaminación entre tests (SQLite/PgSQL compatible)
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        \Illuminate\Support\Facades\DB::table('ventas')->truncate();
        \Illuminate\Support\Facades\DB::table('venta_items')->truncate();
        \Illuminate\Support\Facades\DB::table('cuentas_por_cobrar')->truncate();
        \Illuminate\Support\Facades\DB::table('pedidos')->truncate();
        \Illuminate\Support\Facades\DB::table('pedido_items')->truncate();
        \Illuminate\Support\Facades\DB::table('inventarios')->truncate();
        \Illuminate\Support\Facades\DB::table('productos')->truncate();
        \Illuminate\Support\Facades\DB::table('clientes')->truncate();
        \Illuminate\Support\Facades\DB::table('almacenes')->truncate();
        \Illuminate\Support\Facades\DB::table('users')->truncate();
        \Illuminate\Support\Facades\DB::table('empresas')->truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        // Crear empresa única para el test con ID 1
        $empresa = \App\Models\Empresa::updateOrCreate(
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
        \App\Support\EmpresaResolver::setContext(1);

        // Crear datos básicos para las pruebas (SAT catalogs, etc.)
        $this->createBasicData();

        // Crear usuario asociado a esa empresa
        $user = \App\Models\User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'empresa_id' => 1
            ]
        );

        $team = \App\Models\Team::updateOrCreate(
            ['name' => 'Test Team'],
            [
                'user_id' => $user->id,
                'personal_team' => true
            ]
        );

        $user->update(['current_team_id' => $team->id]);

        // Configurar almacén por defecto para el usuario
        $almacen = \App\Models\Almacen::updateOrCreate(
            ['id' => 1],
            [
                'nombre' => 'Almacén Principal Test',
                'empresa_id' => 1,
                'estado' => 'activo'
            ]
        );
        $user->update([
            'almacen_id' => $almacen->id,
            'almacen_venta_id' => $almacen->id
        ]);

        // Crear cliente y producto inicial bajo la misma empresa
        $cliente = Cliente::updateOrCreate(
            ['id' => 1],
            [
                'nombre_razon_social' => 'Cliente Test',
                'email' => 'cliente@test.com',
                'rfc' => 'XAXX010101000',
                'empresa_id' => 1,
                'codigo' => 'C-001',
                'activo' => true,
                'credito_activo' => true,
                'limite_credito' => 100000
            ]
        );

        $producto = Producto::updateOrCreate(
            ['id' => 1],
            [
                'nombre' => 'Monitor LED 24"',
                'codigo' => 'MON-001',
                'precio_venta' => 1000.00,
                'stock' => 10,
                'estado' => 'activo',
                'empresa_id' => 1
            ]
        );

        // Crear inventario para el producto de prueba para asegurar stock
        \App\Models\Inventario::updateOrCreate(
            [
                'producto_id' => 1,
                'almacen_id' => 1,
                'empresa_id' => 1
            ],
            [
                'cantidad' => 10,
                'stock_minimo' => 0
            ]
        );

        // Inyectar configuración de IVA al 16% para la empresa 1
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

        $this->actingAs($user);
    }

    /**
     * Crear datos básicos para pruebas
     */
    private function createBasicData()
    {
        // Crear estado SAT
        \App\Models\SatEstado::updateOrCreate(
            ['clave' => 'SON'],
            ['nombre' => 'Sonora']
        );

        // Crear regimen fiscal SAT
        \App\Models\SatRegimenFiscal::updateOrCreate(
            ['clave' => '601'],
            [
                'descripcion' => 'General de Ley Personas Morales',
                'persona_fisica' => false,
                'persona_moral' => true
            ]
        );

        // Crear uso CFDI SAT
        \App\Models\SatUsoCfdi::updateOrCreate(
            ['clave' => 'G01'],
            [
                'descripcion' => 'Adquisición de mercancías',
                'persona_fisica' => true,
                'persona_moral' => true,
                'regimen_fiscal_receptor' => '601'
            ]
        );

        // Crear almacén
        Almacen::create([
            'nombre' => 'Almacén Principal',
            'estado' => 'activo'
        ]);

        // Crear categoría, marca, proveedor
        $categoria = Categoria::create(['nombre' => 'Electrónicos']);
        $marca = Marca::create(['nombre' => 'Samsung']);
        $proveedor = Proveedor::create([
            'nombre_razon_social' => 'Proveedor Test',
            'email' => 'proveedor@test.com',
            'telefono' => '1234567890',
            'tipo_persona' => 'fisica',
            'rfc' => 'TEST123456789',
            'regimen_fiscal' => '601',
            'uso_cfdi' => 'G01',
            'calle' => 'Calle Test',
            'numero_exterior' => '123',
            'colonia' => 'Colonia Test',
            'codigo_postal' => '12345',
            'municipio' => 'Municipio Test',
            'estado' => 'Estado Test',
            'pais' => 'México'
        ]);

        // Crear producto con stock
        Producto::create([
            'nombre' => 'Monitor LED 24"',
            'codigo' => 'MON-001',
            'codigo_barras' => '1234567890123',
            'categoria_id' => $categoria->id,
            'marca_id' => $marca->id,
            'proveedor_id' => $proveedor->id,
            'almacen_id' => 1,
            'stock' => 10,
            'stock_minimo' => 5,
            'precio_compra' => 800.00,
            'precio_venta' => 1000.00,
            'impuesto' => 16.00,
            'unidad_medida' => 'pieza',
            'tipo_producto' => 'fisico',
            'estado' => 'activo'
        ]);

        // Crear cliente
        Cliente::create([
            'nombre_razon_social' => 'Cliente Test',
            'email' => 'cliente@test.com',
            'telefono' => '0987654321',
            'tipo_persona' => 'fisica',
            'rfc' => 'TEST123456789',
            'regimen_fiscal' => '601',
            'uso_cfdi' => 'G01',
            'calle' => 'Calle Test',
            'numero_exterior' => '123',
            'colonia' => 'Colonia Test',
            'codigo_postal' => '12345',
            'municipio' => 'Municipio Test',
            'estado' => 'SON',
            'pais' => 'MX'
        ]);
    }

    /**
     * Test flujo completo de venta: creación → validación stock → creación cuenta por cobrar
     */
    public function test_flujo_venta_completo()
    {
        // 1. Verificar que el producto tiene stock disponible
        $producto = Producto::first();
        $this->assertEquals(10, $producto->stock_disponible);
        $this->assertEquals(10, $producto->stock);
        $this->assertEquals(0, $producto->reservado);

        // 2. Crear venta con producto
        $cliente = Cliente::first();
        $ventaData = [
            'cliente_id' => $cliente->id,
            'almacen_id' => 1,
            'metodo_pago' => 'efectivo',
            'productos' => [
                [
                    'id' => $producto->id,
                    'tipo' => 'producto',
                    'cantidad' => 2,
                    'precio' => 1000.00,
                    'descuento' => 0
                ]
            ],
            'descuento_general' => 0
        ];

        $response = $this->post(route('ventas.store'), $ventaData);
        $response->assertRedirect(route('ventas.index'));

        // El sistema registra el pago automáticamente para ventas en efectivo
        $this->assertDatabaseHas('ventas', [
            'cliente_id' => $cliente->id,
            'total' => 2320.00, // 2000 + 320 IVA (16%)
            'pagado' => true
        ]);
    }

    /** @test */
    public function envio_a_venta_deduce_inventario()
    {
        $cliente = Cliente::find(1);
        $producto = Producto::find(1);

        // Crear pedido previo
        $pedido = Pedido::create([
            'cliente_id' => $cliente->id,
            'numero_pedido' => 'PED-001',
            'subtotal' => 3000.00,
            'iva' => 480.00,
            'total' => 3480.00,
            'fecha' => now(),
            'estado' => EstadoPedido::Confirmado,
            'empresa_id' => 1
        ]);

        PedidoItem::create([
            'pedido_id' => $pedido->id,
            'pedible_id' => $producto->id,
            'pedible_type' => Producto::class,
            'cantidad' => 3,
            'precio' => 1000.00,
            'subtotal' => 3000.00
        ]);

        // Enviar a venta (simular desde pedido)
        $response = $this->post(route('pedidos.enviar-a-venta', $pedido->id));
        $response->assertJson(['success' => true]);

        // Verificar que el stock se dedujo
        $producto->refresh();
        $this->assertEquals(7, $producto->stock); // 10 - 3
    }

    /** @test */
    public function validacion_stock_insuficiente()
    {
        $producto = Producto::find(1);

        // Intentar convertir pedido con stock insuficiente (15 > 10)
        $pedido = Pedido::create([
            'cliente_id' => 1,
            'numero_pedido' => 'PED-EXCESS',
            'subtotal' => 15000.00,
            'iva' => 2400.00,
            'total' => 17400.00,
            'estado' => EstadoPedido::Confirmado,
            'empresa_id' => 1
        ]);

        PedidoItem::create([
            'pedido_id' => $pedido->id,
            'pedible_id' => $producto->id,
            'pedible_type' => Producto::class,
            'cantidad' => 15,
            'precio' => 1000.00,
            'subtotal' => 15000.00
        ]);

        $response = $this->post(route('pedidos.enviar-a-venta', $pedido->id));
        $response->assertStatus(500); // Actualmente lanza RuntimeException -> 500
    }

    /**
     * Test pago completo actualiza estados
     */
    public function test_pago_completo_actualiza_estados()
    {
        // Crear venta a CREDITO para que no se pague sola
        $producto = Producto::first();
        $cliente = Cliente::first();
        $ventaData = [
            'cliente_id' => $cliente->id,
            'productos' => [
                [
                    'id' => $producto->id,
                    'tipo' => 'producto',
                    'cantidad' => 1,
                    'precio' => 1000.00,
                    'descuento' => 0
                ]
            ],
            'almacen_id' => 1,
            'metodo_pago' => 'credito',
            'descuento_general' => 0
        ];

        $response = $this->post(route('ventas.store'), $ventaData);

        // Assert creation was successful before fetching
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $venta = Venta::orderBy('id', 'desc')->first();
        $this->assertNotNull($venta, 'La venta no se creó correctamente en BD');

        // Buscar la CxC asociada a la venta recien creada
        $cuentaPorCobrar = \App\Models\CuentasPorCobrar::where('cobrable_id', $venta->id)
            ->where('cobrable_type', 'venta')
            ->first();

        // Registrar pago completo
        $pagoData = [
            'monto' => 1160.00, // Total con IVA (16%)
            'notas' => 'Pago completo',
            'metodo_pago' => 'efectivo'
        ];

        $response = $this->post(route('cuentas-por-cobrar.registrar-pago', $cuentaPorCobrar->id), $pagoData);
        $response->assertRedirect();

        // Verificar estados
        $cuentaPorCobrar->refresh();
        $venta->refresh();

        $this->assertEquals(0, $cuentaPorCobrar->monto_pendiente);
        $this->assertEquals(1160.00, $cuentaPorCobrar->monto_pagado);
        $this->assertEquals('pagado', $cuentaPorCobrar->estado);
        $this->assertTrue($venta->pagado);
        $this->assertEquals(EstadoVenta::Aprobada, $venta->estado);
    }

    /**
     * Test reserva de inventario en pedidos
     */
    public function test_reserva_inventario_pedidos()
    {
        $producto = Producto::first();

        // Crear pedido (simular)
        $cliente = Cliente::first();
        $pedidoData = [
            'cliente_id' => $cliente->id,
            'productos' => [
                [
                    'id' => $producto->id,
                    'tipo' => 'producto',
                    'cantidad' => 2,
                    'precio' => 1000.00,
                    'descuento' => 0
                ]
            ],
            'descuento_general' => 0
        ];

        $this->post(route('pedidos.store'), $pedidoData);
        $pedido = \App\Models\Pedido::first();

        // Confirmar pedido (reserva inventario)
        $response = $this->post(route('pedidos.confirmar', $pedido->id));
        $response->assertJson(['success' => true]);

        // Verificar reserva
        $producto->refresh();
        $this->assertEquals(2, $producto->reservado);
        $this->assertEquals(8, $producto->stock_disponible); // 10 - 2

        // Enviar a venta (consume reserva)
        $response = $this->post(route('pedidos.enviar-a-venta', $pedido->id));
        $response->assertJson(['success' => true]);

        // Verificar que reserva se consumió
        $producto->refresh();
        $this->assertEquals(0, $producto->reservado);
        $this->assertEquals(8, $producto->stock); // 10 - 2
    }
}

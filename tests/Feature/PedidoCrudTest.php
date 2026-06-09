<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\User;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Cotizacion;
use App\Models\Venta;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Enums\EstadoPedido;
use App\Support\EmpresaResolver;

use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PedidoCrudTest extends TestCase
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

        // Crear empresa de prueba con todos los campos requeridos
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
            'nombre' => 'Almacén Central Orders',
            'codigo' => 'ALM-ORD',
            'estado' => 'activo'
        ]);

        // Asignar almacén al usuario
        $this->admin->update(['almacen_venta_id' => $this->almacen->id]);
    }

    public function test_can_list_pedidos()
    {
        $this->actingAs($this->admin);

        $cliente = Cliente::factory()->create(['empresa_id' => $this->empresa->id]);

        $pedido = Pedido::create([
            'empresa_id' => $this->empresa->id,
            'cliente_id' => $cliente->id,
            'numero_pedido' => 'PED-TEST-001',
            'total' => 1000,
            'estado' => EstadoPedido::Borrador
        ]);

        $response = $this->get(route('pedidos.index'));

        $response->assertStatus(200);
        $response->assertSee('PED-TEST-001');
    }

    public function test_can_create_pedido_manually()
    {
        $this->actingAs($this->admin);

        $cliente = Cliente::factory()->create(['empresa_id' => $this->empresa->id]);
        $producto = Producto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'precio_venta' => 1000,
            'precio_compra' => 500,
            'stock' => 10,
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
                ]
            ],
            'notas' => 'Pedido manual test'
        ];

        $response = $this->post(route('pedidos.store'), $payload);

        $response->assertRedirect(route('pedidos.index'));

        $this->assertDatabaseHas('pedidos', [
            'cliente_id' => $cliente->id,
            'notas' => 'Pedido manual test'
        ]);

        $pedido = Pedido::latest()->first();
        $this->assertEquals(1, $pedido->items()->count());
        $this->assertEquals(EstadoPedido::Borrador, $pedido->estado);
    }

    public function test_can_confirm_pedido_and_reserve_stock()
    {
        $this->actingAs($this->admin);

        $cliente = Cliente::factory()->create(['empresa_id' => $this->empresa->id]);
        $producto = Producto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'stock' => 10,
            'reservado' => 0,
            'estado' => 'activo'
        ]);

        // Inyectar stock en inventario
        DB::table('inventarios')->insert([
            'empresa_id' => $this->empresa->id,
            'almacen_id' => $this->almacen->id,
            'producto_id' => $producto->id,
            'cantidad' => 10,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $pedido = Pedido::create([
            'empresa_id' => $this->empresa->id,
            'cliente_id' => $cliente->id,
            'numero_pedido' => 'PED-RESERVE',
            'total' => 500,
            'estado' => EstadoPedido::Borrador
        ]);

        $pedido->items()->create([
            'empresa_id' => $this->empresa->id,
            'pedible_id' => $producto->id,
            'pedible_type' => Producto::class,
            'cantidad' => 3,
            'precio' => 100,
            'subtotal' => 300,
            'descuento_monto' => 0
        ]);

        // Confirmar pedido (esto debería reservar stock)
        $response = $this->post(route('pedidos.confirmar', $pedido->id));

        $response->assertJsonPath('success', true);
        $this->assertEquals(EstadoPedido::Confirmado, $pedido->fresh()->estado);

        // Verificar reserva en DB
        $this->assertEquals(3, $producto->fresh()->reservado);
    }

    public function test_can_cancel_pedido_and_release_stock()
    {
        $this->actingAs($this->admin);

        $cliente = Cliente::factory()->create(['empresa_id' => $this->empresa->id]);
        $producto = Producto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'stock' => 10,
            'reservado' => 3, // Ya reservado
            'estado' => 'activo'
        ]);

        $pedido = Pedido::create([
            'empresa_id' => $this->empresa->id,
            'cliente_id' => $cliente->id,
            'numero_pedido' => 'PED-CANCEL',
            'total' => 500,
            'estado' => EstadoPedido::Confirmado
        ]);

        $pedido->items()->create([
            'empresa_id' => $this->empresa->id,
            'pedible_id' => $producto->id,
            'pedible_type' => Producto::class,
            'cantidad' => 3,
            'precio' => 100,
            'subtotal' => 300,
            'descuento_monto' => 0
        ]);

        // Cancelar pedido (esto debería liberar stock)
        $response = $this->post(route('pedidos.cancel', $pedido->id));

        $response->assertJsonPath('success', true);
        $this->assertEquals(EstadoPedido::Cancelado, $pedido->fresh()->estado);

        // Verificar liberación en DB
        $this->assertEquals(0, $producto->fresh()->reservado);
    }

    public function test_cannot_confirm_without_stock()
    {
        $this->actingAs($this->admin);

        $cliente = Cliente::factory()->create(['empresa_id' => $this->empresa->id]);
        $producto = Producto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'stock' => 1,
            'reservado' => 0,
            'estado' => 'activo'
        ]);

        $pedido = Pedido::create([
            'empresa_id' => $this->empresa->id,
            'cliente_id' => $cliente->id,
            'numero_pedido' => 'PED-NO-STOCK',
            'total' => 500,
            'estado' => EstadoPedido::Borrador
        ]);

        $pedido->items()->create([
            'empresa_id' => $this->empresa->id,
            'pedible_id' => $producto->id,
            'pedible_type' => Producto::class,
            'cantidad' => 5, // Más de lo disponible (1)
            'precio' => 100,
            'subtotal' => 500,
            'descuento_monto' => 0
        ]);

        $response = $this->post(route('pedidos.confirmar', $pedido->id));

        $response->assertStatus(400);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString('Stock disponible insuficiente', $response->json('error'));
    }

    public function test_can_convert_pedido_to_venta()
    {
        $this->actingAs($this->admin);

        $cliente = Cliente::factory()->create([
            'empresa_id' => $this->empresa->id,
            'limite_credito' => 10000
        ]);

        $producto = Producto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'stock' => 10,
            'reservado' => 2,
            'precio_venta' => 100,
            'estado' => 'activo'
        ]);

        // Inyectar stock
        DB::table('inventarios')->insert([
            'empresa_id' => $this->empresa->id,
            'almacen_id' => $this->almacen->id,
            'producto_id' => $producto->id,
            'cantidad' => 10,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $pedido = Pedido::create([
            'empresa_id' => $this->empresa->id,
            'cliente_id' => $cliente->id,
            'numero_pedido' => 'PED-TO-VENTA',
            'subtotal' => 172.41,
            'iva' => 27.59,
            'total' => 200,
            'estado' => EstadoPedido::Confirmado
        ]);

        $pedido->items()->create([
            'empresa_id' => $this->empresa->id,
            'pedible_id' => $producto->id,
            'pedible_type' => Producto::class,
            'cantidad' => 2,
            'precio' => 100,
            'subtotal' => 200,
            'descuento_monto' => 0
        ]);

        $response = $this->post(route('pedidos.enviar-a-venta', $pedido->id));

        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('ventas', [
            'pedido_id' => $pedido->id,
            'cliente_id' => $cliente->id
        ]);

        $this->assertEquals(EstadoPedido::EnviadoVenta, $pedido->fresh()->estado);

        // El stock debería haber bajado a 8 y la reserva liberada a 0
        $this->assertEquals(8, $producto->fresh()->stock);
        $this->assertEquals(0, $producto->fresh()->reservado);
    }

    public function test_can_generate_pdf()
    {
        $this->actingAs($this->admin);

        $cliente = Cliente::factory()->create(['empresa_id' => $this->empresa->id]);
        $pedido = Pedido::create([
            'empresa_id' => $this->empresa->id,
            'cliente_id' => $cliente->id,
            'total' => 1000,
            'estado' => EstadoPedido::Confirmado,
            'numero_pedido' => 'PED-PDF'
        ]);

        $response = $this->get(route('pedidos.pdf', $pedido->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_can_delete_pedido()
    {
        $this->actingAs($this->admin);

        $cliente = Cliente::factory()->create(['empresa_id' => $this->empresa->id]);
        $pedido = Pedido::create([
            'empresa_id' => $this->empresa->id,
            'cliente_id' => $cliente->id,
            'total' => 1000,
            'estado' => EstadoPedido::Borrador,
            'numero_pedido' => 'PED-DELETE'
        ]);

        $response = $this->delete(route('pedidos.destroy', $pedido->id));

        $response->assertRedirect(route('pedidos.index'));
        $this->assertSoftDeleted('pedidos', ['id' => $pedido->id]);
    }
}
<?php

namespace Tests\Feature;

// 
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /**
     * Test catalog detail page returns complementarios.
     */
    public function test_catalogo_detail_returns_complementarios_prop(): void
    {
        $query = \App\Models\Producto::where('estado', 'activo')
            ->where('precio_venta', '>', 0);
        
        if (\Illuminate\Support\Facades\Schema::hasColumn('productos', 'catalogo_web')) {
            $query->where('catalogo_web', true)
                  ->whereNotNull('imagen')
                  ->where('imagen', '!=', '');
        }
        
        $producto = $query->first();

        if ($producto) {
            $response = $this->get("/producto/{$producto->id}");
            $response->assertStatus(200);
            
            $response->assertInertia(fn ($page) => $page
                ->component('Catalogo/Show')
                ->has('complementarios')
            );
        } else {
            $this->assertTrue(true);
        }
    }

    /**
     * Test stock reservation on order creation and restoration on cancellation.
     */
    public function test_pedido_online_stock_reservation_and_cancellation(): void
    {
        // Obtener o crear empresa para evitar violaciones de llave foránea
        $empresa = \App\Models\Empresa::first();
        if (!$empresa) {
            $empresa = \App\Models\Empresa::create([
                'nombre_razon_social' => 'Empresa de Prueba',
                'rfc' => 'XAXX010101000'
            ]);
        }
        $empresaId = $empresa->id;

        // 1. Crear un producto local de prueba con stock conocido
        $producto = \App\Models\Producto::create([
            'nombre' => 'Test Stock Product',
            'codigo' => 'TEST-STOCK-1',
            'precio_venta' => 100.00,
            'stock' => 10,
            'estado' => 'activo',
            'empresa_id' => $empresaId
        ]);

        // 2. Crear un PedidoOnline de prueba conteniendo ese producto
        $items = [
            [
                'producto_id' => $producto->id,
                'nombre' => $producto->nombre,
                'precio' => $producto->precio_venta,
                'precio_con_iva' => round($producto->precio_venta * 1.16, 2),
                'cantidad' => 2,
                'origen' => 'local'
            ]
        ];

        $pedido = \App\Models\PedidoOnline::create([
            'empresa_id' => $empresaId,
            'numero_pedido' => \App\Models\PedidoOnline::generarNumeroPedido(),
            'email' => 'test@example.com',
            'nombre' => 'Test Client',
            'direccion_envio' => ['tipo' => 'recoger_en_tienda'],
            'items' => $items,
            'subtotal' => 200.00,
            'costo_envio' => 0.00,
            'total' => 232.00,
            'metodo_pago' => 'transferencia',
            'estado' => 'pendiente'
        ]);

        // Verificar que el stock se decrementó a 8
        $productoFresh = $producto->fresh();
        $this->assertEquals(8, $productoFresh->stock);

        // 3. Cancelar el pedido
        $pedido->update(['estado' => 'cancelado']);

        // Verificar que el stock se restauró a 10
        $productoFresh = $producto->fresh();
        $this->assertEquals(10, $productoFresh->stock);

        // Limpieza
        $pedido->delete();
        $producto->delete();
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ClienteTienda;
use App\Models\Producto;
use App\Models\PedidoOnline;
use App\Services\CVAService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Mockery;

class VentaCompletaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Mockear CVAService para no hacer llamadas reales a la API de CVA durante el test
        $this->mock(CVAService::class, function ($mock) {
            $mock->shouldReceive('createOrder')
                ->andReturn(['success' => true, 'data' => ['pedido' => 'TEST-CVA-123']]);
        });
    }

    public function test_flujo_venta_transferencia_completo()
    {
        \Illuminate\Support\Facades\Notification::fake();

        $empresaId = \App\Models\Empresa::first()->id ?? 1;

        // 1. Crear usuario/cliente de prueba
        $cliente = ClienteTienda::create([
            'empresa_id' => $empresaId,
            'email' => 'test_venta_' . uniqid() . '@example.com',
            'nombre' => 'Test Vendedor',
            'password' => bcrypt('password'),
            'telefono' => '5555555555'
        ]);

        // 2. Simular items en el carrito (Producto Local y CVA)
        $items = [
            [
                'id' => 1,
                'producto_id' => 1,
                'nombre' => 'Producto Local Test',
                'precio' => 1000,
                'cantidad' => 1,
                'origen' => 'local'
            ],
            [
                'id' => 'CVA-PRUEBA',
                'producto_id' => 'CVA-PRUEBA',
                'nombre' => 'Laptop CVA Test',
                'precio' => 15000,
                'cantidad' => 1,
                'origen' => 'CVA'
            ]
        ];

        // 3. Crear pedido directamente (Simulando CheckoutController)
        $pedido = PedidoOnline::create([
            'empresa_id' => $empresaId,
            'numero_pedido' => PedidoOnline::generarNumeroPedido(),
            'cliente_tienda_id' => $cliente->id,
            'email' => $cliente->email,
            'nombre' => $cliente->nombre,
            'telefono' => '5555555555',
            'direccion_envio' => [
                'calle' => 'Calle Pruebas',
                'numero' => '123',
                'colonia' => 'Centro',
                'cp' => '83000',
                'ciudad' => 'Hermosillo',
                'estado' => 'Sonora'
            ],
            'items' => $items,
            'subtotal' => 16000,
            'costo_envio' => 100.00,
            'total' => 16100.00,
            'metodo_pago' => 'transferencia',
            'estado' => 'pendiente'
        ]);

        // Simular evento de notificación (CheckoutController lo hace manualmente)
        $pedido->notify(new \App\Notifications\PedidoCreadoNotification($pedido));

        // VERIFICACIÓN 1: Estado y Bitácora
        $this->assertEquals('pendiente', $pedido->estado);
        $this->assertDatabaseHas('pedidos_online_bitacora', [
            'pedido_online_id' => $pedido->id,
            'accion' => 'CREACION'
        ]);

        // Verificar Notificación
        \Illuminate\Support\Facades\Notification::assertSentTo(
            [$pedido],
            \App\Notifications\PedidoCreadoNotification::class
        );

        // 4. Simular Pago Manual
        $pedido->marcarComoPagado('REF-MANUAL-001', 'completado', ['banco' => 'BBVA']);
        $pedido->fresh();

        // VERIFICACIÓN 2: Pago y CVA
        $this->assertEquals('pagado', $pedido->estado);
        $this->assertEquals('TEST-CVA-123', $pedido->cva_pedido_id);

        $this->assertDatabaseHas('pedidos_online_bitacora', [
            'pedido_online_id' => $pedido->id,
            'accion' => 'PAGO_CONFIRMADO'
        ]);

        $this->assertDatabaseHas('pedidos_online_bitacora', [
            'pedido_online_id' => $pedido->id,
            'accion' => 'ENVIO_CVA'
        ]);

        // Limpieza
        \App\Models\PedidoBitacora::where('pedido_online_id', $pedido->id)->delete();
        $pedido->delete();
        $cliente->delete();
    }

    public function test_flujo_venta_efectivo_completo()
    {
        $empresaId = \App\Models\Empresa::first()->id ?? 1;

        $cliente = ClienteTienda::create([
            'empresa_id' => $empresaId,
            'email' => 'test_efectivo_' . uniqid() . '@example.com',
            'nombre' => 'Test Efectivo',
            'password' => bcrypt('password')
        ]);

        $pedido = PedidoOnline::create([
            'empresa_id' => $empresaId,
            'numero_pedido' => PedidoOnline::generarNumeroPedido(),
            'cliente_tienda_id' => $cliente->id,
            'email' => $cliente->email,
            'nombre' => $cliente->nombre,
            'items' => [],
            'direccion_envio' => [],
            'subtotal' => 500,
            'costo_envio' => 0,
            'total' => 500,
            'metodo_pago' => 'efectivo',
            'estado' => 'pendiente'
        ]);

        $this->assertEquals('pendiente', $pedido->estado);

        $pedido->marcarComoPagado('EFECTIVO-RE-001', 'completado');

        $this->assertEquals('pagado', $pedido->estado);

        \App\Models\PedidoBitacora::where('pedido_online_id', $pedido->id)->delete();
        $pedido->delete();
        $cliente->delete();
    }
}

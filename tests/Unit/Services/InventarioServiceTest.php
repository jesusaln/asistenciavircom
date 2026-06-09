<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\InventarioService;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\Inventario;
use App\Models\InventarioMovimiento;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use RuntimeException;

/**
 * Tests para InventarioService
 * 
 * ✅ SAFE: Usa DatabaseTransactions para rollback automático
 */
class InventarioServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected InventarioService $service;
    protected Producto $producto;
    protected Almacen $almacen;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InventarioService::class);

        // Autenticar un usuario para los movimientos de inventario
        $user = \App\Models\User::first();
        if (!$user) {
            $this->markTestSkipped('No hay usuarios en la BD');
        }
        $this->actingAs($user);

        // Usar datos existentes de la BD para evitar problemas con constraints NOT NULL
        $this->almacen = Almacen::where('estado', 'activo')->first();
        if (!$this->almacen) {
            $this->markTestSkipped('No hay almacenes activos en la BD');
        }

        // Usar producto existente con inventario en ese almacén
        $inventario = Inventario::where('almacen_id', $this->almacen->id)
            ->where('cantidad', '>', 20)
            ->first();

        if (!$inventario) {
            $this->markTestSkipped('No hay inventario disponible en la BD');
        }

        $this->producto = Producto::find($inventario->producto_id);
        if (!$this->producto) {
            $this->markTestSkipped('No hay productos en la BD');
        }
    }

    /**
     * @test
     * Test que tieneStockSuficiente retorna true cuando hay stock
     */
    public function tiene_stock_suficiente_retorna_true_cuando_hay_stock()
    {
        $this->producto->stock = 50;

        $resultado = $this->service->tieneStockSuficiente($this->producto, 30);

        $this->assertTrue($resultado);
    }

    /**
     * @test
     * Test que tieneStockSuficiente retorna false cuando no hay stock suficiente
     */
    public function tiene_stock_suficiente_retorna_false_cuando_no_hay_stock()
    {
        $this->producto->stock = 20;

        $resultado = $this->service->tieneStockSuficiente($this->producto, 50);

        $this->assertFalse($resultado);
    }

    /**
     * @test
     * Test que tieneStockSuficiente retorna true cuando stock es exactamente igual
     */
    public function tiene_stock_suficiente_retorna_true_cuando_stock_es_exacto()
    {
        $this->producto->stock = 30;

        $resultado = $this->service->tieneStockSuficiente($this->producto, 30);

        $this->assertTrue($resultado);
    }

    /**
     * @test
     * Test que entrada incrementa el stock del producto
     */
    public function entrada_incrementa_stock_del_producto()
    {
        $stockInicial = $this->producto->stock;
        $cantidadEntrada = 25;

        $this->service->entrada($this->producto, $cantidadEntrada, [
            'almacen_id' => $this->almacen->id,
            'motivo' => 'Test entrada',
        ]);

        $this->producto->refresh();
        $this->assertEquals($stockInicial + $cantidadEntrada, $this->producto->stock);
    }

    /**
     * @test
     * Test que salida decrementa el stock del producto
     */
    public function salida_decrementa_stock_del_producto()
    {
        $stockInicial = $this->producto->stock;
        $cantidadSalida = 10;

        $this->service->salida($this->producto, $cantidadSalida, [
            'almacen_id' => $this->almacen->id,
            'motivo' => 'Test salida',
        ]);

        $this->producto->refresh();
        $this->assertEquals($stockInicial - $cantidadSalida, $this->producto->stock);
    }

    /**
     * @test
     * Test que salida lanza excepción cuando no hay stock suficiente
     */
    public function salida_lanza_excepcion_cuando_no_hay_stock()
    {
        $this->expectException(RuntimeException::class);

        $this->service->salida($this->producto, 500, [
            'almacen_id' => $this->almacen->id,
            'motivo' => 'Test salida sin stock',
        ]);
    }

    /**
     * @test
     * Test que entrada registra un movimiento de inventario
     */
    public function entrada_registra_movimiento_de_inventario()
    {
        $cantidadEntrada = 15;

        $this->service->entrada($this->producto, $cantidadEntrada, [
            'almacen_id' => $this->almacen->id,
            'motivo' => 'Test registro movimiento',
        ]);

        $movimiento = InventarioMovimiento::where('producto_id', $this->producto->id)
            ->where('tipo', 'entrada')
            ->where('cantidad', $cantidadEntrada)
            ->first();

        $this->assertNotNull($movimiento);
        $this->assertEquals('entrada', $movimiento->tipo);
        $this->assertEquals($cantidadEntrada, $movimiento->cantidad);
    }

    /**
     * @test
     * Test que salida registra un movimiento de inventario
     */
    public function salida_registra_movimiento_de_inventario()
    {
        $cantidadSalida = 5;

        $this->service->salida($this->producto, $cantidadSalida, [
            'almacen_id' => $this->almacen->id,
            'motivo' => 'Test registro movimiento salida',
        ]);

        $movimiento = InventarioMovimiento::where('producto_id', $this->producto->id)
            ->where('tipo', 'salida')
            ->where('cantidad', $cantidadSalida)
            ->first();

        $this->assertNotNull($movimiento);
        $this->assertEquals('salida', $movimiento->tipo);
    }

    /**
     * @test
     * Test que obtenerEstadisticasGenerales retorna estructura correcta
     */
    public function obtener_estadisticas_generales_retorna_estructura_correcta()
    {
        $estadisticas = $this->service->obtenerEstadisticasGenerales();

        $this->assertIsArray($estadisticas);
        $this->assertArrayHasKey('total_movimientos', $estadisticas);
        $this->assertArrayHasKey('total_entradas', $estadisticas);
        $this->assertArrayHasKey('total_salidas', $estadisticas);
        $this->assertArrayHasKey('productos_con_movimientos', $estadisticas);
        $this->assertArrayHasKey('movimientos_hoy', $estadisticas);
        $this->assertArrayHasKey('movimientos_este_mes', $estadisticas);
    }

    /**
     * @test
     * Test que movimiento registra stock anterior y posterior correctamente
     */
    public function movimiento_registra_stock_anterior_y_posterior()
    {
        // Obtener stock real del inventario, no valor hardcodeado
        $inventarioActual = Inventario::where('producto_id', $this->producto->id)
            ->where('almacen_id', $this->almacen->id)
            ->first();
        $stockAnterior = $inventarioActual->cantidad;
        $cantidadEntrada = 20;

        $this->service->entrada($this->producto, $cantidadEntrada, [
            'almacen_id' => $this->almacen->id,
            'motivo' => 'Test stock tracking',
        ]);

        $movimiento = InventarioMovimiento::where('producto_id', $this->producto->id)
            ->where('tipo', 'entrada')
            ->orderBy('id', 'desc')
            ->first();

        $this->assertEquals($stockAnterior, $movimiento->stock_anterior);
        $this->assertEquals($stockAnterior + $cantidadEntrada, $movimiento->stock_posterior);
    }

    /**
     * @test
     * Test que ajustar rechaza cantidad cero o negativa
     */
    public function ajustar_rechaza_cantidad_cero_o_negativa()
    {
        $this->expectException(InvalidArgumentException::class);

        // Usamos reflection para acceder al método protegido
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('ajustar');
        $method->setAccessible(true);

        $method->invoke($this->service, $this->producto, 'entrada', 0, []);
    }

    /**
     * @test
     * Test que ajustar rechaza tipo de movimiento inválido
     */
    public function ajustar_rechaza_tipo_invalido()
    {
        $this->expectException(InvalidArgumentException::class);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('ajustar');
        $method->setAccessible(true);

        $method->invoke($this->service, $this->producto, 'invalid_type', 10, []);
    }
}

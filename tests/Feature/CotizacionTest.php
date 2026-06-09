<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Cotizacion;
use App\Models\CotizacionItem;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Cliente;


class CotizacionTest extends TestCase
{
     // Limpia la DB entre tests

    public function test_crear_cotizacion_con_items()
    {
        // 1. Crear un cliente
        $cliente = Cliente::factory()->create();

        // 2. Crear productos y servicios de prueba
        $producto = Producto::factory()->create(['precio_venta' => 100]);
        $servicio = Servicio::factory()->create(['precio' => 200]);

        // 3. Crear cotización
        $cotizacion = Cotizacion::create([
            'cliente_id' => $cliente->id,
            'subtotal' => 0,
            'descuento_general' => 0,
            'iva' => 0,
            'total' => 0,
            'estado' => 'pendiente',
        ]);

        // 4. Agregar items
        $itemProducto = CotizacionItem::create([
            'cotizacion_id' => $cotizacion->id,
            'cotizable_id' => $producto->id,
            'cotizable_type' => Producto::class,
            'cantidad' => 2,
            'precio' => $producto->precio_venta,
            'descuento' => 10,
            'subtotal' => 2 * $producto->precio_venta,
            'descuento_monto' => 2 * $producto->precio_venta * 0.10
        ]);

        $itemServicio = CotizacionItem::create([
            'cotizacion_id' => $cotizacion->id,
            'cotizable_id' => $servicio->id,
            'cotizable_type' => Servicio::class,
            'cantidad' => 1,
            'precio' => $servicio->precio,
            'descuento' => 0,
            'subtotal' => $servicio->precio,
            'descuento_monto' => 0
        ]);

        // 5. Recalcular totales
        $cotizacion->subtotal = $cotizacion->items->sum('subtotal');
        $cotizacion->descuento_general = $cotizacion->items->sum('descuento_monto');
        $ivaRate = \App\Services\EmpresaConfiguracionService::getIvaPorcentaje() / 100;
        $cotizacion->iva = ($cotizacion->subtotal - $cotizacion->descuento_general) * $ivaRate;
        $cotizacion->total = $cotizacion->subtotal - $cotizacion->descuento_general + $cotizacion->iva;
        $cotizacion->save();

        // 6. Validar cálculos
        $this->assertEquals(400, $cotizacion->subtotal); // 2*100 + 200
        $this->assertEquals(20, $cotizacion->descuento_general); // 10% de 200
        $this->assertEquals(62.4, $cotizacion->iva); // 16% de (400-20)
        $this->assertEquals(442.4, $cotizacion->total); // subtotal - descuento + IVA
    }
}

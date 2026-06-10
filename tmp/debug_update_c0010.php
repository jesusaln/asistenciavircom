<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$compra = App\Models\Compra::with('compraItems')->where('numero_compra', 'C0010')->first();

if (!$compra) {
    echo "Compra no encontrada\n";
    exit(1);
}

$validatedData = [
    'proveedor_id' => 5,
    'almacen_id' => 2,
    'metodo_pago' => 'efectivo',
    'descuento_general' => 0,
    'productos' => [
        ['id' => 45, 'cantidad' => 38, 'precio' => 33.12, 'descuento' => 0],
        ['id' => 44, 'cantidad' => 74, 'precio' => 28.82, 'descuento' => 0],
    ],
];

$inventarioService = app(App\Services\InventarioService::class);

try {
    DB::transaction(function () use ($compra, $validatedData, $inventarioService) {
        // Map de cantidades antiguas
        $oldItems = $compra->compraItems;
        $oldQtyByProduct = [];
        foreach ($oldItems as $item) {
            $oldQtyByProduct[$item->comprable_id] = $item->cantidad;
        }

        // Totales
        $subtotal = 0;
        $descuentoItems = 0;
        $descuentoGeneral = $validatedData['descuento_general'] ?? 0;
        foreach ($validatedData['productos'] as $productoData) {
            $subtotalProducto = $productoData['cantidad'] * $productoData['precio'];
            $descuentoMonto = $subtotalProducto * (($productoData['descuento'] ?? 0) / 100);
            $subtotal += $subtotalProducto;
            $descuentoItems += $descuentoMonto;
        }
        $subtotalDespuesDescuentoGeneral = $subtotal - $descuentoItems - $descuentoGeneral;
        $ivaRate = \App\Services\EmpresaConfiguracionService::getIvaPorcentaje() / 100;
        $iva = $subtotalDespuesDescuentoGeneral * $ivaRate;
        $total = $subtotalDespuesDescuentoGeneral + $iva;

        // Update compra
        $compra->update([
            'proveedor_id' => $validatedData['proveedor_id'],
            'almacen_id' => $validatedData['almacen_id'],
            'metodo_pago' => $validatedData['metodo_pago'] ?? null,
            'subtotal' => $subtotal,
            'descuento_items' => $descuentoItems,
            'descuento_general' => $descuentoGeneral,
            'iva' => $iva,
            'total' => $total,
        ]);

        // CxP
        $cuentaPorPagar = \App\Models\CuentasPorPagar::where('compra_id', $compra->id)->first();
        if ($cuentaPorPagar) {
            $montoPagado = $cuentaPorPagar->monto_pagado;
            $nuevoPendiente = $total - $montoPagado;
            $cuentaPorPagar->update([
                'monto_total' => $total,
                'monto_pendiente' => $nuevoPendiente,
                'estado' => $nuevoPendiente <= 0 ? 'pagada' : ($montoPagado > 0 ? 'parcial' : 'pendiente'),
            ]);
        }

        // Delete old items
        $compra->compraItems()->delete();

        // Crear items y ajustar inventario por delta
        foreach ($validatedData['productos'] as $productoData) {
            $producto = App\Models\Producto::findOrFail($productoData['id']);
            $cantidad = $productoData['cantidad'];
            $precio = $productoData['precio'];
            $descuento = $productoData['descuento'] ?? 0;
            $subtotalProd = $cantidad * $precio;
            $descuentoMonto = $subtotalProd * ($descuento / 100);
            $subtotalFinal = $subtotalProd - $descuentoMonto;

            $oldQty = $oldQtyByProduct[$producto->id] ?? 0;
            $delta = $cantidad - $oldQty;

            if ($delta > 0) {
                $inventarioService->entrada($producto, $delta, [
                    'skip_transaction' => true,
                    'motivo' => 'Debug edicion compra delta +',
                    'almacen_id' => $validatedData['almacen_id'],
                    'user_id' => \Illuminate\Support\Facades\Auth::id(),
                    'referencia_type' => 'App\\Models\\Compra',
                    'referencia_id' => $compra->id,
                ]);
            } elseif ($delta < 0) {
                $inventarioService->salida($producto, abs($delta), [
                    'skip_transaction' => true,
                    'motivo' => 'Debug edicion compra delta -',
                    'almacen_id' => $compra->almacen_id,
                    'user_id' => \Illuminate\Support\Facades\Auth::id(),
                    'referencia_type' => 'App\\Models\\Compra',
                    'referencia_id' => $compra->id,
                ]);
            }

            App\Models\CompraItem::create([
                'compra_id' => $compra->id,
                'comprable_id' => $productoData['id'],
                'comprable_type' => App\Models\Producto::class,
                'cantidad' => $cantidad,
                'precio' => $precio,
                'descuento' => $descuento,
                'subtotal' => $subtotalFinal,
                'descuento_monto' => $descuentoMonto,
            ]);
        }
    });

    echo "Actualizacion completada\n";
} catch (Throwable $e) {
    echo "Error: {$e->getMessage()}\n";
}

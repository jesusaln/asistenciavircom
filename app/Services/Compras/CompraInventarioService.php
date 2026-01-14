<?php

namespace App\Services\Compras;

use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\Inventario;
use App\Models\Producto;
use App\Services\InventarioService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para gestión de inventario en compras
 * Coordina con InventarioService para entradas/salidas
 */
class CompraInventarioService
{
    public function __construct(
        private readonly InventarioService $inventarioService,
        private readonly CompraSerieService $serieService
    ) {
    }

    /**
     * Procesar entrada de inventario para un producto
     */
    public function procesarEntrada(
        Compra $compra,
        Producto $producto,
        int $cantidad,
        int $almacenId,
        array $detallesAdicionales = []
    ): void {
        $this->inventarioService->entrada($producto, $cantidad, [
            'skip_transaction' => true,
            'motivo' => 'Nueva compra',
            'almacen_id' => $almacenId,
            'user_id' => Auth::id(),
            'referencia_type' => 'App\\Models\\Compra',
            'referencia_id' => $compra->id,
            'detalles' => array_merge([
                'compra_id' => $compra->id,
                'producto_id' => $producto->id,
            ], $detallesAdicionales),
        ]);

        Log::info('Entrada de inventario procesada', [
            'compra_id' => $compra->id,
            'producto_id' => $producto->id,
            'cantidad' => $cantidad,
            'almacen_id' => $almacenId
        ]);
    }

    /**
     * Procesar salida de inventario para un producto
     */
    public function procesarSalida(
        Compra $compra,
        Producto $producto,
        int $cantidad,
        int $almacenId,
        string $motivo = 'Cancelación de compra',
        array $detallesAdicionales = []
    ): void {
        $this->inventarioService->salida($producto, $cantidad, [
            'skip_transaction' => true,
            'motivo' => $motivo,
            'almacen_id' => $almacenId,
            'user_id' => Auth::id(),
            'referencia_type' => 'App\\Models\\Compra',
            'referencia_id' => $compra->id,
            'detalles' => array_merge([
                'compra_id' => $compra->id,
                'producto_id' => $producto->id,
            ], $detallesAdicionales),
        ]);

        Log::info('Salida de inventario procesada', [
            'compra_id' => $compra->id,
            'producto_id' => $producto->id,
            'cantidad' => $cantidad,
            'almacen_id' => $almacenId
        ]);
    }

    /**
     * Validar que hay stock suficiente para cancelar la compra
     * @return array Errores encontrados, vacío si todo OK
     */
    public function validarStockParaCancelar(Compra $compra): array
    {
        $errores = [];
        $compraItems = CompraItem::where('compra_id', $compra->id)->get();

        foreach ($compraItems as $item) {
            $producto = Producto::find($item->comprable_id);
            if (!$producto) {
                continue;
            }

            $inventario = Inventario::where('producto_id', $producto->id)
                ->where('almacen_id', $compra->almacen_id)
                ->first();

            if (!$inventario || $inventario->cantidad < $item->cantidad) {
                $almacenNombre = $compra->almacen->nombre ?? 'desconocido';
                $errores[] = "Stock insuficiente del producto '{$producto->nombre}' en el almacén '{$almacenNombre}' para revertir la compra.";
            }
        }

        return $errores;
    }

    /**
     * Revertir inventario de una compra (para cancelación/eliminación)
     */
    public function revertirCompra(Compra $compra): void
    {
        $compraItems = CompraItem::where('compra_id', $compra->id)->get();


        foreach ($compraItems as $item) {
            $producto = Producto::find($item->comprable_id);
            if (!$producto) {
                continue;
            }

            if ($producto->requiere_serie ?? false) {
                // Para productos con series, verificar cuántas están en stock
                $seriesEnStock = $this->serieService->contarSeriesEnStockPorProducto($compra->id);
                $seriesQueDecrementaran = $seriesEnStock[$producto->id] ?? 0;

                $cantidadEsperada = $item->cantidad;
                $diferencia = $cantidadEsperada - $seriesQueDecrementaran;

                // Si hay diferencia, ajustar manualmente el faltante
                if ($diferencia > 0) {
                    Log::warning("Compra cancelación: Series faltantes detectadas", [
                        'compra_id' => $compra->id,
                        'producto_id' => $producto->id,
                        'cantidad_esperada' => $cantidadEsperada,
                        'series_en_stock' => $seriesQueDecrementaran,
                        'diferencia' => $diferencia,
                    ]);

                    $this->procesarSalida(
                        $compra,
                        $producto,
                        $diferencia,
                        $compra->almacen_id,
                        'Cancelación de compra - Ajuste por series faltantes/vendidas',
                        [
                            'compra_item_id' => $item->id,
                            'series_faltantes' => $diferencia,
                            'razon' => 'Series no en stock o ya eliminadas',
                        ]
                    );
                }
                // Las series en en_stock se decrementarán automáticamente por el observer
            } else {
                // Productos sin serie: ajustar inventario manualmente
                $this->procesarSalida(
                    $compra,
                    $producto,
                    $item->cantidad,
                    $compra->almacen_id,
                    'Cancelación de compra',
                    ['compra_item_id' => $item->id]
                );
            }
        }

        Log::info('Inventario de compra revertido', ['compra_id' => $compra->id]);
    }

    /**
     * Procesar diferencia de stock (para edición de compras)
     */
    public function procesarDelta(
        Compra $compra,
        Producto $producto,
        int $cantidadAnterior,
        int $cantidadNueva,
        int $almacenId,
        float $precio,
        float $descuento = 0
    ): void {
        $delta = $cantidadNueva - $cantidadAnterior;

        if ($delta > 0) {
            $this->procesarEntrada($compra, $producto, $delta, $almacenId, [
                'precio_unitario' => $precio,
                'descuento' => $descuento,
                'delta' => $delta,
            ]);
        } elseif ($delta < 0) {
            $this->procesarSalida(
                $compra,
                $producto,
                abs($delta),
                $almacenId,
                'Edición de compra: ajuste por reducción',
                [
                    'precio_unitario' => $precio,
                    'descuento' => $descuento,
                    'delta' => $delta,
                ]
            );
        }
    }
}

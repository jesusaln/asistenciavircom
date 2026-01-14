<?php

namespace App\Services\Compras;

use App\Models\Compra;
use App\Models\Producto;
use App\Models\ProductoSerie;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para gestión de números de serie en compras
 */
class CompraSerieService
{
    /**
     * Crear series para un producto en una compra
     */
    public function crearSeries(Compra $compra, Producto $producto, array $seriales, int $almacenId): void
    {
        foreach ($seriales as $serie) {
            ProductoSerie::create([
                'producto_id' => $producto->id,
                'compra_id' => $compra->id,
                'almacen_id' => $almacenId,
                'numero_serie' => trim((string) $serie),
                'estado' => 'en_stock',
            ]);
        }

        Log::info('Series creadas para producto', [
            'compra_id' => $compra->id,
            'producto_id' => $producto->id,
            'cantidad_series' => count($seriales)
        ]);
    }

    /**
     * Eliminar series en stock de una compra
     * @return int Número de series eliminadas
     */
    public function eliminarSeriesEnStock(int $compraId): int
    {
        // ✅ FIX: Obtener modelos y eliminar uno por uno para disparar el Observer
        // El Observer se encarga de decrementar el inventario
        $series = ProductoSerie::where('compra_id', $compraId)
            ->where('estado', 'en_stock')
            ->get();
            
        $count = $series->count();

        foreach ($series as $serie) {
            $serie->delete();
        }

        Log::info('Series en stock eliminadas', [
            'compra_id' => $compraId,
            'cantidad' => $count
        ]);

        return $count;
    }

    /**
     * Validar que no existan series vendidas
     */
    public function existenSeriesVendidas(int $compraId): bool
    {
        return ProductoSerie::where('compra_id', $compraId)
            ->where('estado', '!=', 'en_stock')
            ->exists();
    }

    /**
     * Contar series en stock por producto
     */
    public function contarSeriesEnStockPorProducto(int $compraId): array
    {
        return ProductoSerie::where('compra_id', $compraId)
            ->where('estado', 'en_stock')
            ->get()
            ->groupBy('producto_id')
            ->map(fn($items) => $items->count())
            ->toArray();
    }

    /**
     * Actualizar series de una compra
     */
    public function actualizarSeries(Compra $compra, array $serialesPorProducto): void
    {
        // Silenciar el observer de inventario para evitar doble conteo
        // CompraInventarioService maneja los cambios de cantidad neta
        // Aquí solo estamos intercambiando números de serie
        \App\Observers\ProductoSerieObserver::$muteInventoryUpdates = true;

        try {
            foreach ($serialesPorProducto as $productoId => $nuevasSeriales) {
                // Obtener series actuales de esta compra para este producto
                $seriesActuales = ProductoSerie::where('compra_id', $compra->id)
                    ->where('producto_id', $productoId)
                    ->get()
                    ->keyBy('numero_serie');
                
                $seriesActualesNumeros = $seriesActuales->pluck('numero_serie')->toArray();
                
                // Identificar series a eliminar
                $seriesAEliminar = array_diff($seriesActualesNumeros, $nuevasSeriales);
                
                // Identificar series a agregar
                $seriesAAgregar = array_diff($nuevasSeriales, $seriesActualesNumeros);
                
                // Eliminar series que ya no están
                if (!empty($seriesAEliminar)) {
                    // Aquí seguimos usando bulk delete porque el observer está silenciado
                    // y realmente queremos un reemplazo directo sin afectar stock
                    ProductoSerie::where('compra_id', $compra->id)
                        ->where('producto_id', $productoId)
                        ->whereIn('numero_serie', $seriesAEliminar)
                        ->delete();
                }
                
                // Agregar nuevas series
                foreach ($seriesAAgregar as $serie) {
                    // Validar que la serie no exista para este producto en otra compra
                    $existe = ProductoSerie::where('producto_id', $productoId)
                        ->where('numero_serie', $serie)
                        ->where('compra_id', '!=', $compra->id)
                        ->exists();

                    if ($existe) {
                        throw new \Exception("La serie '{$serie}' ya existe para este producto en otra compra.");
                    }

                    ProductoSerie::create([
                        'producto_id' => $productoId,
                        'compra_id' => $compra->id,
                        'almacen_id' => $compra->almacen_id,
                        'numero_serie' => $serie,
                        'estado' => 'en_stock',
                    ]);
                }
            }

            Log::info('Series actualizadas para compra', ['compra_id' => $compra->id]);
        } finally {
            // Restaurar comportamiento normal del observer
            \App\Observers\ProductoSerieObserver::$muteInventoryUpdates = false;
        }
    }

    /**
     * Eliminar todas las series de una compra
     */
    public function eliminarTodasLasSeries(int $compraId): void
    {
        // ✅ FIX: Obtener modelos y eliminar uno por uno para disparar el Observer
        // Importante para: 
        // 1. CompraController::destroy -> el observer decrementa el stock
        // 2. CompraController::cancel -> el observer decrementa el stock
        $series = ProductoSerie::where('compra_id', $compraId)->get();
        
        foreach ($series as $serie) {
            $serie->delete();
        }
        
        Log::info('Todas las series eliminadas', ['compra_id' => $compraId, 'cantidad' => $series->count()]);
    }
}

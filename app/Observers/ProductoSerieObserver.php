<?php

namespace App\Observers;

use App\Models\ProductoSerie;
use App\Models\Inventario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Observer para sincronizar automáticamente la tabla inventarios
 * cuando cambia el estado de una serie en producto_series
 * 
 * ✅ FIX #2: Solución a la desincronización entre inventarios y producto_series
 */
class ProductoSerieObserver
{
    /**
     * Flag estatico para silenciar actualizaciones de inventario temporalmente.
     * Util cuando se realizan operaciones masivas donde el servicio maneja la cantidad.
     * @var bool
     */
    public static bool $muteInventoryUpdates = false;

    /**
     * Handle the ProductoSerie "created" event.
     */
    public function created(ProductoSerie $serie): void
    {
        // Solo sincronizar si la serie está en stock
        // No sincronizar si tiene compra_id, porque InventarioService ya lo hizo en CompraController::store()
        // ✅ FIX: actualizarSeries() ahora actualiza series en sitio en lugar de borrar/recrear,
        // por lo que este check sigue siendo válido
        if ($serie->estado === 'en_stock' && !$serie->compra_id) {
            $this->sincronizarInventario($serie, 'incremento');
        }
    }

    /**
     * Handle the ProductoSerie "updated" event.
     */
    public function updated(ProductoSerie $serie): void
    {
        // Solo sincronizar si cambió el estado
        if ($serie->isDirty('estado')) {
            $estadoAnterior = $serie->getOriginal('estado');
            $estadoNuevo = $serie->estado;

            // De cualquier estado a 'en_stock' → incrementar
            if ($estadoNuevo === 'en_stock' && $estadoAnterior !== 'en_stock') {
                $this->sincronizarInventario($serie, 'incremento');
            }
            // De 'en_stock' a cualquier otro estado → decrementar
            elseif ($estadoAnterior === 'en_stock' && $estadoNuevo !== 'en_stock') {
                $this->sincronizarInventario($serie, 'decremento');
            }
        }

        // Si cambió el almacén y está en stock, mover entre almacenes
        if ($serie->isDirty('almacen_id') && $serie->estado === 'en_stock') {
            $almacenAnterior = $serie->getOriginal('almacen_id');
            $almacenNuevo = $serie->almacen_id;

            if ($almacenAnterior && $almacenNuevo && $almacenAnterior !== $almacenNuevo) {
                $this->moverEntreAlmacenes($serie, $almacenAnterior, $almacenNuevo);
            }
        }
    }

    /**
     * Handle the ProductoSerie "deleted" event.
     */
    public function deleted(ProductoSerie $serie): void
    {
        // Si se elimina una serie que estaba en stock, decrementar
        if ($serie->estado === 'en_stock') {
            $this->sincronizarInventario($serie, 'decremento');
        }
    }

    /**
     * Handle the ProductoSerie "restored" event.
     */
    public function restored(ProductoSerie $serie): void
    {
        // Si se restaura una serie en stock, incrementar
        if ($serie->estado === 'en_stock') {
            $this->sincronizarInventario($serie, 'incremento');
        }
    }

    /**
     * Sincronizar inventario cuando cambia el estado de una serie
     * 
     * @param ProductoSerie $serie
     * @param string $tipo 'incremento' o 'decremento'
     */
    protected function sincronizarInventario(ProductoSerie $serie, string $tipo): void
    {
        if (self::$muteInventoryUpdates) {
            return;
        }

        if (!$serie->producto_id || !$serie->almacen_id) {
            Log::warning('ProductoSerieObserver: Serie sin producto_id o almacen_id', [
                'serie_id' => $serie->id,
                'numero_serie' => $serie->numero_serie,
            ]);
            return;
        }

        try {
            DB::transaction(function () use ($serie, $tipo) {
                $producto = \App\Models\Producto::find($serie->producto_id);
                $inventarioService = app(\App\Services\InventarioService::class);

                // Determinar referencia y motivo basado en el contexto
                $referencia = null;
                $referenciaType = null;
                $referenciaId = $serie->venta_id ?? $serie->getOriginal('venta_id');
                $motivo = $tipo === 'incremento' ? 'Entrada por cambio de estado de serie' : 'Salida por cambio de estado de serie';

                if ($referenciaId) {
                    $referencia = \App\Models\Venta::find($referenciaId);
                    $motivo = $tipo === 'incremento' ? 'Devolución de serie por cancelación de venta' : 'Venta de producto con serie';
                } elseif ($compraId = ($serie->compra_id ?? $serie->getOriginal('compra_id'))) {
                    $referencia = \App\Models\Compra::find($compraId);
                    $motivo = $tipo === 'incremento' ? 'Entrada de serie por compra' : 'Devolución de serie por cancelación de compra';
                }

                $contexto = [
                    'almacen_id' => $serie->almacen_id,
                    'motivo' => $motivo,
                    'referencia' => $referencia,
                    'skip_transaction' => true,
                    'detalles' => [
                        'numero_serie' => $serie->numero_serie,
                        'serie_id' => $serie->id,
                        'estado_serie' => $serie->estado,
                        'origen' => 'ProductoSerieObserver',
                    ],
                ];

                if ($tipo === 'incremento') {
                    $inventarioService->entrada($producto, 1, $contexto);
                } else {
                    $inventarioService->salida($producto, 1, $contexto);
                }

                Log::info('ProductoSerieObserver: Inventario sincronizado via InventarioService', [
                    'serie_id' => $serie->id,
                    'numero_serie' => $serie->numero_serie,
                    'tipo' => $tipo,
                    'almacen_id' => $serie->almacen_id,
                ]);

                // Validar consistencia después de sincronizar
                $this->validarConsistencia($serie->producto_id);
            });
        } catch (\Exception $e) {
            Log::error('ProductoSerieObserver: Error sincronizando inventario', [
                'serie_id' => $serie->id,
                'numero_serie' => $serie->numero_serie,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Mover serie entre almacenes
     * 
     * @param ProductoSerie $serie
     * @param int $almacenAnterior
     * @param int $almacenNuevo
     */
    protected function moverEntreAlmacenes(ProductoSerie $serie, int $almacenAnterior, int $almacenNuevo): void
    {
        try {
            DB::transaction(function () use ($serie, $almacenAnterior, $almacenNuevo) {
                $producto = \App\Models\Producto::find($serie->producto_id);
                $inventarioService = app(\App\Services\InventarioService::class);

                // Salida del almacén anterior
                $inventarioService->salida($producto, 1, [
                    'almacen_id' => $almacenAnterior,
                    'motivo' => 'Traspaso entre almacenes (Salida)',
                    'skip_transaction' => true,
                    'detalles' => [
                        'serie_id' => $serie->id,
                        'numero_serie' => $serie->numero_serie,
                        'almacen_destino_id' => $almacenNuevo,
                    ],
                ]);

                // Entrada al almacén nuevo
                $inventarioService->entrada($producto, 1, [
                    'almacen_id' => $almacenNuevo,
                    'motivo' => 'Traspaso entre almacenes (Entrada)',
                    'skip_transaction' => true,
                    'detalles' => [
                        'serie_id' => $serie->id,
                        'numero_serie' => $serie->numero_serie,
                        'almacen_origen_id' => $almacenAnterior,
                    ],
                ]);

                Log::info('ProductoSerieObserver: Serie movida entre almacenes con trazabilidad', [
                    'serie_id' => $serie->id,
                    'numero_serie' => $serie->numero_serie,
                    'almacen_anterior' => $almacenAnterior,
                    'almacen_nuevo' => $almacenNuevo,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('ProductoSerieObserver: Error moviendo serie entre almacenes', [
                'serie_id' => $serie->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Validar consistencia entre inventario y series
     * ✅ MEJORADO: Auto-repara discrepancias cuando las detecta
     * 
     * @param int $productoId
     */
    protected function validarConsistencia(int $productoId): void
    {
        try {
            // Contar series en stock por almacén
            $seriesPorAlmacen = ProductoSerie::where('producto_id', $productoId)
                ->where('estado', 'en_stock')
                ->whereNull('deleted_at')
                ->selectRaw('almacen_id, COUNT(*) as cantidad')
                ->groupBy('almacen_id')
                ->pluck('cantidad', 'almacen_id')
                ->toArray();

            // Obtener todos los almacenes que tienen inventario de este producto
            $inventarios = Inventario::where('producto_id', $productoId)->get();

            $discrepanciaEncontrada = false;
            $totalSeriesEnStock = 0;

            // Verificar cada inventario
            foreach ($inventarios as $inventario) {
                $seriesEnAlmacen = $seriesPorAlmacen[$inventario->almacen_id] ?? 0;
                $totalSeriesEnStock += $seriesEnAlmacen;

                if ($inventario->cantidad != $seriesEnAlmacen) {
                    $discrepanciaEncontrada = true;

                    Log::warning('ProductoSerieObserver: Auto-reparando discrepancia', [
                        'producto_id' => $productoId,
                        'almacen_id' => $inventario->almacen_id,
                        'inventario_actual' => $inventario->cantidad,
                        'series_en_stock' => $seriesEnAlmacen,
                    ]);

                    // ✅ AUTO-REPARAR: Actualizar inventario al valor correcto
                    $inventario->cantidad = $seriesEnAlmacen;
                    $inventario->save();
                }

                // Remover del array para detectar almacenes sin registro de inventario
                unset($seriesPorAlmacen[$inventario->almacen_id]);
            }

            // Crear registros de inventario para almacenes que tienen series pero no tenían registro
            foreach ($seriesPorAlmacen as $almacenId => $cantidad) {
                $totalSeriesEnStock += $cantidad;
                $discrepanciaEncontrada = true;

                Log::warning('ProductoSerieObserver: Creando inventario faltante', [
                    'producto_id' => $productoId,
                    'almacen_id' => $almacenId,
                    'series_en_stock' => $cantidad,
                ]);

                // ✅ AUTO-REPARAR: Crear registro de inventario
                Inventario::create([
                    'producto_id' => $productoId,
                    'almacen_id' => $almacenId,
                    'cantidad' => $cantidad,
                    'stock_minimo' => 0,
                ]);
            }

            // Actualizar stock total del producto si hubo discrepancia
            if ($discrepanciaEncontrada) {
                $nuevoStockTotal = Inventario::where('producto_id', $productoId)->sum('cantidad');
                DB::table('productos')
                    ->where('id', $productoId)
                    ->update(['stock' => $nuevoStockTotal]);

                Log::info('ProductoSerieObserver: Stock total actualizado', [
                    'producto_id' => $productoId,
                    'nuevo_stock_total' => $nuevoStockTotal,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('ProductoSerieObserver: Error validando/reparando consistencia', [
                'producto_id' => $productoId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

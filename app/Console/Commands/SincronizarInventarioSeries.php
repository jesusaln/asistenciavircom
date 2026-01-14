<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductoSerie;
use App\Models\Inventario;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;

/**
 * Comando para sincronizar inventario con las series reales
 * 
 * Este comando corrige discrepancias entre la tabla inventarios
 * y la tabla producto_series para productos serializados
 */
class SincronizarInventarioSeries extends Command
{
    protected $signature = 'inventario:sincronizar-series 
                            {--producto= : ID de producto específico (opcional)}
                            {--almacen= : ID de almacén específico (opcional)}
                            {--dry-run : Mostrar diferencias sin hacer cambios}';

    protected $description = 'Sincroniza el inventario con las series reales (en_stock) de productos serializados';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $productoId = $this->option('producto');
        $almacenId = $this->option('almacen');

        if ($dryRun) {
            $this->warn('🔍 MODO DRY-RUN: Solo se mostrarán las diferencias sin hacer cambios');
        }

        $this->info('Analizando discrepancias entre inventario y series...');

        // Obtener productos serializados
        $query = Producto::where(function ($q) {
            $q->where('requiere_serie', true)
              ->orWhere('maneja_series', true)
              ->orWhere('expires', true);
        });

        if ($productoId) {
            $query->where('id', $productoId);
        }

        $productos = $query->get();

        $totalCorregidos = 0;
        $totalDiscrepancias = 0;

        foreach ($productos as $producto) {
            // Obtener todas las combinaciones producto-almacen con series
            $almacenesQuery = ProductoSerie::where('producto_id', $producto->id)
                ->whereNull('deleted_at')
                ->select('almacen_id')
                ->distinct();
            
            if ($almacenId) {
                $almacenesQuery->where('almacen_id', $almacenId);
            }

            $almacenes = $almacenesQuery->pluck('almacen_id');

            foreach ($almacenes as $almId) {
                // Contar series en_stock para este producto en este almacén
                $seriesEnStock = ProductoSerie::where('producto_id', $producto->id)
                    ->where('almacen_id', $almId)
                    ->where('estado', 'en_stock')
                    ->whereNull('deleted_at')
                    ->count();

                // Obtener inventario actual
                $inventario = Inventario::where('producto_id', $producto->id)
                    ->where('almacen_id', $almId)
                    ->first();

                $cantidadActual = $inventario ? $inventario->cantidad : 0;

                // Verificar discrepancia
                if ($cantidadActual != $seriesEnStock) {
                    $totalDiscrepancias++;
                    
                    $almacen = \App\Models\Almacen::find($almId);
                    $nombreAlmacen = $almacen ? $almacen->nombre : "ID: {$almId}";

                    $this->warn("  ⚠️ {$producto->nombre} en {$nombreAlmacen}:");
                    $this->line("     Inventario actual: {$cantidadActual}");
                    $this->line("     Series en_stock:   {$seriesEnStock}");
                    $this->line("     Diferencia:        " . ($cantidadActual - $seriesEnStock));

                    if (!$dryRun) {
                        DB::transaction(function () use ($producto, $almId, $seriesEnStock, $inventario) {
                            if ($inventario) {
                                $inventario->cantidad = $seriesEnStock;
                                $inventario->save();
                            } else {
                                Inventario::create([
                                    'producto_id' => $producto->id,
                                    'almacen_id' => $almId,
                                    'cantidad' => $seriesEnStock,
                                    'stock_minimo' => 0,
                                ]);
                            }
                        });
                        
                        $totalCorregidos++;
                        $this->info("     ✅ Corregido a {$seriesEnStock}");
                    }
                }
            }
        }

        // Actualizar stock total de productos
        if (!$dryRun && $totalCorregidos > 0) {
            $this->info("\nActualizando stock total de productos...");

            $productosQuery = Producto::where(function ($q) {
                $q->where('requiere_serie', true)
                  ->orWhere('maneja_series', true)
                  ->orWhere('expires', true);
            });

            if ($productoId) {
                $productosQuery->where('id', $productoId);
            }

            $productosQuery->each(function ($producto) {
                $totalStock = Inventario::where('producto_id', $producto->id)->sum('cantidad');
                $producto->stock = $totalStock;
                $producto->save();
                $this->line("  📦 {$producto->nombre}: stock = {$totalStock}");
            });
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("📊 Resumen: {$totalDiscrepancias} discrepancias encontradas");
            $this->warn("Ejecuta sin --dry-run para corregir");
        } else {
            $this->info("✅ Sincronización completada: {$totalCorregidos}/{$totalDiscrepancias} registros corregidos");
        }

        return Command::SUCCESS;
    }
}

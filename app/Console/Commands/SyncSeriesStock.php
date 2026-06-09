<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Producto;
use App\Models\ProductoSerie;
use App\Models\Inventario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncSeriesStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'productos:sync-series-stock
                            {--dry-run : Mostrar cambios sin aplicarlos}
                            {--auto : Ejecutar sin confirmación}
                            {--producto= : Sincronizar solo un producto específico}
                            {--notify : Enviar notificación si se encuentran discrepancias}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizar stock de productos con series basándose en el conteo real de series en stock';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=======================================================');
        $this->info('  SINCRONIZACIÓN DE STOCK BASADO EN SERIES REALES');
        $this->info('=======================================================');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $auto = $this->option('auto');
        $productoId = $this->option('producto');

        if ($dryRun) {
            $this->warn('Modo DRY-RUN: No se aplicarán cambios');
            $this->newLine();
        }

        // Obtener productos a procesar
        $query = Producto::where('requiere_serie', true);
        
        if ($productoId) {
            $query->where('id', $productoId);
        }
        
        $productos = $query->get();

        if ($productos->isEmpty()) {
            $this->error('No se encontraron productos para procesar');
            return 1;
        }

        $this->info("Productos a procesar: {$productos->count()}");
        $this->newLine();

        $cambios = [];
        $errores = [];

        if (!$dryRun) {
            DB::beginTransaction();
        }

        try {
            foreach ($productos as $producto) {
                $this->line("Procesando: {$producto->nombre} (ID: {$producto->id})");
                
                // Obtener series en stock
                $seriesPorAlmacen = ProductoSerie::where('producto_id', $producto->id)
                    ->whereNull('deleted_at')
                    ->where('estado', 'en_stock')
                    ->get()
                    ->groupBy('almacen_id');
                
                $totalSeriesEnStock = $seriesPorAlmacen->sum(fn($group) => $group->count());
                $this->line("  Series en stock: {$totalSeriesEnStock}");
                
                // Obtener inventarios actuales
                $inventariosActuales = Inventario::where('producto_id', $producto->id)->get();
                
                // Procesar cada almacén
                $almacenesConSeries = $seriesPorAlmacen->keys()->toArray();
                $almacenesConInventario = $inventariosActuales->pluck('almacen_id')->toArray();
                $todosLosAlmacenes = array_unique(array_merge($almacenesConSeries, $almacenesConInventario));
                
                foreach ($todosLosAlmacenes as $almacenId) {
                    if (!$almacenId) continue;
                    
                    $cantidadReal = isset($seriesPorAlmacen[$almacenId]) ? $seriesPorAlmacen[$almacenId]->count() : 0;
                    
                    $inventario = Inventario::where('producto_id', $producto->id)
                        ->where('almacen_id', $almacenId)
                        ->first();
                    
                    $cantidadActual = $inventario ? $inventario->cantidad : 0;
                    
                    if ($cantidadActual != $cantidadReal) {
                        $this->warn("  Almacén {$almacenId}: Ajustando de {$cantidadActual} a {$cantidadReal}");
                        
                        if (!$dryRun) {
                            if ($inventario) {
                                $inventario->cantidad = $cantidadReal;
                                $inventario->save();
                            } else {
                                Inventario::create([
                                    'producto_id' => $producto->id,
                                    'almacen_id' => $almacenId,
                                    'cantidad' => $cantidadReal,
                                    'stock_minimo' => 0,
                                ]);
                            }
                        }
                        
                        $cambios[] = [
                            'producto_id' => $producto->id,
                            'producto_nombre' => $producto->nombre,
                            'almacen_id' => $almacenId,
                            'cantidad_anterior' => $cantidadActual,
                            'cantidad_nueva' => $cantidadReal,
                            'diferencia' => $cantidadReal - $cantidadActual,
                        ];
                    }
                }
                
                // Actualizar stock total
                $stockTotal = $dryRun 
                    ? $totalSeriesEnStock 
                    : Inventario::where('producto_id', $producto->id)->sum('cantidad');
                $stockAnterior = $producto->stock;
                
                if ($stockAnterior != $stockTotal) {
                    $this->warn("  Stock total: Ajustando de {$stockAnterior} a {$stockTotal}");
                    
                    if (!$dryRun) {
                        DB::table('productos')
                            ->where('id', $producto->id)
                            ->update(['stock' => $stockTotal]);
                    }
                    
                    $cambios[] = [
                        'producto_id' => $producto->id,
                        'producto_nombre' => $producto->nombre,
                        'almacen_id' => 'TOTAL',
                        'cantidad_anterior' => $stockAnterior,
                        'cantidad_nueva' => $stockTotal,
                        'diferencia' => $stockTotal - $stockAnterior,
                    ];
                }
                
                $this->newLine();
            }
            
            // Mostrar resumen
            $this->info('=======================================================');
            $this->info('  RESUMEN');
            $this->info('=======================================================');
            $this->newLine();
            
            if (count($cambios) > 0) {
                $this->warn("Se encontraron " . count($cambios) . " ajustes necesarios:");
                $this->newLine();
                
                // Crear tabla de cambios
                $tableData = [];
                foreach ($cambios as $cambio) {
                    $signo = $cambio['diferencia'] >= 0 ? '+' : '';
                    $tableData[] = [
                        $cambio['producto_nombre'],
                        $cambio['almacen_id'],
                        $cambio['cantidad_anterior'],
                        $cambio['cantidad_nueva'],
                        $signo . $cambio['diferencia']
                    ];
                }
                
                $this->table(
                    ['Producto', 'Almacén', 'Anterior', 'Nueva', 'Diferencia'],
                    $tableData
                );
                
                if ($dryRun) {
                    $this->info('Modo DRY-RUN: No se aplicaron cambios');
                } else {
                    if (!$auto && !$this->confirm('¿Deseas aplicar estos cambios?', true)) {
                        DB::rollBack();
                        $this->error('Operación cancelada');
                        return 1;
                    }
                    
                    DB::commit();
                    $this->info('✓ Cambios aplicados exitosamente');
                    
                    // Guardar log
                    $logData = [
                        'fecha' => now()->toDateTimeString(),
                        'cambios' => $cambios,
                        'modo' => $auto ? 'auto' : 'manual'
                    ];
                    
                    $logFile = storage_path('logs/sync_series_stock.log');
                    file_put_contents(
                        $logFile, 
                        json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n",
                        FILE_APPEND
                    );
                    
                    Log::info('Sincronización de stock completada', [
                        'cambios_realizados' => count($cambios),
                        'productos_afectados' => count(array_unique(array_column($cambios, 'producto_id')))
                    ]);
                }
                
                // Notificar si se solicitó
                if ($this->option('notify') && count($cambios) > 0) {
                    $this->warn('⚠️  Se encontraron discrepancias - Revisar logs');
                }
                
            } else {
                if (!$dryRun) {
                    DB::commit();
                }
                $this->info('✓ No se encontraron discrepancias. Todo está sincronizado.');
            }
            
            return 0;
            
        } catch (\Exception $e) {
            if (!$dryRun) {
                DB::rollBack();
            }
            
            $this->error('ERROR: ' . $e->getMessage());
            Log::error('Error en sincronización de stock', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }
}

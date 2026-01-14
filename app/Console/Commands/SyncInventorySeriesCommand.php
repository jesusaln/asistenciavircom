<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Producto;

class SyncInventorySeriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:sync-series
                            {--producto_id= : Sincronizar solo un producto específico}
                            {--dry-run : Mostrar cambios sin aplicarlos}
                            {--force : Ejecutar sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza la tabla inventarios con el conteo real de series disponibles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $productoId = $this->option('producto_id');
        $force = $this->option('force');

        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║  SINCRONIZACIÓN INVENTARIO-SERIES                              ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        if ($dryRun) {
            $this->warn('🔍 MODO DRY-RUN: No se aplicarán cambios');
            $this->newLine();
        }

        // Crear respaldo antes de modificar
        $backupFile = null;
        if (!$dryRun) {
            $backupFile = storage_path('backups/inventarios_backup_' . date('Y-m-d_H-i-s') . '.sql');
            $backupDir = dirname($backupFile);

            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $this->info("📦 Creando respaldo en: $backupFile");

            try {
                $inventarios = DB::table('inventarios')->get();
                $backup = "-- Backup de inventarios - " . date('Y-m-d H:i:s') . "\n\n";

                foreach ($inventarios as $inv) {
                    $backup .= sprintf(
                        "UPDATE inventarios SET cantidad = %d WHERE id = %d; -- producto_id=%d, almacen_id=%d\n",
                        $inv->cantidad,
                        $inv->id,
                        $inv->producto_id,
                        $inv->almacen_id
                    );
                }

                file_put_contents($backupFile, $backup);
                $this->info('✅ Respaldo creado exitosamente');
                $this->newLine();
            } catch (\Exception $e) {
                $this->error('❌ Error creando respaldo: ' . $e->getMessage());
                return 1;
            }
        }

        // Obtener productos que requieren series
        $query = Producto::where('requiere_serie', true)
            ->where('estado', 'activo');

        if ($productoId) {
            $query->where('id', $productoId);
        }

        $productos = $query->get();

        $this->info("📊 Productos a sincronizar: " . $productos->count());
        $this->newLine();

        $cambios = [];
        $totalCambios = 0;

        foreach ($productos as $producto) {
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info("Producto ID {$producto->id}: {$producto->nombre}");
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            // Obtener todos los almacenes
            $almacenes = DB::table('almacenes')->where('estado', 'activo')->get();

            foreach ($almacenes as $almacen) {
                // Contar series en stock para este producto en este almacén
                $seriesEnStock = DB::table('producto_series')
                    ->where('producto_id', $producto->id)
                    ->where('almacen_id', $almacen->id)
                    ->where('estado', 'en_stock')
                    ->count();

                // Obtener cantidad actual en inventarios
                $inventario = DB::table('inventarios')
                    ->where('producto_id', $producto->id)
                    ->where('almacen_id', $almacen->id)
                    ->first();

                $cantidadActual = $inventario ? $inventario->cantidad : null;

                // Verificar si hay discrepancia
                if ($cantidadActual !== $seriesEnStock) {
                    $cambio = [
                        'producto_id' => $producto->id,
                        'producto_nombre' => $producto->nombre,
                        'almacen_id' => $almacen->id,
                        'almacen_nombre' => $almacen->nombre,
                        'cantidad_actual' => $cantidadActual ?? 'NULL',
                        'series_en_stock' => $seriesEnStock,
                        'inventario_id' => $inventario->id ?? null,
                    ];

                    $cambios[] = $cambio;
                    $totalCambios++;

                    $this->line(sprintf(
                        "  📍 Almacén: %s (ID: %d)",
                        $almacen->nombre,
                        $almacen->id
                    ));
                    $this->line(sprintf(
                        "     Inventario actual: %s → Series en stock: %d",
                        $cantidadActual ?? 'NULL',
                        $seriesEnStock
                    ));

                    if (!$dryRun) {
                        try {
                            if ($inventario) {
                                // Actualizar registro existente
                                DB::table('inventarios')
                                    ->where('id', $inventario->id)
                                    ->update([
                                        'cantidad' => $seriesEnStock,
                                        'updated_at' => now(),
                                    ]);
                                $this->info("     ✅ Actualizado a $seriesEnStock unidades");
                            } else {
                                // Crear nuevo registro
                                DB::table('inventarios')->insert([
                                    'producto_id' => $producto->id,
                                    'almacen_id' => $almacen->id,
                                    'cantidad' => $seriesEnStock,
                                    'stock_minimo' => 1,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                                $this->info("     ✅ Registro creado con $seriesEnStock unidades");
                            }
                        } catch (\Exception $e) {
                            $this->error("     ❌ Error: " . $e->getMessage());
                        }
                    } else {
                        $this->comment("     🔍 [DRY-RUN] Se actualizaría a $seriesEnStock unidades");
                    }

                    $this->newLine();
                }
            }

            // Actualizar stock total del producto
            if (!$dryRun && $totalCambios > 0) {
                $totalStock = DB::table('inventarios')
                    ->where('producto_id', $producto->id)
                    ->sum('cantidad');

                DB::table('productos')
                    ->where('id', $producto->id)
                    ->update(['stock' => $totalStock]);

                $this->info("  📦 Stock total actualizado: $totalStock unidades");
                $this->newLine();
            }
        }

        $this->newLine();
        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║  RESUMEN                                                       ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        if ($totalCambios > 0) {
            $this->info("📊 Total de cambios detectados: $totalCambios");
            $this->newLine();

            if ($dryRun) {
                $this->warn('🔍 Modo DRY-RUN: Los cambios NO fueron aplicados');
                $this->comment('   Para aplicar los cambios, ejecuta:');
                $this->comment('   php artisan inventory:sync-series --force');
                $this->newLine();
            } else {
                $this->info('✅ Sincronización completada exitosamente');
                $this->info("📦 Respaldo guardado en: $backupFile");
                $this->newLine();

                // Generar reporte de cambios
                $reportFile = storage_path('logs/sync_inventory_' . date('Y-m-d_H-i-s') . '.log');
                $report = "Sincronización Inventario-Series - " . date('Y-m-d H:i:s') . "\n\n";
                $report .= "Cambios realizados:\n\n";

                foreach ($cambios as $cambio) {
                    $report .= sprintf(
                        "Producto: %s (ID: %d)\n",
                        $cambio['producto_nombre'],
                        $cambio['producto_id']
                    );
                    $report .= sprintf(
                        "Almacén: %s (ID: %d)\n",
                        $cambio['almacen_nombre'],
                        $cambio['almacen_id']
                    );
                    $report .= sprintf(
                        "Cambio: %s → %d\n\n",
                        $cambio['cantidad_actual'],
                        $cambio['series_en_stock']
                    );
                }

                file_put_contents($reportFile, $report);
                $this->info("📄 Reporte guardado en: $reportFile");
                $this->newLine();
            }
        } else {
            $this->info('✅ No se detectaron discrepancias');
            $this->comment('   El inventario está sincronizado con las series');
            $this->newLine();
        }

        // Mostrar estado final
        if (!$dryRun && $totalCambios > 0) {
            $this->info('╔════════════════════════════════════════════════════════════════╗');
            $this->info('║  VERIFICACIÓN POST-SINCRONIZACIÓN                              ║');
            $this->info('╚════════════════════════════════════════════════════════════════╝');
            $this->newLine();

            foreach ($productos as $producto) {
                $totalInventario = DB::table('inventarios')
                    ->where('producto_id', $producto->id)
                    ->sum('cantidad');

                $totalSeries = DB::table('producto_series')
                    ->where('producto_id', $producto->id)
                    ->where('estado', 'en_stock')
                    ->count();

                $status = $totalInventario === $totalSeries ? '✅' : '❌';

                $this->line(sprintf(
                    "%s Producto: %s",
                    $status,
                    $producto->nombre
                ));
                $this->comment(sprintf(
                    "   Inventario: %d | Series: %d",
                    $totalInventario,
                    $totalSeries
                ));
                $this->newLine();
            }
        }

        $this->info('Finalizado.');
        return 0;
    }
}

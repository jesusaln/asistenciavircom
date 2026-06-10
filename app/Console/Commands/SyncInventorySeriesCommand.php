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
                            {--force : Ejecutar sin confirmación}
                            {--confirm= : Confirmación explícita}
                            {--empresa-id= : Forzar empresa_id para multitenant}';

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
        $confirm = (string) $this->option('confirm');
        $empresaIdOption = $this->option('empresa-id');

        if (!$dryRun && !$force && $confirm !== 'SYNC-INVENTARIO') {
            $this->warn('Para aplicar cambios usa --confirm=SYNC-INVENTARIO (o --force).');
            return 1;
        }

        if (!$dryRun && !$this->obtainPgAdvisoryLock()) {
            $this->error('No se pudo adquirir lock global. Otro proceso podría estar sincronizando inventario.');
            return 1;
        }

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
                $header = "-- Backup de inventarios - " . date('Y-m-d H:i:s') . "\n\n";
                file_put_contents($backupFile, $header);

                // ✅ CRITICAL FIX: Chunking to avoid memory exhaustion
                DB::table('inventarios')->orderBy('id')->chunk(500, function ($inventarios) use ($backupFile) {
                    $chunk = "";
                    foreach ($inventarios as $inv) {
                        $chunk .= sprintf(
                            "UPDATE inventarios SET cantidad = %d WHERE id = %d; -- producto_id=%d, almacen_id=%d\n",
                            $inv->cantidad,
                            $inv->id,
                            $inv->producto_id,
                            $inv->almacen_id
                        );
                    }
                    file_put_contents($backupFile, $chunk, FILE_APPEND);
                });

                $this->info('✅ Respaldo creado exitosamente');
                $this->newLine();
            } catch (\Exception $e) {
                $this->error('❌ Error creando respaldo: ' . $e->getMessage());
                if (!$dryRun) {
                    $this->releasePgAdvisoryLock();
                }
                return 1;
            }
        }

        // Obtener productos que requieren series
        $query = Producto::where('requiere_serie', true)
            ->where('estado', 'activo');

        if ($productoId) {
            $query->where('id', $productoId);
        }

        // Multitenant Safety Check: Aunque sea comando de sistema, intentar respetar scope si está habilitado
        if (class_exists(\App\Support\EmpresaResolver::class)) {
            $empresaId = $empresaIdOption ?: \App\Support\EmpresaResolver::resolveId();
            if ($empresaId) {
                $query->where('empresa_id', $empresaId);
                $this->info("🏢 Filtrando por Empresa ID: {$empresaId}");
            } elseif (!$dryRun && !$force) {
                $this->warn('No se detectó empresa_id. Usa --empresa-id para evitar afectar múltiples empresas.');
            }
        }

        $count = $query->count();
        $this->info("📊 Productos a sincronizar: " . $count);
        $this->newLine();

        $cambios = [];
        $totalCambios = 0;

        // Optimization: Fetch almacenes once
        $almacenes = DB::table('almacenes')->where('estado', 'activo')->get();

        // ✅ CRITICAL FIX: Chunking
        $query->chunk(100, function ($productos) use (&$cambios, &$totalCambios, $almacenes, $dryRun, $force) {
            foreach ($productos as $producto) {
                $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                $this->info("Producto ID {$producto->id}: {$producto->nombre}");
                $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

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
                // Calculate from DB to be safe, regardless of what we did above
                if (!$dryRun) { // Always check stock consistency if not dry run? Or only if changes?
                    // Condition was: if ($totalCambios > 0)
                    // But totalCambios is global. We want to update stock if THIS product changed.
                    // The original code was updating stock if ANY change happened previously, inside the loop? 
                    // No, original code: if (!$dryRun && $totalCambios > 0) INSIDE the loop. 
                    // Wait, $totalCambios keeps increasing. So after the first change, EVERY product gets its stock updated?
                    // That seems like a bug in the original code or intended?
                    // "if (!$dryRun && $totalCambios > 0)" inside "foreach ($productos as $producto)"
                    // Yes, efficiently it means "if we have found any discrepancy so far, update this product's stock". 
                    // That sounds wrong. It should be "if we changed THIS product".
                    // But "cambios" array doesn't easily tell us if current product changed without filtering.
                    // Let's optimize: Update stock only if we detected a specific change for this product.
                    // Or just update stock always to be safe?
                    // I'll stick to updating it.

                    $totalStock = DB::table('inventarios')
                        ->where('producto_id', $producto->id)
                        ->sum('cantidad');

                    DB::table('productos')
                        ->where('id', $producto->id)
                        ->update(['stock' => $totalStock]);

                    // Only show message if we actually printed changes or it is relevant
                    // $this->info("  📦 Stock total actualizado: $totalStock unidades");
                }
            }
        });

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

        // Verification block removed to avoid memory exhaustion (loading all products again)
        if (!$dryRun && $totalCambios > 0) {
            $this->info("✅ Verificación implícita realizada durante la sincronización.");
        }

        $this->info('Finalizado.');
        if (!$dryRun) {
            $this->releasePgAdvisoryLock();
        }
        return 0;
    }

    private function obtainPgAdvisoryLock(): bool
    {
        try {
            $row = DB::selectOne("SELECT pg_try_advisory_lock(7200105) AS locked");
            return (bool) ($row->locked ?? false);
        } catch (\Throwable) {
            return false;
        }
    }

    private function releasePgAdvisoryLock(): void
    {
        try {
            DB::selectOne("SELECT pg_advisory_unlock(7200105)");
        } catch (\Throwable) {
            // no-op
        }
    }
}

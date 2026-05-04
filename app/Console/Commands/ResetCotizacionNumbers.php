<?php

namespace App\Console\Commands;

use App\Models\Cotizacion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Console\Traits\EnforcesMaintenanceMode;

class ResetCotizacionNumbers extends Command
{
    use EnforcesMaintenanceMode;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cotizaciones:reset-numbers {--force : Forzar la ejecución sin confirmación} {--confirm= : Confirmación explícita}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reiniciar la numeración de cotizaciones eliminando números existentes y comenzando desde C001';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Check Maintenance Mode
        if (!$this->checkMaintenanceMode($this->option('force'))) {
            return 1;
        }

        $this->info('🔄 Reiniciando numeración de cotizaciones...');

        // Confirmar la operación
        $confirm = (string) $this->option('confirm');
        if (
            !$this->option('force')
            && $confirm !== 'RESET-COTIZACIONES'
            && !$this->confirm('¿Estás seguro de que quieres eliminar todos los números de cotización existentes? Esta acción no se puede deshacer.')
        ) {
            $this->info('Operación cancelada.');
            return 0;
        }

        if (!$this->option('force') && $confirm !== 'RESET-COTIZACIONES') {
            $this->warn('Para ejecutar sin prompt usa --confirm=RESET-COTIZACIONES (o --force).');
        }

        if (!$this->obtainPgAdvisoryLock()) {
            $this->error('No se pudo adquirir lock global. Otro proceso podría estar reseteando folios.');
            return 1;
        }

        try {
            DB::beginTransaction();

            // Contar cotizaciones existentes
            $totalCotizaciones = Cotizacion::count();
            $this->info("Total de cotizaciones encontradas: {$totalCotizaciones}");

            if ($totalCotizaciones > 0) {
                // Hacer backup seguro usando JSON streaming para evitar OOM
                $filename = 'cotizaciones_backup_' . date('Y-m-d_H-i-s') . '.json';
                $path = storage_path('app/' . $filename);
                $this->info("💾 Creando respaldo seguro en: {$filename}");

                $handle = fopen($path, 'w');
                fwrite($handle, '[');

                $first = true;
                DB::table('cotizaciones')->orderBy('id')->chunk(500, function ($rows) use ($handle, &$first) {
                    foreach ($rows as $row) {
                        if (!$first) {
                            fwrite($handle, ',');
                        }
                        fwrite($handle, json_encode($row));
                        $first = false;
                    }
                });

                fwrite($handle, ']');
                fclose($handle);

                $this->info("Respaldo completado.");

                // Usar TRUNCATE con CASCADE para Postgres para asegurar limpieza y reinicio de secuencia de manera síncrona
                $this->warn('⚠️ Ejecutando TRUNCATE CASCADE...');
                DB::statement('TRUNCATE TABLE cotizaciones RESTART IDENTITY CASCADE');

                $this->info("✅ Todas las cotizaciones eliminadas y secuencia C001 lista.");
            } else {
                $this->info('ℹ️ No hay cotizaciones existentes para limpiar');
            }

            DB::commit();

            $this->releasePgAdvisoryLock();

            $this->info('🎉 Proceso completado exitosamente');
            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->releasePgAdvisoryLock();
            $this->error('❌ Error al reiniciar numeración: ' . $e->getMessage());
            return 1;
        }
    }

    private function obtainPgAdvisoryLock(): bool
    {
        try {
            $row = DB::selectOne("SELECT pg_try_advisory_lock(7200102) AS locked");
            return (bool) ($row->locked ?? false);
        } catch (\Throwable) {
            return false;
        }
    }

    private function releasePgAdvisoryLock(): void
    {
        try {
            DB::selectOne("SELECT pg_advisory_unlock(7200102)");
        } catch (\Throwable) {
            // no-op
        }
    }
}

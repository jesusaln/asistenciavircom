<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Alerta cuando hay trabajos fallidos en la cola (tabla failed_jobs).
 * Para dashboard en tiempo real en Redis, considerar laravel/horizon.
 */
class AlertFailedQueueJobsCommand extends Command
{
    protected $signature = 'queue:alert-failed
                            {--threshold=1 : Número mínimo de fallos para registrar alerta crítica}';

    protected $description = 'Registra en logs si hay jobs fallidos pendientes (failed_jobs)';

    public function handle(): int
    {
        try {
            if (! Schema::hasTable('failed_jobs')) {
                return self::SUCCESS;
            }
        } catch (Throwable $e) {
            $this->warn('No se pudo consultar failed_jobs (base de datos no disponible).');

            return self::SUCCESS;
        }

        try {
            $count = (int) DB::table('failed_jobs')->count();
        } catch (Throwable $e) {
            Log::warning('queue:alert-failed no pudo contar failed_jobs', ['error' => $e->getMessage()]);

            return self::SUCCESS;
        }
        $threshold = max(1, (int) $this->option('threshold'));

        if ($count >= $threshold) {
            Log::critical('Cola: hay trabajos fallidos sin resolver', [
                'failed_jobs_count' => $count,
                'threshold' => $threshold,
            ]);
            $this->warn("Hay {$count} job(s) en failed_jobs (umbral: {$threshold}). Revisar `php artisan queue:failed`.");
        } else {
            $this->info('No hay umbral de alerta de jobs fallidos.');
        }

        return self::SUCCESS;
    }
}

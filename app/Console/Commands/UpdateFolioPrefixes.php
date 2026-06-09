<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FolioConfig;
use Illuminate\Support\Facades\Log;

class UpdateFolioPrefixes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'folio:update-prefixes {--confirm= : Confirmación explícita}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates folio prefixes to user-friendly values';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startedAt = microtime(true);
        $confirm = (string) $this->option('confirm');
        if ($confirm !== 'SYNC-FOLIOS') {
            $this->warn('Este comando modifica prefijos globales. Usa --confirm=SYNC-FOLIOS para ejecutar.');
            return self::FAILURE;
        }

        $this->info('Updating Folio Prefixes...');

        if (!$this->obtainPgAdvisoryLock()) {
            $this->error('No se pudo adquirir lock global. Otro proceso podría estar actualizando folios.');
            return self::FAILURE;
        }

        try {

            $prefixes = [
                'cliente' => 'CLI',
                'proveedor' => 'PRO',
                'cita' => 'CIT',
                'herramienta' => 'HER',
                'producto' => 'PROD',
                'servicio' => 'SERV',
                'mantenimiento' => 'MTO',
                'nomina' => 'NOM',
                'prestamo' => 'PRE',
                'renta' => 'REN',
                'ticket' => 'TKT',
                'traspaso' => 'TRA',
                'vacacion' => 'VAC',
                // Core Modules Integration
                'cotizacion' => 'COT',
                'venta' => 'VEN',
                'pedido' => 'PED',
                'orden_compra' => 'ORD',
                'compra' => 'COM',
            ];

            foreach ($prefixes as $type => $prefix) {
                $config = FolioConfig::firstOrCreate(
                    ['document_type' => $type],
                    ['current_number' => 0, 'padding' => 4] // Default padding 4 mostly
                );

                // Just update the prefix
                $config->update(['prefix' => $prefix]);

                // Ensure padding is at least 3 or 4 if desired, or keep existing. 
                // Let's safe-guard padding to be at least 3.
                if ($config->padding < 3) {
                    $config->update(['padding' => 3]);
                }

                $this->info("Updated {$type} -> Prefix: {$prefix}");
            }

            Log::info('UpdateFolioPrefixes completado', [
                'count' => count($prefixes),
                'duration_s' => round(microtime(true) - $startedAt, 2),
            ]);
            $this->info('Prefixes updated successfully!');
            $this->releasePgAdvisoryLock();
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->releasePgAdvisoryLock();
            Log::error('UpdateFolioPrefixes fallo', [
                'error' => $e->getMessage(),
            ]);
            $this->error('Error updating prefixes: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function obtainPgAdvisoryLock(): bool
    {
        try {
            $row = \Illuminate\Support\Facades\DB::selectOne("SELECT pg_try_advisory_lock(7200201) AS locked");
            return (bool) ($row->locked ?? false);
        } catch (\Throwable) {
            return false;
        }
    }

    private function releasePgAdvisoryLock(): void
    {
        try {
            \Illuminate\Support\Facades\DB::selectOne("SELECT pg_advisory_unlock(7200201)");
        } catch (\Throwable) {
            // no-op
        }
    }
}

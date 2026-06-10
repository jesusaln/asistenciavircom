<?php

namespace App\Console\Commands;

use App\Models\SatDescargaDetalle;
use App\Services\SatDescargaMasivaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SatImportStaging extends Command
{
    protected $signature = 'sat:import-staging {--limit=1000 : Límite de registros a importar}';
    protected $description = 'Importa los CFDIs descargados del SAT desde la tabla temporal (staging) hacia el Administrador de Documentos principal';

    public function handle(SatDescargaMasivaService $service)
    {
        $limit = $this->option('limit');
        $this->info("Buscando CFDIs pendientes en staging para importar...");

        $pendientes = SatDescargaDetalle::where('importado', false)
            ->limit($limit)
            ->get();

        if ($pendientes->isEmpty()) {
            $this->info("No hay registros pendientes por importar en staging.");
            return 0;
        }

        $ids = $pendientes->pluck('id')->toArray();
        $total = count($ids);
        $this->info("Se encontraron {$total} documentos para importar.");

        try {
            $result = $service->importarDesdeStaging($ids);
            $this->info("Importación completada: {$result['inserted']} insertados exitosamente, {$result['errors']} errores.");
            Log::info("SAT Import Staging (03:50): Completado", ['inserted' => $result['inserted'], 'errors' => $result['errors']]);
        } catch (\Throwable $e) {
            $this->error("Error durante la importación: " . $e->getMessage());
            Log::error("SAT Import Staging Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}

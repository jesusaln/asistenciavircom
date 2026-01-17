<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PolizaMantenimientoService;

class GenerarMantenimientosPoliza extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'polizas:generar-mantenimientos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera tareas de mantenimiento para pólizas activas según su periodicidad';

    /**
     * Execute the console command.
     */
    public function handle(PolizaMantenimientoService $service)
    {
        $this->info('Iniciando generación de mantenimientos...');

        try {
            $count = $service->generarMantenimientos();
            $this->info("✅ Se generaron {$count} nuevas tareas de mantenimiento.");
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}

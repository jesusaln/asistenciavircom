<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PolizaAutomationService;

class ProcessPolizaMaintenance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-poliza-maintenance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesa las pólizas de servicio que tienen mantenimientos programados para generar tickets y citas automáticas.';

    /**
     * Execute the console command.
     */
    public function handle(PolizaAutomationService $service)
    {
        $this->info('Iniciando procesamiento de mantenimientos de pólizas...');

        try {
            $service->processScheduledMaintenances();
            $this->info('Procesamiento completado con éxito.');
        } catch (\Exception $e) {
            $this->error('Error durante el procesamiento: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

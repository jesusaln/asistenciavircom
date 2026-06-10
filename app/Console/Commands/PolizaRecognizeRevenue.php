<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PolizaRentabilidadService;

class PolizaRecognizeRevenue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'polizas:recognize-revenue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesa el reconocimiento de ingresos mensual según IFRS15';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando reconocimiento de ingresos (IFRS15)...');

        $service = app(PolizaRentabilidadService::class);
        $total = $service->recognizeMonthlyRevenue();

        $this->info("Proceso completado. Pólizas actualizadas: {$total}");
    }
}

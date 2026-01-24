<?php

namespace App\Console\Commands;

use App\Services\PolizaBillingService;
use Illuminate\Console\Command;

class PolizaGenerateCharges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'polizas:generate-charges';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera los cargos mensuales de las pólizas activas según su día de cobro.';

    /**
     * Execute the console command.
     */
    public function handle(PolizaBillingService $billingService)
    {
        $this->info('Iniciando proceso de generación de cargos...');

        $count = $billingService->processDailyBilling();

        $this->info("Proceso completado. Se generaron {$count} cargos nuevos.");

        return 0;
    }
}

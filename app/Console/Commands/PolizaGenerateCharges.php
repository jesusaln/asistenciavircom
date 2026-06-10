<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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
    protected $description = 'Desactivado: los cargos de pólizas se generan manualmente por el agente.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $message = 'Generación automática de cargos de pólizas desactivada. Use el flujo manual desde la póliza.';
        $this->warn($message);
        Log::info($message);

        return 0;
    }
}

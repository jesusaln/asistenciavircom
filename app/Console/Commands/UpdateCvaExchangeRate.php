<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CVAService;
use App\Models\EmpresaConfiguracion;
use Illuminate\Support\Facades\Log;

class UpdateCvaExchangeRate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cva:update-tc';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza el tipo de cambio desde la API de CVA';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $config = EmpresaConfiguracion::getConfig();

        if (!$config->cva_active) {
            $this->warn('La integración con CVA no está activa.');
            return;
        }

        if (!$config->cva_tipo_cambio_auto) {
            $this->info('La actualización automática del tipo de cambio está desactivada.');
            return;
        }

        $this->info('Consultando tipo de cambio en CVA...');

        $service = new CVAService();
        $tc = $service->updateExchangeRate();

        if ($tc) {
            $this->success("Tipo de cambio actualizado correctamente: $tc MXN/USD");

            // Limpiar caché para que los precios se actualicen
            \Illuminate\Support\Facades\Cache::flush();
            $this->info('Caché de precios limpiada.');
        } else {
            $this->error('No se pudo obtener el tipo de cambio de CVA.');
        }
    }

    protected function success($message)
    {
        $this->output->writeln("<info>✔</info> $message");
    }
}

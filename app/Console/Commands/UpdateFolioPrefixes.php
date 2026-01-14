<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FolioConfig;

class UpdateFolioPrefixes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'folio:update-prefixes';

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
        $this->info('Updating Folio Prefixes...');

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

        $this->info('Prefixes updated successfully!');
    }
}

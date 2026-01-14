<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CVAService;

class CVAListarInfoEnvio extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cva:info-envio';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Muestra las sucursales y paqueterías disponibles en CVA';

    /**
     * Execute the console command.
     */
    public function handle(CVAService $cvaService)
    {
        $this->info('Consultando información de envío CVA...');

        // 1. Sucursales
        $this->info('--- Sucursales ---');
        $sucursales = $cvaService->getSucursales();

        if (empty($sucursales)) {
            $this->warn('No se pudieron obtener sucursales.');
        } else {
            $headers = ['Clave', 'Nombre', 'CP'];
            $data = [];
            foreach ($sucursales as $sucursal) {
                $data[] = [
                    $sucursal['clave'] ?? 'N/A',
                    $sucursal['nombre'] ?? 'N/A',
                    $sucursal['cp'] ?? 'N/A',
                ];
            }
            $this->table($headers, $data);
        }

        $this->newLine();

        // 2. Paqueterías
        $this->info('--- Paqueterías ---');
        $paqueterias = $cvaService->getPaqueterias();

        if (empty($paqueterias)) {
            $this->warn('No se pudieron obtener paqueterías.');
        } else {
            $headers = ['Clave', 'Descripción'];
            $data = [];
            foreach ($paqueterias as $paqueteria) {
                $data[] = [
                    $paqueteria['clave'] ?? 'N/A',
                    $paqueteria['descripcion'] ?? 'N/A',
                ];
            }
            $this->table($headers, $data);
        }

        return 0;
    }
}

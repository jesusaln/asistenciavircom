<?php

namespace App\Console\Commands\Contabilidad;

use Illuminate\Console\Command;
use App\Services\Contab\ContabilidadService;
use App\Models\Empresa;

class SincronizarEstadosSat extends Command
{
    protected $signature = 'contabilidad:sync-sat {--empresa= : ID de la empresa específica} {--limit=100 : Cantidad de CFDIs por lote}';
    protected $description = 'Sincroniza el estado de los CFDIs con el SAT para detectar cancelaciones sorpresa.';

    protected ContabilidadService $service;

    public function __construct(ContabilidadService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle()
    {
        $empresaId = $this->option('empresa');
        $limit = (int) $this->option('limit');

        if ($empresaId) {
            $empresas = Empresa::where('id', $empresaId)->get();
        } else {
            $empresas = Empresa::all();
        }

        if ($empresas->isEmpty()) {
            $this->error("No se encontraron empresas para procesar.");
            return 1;
        }

        foreach ($empresas as $empresa) {
            $this->info("Procesando empresa: {$empresa->nombre} (RFC: {$empresa->rfc})");
            
            $res = $this->service->sincronizarEstadosSat($empresa->id, $limit);

            if ($res['errores'] > 0) {
                $this->error("Se detectaron {$res['errores']} errores durante la sincronización.");
            }

            $this->table(
                ['Procesados', 'Cancelados Detectados', 'Errores'],
                [[$res['procesados'], $res['cancelados'], $res['errores']]]
            );

            if (!empty($res['detalles'])) {
                $this->warn("Facturas canceladas detectadas:");
                $this->table(
                    ['UUID', 'Folio', 'Receptor/Emisor', 'Total'],
                    array_map(fn($d) => [$d['uuid'], $d['folio'], $d['receptor'], $d['total']], $res['detalles'])
                );
            }
        }

        $this->info("Sincronización terminada.");
        return 0;
    }
}

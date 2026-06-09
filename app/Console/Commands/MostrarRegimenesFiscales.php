<?php

namespace App\Console\Commands;

use App\Models\SatRegimenFiscal;
use Illuminate\Console\Command;

class MostrarRegimenesFiscales extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sat:regimenes-fiscales {tipo_persona : Tipo de persona (fisica|moral)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mostrar regímenes fiscales válidos para un tipo de persona';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tipoPersona = $this->argument('tipo_persona');

        if (!in_array($tipoPersona, ['fisica', 'moral'])) {
            $this->error('Tipo de persona debe ser "fisica" o "moral"');
            return 1;
        }

        $this->info("🔍 Regímenes fiscales para {$tipoPersona}:");

        $regimenes = SatRegimenFiscal::query()
            ->when($tipoPersona === 'fisica', fn($q) => $q->where('persona_fisica', true))
            ->when($tipoPersona === 'moral', fn($q) => $q->where('persona_moral', true))
            ->orderBy('clave')
            ->get(['clave', 'descripcion']);

        if ($regimenes->isEmpty()) {
            $this->warn('No se encontraron regímenes fiscales para este tipo de persona');
            return 0;
        }

        $this->table(
            ['Clave', 'Descripción'],
            $regimenes->map(fn($r) => [$r->clave, $r->descripcion])
        );

        $this->info("Total: {$regimenes->count()} regímenes fiscales");

        return 0;
    }
}

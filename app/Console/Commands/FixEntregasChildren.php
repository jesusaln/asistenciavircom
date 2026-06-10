<?php

namespace App\Console\Commands;

use App\Models\EntregaDinero;
use Illuminate\Console\Command;

class FixEntregasChildren extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:entregas-children';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza el estado recibido en todos los registros hijos cuyos lotes o declaraciones de Mi Corte padre ya estén recibidos.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Buscando lotes y declaraciones recibidas...");

        $lotesRecibidos = EntregaDinero::whereIn('tipo_origen', ['lote', 'declaracion_mi_corte'])
            ->where('estado', 'recibido')
            ->get();

        $count = 0;
        foreach ($lotesRecibidos as $lote) {
            foreach ($lote->children as $child) {
                if ($child->estado !== 'recibido') {
                    $child->update([
                        'estado' => 'recibido',
                        'recibido_por' => $lote->recibido_por,
                        'fecha_recibido' => $lote->fecha_recibido ?? now(),
                        'notas_recibido' => "Recibido vía Lote #{$lote->id} (Sincronización automática)",
                        'cuenta_bancaria_id' => $lote->cuenta_bancaria_id,
                    ]);
                    $count++;
                }
            }
        }

        $this->info("¡Sincronización completada! {$count} registros hijos actualizados a recibido.");
        return 0;
    }
}

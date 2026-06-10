<?php

namespace App\Console\Commands;

use App\Models\MovimientoBancario;
use Illuminate\Console\Command;

class BackfillMovimientosBancariosOrigenTipo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'movimientos:backfill-origen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rellena el campo origen_tipo de los movimientos bancarios existentes basándose en el conciliable_type';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando backfill de origen_tipo para movimientos bancarios...');

        // Mapeo de conciliable_type a origen_tipo
        $mapping = [
            'App\\Models\\CuentasPorCobrar' => 'venta',
            'App\\Models\\Cobranza' => 'renta',
            'App\\Models\\PagoPrestamo' => 'prestamo',
            'App\\Models\\HistorialPagoPrestamo' => 'prestamo',
            'App\\Models\\EntregaDinero' => 'cobro',
            'App\\Models\\TraspasoBancario' => 'traspaso',
            'App\\Models\\CuentasPorPagar' => 'pago',
        ];

        $totalUpdated = 0;
        $totalSkipped = 0;

        // 1. Actualizar movimientos con conciliable_type definido
        foreach ($mapping as $conciliableType => $origenTipo) {
            $count = MovimientoBancario::where('conciliable_type', $conciliableType)
                ->whereNull('origen_tipo')
                ->update(['origen_tipo' => $origenTipo]);

            if ($count > 0) {
                $this->line("  ✓ {$count} movimientos actualizados con origen_tipo = '{$origenTipo}' (de {$conciliableType})");
                $totalUpdated += $count;
            }
        }

        // 2. Detectar traspasos por concepto (si no tienen conciliable_type)
        $traspasosCount = MovimientoBancario::whereNull('origen_tipo')
            ->whereNull('conciliable_type')
            ->where(function ($query) {
                $query->where('concepto', 'like', '%traspaso%')
                    ->orWhere('concepto', 'like', '%Traspaso%')
                    ->orWhere('concepto', 'like', '%TRASPASO%');
            })
            ->update(['origen_tipo' => 'traspaso']);

        if ($traspasosCount > 0) {
            $this->line("  ✓ {$traspasosCount} movimientos detectados como traspasos por concepto");
            $totalUpdated += $traspasosCount;
        }

        // 3. Movimientos sin conciliable_type ni concepto identificable -> 'otro'
        $otrosCount = MovimientoBancario::whereNull('origen_tipo')
            ->whereNull('conciliable_type')
            ->update(['origen_tipo' => 'otro']);

        if ($otrosCount > 0) {
            $this->line("  ✓ {$otrosCount} movimientos marcados como 'otro' (sin origen identificable)");
            $totalUpdated += $otrosCount;
        }

        // Resumen
        $this->newLine();
        $this->info("═══════════════════════════════════════════");
        $this->info("  Backfill completado:");
        $this->info("  - Movimientos actualizados: {$totalUpdated}");
        $this->info("  - Movimientos con origen_tipo ya definido: " . 
            MovimientoBancario::whereNotNull('origen_tipo')->count());
        $this->info("═══════════════════════════════════════════");

        return Command::SUCCESS;
    }
}

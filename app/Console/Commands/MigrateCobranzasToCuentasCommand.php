<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateCobranzasToCuentasCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-cobranzas-to-cuentas-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration of Cobranzas to CuentasPorCobrar...');
        
        $cobranzas = \App\Models\Cobranza::all();
        $bar = $this->output->createProgressBar(count($cobranzas));
        
        foreach ($cobranzas as $cobranza) {
            // Check if already migrated to avoid duplicates (optional check)
            /* 
            $exists = \App\Models\CuentasPorCobrar::where('cobrable_id', $cobranza->renta_id)
                ->where('cobrable_type', \App\Models\Renta::class)
                ->whereDate('fecha_vencimiento', $cobranza->fecha_cobro)
                ->exists();
            if ($exists) continue; 
            */

            \App\Models\CuentasPorCobrar::create([
                'cobrable_id' => $cobranza->renta_id,
                'cobrable_type' => \App\Models\Renta::class,
                'venta_id' => null, // Explicitly null
                'fecha_vencimiento' => $cobranza->fecha_cobro,
                'monto_total' => $cobranza->monto_cobrado,
                'monto_pagado' => $cobranza->monto_pagado ?? 0,
                'monto_pendiente' => max(0, $cobranza->monto_cobrado - ($cobranza->monto_pagado ?? 0)),
                'estado' => $this->mapEstado($cobranza->estado),
                'notas' => ucfirst($cobranza->concepto ?? 'Mensualidad') . ($cobranza->notas ? " - " . $cobranza->notas : ''),
                'created_at' => $cobranza->created_at,
                'updated_at' => $cobranza->updated_at,
            ]);
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->info("\nMigration completed.");
    }

    private function mapEstado($estado)
    {
        // Map Cobranza status to CuentasPorCobrar status if different
        // Assuming they are similar: pendiente, pagado, vencido, parcial
        return $estado;
    }
}

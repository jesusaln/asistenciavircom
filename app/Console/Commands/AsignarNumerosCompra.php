<?php

namespace App\Console\Commands;

use App\Models\Compra;
use Illuminate\Console\Command;

class AsignarNumerosCompra extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:asignar-numeros-compra {--force : Forzar reasignación de números}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Asigna números de compra a las compras existentes que no los tengan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');

        if ($force) {
            $this->warn('Modo fuerza activado: se reasignarán todos los números de compra');
        }

        // Compras directas (sin orden_compra_id)
        $comprasDirectas = Compra::whereNull('orden_compra_id')
            ->when(!$force, fn($q) => $q->whereNull('numero_compra'))
            ->orderBy('created_at')
            ->get();

        if ($comprasDirectas->count() > 0) {
            $this->info("Asignando números a {$comprasDirectas->count()} compras directas...");

            $numero = 1;
            foreach ($comprasDirectas as $compra) {
                $numeroCompra = 'C' . str_pad($numero, 4, '0', STR_PAD_LEFT);
                $compra->update(['numero_compra' => $numeroCompra]);
                $numero++;
                $this->line("Compra ID {$compra->id}: {$numeroCompra}");
            }
        }

        // Compras desde órdenes de compra
        $comprasDesdeOrdenes = Compra::whereNotNull('orden_compra_id')
            ->when(!$force, fn($q) => $q->whereNull('numero_compra'))
            ->orderBy('created_at')
            ->get();

        if ($comprasDesdeOrdenes->count() > 0) {
            $this->info("Asignando números a {$comprasDesdeOrdenes->count()} compras desde órdenes...");

            $numero = 1;
            foreach ($comprasDesdeOrdenes as $compra) {
                $numeroCompra = 'OCC-' . str_pad($numero, 4, '0', STR_PAD_LEFT);
                $compra->update(['numero_compra' => $numeroCompra]);
                $numero++;
                $this->line("Compra ID {$compra->id} (desde orden {$compra->orden_compra_id}): {$numeroCompra}");
            }
        }

        $totalAsignadas = $comprasDirectas->count() + $comprasDesdeOrdenes->count();

        if ($totalAsignadas > 0) {
            $this->info("✅ Proceso completado. Se asignaron números a {$totalAsignadas} compras.");
        } else {
            $this->info("ℹ️  No hay compras sin número asignado.");
        }

        return Command::SUCCESS;
    }
}

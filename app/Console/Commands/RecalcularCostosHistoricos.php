<?php

namespace App\Console\Commands;

use App\Models\Venta;
use Illuminate\Console\Command;

class RecalcularCostosHistoricos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:recalcular-costos-historicos {--venta_id= : ID específico de venta para recalcular}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalcula los costos históricos de las ventas existentes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ventaId = $this->option('venta_id');

        if ($ventaId) {
            // Recalcular una venta específica
            $venta = Venta::with(['productos', 'items.ventable'])->find($ventaId);

            if (!$venta) {
                $this->error("Venta con ID {$ventaId} no encontrada");
                return 1;
            }

            $this->info("Recalculando costos históricos para la venta {$venta->numero_venta}...");

            try {
                $costoAnterior = $venta->calcularCostoTotal();
                $venta->recalcularCostosHistoricos();
                $costoNuevo = $venta->calcularCostoTotal();

                $this->info("Venta {$venta->numero_venta}:");
                $this->info("  Costo anterior: $" . number_format($costoAnterior, 2));
                $this->info("  Costo nuevo: $" . number_format($costoNuevo, 2));
                $this->info("  Diferencia: $" . number_format($costoNuevo - $costoAnterior, 2));

                $this->newLine();
                $this->info("✅ Costos históricos recalculados exitosamente para la venta {$venta->numero_venta}");

            } catch (\Exception $e) {
                $this->error("Error al recalcular costos para la venta {$ventaId}: " . $e->getMessage());
                return 1;
            }

        } else {
            // Recalcular todas las ventas
            $this->info("Recalculando costos históricos para todas las ventas...");
            $this->newLine();

            $ventas = Venta::with(['productos', 'items.ventable'])->get();
            $totalVentas = $ventas->count();
            $procesadas = 0;
            $errores = 0;
            $cambios = 0;

            $bar = $this->output->createProgressBar($totalVentas);
            $bar->start();

            foreach ($ventas as $venta) {
                try {
                    $costoAnterior = $venta->calcularCostoTotal();
                    $venta->recalcularCostosHistoricos();
                    $costoNuevo = $venta->calcularCostoTotal();

                    if (abs($costoNuevo - $costoAnterior) > 0.01) { // Si hay diferencia significativa
                        $cambios++;
                        $this->newLine();
                        $this->line("  <fg=yellow>Venta {$venta->numero_venta}: $" . number_format($costoAnterior, 2) . " → $" . number_format($costoNuevo, 2) . "</>");
                    }

                    $procesadas++;

                } catch (\Exception $e) {
                    $errores++;
                    $this->newLine();
                    $this->error("  Error en venta {$venta->numero_venta}: " . $e->getMessage());
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->newLine();

            $this->info("📊 Resumen del proceso:");
            $this->info("  Total de ventas: {$totalVentas}");
            $this->info("  Procesadas: {$procesadas}");
            $this->info("  Con cambios: {$cambios}");
            $this->info("  Errores: {$errores}");

            if ($errores === 0) {
                $this->info("✅ Todos los costos históricos se recalcularon exitosamente");
            } else {
                $this->warn("⚠️  Se completó el proceso pero hubo {$errores} errores");
            }
        }

        return 0;
    }
}

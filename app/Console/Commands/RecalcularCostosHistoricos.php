<?php

namespace App\Console\Commands;

use App\Models\Venta;
use Illuminate\Console\Command;

use App\Console\Traits\EnforcesMaintenanceMode;

class RecalcularCostosHistoricos extends Command
{
    use EnforcesMaintenanceMode;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ventas:recalcular-costos {--venta_id= : El ID de la venta específica a recalcular}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalcula los costos históricos de las ventas basados en las entradas de almacén';

    public function handle()
    {
        // 1. Check Maintenance Mode
        if (!$this->checkMaintenanceMode()) {
            return 1;
        }

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

            $totalVentas = Venta::count();
            $procesadas = 0;
            $errores = 0;
            $cambios = 0;

            $bar = $this->output->createProgressBar($totalVentas);
            $bar->start();

            // Use chunkById to avoid memory exhaustion
            Venta::with(['productos', 'items.ventable'])->chunkById(100, function ($ventas) use ($bar, &$procesadas, &$errores, &$cambios) {
                foreach ($ventas as $venta) {
                    try {
                        $costoAnterior = $venta->calcularCostoTotal();
                        $venta->recalcularCostosHistoricos();
                        $costoNuevo = $venta->calcularCostoTotal();

                        if (abs($costoNuevo - $costoAnterior) > 0.01) { // Si hay diferencia significativa
                            $cambios++;
                            // Clear progress bar line to print log
                            $this->output->write("\x0D");
                            $this->output->write("\x1B[2K");
                            $this->line("  Venta {$venta->numero_venta}: $" . number_format($costoAnterior, 2) . " → $" . number_format($costoNuevo, 2));
                            $bar->display(); // Redraw bar
                        }

                        $procesadas++;

                    } catch (\Exception $e) {
                        $errores++;
                        $this->output->write("\x0D");
                        $this->output->write("\x1B[2K");
                        $this->error("  Error en venta {$venta->numero_venta}: " . $e->getMessage());
                        $bar->display();
                    }

                    $bar->advance();
                }
            });

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

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\ProductoSerie;
use App\Models\Venta;

class RevertirSeriesVendidas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'series:revertir-vendidas {--venta_id= : ID específico de venta} {--dry-run : Solo mostrar qué se haría sin ejecutar cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revertir series de productos vendidos a estado en_stock para ventas canceladas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ventaId = $this->option('venta_id');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 MODO DRY-RUN: Solo se mostrarán los cambios sin ejecutarlos');
        }

        if ($ventaId) {
            // Revertir series para una venta específica
            $this->revertirSeriesVentaEspecifica($ventaId, $dryRun);
        } else {
            // Revertir series para todas las ventas canceladas
            $this->revertirSeriesTodasVentasCanceladas($dryRun);
        }

        return Command::SUCCESS;
    }

    private function revertirSeriesVentaEspecifica($ventaId, $dryRun)
    {
        $venta = Venta::with('items.ventable')->find($ventaId);

        if (!$venta) {
            $this->error("Venta con ID {$ventaId} no encontrada");
            return;
        }

        if ($venta->estado->value !== 'cancelada') {
            $this->error("La venta {$ventaId} no está cancelada (estado actual: {$venta->estado->value})");
            return;
        }

        $this->info("Procesando venta cancelada: {$venta->numero_venta} (ID: {$ventaId})");

        $totalSeriesRevertidas = 0;

        foreach ($venta->items as $item) {
            if ($item->ventable_type === 'App\\Models\\Producto' && ($item->ventable->requiere_serie ?? false)) {
                $seriesRevertidas = $this->revertirSeriesItem($item, $venta, $dryRun);
                $totalSeriesRevertidas += $seriesRevertidas;
            }
        }

        $this->info("Total de series revertidas para la venta {$venta->numero_venta}: {$totalSeriesRevertidas}");
    }

    private function revertirSeriesTodasVentasCanceladas($dryRun)
    {
        $ventasCanceladas = Venta::where('estado', 'cancelada')
            ->with('items.ventable')
            ->get();

        if ($ventasCanceladas->isEmpty()) {
            $this->info('No se encontraron ventas canceladas');
            return;
        }

        $this->info("Encontradas {$ventasCanceladas->count()} ventas canceladas");

        $totalSeriesRevertidas = 0;
        $ventasProcesadas = 0;

        foreach ($ventasCanceladas as $venta) {
            $this->info("Procesando venta: {$venta->numero_venta} (ID: {$venta->id})");

            $seriesVenta = 0;
            foreach ($venta->items as $item) {
                if ($item->ventable_type === 'App\\Models\\Producto' && ($item->ventable->requiere_serie ?? false)) {
                    $seriesRevertidas = $this->revertirSeriesItem($item, $venta, $dryRun);
                    $seriesVenta += $seriesRevertidas;
                }
            }

            if ($seriesVenta > 0) {
                $this->info("  Series revertidas para esta venta: {$seriesVenta}");
                $totalSeriesRevertidas += $seriesVenta;
                $ventasProcesadas++;
            }
        }

        $this->info("Resumen:");
        $this->info("- Ventas procesadas: {$ventasProcesadas}");
        $this->info("- Total de series revertidas: {$totalSeriesRevertidas}");
    }

    private function revertirSeriesItem($item, $venta, $dryRun)
    {
        // Obtener las series asociadas a este item de venta
        $seriesVendidas = DB::table('venta_item_series')
            ->where('venta_item_id', $item->id)
            ->pluck('producto_serie_id');

        if ($seriesVendidas->isEmpty()) {
            return 0;
        }

        // Verificar cuáles series están actualmente como 'vendido'
        $seriesAVerificar = ProductoSerie::whereIn('id', $seriesVendidas)
            ->where('estado', 'vendido')
            ->get();

        if ($seriesAVerificar->isEmpty()) {
            return 0;
        }

        $this->line("  Revirtiendo {$seriesAVerificar->count()} series para producto: {$item->ventable->nombre} (Item ID: {$item->id})");

        if (!$dryRun) {
            // Revertir las series a 'en_stock'
            ProductoSerie::whereIn('id', $seriesAVerificar->pluck('id'))
                ->update(['estado' => 'en_stock']);

            $this->line("    ✅ Series revertidas exitosamente");
        } else {
            $this->line("    🔍 [DRY-RUN] Se revertirían {$seriesAVerificar->count()} series");
        }

        return $seriesAVerificar->count();
    }
}

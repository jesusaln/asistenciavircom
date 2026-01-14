<?php

namespace App\Console\Commands;

use App\Models\Compra;
use App\Models\CompraItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateCompraTotals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:recalculate-compra-totals {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate subtotal, IVA and other totals for existing purchases';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $compras = Compra::with('productos')->get();

        $this->info("Found {$compras->count()} purchases to process");

        $bar = $this->output->createProgressBar($compras->count());
        $bar->start();

        $updated = 0;

        foreach ($compras as $compra) {
            // Calcular totales desde los items
            $subtotal = 0;
            $descuentoItems = 0;

            foreach ($compra->productos as $item) {
                $cantidad = $item->pivot->cantidad ?? 0;
                $precio = $item->pivot->precio ?? 0;
                $descuento = $item->pivot->descuento ?? 0;

                $subtotalProducto = $cantidad * $precio;
                $descuentoMonto = $subtotalProducto * ($descuento / 100);

                $subtotal += $subtotalProducto;
                $descuentoItems += $descuentoMonto;
            }

            // Aplicar descuento general (si existe)
            $descuentoGeneral = $compra->descuento_general ?? 0;
            $subtotalDespuesDescuentoGeneral = $subtotal - $descuentoItems - $descuentoGeneral;

            // Calcular IVA (16%)
            $iva = $subtotalDespuesDescuentoGeneral * 0.16;

            // Total final
            $total = $subtotalDespuesDescuentoGeneral + $iva;

            // Verificar si necesita actualización
            $needsUpdate = (
                $compra->subtotal != $subtotal ||
                $compra->descuento_items != $descuentoItems ||
                $compra->iva != $iva ||
                $compra->total != $total
            );

            if ($needsUpdate) {
                if ($dryRun) {
                    $this->line("\nCompra #{$compra->id}:");
                    $this->line("  Subtotal: {$compra->subtotal} -> {$subtotal}");
                    $this->line("  IVA: {$compra->iva} -> {$iva}");
                    $this->line("  Total: {$compra->total} -> {$total}");
                } else {
                    $compra->update([
                        'subtotal' => $subtotal,
                        'descuento_items' => $descuentoItems,
                        'iva' => $iva,
                        'total' => $total,
                    ]);
                    $updated++;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($dryRun) {
            $this->info('Dry run completed. Use without --dry-run to apply changes.');
        } else {
            $this->info("Updated {$updated} purchases successfully.");
        }
    }
}

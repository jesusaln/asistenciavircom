<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MeliService;
use App\Models\Producto;
use Illuminate\Support\Facades\Log;

class MeliFetchPrices extends Command
{
    protected $signature = 'meli:fetch-prices
                            {--chunk=50 : Productos por lote}
                            {--dry-run : Solo mostrar sin guardar}';

    protected $description = 'Obtener precios sugeridos de ML para productos de la tienda online';

    public function handle(MeliService $meli)
    {
        if (!$meli->isConfigured()) {
            $this->error('MercadoLibre no está configurado.');
            return;
        }

        $this->info('Obteniendo precios sugeridos de MercadoLibre...');
        $dryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');
        $updated = 0;
        $skipped = 0;
        $protected = 0;

        $productos = Producto::where('origen', 'CVA')
            ->where('estado', 'activo')
            ->where('catalogo_web', true)
            ->where('precio_compra', '>', 0)
            ->whereRaw('(COALESCE(stock, 0) + COALESCE(stock_cedis, 0)) >= 3')
            ->get();

        $bar = $this->output->createProgressBar($productos->count());
        $bar->start();

        $i = 0;
        foreach ($productos as $producto) {
            try {
                if ($producto->precio_venta <= $producto->precio_compra || $producto->precio_venta <= 0) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                $mlSuggestedPrice = $meli->getSuggestedPriceFromML($producto->nombre);

                $costoCva = $producto->precio_compra * 1.16;
                $precioMinimoSeguro = $costoCva * 1.30;

                if ($mlSuggestedPrice && $mlSuggestedPrice > $precioMinimoSeguro) {
                    if (!$dryRun) {
                        $producto->update(['precio_tienda_online' => $mlSuggestedPrice]);
                    }
                    $this->line("  {$producto->codigo}: ML sugiere \${$mlSuggestedPrice} → OK");
                    $updated++;
                } elseif ($mlSuggestedPrice && $mlSuggestedPrice > 0) {
                    $this->line("  {$producto->codigo}: ML sugiere \${$mlSuggestedPrice} (muy bajo, mínimo \${$precioMinimoSeguro}) → protegido");
                    $protected++;
                } else {
                    $skipped++;
                }

                $i++;
                if ($i % $chunkSize === 0) {
                    $this->line("  --- Pausa de 2s tras {$chunkSize} productos ---");
                    usleep(2000000);
                }
            } catch (\Exception $e) {
                Log::error('MeliFetchPrices error', ['codigo' => $producto->codigo, 'msg' => $e->getMessage()]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Actualizados: {$updated}, Protegidos: {$protected}, Saltados: {$skipped}");
    }
}

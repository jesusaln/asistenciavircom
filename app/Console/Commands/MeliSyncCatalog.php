<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MeliService;
use App\Models\Producto;
use App\Models\MeliCategoryMapping;
use App\Models\MercadoLibreListing;
use Illuminate\Support\Facades\Log;

class MeliSyncCatalog extends Command
{
    protected $signature = 'meli:sync-catalog
                            {--dry-run : Solo mostrar qué se publicaría sin hacer cambios}';

    protected $description = 'Publicar productos CVA en MercadoLibre';

    public function handle(MeliService $meli)
    {
        if (!$meli->isConfigured()) {
            $this->error('MercadoLibre no está configurado.');
            return;
        }

        $this->info('Sincronizando catálogo con MercadoLibre...');
        $dryRun = $this->option('dry-run');
        $published = 0;
        $skipped = 0;

        $productos = Producto::where('origen', 'CVA')
            ->where('estado', 'activo')
            ->where('catalogo_web', true)
            ->where('precio_compra', '>', 0)
            ->get();

        $bar = $this->output->createProgressBar($productos->count());
        $bar->start();

        foreach ($productos as $producto) {
            try {
                $stockTotal = ($producto->stock ?? 0) + ($producto->stock_cedis ?? 0);

                if ($stockTotal < 3) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                // ⚠️ BLOQUEO DE MARGEN: No permitir vender si no hay ganancia
                if ($producto->precio_venta <= $producto->precio_compra || $producto->precio_venta <= 0) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                $listing = MercadoLibreListing::where('producto_id', $producto->id)->first();

                if ($listing) {
                    if ($listing->status === 'active') {
                        $qty = min($stockTotal, 10);
                        if ($qty !== $listing->stock_published) {
                            if (!$dryRun) {
                                $meli->updateItem($listing->listing_id, ['available_quantity' => $qty]);
                                $listing->update(['stock_published' => $qty, 'last_sync_at' => now()]);
                            }
                            $this->line("  Actualizado {$producto->codigo}: stock {$qty}");
                        }
                    }
                } else {
                    if ($dryRun) {
                        $this->line("  Publicaría: {$producto->codigo} - {$producto->nombre}");
                    } else {
                        // Obtener precio sugerido de ML basado en competencia
                        $mlSuggestedPrice = $meli->getSuggestedPriceFromML($producto->nombre);

                        // Actualizar precio_tienda_online con el precio sugerido de ML
                        if ($mlSuggestedPrice && $mlSuggestedPrice > 0) {
                            $producto->update(['precio_tienda_online' => $mlSuggestedPrice]);
                            $this->line("    ML sugiere: \${$mlSuggestedPrice} → precio_tienda_online actualizado");
                            $priceToUse = $mlSuggestedPrice;
                        } else {
                            // Si no hay sugerencia de ML, usar el precio_venta normal
                            $priceToUse = $producto->precio_venta;
                        }

                        $catNombre = $producto->categoria?->nombre;
                        $mapping = MeliCategoryMapping::where('cva_grupo', $catNombre)->first();
                        $categoryId = $mapping?->meli_category_id ?? 'MLM0000';

                        $itemData = [
                            'title' => mb_substr($producto->nombre, 0, 60),
                            'category_id' => $categoryId,
                            'price' => $priceToUse,
                            'currency_id' => 'MXN',
                            'available_quantity' => min($stockTotal, 10),
                            'buying_mode' => 'buy_it_now',
                            'listing_type_id' => 'gold_special',
                            'condition' => 'new',
                            'pictures' => $producto->imagen ? [['source' => $producto->imagen]] : [],
                            'producto_id' => $producto->id,
                        ];

                        $result = $meli->createItem($itemData);
                        if (isset($result['id'])) {
                            $published++;
                        } else {
                            Log::warning('Meli create failed', ['codigo' => $producto->codigo, 'error' => $result['error'] ?? 'unknown']);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Meli sync error', ['codigo' => $producto->codigo, 'msg' => $e->getMessage()]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Publicados: {$published}, Saltados (stock<3): {$skipped}");
    }
}

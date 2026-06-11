<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MeliService;
use App\Models\MercadoLibreListing;

class MeliSyncStock extends Command
{
    protected $signature = 'meli:sync-stock';
    protected $description = 'Actualizar stock y precios en MercadoLibre';

    public function handle(MeliService $meli)
    {
        if (!$meli->isConfigured()) {
            $this->error('MercadoLibre no está configurado.');
            return;
        }

        $listings = MercadoLibreListing::where('status', 'active')->get();
        $updated = 0;
        $paused = 0;

        foreach ($listings as $listing) {
            $producto = $listing->producto;
            if (!$producto) continue;

            $stockTotal = ($producto->stock ?? 0) + ($producto->stock_cedis ?? 0);

            // ⚠️ BLOQUEO DE MARGEN: Si no hay ganancia, pausar listing
            if ($producto->precio_venta <= $producto->precio_compra || $producto->precio_venta <= 0) {
                $meli->updateItem($listing->listing_id, ['available_quantity' => 0]);
                $listing->update(['status' => 'paused', 'stock_published' => 0, 'last_sync_at' => now()]);
                $paused++;
                continue;
            }

            if ($stockTotal < 3) {
                $meli->updateItem($listing->listing_id, ['available_quantity' => 0]);
                $listing->update(['status' => 'paused', 'stock_published' => 0, 'last_sync_at' => now()]);
                $paused++;
                continue;
            }

            $qty = min($stockTotal, 10);
            $data = ['available_quantity' => $qty];

            if ($listing->price != $producto->precio_venta) {
                $data['price'] = $producto->precio_venta;
            }

            if ($qty !== $listing->stock_published || isset($data['price'])) {
                $meli->updateItem($listing->listing_id, $data);
                $listing->update([
                    'stock_published' => $qty,
                    'price' => $producto->precio_venta,
                    'last_sync_at' => now(),
                ]);
                $updated++;
            }
        }

        $this->info("Actualizados: {$updated}, Pausados (stock<3): {$paused}");
    }
}

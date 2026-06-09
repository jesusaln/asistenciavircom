<?php

namespace App\Services\Inventory;

use App\Models\Producto;
use App\Models\Almacen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryManager
{
    /**
     * Decrement stock for a product in a specific warehouse.
     * Handles kits by decrementing each component.
     */
    public function decrementStock(Producto $producto, int $almacenId, float $cantidad)
    {
        DB::transaction(function () use ($producto, $almacenId, $cantidad) {
            if ($producto->esKit()) {
                $this->processKit($producto, $almacenId, $cantidad, 'decrement');
            } else {
                $this->updateSingleProductStock($producto, $almacenId, $cantidad, 'decrement');
            }
        });
    }

    /**
     * Increment stock for a product in a specific warehouse.
     */
    public function incrementStock(Producto $producto, int $almacenId, float $cantidad)
    {
        DB::transaction(function () use ($producto, $almacenId, $cantidad) {
            if ($producto->esKit()) {
                $this->processKit($producto, $almacenId, $cantidad, 'increment');
            } else {
                $this->updateSingleProductStock($producto, $almacenId, $cantidad, 'increment');
            }
        });
    }

    protected function processKit(Producto $kit, int $almacenId, float $kitCantidad, $action)
    {
        foreach ($kit->kitItems as $item) {
            if (!$item->item || !$item->esProducto()) {
                continue;
            }
            $totalComponentCantidad = $item->cantidad * $kitCantidad;
            $this->updateSingleProductStock($item->item, $almacenId, $totalComponentCantidad, $action);
        }
    }

    protected function updateSingleProductStock(?Producto $producto, int $almacenId, float $cantidad, $action)
    {
        // Skip if product is null
        if (!$producto) {
            return;
        }

        // Skip services or non-product types
        if ($producto->tipo_producto === 'servicio') {
            return;
        }

        $inventario = \App\Models\Inventario::firstOrCreate(
            ['producto_id' => $producto->id, 'almacen_id' => $almacenId],
            ['cantidad' => 0, 'stock_minimo' => 0]
        );

        if ($action === 'decrement') {
            $inventario->decrement('cantidad', $cantidad);
            // Atomic update for product total stock
            $producto->decrement('stock', $cantidad);
            Log::info("Stock decremented", ['producto' => $producto->id, 'almacen' => $almacenId, 'cantidad' => $cantidad]);
        } else {
            $inventario->increment('cantidad', $cantidad);
            // Atomic update for product total stock
            $producto->increment('stock', $cantidad);
            Log::info("Stock incremented", ['producto' => $producto->id, 'almacen' => $almacenId, 'cantidad' => $cantidad]);
        }

        // Removed potential race condition from re-summing:
        // $producto->update(['stock' => \App\Models\Inventario::where('producto_id', $producto->id)->sum('cantidad')]);
    }
}

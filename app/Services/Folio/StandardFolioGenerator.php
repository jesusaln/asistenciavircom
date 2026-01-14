<?php

namespace App\Services\Folio;

use App\Contracts\FolioGeneratorInterface;
use App\Models\Venta;
use App\Models\Almacen;
use Illuminate\Support\Facades\Log;

class StandardFolioGenerator implements FolioGeneratorInterface
{
    public function generarProximoFolio(?int $almacenId = null): string
    {
        // Note: This is a fallback implementation. For high concurrency, 
        // specific database sequences or a separate counters table is recommended.
        
        if (config('ventas.numeracion_por_almacen', false) && $almacenId) {
            $almacen = Almacen::find($almacenId);
            $prefijo = 'A' . $almacen->id . '-V';
            
            // Get last sale for this warehouse
            $ultimaVenta = Venta::where('numero_venta', 'like', $prefijo . '%')
                ->orderBy('id', 'desc')
                ->lockForUpdate() // Try to lock to prevent race conditions (best effort)
                ->first();
                
            $numero = 1;
            if ($ultimaVenta && preg_match('/' . preg_quote($prefijo, '/') . '(\d+)/', $ultimaVenta->numero_venta, $matches)) {
                $numero = (int) $matches[1] + 1;
            }
            
            Log::info('Generated warehouse-specific sale number (Standard)', [
                'almacen_id' => $almacenId,
                'prefijo' => $prefijo,
                'numero' => $numero,
            ]);
            
            return $prefijo . str_pad($numero, 4, '0', STR_PAD_LEFT);
        }
        
        // Global numbering
        $ultimaVenta = Venta::where('numero_venta', 'like', 'V%')
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->first();
            
        $numero = 1;
        if ($ultimaVenta && preg_match('/V(\d+)/', $ultimaVenta->numero_venta, $matches)) {
            $numero = (int) $matches[1] + 1;
        }
        
        $numeroVenta = 'V' . str_pad($numero, 4, '0', STR_PAD_LEFT);
        
        Log::info('Generated sale number (Standard)', [
            'numero' => $numero,
            'numero_venta' => $numeroVenta,
        ]);
        
        return $numeroVenta;
    }
}

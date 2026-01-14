<?php

namespace App\Services\Folio;

use App\Contracts\FolioGeneratorInterface;
use App\Models\Almacen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PostgresFolioGenerator implements FolioGeneratorInterface
{
    public function generarProximoFolio(?int $almacenId = null): string
    {
        if (config('ventas.numeracion_por_almacen', false) && $almacenId) {
            // Warehouse-specific numbering
            $almacen = Almacen::find($almacenId);
            $prefijo = 'A' . $almacen->id . '-V';
            
            // Use sequence for atomic increment
            $numero = DB::selectOne("SELECT nextval('venta_almacen_numero_seq') as num")->num;
            
            Log::info('Generated warehouse-specific sale number (PGSQL)', [
                'almacen_id' => $almacenId,
                'prefijo' => $prefijo,
                'numero' => $numero,
                'numero_venta' => $prefijo . str_pad($numero, 4, '0', STR_PAD_LEFT),
            ]);
            
            return $prefijo . str_pad($numero, 4, '0', STR_PAD_LEFT);
        }
        
        // Global numbering using atomic sequence
        $numero = DB::selectOne("SELECT nextval('venta_numero_seq') as num")->num;
        
        $numeroVenta = 'V' . str_pad($numero, 4, '0', STR_PAD_LEFT);
        
        Log::info('Generated sale number (PGSQL)', [
            'numero' => $numero,
            'numero_venta' => $numeroVenta,
        ]);
        
        return $numeroVenta;
    }
}

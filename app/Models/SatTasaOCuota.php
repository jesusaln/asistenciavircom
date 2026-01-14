<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatTasaOCuota extends Model
{
    protected $table = 'sat_tasas_cuotas';

    protected $fillable = [
        'rango_o_fijo',
        'valor_minimo',
        'valor_maximo',
        'impuesto',
        'factor',
        'traslado',
        'retencion',
    ];

    /**
     * Obtener tasas para IVA traslado
     */
    public static function getIvaTrasladoOptions(): array
    {
        return self::where('impuesto', '002')
            ->where('traslado', true)
            ->where('factor', 'Tasa')
            ->get()
            ->mapWithKeys(fn($item) => [
                (string)$item->valor_maximo => ($item->valor_maximo * 100) . '%'
            ])
            ->toArray();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatImpuesto extends Model
{
    protected $table = 'sat_impuestos';
    protected $primaryKey = 'clave';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['clave', 'descripcion', 'retencion', 'traslado', 'local_o_federal'];

    public static function getOptions(): array
    {
        return self::all()->pluck('descripcion', 'clave')->toArray();
    }
}

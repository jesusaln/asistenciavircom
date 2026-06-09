<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatTipoComprobante extends Model
{
    protected $table = 'sat_tipos_comprobante';
    protected $primaryKey = 'clave';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['clave', 'descripcion'];

    public static function getOptions(): array
    {
        return self::all()->pluck('descripcion', 'clave')->toArray();
    }
}

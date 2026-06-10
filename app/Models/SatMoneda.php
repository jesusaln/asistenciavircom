<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatMoneda extends Model
{
    protected $table = 'sat_monedas';
    protected $primaryKey = 'clave';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['clave', 'descripcion', 'decimales', 'variacion'];

    public static function getOptions(): array
    {
        return self::all()->pluck('descripcion', 'clave')->toArray();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatMotivoCancelacion extends Model
{
    protected $table = 'sat_motivos_cancelacion';
    protected $primaryKey = 'clave';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['clave', 'descripcion'];

    public static function getOptions(): array
    {
        return self::all()->pluck('descripcion', 'clave')->toArray();
    }
}

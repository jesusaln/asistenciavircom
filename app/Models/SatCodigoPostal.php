<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatCodigoPostal extends Model
{
    protected $table = 'sat_codigos_postales';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'clave_estado',
        'municipio',
        'localidad',
        'estimulo_frontera',
        'huso_horario_verano',
        'huso_horario_invierno',
    ];

    protected $casts = [
        'estimulo_frontera' => 'boolean',
    ];

    /**
     * Relación con el estado (si existe tabla de estados SAT)
     */
    public function estado()
    {
        return $this->belongsTo(SatEstado::class, 'clave_estado', 'clave');
    }
}

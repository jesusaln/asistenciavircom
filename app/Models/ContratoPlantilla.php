<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContratoPlantilla extends Model
{
    protected $fillable = [
        'nombre',
        'tipo',
        'vigencia_meses',
        'contenido',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}

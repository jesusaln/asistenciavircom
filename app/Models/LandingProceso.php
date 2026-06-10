<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToEmpresa;

class LandingProceso extends Model
{
    use BelongsToEmpresa;
    protected $fillable = [
        'empresa_id',
        'titulo',
        'descripcion',
        'icono',
        'tipo',
        'orden',
        'activo',
    ];

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden', 'asc');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToEmpresa;

class LandingFaq extends Model
{
    use BelongsToEmpresa;

    protected $table = 'landing_faqs';

    protected $fillable = [
        'empresa_id',
        'pregunta',
        'respuesta',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden')->orderBy('id');
    }
}

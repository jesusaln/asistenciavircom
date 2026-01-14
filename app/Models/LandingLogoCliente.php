<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Models\Concerns\BelongsToEmpresa;

class LandingLogoCliente extends Model
{
    use BelongsToEmpresa;

    protected $table = 'landing_logos_clientes';

    protected $fillable = [
        'empresa_id',
        'nombre_empresa',
        'logo',
        'url',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute()
    {
        if (!$this->logo)
            return null;
        return Storage::url($this->logo);
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden')->orderBy('id');
    }
}

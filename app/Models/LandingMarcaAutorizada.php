<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Models\Concerns\BelongsToEmpresa;

class LandingMarcaAutorizada extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $table = 'landing_marcas_autorizadas';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'logo',
        'tipo',
        'texto_auxiliar',
        'url',
        'orden',
        'activo',
    ];

    protected $appends = ['logo_url'];

    /**
     * Get the logo URL.
     */
    public function getLogoUrlAttribute()
    {
        return $this->logo ? Storage::url($this->logo) : null;
    }

    /**
     * Scope for active brands.
     */
    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope for ordered brands.
     */
    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }
}

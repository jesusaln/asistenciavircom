<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

class CategoriaHerramienta extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'nombre',
        'slug',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Relaciones
    public function herramientas()
    {
        return $this->hasMany(Herramienta::class, 'categoria_id');
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeInactivas($query)
    {
        return $query->where('activo', false);
    }

    // Accessors
    public function getNombreFormattedAttribute()
    {
        return ucfirst($this->nombre);
    }

    // Métodos auxiliares
    public function estaActiva()
    {
        return $this->activo;
    }

    public function tieneHerramientas()
    {
        return $this->herramientas()->count() > 0;
    }
}

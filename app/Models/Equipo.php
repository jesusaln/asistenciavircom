<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Concerns\BelongsToEmpresa;

class Equipo extends Model
{
    use SoftDeletes, BelongsToEmpresa;

    protected $table = 'equipos';

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'marca',
        'modelo',
        'numero_serie',
        'descripcion',
        'especificaciones',
        'imagen',
        'precio_renta_mensual',
        'precio_compra',
        'fecha_adquisicion',
        'estado',
        'condicion',
        'ubicacion_fisica',
        'notas',
        'accesorios',
        'fecha_garantia',
        'proveedor',
    ];

    protected $casts = [
        'especificaciones' => 'array',
        'accesorios' => 'array',
        'fecha_adquisicion' => 'date',
        'fecha_garantia' => 'date',
    ];

    protected $appends = ['serie'];

    /**
     * Accesor para serie (mapeado a numero_serie)
     */
    public function getSerieAttribute(): ?string
    {
        return $this->numero_serie;
    }

    /**
     * Relación muchos a muchos con Rentas
     */
    public function rentas(): BelongsToMany
    {
        return $this->belongsToMany(Renta::class, 'equipo_renta', 'equipo_id', 'renta_id')
            ->withPivot('precio_mensual')
            ->withTimestamps();
    }
    /**
     * Scope para equipos activos
     */
    public function scopeActive($query)
    {
        // Asumiendo que 'activo' es un estado válido en la columna 'estado'
        // o si hay una columna 'activo', usarla.
        // Viendo el fillable, tiene 'estado'.
        return $query->where('estado', 'activo');
    }
}

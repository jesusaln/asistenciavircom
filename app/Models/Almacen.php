<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Almacen extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $table = 'almacenes';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'descripcion',
        'ubicacion',
        'direccion',
        'telefono',
        'responsable',
        'estado',
    ];

    protected $casts = [
        'estado' => 'string',
    ];

    /**
     * Relación con productos (legacy)
     */
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    /**
     * Relación con inventarios
     */
    public function inventarios(): HasMany
    {
        return $this->hasMany(Inventario::class);
    }

    /**
     * Relación con el usuario responsable
     */
    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable', 'id');
    }

    /**
     * Scope para almacenes activos
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    /**
     * Scope para almacenes inactivos
     */
    public function scopeInactivos($query)
    {
        return $query->where('estado', 'inactivo');
    }
}

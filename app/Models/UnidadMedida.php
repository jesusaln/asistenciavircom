<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnidadMedida extends Model
{
    use HasFactory, SoftDeletes, BelongsToEmpresa;

    protected $table = 'unidades_medida';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'abreviatura',
        'descripcion',
        'estado',
    ];

    protected $attributes = [
        'estado' => 'activo',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Scope para unidades activas
     */
    public function scopeActivas($query)
    {
        return $query->where('estado', 'activo');
    }

    /**
     * Obtener productos que usan esta unidad de medida
     */
    public function productos()
    {
        return $this->hasMany(Producto::class, 'unidad_medida', 'nombre');
    }

    /**
     * Verificar si la unidad puede ser eliminada
     */
    public function puedeSerEliminada()
    {
        return $this->productos()->count() === 0;
    }
}

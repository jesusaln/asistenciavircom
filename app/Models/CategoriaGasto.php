<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

class CategoriaGasto extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $table = 'categoria_gastos';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'descripcion',
        'codigo',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Gastos asociados a esta categoría
     */
    public function compras()
    {
        return $this->hasMany(Compra::class, 'categoria_gasto_id');
    }

    /**
     * Scope para categorías activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}

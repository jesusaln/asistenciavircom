<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AjusteInventario extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $table = 'ajustes_inventario';

    protected $fillable = [
        'empresa_id',
        'producto_id',
        'almacen_id',
        'user_id',
        'tipo',
        'cantidad_anterior',
        'cantidad_ajuste',
        'cantidad_nueva',
        'motivo',
        'observaciones',
    ];

    protected $casts = [
        'tipo' => 'string',
        'cantidad_anterior' => 'integer',
        'cantidad_ajuste' => 'integer',
        'cantidad_nueva' => 'integer',
    ];

    /**
     * Relación con producto
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Relación con almacén
     */
    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    /**
     * Relación con usuario que realizó el ajuste
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

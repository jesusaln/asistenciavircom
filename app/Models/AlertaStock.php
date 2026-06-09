<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertaStock extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $table = 'alertas_stock';

    protected $fillable = [
        'empresa_id',
        'producto_id',
        'almacen_id',
        'tipo',
        'stock_actual',
        'stock_minimo',
        'mensaje',
        'leida',
        'leida_at',
    ];

    protected $casts = [
        'tipo' => 'string',
        'stock_actual' => 'integer',
        'stock_minimo' => 'integer',
        'leida' => 'boolean',
        'leida_at' => 'datetime',
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
     * Scope para alertas no leídas
     */
    public function scopeNoLeidas($query)
    {
        return $query->where('leida', false);
    }

    /**
     * Scope para alertas críticas
     */
    public function scopeCriticas($query)
    {
        return $query->where('tipo', 'critico');
    }

    /**
     * Scope para alertas de agotamiento
     */
    public function scopeAgotadas($query)
    {
        return $query->where('tipo', 'agotado');
    }
}

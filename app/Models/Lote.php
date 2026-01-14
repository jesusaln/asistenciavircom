<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lote extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'producto_id',
        'almacen_id',
        'numero_lote',
        'fecha_caducidad',
        'cantidad_inicial',
        'cantidad_actual',
        'costo_unitario',
    ];

    protected $casts = [
        'fecha_caducidad' => 'date',
        'cantidad_inicial' => 'integer',
        'cantidad_actual' => 'integer',
        'costo_unitario' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<Producto, Lote>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * @return BelongsTo<Almacen, Lote>
     */
    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    /**
     * @return HasMany<InventarioMovimiento>
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(InventarioMovimiento::class, 'lote_id');
    }

    /**
     * Verifica si el lote está caducado
     */
    public function estaCaducado(): bool
    {
        return $this->fecha_caducidad && $this->fecha_caducidad->isPast();
    }

    /**
     * Verifica si el lote está próximo a caducar (30 días)
     */
    public function estaProximoCaducar(): bool
    {
        return $this->fecha_caducidad && $this->fecha_caducidad->diffInDays(now()) <= 30;
    }

    /**
     * Scope para lotes activos (con cantidad > 0)
     */
    public function scopeActivos($query)
    {
        return $query->where('cantidad_actual', '>', 0);
    }

    /**
     * Scope para lotes no caducados
     */
    public function scopeNoCaducados($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('fecha_caducidad')
              ->orWhere('fecha_caducidad', '>', now());
        });
    }
}

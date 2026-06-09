<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoManual extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $table = 'movimientos_manuales';

    protected $fillable = [
        'empresa_id',
        'producto_id',
        'almacen_id',
        'user_id',
        'tipo',
        'cantidad',
        'costo_unitario',
        'total',
        'categoria',
        'motivo',
        'observaciones',
        'referencia',
    ];

    protected $casts = [
        'tipo' => 'string',
        'cantidad' => 'integer',
        'costo_unitario' => 'decimal:2',
        'total' => 'decimal:2',
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
     * Relación con usuario que realizó el movimiento
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope para entradas
     */
    public function scopeEntradas($query)
    {
        return $query->where('tipo', 'entrada');
    }

    /**
     * Scope para salidas
     */
    public function scopeSalidas($query)
    {
        return $query->where('tipo', 'salida');
    }
}

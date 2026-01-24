<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolizaCargo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'poliza_id',
        'subtotal',
        'iva',
        'total',
        'moneda',
        'concepto',
        'tipo_ciclo',
        'fecha_emision',
        'fecha_vencimiento',
        'estado',
        'referencia_pago',
        'metodo_pago',
        'fecha_pago',
        'notas',
        'metadata',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'iva' => 'decimal:2',
        'total' => 'decimal:2',
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_pago' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Relación con la póliza.
     */
    public function poliza(): BelongsTo
    {
        return $this->belongsTo(PolizaServicio::class, 'poliza_id');
    }

    /**
     * Scope para cargos pendientes.
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope para cargos vencidos.
     */
    public function scopeVencidos($query)
    {
        return $query->where('estado', 'vencido')
            ->orWhere(function ($q) {
                $q->where('estado', 'pendiente')
                    ->where('fecha_vencimiento', '<', now());
            });
    }
}

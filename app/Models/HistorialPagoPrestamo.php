<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class HistorialPagoPrestamo extends Model implements AuditableContract
{
    use HasFactory, AuditableTrait, SoftDeletes, BelongsToEmpresa;

    protected $table = 'historial_pagos_prestamos';

    protected $fillable = [
        'empresa_id',
        'pago_prestamo_id',
        'prestamo_id',
        'monto_pagado',
        'fecha_pago',
        'fecha_registro',
        'metodo_pago',
        'referencia',
        'cuenta_bancaria_id',
        'notas',
        'confirmado',
    ];

    protected $casts = [
        'monto_pagado' => 'decimal:2',
        'fecha_pago' => 'date',
        'fecha_registro' => 'date',
        'confirmado' => 'boolean',
    ];

    protected $attributes = [
        'confirmado' => false,
    ];

    protected $auditExclude = [
        'updated_at',
    ];

    /**
     * Relaciones
     */
    public function pagoPrestamo(): BelongsTo
    {
        return $this->belongsTo(PagoPrestamo::class, 'pago_prestamo_id');
    }

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }

    public function cuentaBancaria(): BelongsTo
    {
        return $this->belongsTo(CuentaBancaria::class);
    }

    /**
     * Scopes
     */
    public function scopePorPrestamo($query, $prestamoId)
    {
        return $query->where('prestamo_id', $prestamoId);
    }

    public function scopePorPago($query, $pagoId)
    {
        return $query->where('pago_prestamo_id', $pagoId);
    }

    public function scopeConfirmados($query)
    {
        return $query->where('confirmado', true);
    }

    public function scopeOrdenCronologico($query)
    {
        return $query->orderBy('fecha_pago')->orderBy('created_at');
    }

    /**
     * Accessors y Mutators
     */
    public function getMetodoPagoTextoAttribute(): string
    {
        return match($this->metodo_pago) {
            'efectivo' => 'Efectivo',
            'transferencia' => 'Transferencia Bancaria',
            'tarjeta_debito' => 'Tarjeta de Débito',
            'tarjeta_credito' => 'Tarjeta de Crédito',
            'cheque' => 'Cheque',
            'otro' => 'Otro',
            default => 'No especificado'
        };
    }

    public function setFechaRegistroAttribute($value): void
    {
        $this->attributes['fecha_registro'] = $value ?: now()->toDateString();
    }
}

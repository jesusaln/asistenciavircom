<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PagoComision extends Model
{
    use SoftDeletes, BelongsToEmpresa;

    protected $table = 'pagos_comisiones';

    protected $fillable = [
        'empresa_id',
        'vendedor_type',
        'vendedor_id',
        'periodo_inicio',
        'periodo_fin',
        'monto_comision',
        'monto_pagado',
        'estado',
        'fecha_pago',
        'metodo_pago',
        'referencia_pago',
        'cuenta_bancaria_id',
        'detalles_ventas',
        'num_ventas',
        'total_ventas',
        'notas',
        'pagado_por',
        'created_by',
    ];

    protected $casts = [
        'periodo_inicio' => 'date',
        'periodo_fin' => 'date',
        'fecha_pago' => 'date',
        'monto_comision' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'total_ventas' => 'decimal:2',
        'detalles_ventas' => 'array',
    ];

    // Relación polimórfica con vendedor
    public function vendedor()
    {
        return $this->morphTo();
    }

    // Usuario que pagó
    public function pagadoPorUser()
    {
        return $this->belongsTo(User::class, 'pagado_por');
    }

    // Usuario que creó el registro
    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Cuenta bancaria usada para el pago
    public function cuentaBancaria()
    {
        return $this->belongsTo(CuentaBancaria::class);
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopePagados($query)
    {
        return $query->where('estado', 'pagado');
    }

    public function scopeDelPeriodo($query, $inicio, $fin)
    {
        return $query->whereBetween('periodo_inicio', [$inicio, $fin]);
    }

    public function scopeDeVendedor($query, $type, $id)
    {
        return $query->where('vendedor_type', $type)->where('vendedor_id', $id);
    }

    // Helpers
    public function getNombreVendedorAttribute()
    {
        return $this->vendedor?->name ?? 'Vendedor no disponible';
    }

    public function getMontoPendienteAttribute()
    {
        return $this->monto_comision - $this->monto_pagado;
    }

    public function getEstaCompletaAttribute()
    {
        return $this->estado === 'pagado';
    }

    // Marcar como pagado
    public function marcarComoPagado($monto = null, $metodoPago = null, $referencia = null, $cuentaBancariaId = null)
    {
        $this->update([
            'monto_pagado' => $monto ?? $this->monto_comision,
            'estado' => 'pagado',
            'fecha_pago' => now(),
            'metodo_pago' => $metodoPago,
            'referencia_pago' => $referencia,
            'cuenta_bancaria_id' => $cuentaBancariaId,
            'pagado_por' => auth()->id(),
        ]);

        return $this;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToEmpresa;
use App\Models\Concerns\Blameable;

class CuentasPorPagar extends Model
{
    use HasFactory, SoftDeletes, Blameable, BelongsToEmpresa;

    const SALDO_MINIMO_CIERRE = 0.90;

    protected $table = 'cuentas_por_pagar';

    protected $fillable = [
        'empresa_id',
        'compra_id',
        'cfdi_id',
        'proveedor_id',
        'monto_total',
        'monto_pagado',
        'monto_pendiente',
        'fecha_vencimiento',
        'estado',
        'notas',
        'pagado',
        'metodo_pago',
        'cuenta_bancaria_id',
        'fecha_pago',
        'pagado_por',
        'pagado_con_rep',
        'pue_pagado',
        'notas_pago',
    ];

    protected $casts = [
        'monto_total' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'monto_pendiente' => 'decimal:2',
        'fecha_vencimiento' => 'date',
        'fecha_pago' => 'datetime',
        'pagado' => 'boolean',
        'pagado_con_rep' => 'boolean',
        'pue_pagado' => 'boolean',
    ];

    /**
     * @return BelongsTo<Compra, CuentasPorPagar>
     */
    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class);
    }

    /**
     * Relación con CFDI (para cuentas creadas desde CFDI)
     */
    public function cfdi(): BelongsTo
    {
        return $this->belongsTo(Cfdi::class);
    }

    /**
     * Relación directa con proveedor (para cuentas desde CFDI sin compra)
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    // Blameable relationships
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /** Relación con cuenta bancaria */
    public function cuentaBancaria(): BelongsTo
    {
        return $this->belongsTo(CuentaBancaria::class);
    }

    /**
     * Verifica si la cuenta está vencida
     */
    public function estaVencida(): bool
    {
        return $this->fecha_vencimiento && \Carbon\Carbon::parse($this->fecha_vencimiento)->isPast() && $this->estado !== 'pagado';
    }

    /**
     * Calcula el monto pendiente
     * ✅ FIX: Proteger contra valores negativos
     */
    public function calcularPendiente(): float
    {
        $pendiente = (float) $this->monto_total - (float) $this->monto_pagado;
        return $pendiente <= self::SALDO_MINIMO_CIERRE ? 0 : $pendiente;
    }

    /**
     * Actualiza el estado basado en los pagos
     */
    public function actualizarEstado(): void
    {
        $pendiente = $this->calcularPendiente();

        if ($pendiente <= 0) {
            $this->estado = 'pagado';
        } elseif ($this->monto_pagado > 0) {
            $this->estado = 'parcial';
        } elseif ($this->estaVencida()) {
            $this->estado = 'vencido';
        } else {
            $this->estado = 'pendiente';
        }

        $this->monto_pendiente = (float) $pendiente;
        $this->save();
    }

    /**
     * Registra un pago parcial
     * ✅ ACTUALIZADO: Permite vincular cuenta bancaria y descontar saldo
     */
    public function registrarPago(float $monto, string $notas = null, ?int $cuentaBancariaId = null, bool $pagadoConRep = false): void
    {
        $this->monto_pagado = (float) ((float) $this->monto_pagado + $monto);

        if ($pagadoConRep) {
            $this->pagado_con_rep = true;
        }

        if ($notas) {
            $this->notas = ($this->notas ? $this->notas . "\n" : '') . "Pago: {$monto} - {$notas}";
        }

        // Registrar movimiento bancario si se especificó cuenta
        if ($cuentaBancariaId) {
            $cuentaBancaria = CuentaBancaria::find($cuentaBancariaId);
            if ($cuentaBancaria) {
                $nombre = $this->compra?->proveedor?->nombre_razon_social ?? $this->proveedor?->nombre_razon_social ?? 'Proveedor';
                $doc = $this->compra?->numero_compra ?? "ID-{$this->id}";

                $cuentaBancaria->registrarMovimiento(
                    'retiro',
                    $monto,
                    "Pago Parcial CXP #{$doc} - {$nombre}",
                    'pago'
                )->update([
                            'conciliable_type' => self::class,
                            'conciliable_id' => $this->id,
                        ]);
            }
        }

        $this->actualizarEstado();
    }

    /**
     * Marca la cuenta como pagada con información detallada
     * ✅ ACTUALIZADO: Descuenta solo lo pendiente del banco y mejora resolución de nombres
     */
    public function marcarPagado(string $metodoPago, ?int $cuentaBancariaId = null, string $notas = null, bool $puePagado = false, bool $pagadoConRep = false): void
    {
        $pendiente = $this->calcularPendiente();

        $this->update([
            'pagado' => true,
            'estado' => 'pagado',
            'metodo_pago' => $metodoPago,
            'cuenta_bancaria_id' => $cuentaBancariaId,
            'fecha_pago' => now(),
            'pagado_por' => auth()->id(),
            'notas_pago' => $notas,
            'monto_pagado' => $this->monto_total,
            'monto_pendiente' => 0,
            'pue_pagado' => $puePagado,
            'pagado_con_rep' => $pagadoConRep,
        ]);

        // Crear movimiento bancario (retiro) si hay monto pendiente y se seleccionó cuenta
        if ($cuentaBancariaId && $pendiente > 0) {
            $cuentaBancaria = CuentaBancaria::find($cuentaBancariaId);
            if ($cuentaBancaria) {
                $nombre = $this->compra?->proveedor?->nombre_razon_social ?? $this->proveedor?->nombre_razon_social ?? 'Proveedor';
                $doc = $this->compra?->numero_compra ?? "ID-{$this->id}";

                $cuentaBancaria->registrarMovimiento(
                    'retiro',
                    $pendiente,
                    "Liquidación CXP #{$doc} - {$nombre}",
                    'pago'
                )->update([
                            'conciliable_type' => self::class,
                            'conciliable_id' => $this->id,
                        ]);
            }
        }
    }

    /**
     * Relación con el usuario que pagó
     */
    public function pagadoPor()
    {
        return $this->belongsTo(User::class, 'pagado_por');
    }

    /**
     * Scope para cuentas pendientes
     */
    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['pendiente', 'parcial', 'vencido']);
    }

    /**
     * Scope para cuentas vencidas
     * ✅ FIX: Usar agrupación correcta para evitar resultados inesperados
     */
    public function scopeVencidas($query)
    {
        return $query->where(function ($q) {
            $q->where('estado', 'vencido')
                ->orWhere(function ($sub) {
                    $sub->where('fecha_vencimiento', '<', now())
                        ->whereNotIn('estado', ['pagado', 'cancelada']);
                });
        });
    }
}

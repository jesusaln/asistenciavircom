<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MovimientoBancario extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $table = 'movimientos_bancarios';

    protected $fillable = [
        'empresa_id',
        'fecha',
        'concepto',
        'referencia',
        'monto',
        'saldo',
        'tipo',
        'origen_tipo',
        'banco',
        'cuenta_bancaria',
        'cuenta_bancaria_id',
        'estado',
        'conciliable_type',
        'conciliable_id',
        'archivo_origen',
        'usuario_id',
        'conciliado_por',
        'conciliado_at',
        'notas',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
        'saldo' => 'decimal:2',
        'conciliado_at' => 'datetime',
    ];

    // ==================== RELACIONES ====================

    /**
     * Relación polimórfica con CuentasPorCobrar o CuentasPorPagar
     */
    public function conciliable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Cuenta bancaria asociada
     */
    public function cuentaBancaria(): BelongsTo
    {
        return $this->belongsTo(CuentaBancaria::class, 'cuenta_bancaria_id');
    }

    /**
     * Usuario que importó el movimiento
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Usuario que concilió el movimiento
     */
    public function conciliadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conciliado_por');
    }

    // ==================== SCOPES ====================

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeConciliados($query)
    {
        return $query->where('estado', 'conciliado');
    }

    public function scopeIgnorados($query)
    {
        return $query->where('estado', 'ignorado');
    }

    public function scopeDepositos($query)
    {
        return $query->where('tipo', 'deposito');
    }

    public function scopeRetiros($query)
    {
        return $query->where('tipo', 'retiro');
    }

    public function scopeBanco($query, string $banco)
    {
        return $query->where('banco', $banco);
    }

    // ==================== MÉTODOS ====================

    /**
     * Conciliar con una cuenta por cobrar o pagar
     */
    public function conciliar(Model $cuenta, ?int $usuarioId = null): bool
    {
        $this->update([
            'estado' => 'conciliado',
            'conciliable_type' => get_class($cuenta),
            'conciliable_id' => $cuenta->id,
            'conciliado_por' => $usuarioId ?? auth()->id(),
            'conciliado_at' => now(),
        ]);

        // Marcar la cuenta como pagada
        if ($cuenta instanceof CuentasPorCobrar) {
            $cuenta->update([
                'monto_pagado' => $cuenta->monto_total,
                'monto_pendiente' => 0,
                'estado' => 'pagado',
            ]);
        } elseif ($cuenta instanceof CuentasPorPagar) {
            $cuenta->update([
                'monto_pagado' => $cuenta->monto_total,
                'monto_pendiente' => 0,
                'estado' => 'pagado',
            ]);
        }

        // Actualizar saldo de cuenta bancaria si está vinculada
        if ($this->cuenta_bancaria_id && $this->cuentaBancaria) {
            $this->cuentaBancaria->actualizarSaldoPorMovimiento($this);
        }

        return true;
    }

    /**
     * Revertir conciliación
     */
    public function revertirConciliacion(): bool
    {
        $cuenta = $this->conciliable;

        if ($cuenta) {
            // Restaurar cuenta a pendiente
            $cuenta->update([
                'monto_pagado' => 0,
                'monto_pendiente' => $cuenta->monto_total,
                'estado' => 'pendiente',
            ]);
        }

        // Revertir saldo de cuenta bancaria si está vinculada
        if ($this->cuenta_bancaria_id && $this->cuentaBancaria) {
            $this->cuentaBancaria->revertirSaldoPorMovimiento($this);
        }

        $this->update([
            'estado' => 'pendiente',
            'conciliable_type' => null,
            'conciliable_id' => null,
            'conciliado_por' => null,
            'conciliado_at' => null,
        ]);

        return true;
    }

    /**
     * Marcar como ignorado
     */
    public function ignorar(): bool
    {
        return $this->update(['estado' => 'ignorado']);
    }

    /**
     * Verificar si es un depósito
     */
    public function esDeposito(): bool
    {
        return $this->tipo === 'deposito';
    }

    /**
     * Verificar si es un retiro
     */
    public function esRetiro(): bool
    {
        return $this->tipo === 'retiro';
    }

    /**
     * Obtener el monto en formato absoluto
     */
    public function getMontoAbsolutoAttribute(): float
    {
        return abs($this->monto);
    }
}

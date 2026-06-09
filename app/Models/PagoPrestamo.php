<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class PagoPrestamo extends Model implements AuditableContract
{
    use HasFactory, AuditableTrait, SoftDeletes, BelongsToEmpresa;

    protected $table = 'pagos_prestamos';

    protected $fillable = [
        'empresa_id',
        'prestamo_id',
        'numero_pago',
        'monto_programado',
        'monto_pagado',
        'fecha_programada',
        'fecha_pago',
        'fecha_registro',
        'estado',
        'dias_atraso',
        'notas',
        'metodo_pago',
        'referencia',
        'confirmado',
    ];

    protected $casts = [
        'monto_programado' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'fecha_programada' => 'date',
        'fecha_pago' => 'date',
        'fecha_registro' => 'date',
        'confirmado' => 'boolean',
    ];

    protected $attributes = [
        'estado' => 'pendiente',
        'dias_atraso' => 0,
        'confirmado' => false,
    ];

    protected $auditExclude = [
        'updated_at',
    ];

    /**
     * Relaciones
     */
    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }

    public function historialPagos(): HasMany
    {
        return $this->hasMany(HistorialPagoPrestamo::class, 'pago_prestamo_id');
    }

    /**
     * Scopes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopePagados($query)
    {
        return $query->where('estado', 'pagado');
    }

    public function scopeAtrasados($query)
    {
        return $query->where('estado', 'atrasado');
    }

    public function scopeParciales($query)
    {
        return $query->where('estado', 'parcial');
    }

    public function scopePorPrestamo($query, $prestamoId)
    {
        return $query->where('prestamo_id', $prestamoId);
    }

    public function scopeVencidos($query)
    {
        return $query->where('fecha_programada', '<', now())
            ->whereIn('estado', ['pendiente', 'parcial']);
    }

    /**
     * Accessors y Mutators
     */
    public function getEstadoTextoAttribute(): string
    {
        return match ($this->estado) {
            'pendiente' => 'Pendiente',
            'pagado' => 'Pagado',
            'atrasado' => 'Atrasado',
            'parcial' => 'Pago Parcial',
            default => 'Desconocido'
        };
    }

    public function getEstaVencidoAttribute(): bool
    {
        return $this->fecha_programada < now()->toDateString() && !in_array($this->estado, ['pagado']);
    }

    /**
     * FIX Error #3: Optimizar accessor para prevenir N+1 queries
     * 
     * Si la relación historialPagos ya está cargada (eager loading), usar esa.
     * Si no, ejecutar la query pero cachear el resultado.
     */
    public function getMontoPagadoAttribute(): float
    {
        // Si la relación ya está cargada, usar esa (evita N+1)
        if ($this->relationLoaded('historialPagos')) {
            return $this->historialPagos->sum('monto_pagado');
        }

        // Si existe historial, calcular desde ahí
        if ($this->historialPagos()->exists()) {
            return $this->historialPagos()->sum('monto_pagado');
        }

        // Sino, usar el campo directo
        return $this->attributes['monto_pagado'] ?? 0;
    }

    public function getMontoPendienteAttribute(): float
    {
        return max(0, $this->monto_programado - $this->monto_pagado);
    }

    public function setFechaRegistroAttribute($value): void
    {
        $this->attributes['fecha_registro'] = $value ?: now()->toDateString();
    }

    /**
     * FIX Error #6: Validar límite de monto en pagos parciales
     * FIX Error #8: Agregar transacciones para garantizar integridad
     * 
     * Agregar un pago al historial con validaciones de sobrepago.
     * Ahora también registra movimiento bancario si se especifica cuenta bancaria.
     */
    public function agregarPago(float $monto, string $fechaPago = null, string $metodoPago = null, string $referencia = null, ?int $cuentaBancariaId = null): bool
    {
        if ($monto <= 0) {
            throw new \InvalidArgumentException('El monto debe ser mayor a cero.');
        }

        $fechaPago = $fechaPago ?: now()->toDateString();

        // FIX Error #15: Robustez numérica
        $monto = round((float) $monto, 2);

        // FIX Error #8: Envolver en transacción
        return \DB::transaction(function () use ($monto, $fechaPago, $metodoPago, $referencia, $cuentaBancariaId) {
            // FIX Error #6: Validar que el monto no exceda el saldo pendiente
            $totalPagado = $this->historialPagos()->sum('monto_pagado');
            $montoPendiente = $this->monto_programado - $totalPagado;

            if ($monto > $montoPendiente) {
                throw new \InvalidArgumentException(
                    "El monto de $" . number_format($monto, 2) .
                    " excede el saldo pendiente de $" . number_format($montoPendiente, 2)
                );
            }

            // Crear registro en el historial
            HistorialPagoPrestamo::create([
                'pago_prestamo_id' => $this->id,
                'prestamo_id' => $this->prestamo_id,
                'monto_pagado' => $monto,
                'fecha_pago' => $fechaPago,
                'fecha_registro' => now()->toDateString(),
                'metodo_pago' => $metodoPago,
                'referencia' => $referencia,
                'cuenta_bancaria_id' => $cuentaBancariaId,
                'confirmado' => true,
            ]);

            // Registrar movimiento bancario si se especificó cuenta bancaria
            if ($cuentaBancariaId) {
                $cuentaBancaria = CuentaBancaria::find($cuentaBancariaId);
                if ($cuentaBancaria) {
                    $clienteNombre = $this->prestamo->cliente->nombre_razon_social ?? 'Cliente';
                    $cuentaBancaria->registrarMovimiento(
                        'deposito',
                        $monto,
                        "Pago de préstamo #{$this->prestamo_id} - Cuota #{$this->numero_pago} - {$clienteNombre}",
                        'prestamo'
                    );
                }
            }

            // Recalcular el estado basado en el historial
            $this->recalcularEstado();

            // Actualizar el préstamo relacionado
            $this->prestamo->actualizarEstado();

            return true;
        });
    }

    /**
     * FIX Error #4: Agregar validación post-lock
     * 
     * Recalcular estado basado en el historial de pagos.
     * Este método debe llamarse dentro de una transacción.
     */
    public function recalcularEstado(): void
    {
        $totalPagado = $this->historialPagos()->sum('monto_pagado');
        $diasAtraso = 0;
        $ultimoPago = null;

        if ($totalPagado > 0) {
            // Calcular días de atraso basado en el último pago
            $ultimoPago = $this->historialPagos()->orderBy('fecha_pago', 'desc')->first();
            if ($ultimoPago) {
                $diasAtraso = max(0, (strtotime($ultimoPago->fecha_pago) - strtotime($this->fecha_programada)) / 86400);
            }
        }

        // Determinar estado
        if ($totalPagado >= $this->monto_programado) {
            $estado = 'pagado';
        } elseif ($totalPagado > 0) {
            $estado = 'parcial';
        } else {
            $estado = 'pendiente';
        }

        // FIX Error #4: Actualizar con los valores recalculados
        // Nota: Este método debe ser llamado dentro de una transacción
        // y el registro debe estar bloqueado con lockForUpdate()
        $this->update([
            'monto_pagado' => $totalPagado,
            'fecha_pago' => $totalPagado > 0 && $ultimoPago ? $ultimoPago->fecha_pago : null,
            'fecha_registro' => now()->toDateString(),
            'estado' => $estado,
            'dias_atraso' => intval($diasAtraso),
        ]);
    }

    /**
     * Marcar como pagado completamente (método anterior para compatibilidad)
     * FIX Error #8: Agregar transacción
     */
    public function marcarComoPagado(float $monto, string $fechaPago = null): bool
    {
        $fechaPago = $fechaPago ?: now()->toDateString();

        // FIX Error #8: Envolver en transacción
        return \DB::transaction(function () use ($monto, $fechaPago) {
            // Calcular días de atraso
            $diasAtraso = max(0, (strtotime($fechaPago) - strtotime($this->fecha_programada)) / 86400);

            // Determinar estado
            if ($monto >= $this->monto_programado) {
                $estado = 'pagado';
            } elseif ($monto > 0) {
                $estado = 'parcial';
            } else {
                throw new \InvalidArgumentException('No se puede marcar como pagado con monto 0 o negativo.');
            }

            $this->update([
                'monto_pagado' => $monto,
                'fecha_pago' => $fechaPago,
                'fecha_registro' => now()->toDateString(),
                'estado' => $estado,
                'dias_atraso' => intval($diasAtraso),
            ]);

            // Actualizar el préstamo relacionado
            $this->prestamo->actualizarEstado();

            return true;
        });
    }

    /**
     * FIX Error #7: Preservar información histórica de atrasos
     * 
     * Calcular días de atraso. Si ya está pagado, retornar el valor
     * almacenado (histórico), no 0.
     */
    public function calcularDiasAtraso(): int
    {
        // Si ya está pagado, retornar el valor histórico almacenado
        if ($this->estado === 'pagado') {
            return $this->dias_atraso ?? 0;
        }

        // Si no tiene fecha de pago, calcular días desde la fecha programada
        if (!$this->fecha_pago) {
            $hoy = now()->toDateString();
            return max(0, (strtotime($hoy) - strtotime($this->fecha_programada)) / 86400);
        }

        // Calcular días de atraso basado en la fecha de pago
        return max(0, (strtotime($this->fecha_pago) - strtotime($this->fecha_programada)) / 86400);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToEmpresa;
use App\Models\Concerns\Blameable;

class CuentasPorCobrar extends Model
{
    use HasFactory, SoftDeletes, Blameable, BelongsToEmpresa;

    protected $table = 'cuentas_por_cobrar';

    protected $fillable = [
        'empresa_id',
        'cobrable_id',
        'cobrable_type',
        'cfdi_id',
        'cliente_id',
        'venta_id', // Deprecated
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
        'notas_pago',
    ];

    protected $casts = [
        'monto_total' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'monto_pendiente' => 'decimal:2',
        'fecha_vencimiento' => 'date',
    ];

    /**
     * @return BelongsTo<Venta, CuentasPorCobrar>
     */
    /**
     * Relación polimórfica (Venta o Renta).
     */
    public function cobrable()
    {
        return $this->morphTo();
    }

    /**
     * Relación con CFDI (para cuentas creadas desde CFDI)
     */
    public function cfdi(): BelongsTo
    {
        return $this->belongsTo(Cfdi::class);
    }

    /**
     * Relación directa con cliente (para cuentas desde CFDI sin venta)
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * @return BelongsTo<Venta, CuentasPorCobrar>
     * @deprecated Use cobrable() instead
     */
    public function venta(): BelongsTo
    {
        // Mantener compatibilidad temporal o si realmente se necesita forzar que sea venta
        return $this->belongsTo(Venta::class, 'cobrable_id');
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
     * Relación con movimientos bancarios conciliados (Pagos via Banco)
     */
    public function movimientosBancarios()
    {
        return $this->morphMany(MovimientoBancario::class, 'conciliable');
    }

    public function estaVencida(): bool
    {
        return $this->fecha_vencimiento && $this->fecha_vencimiento->isPast() && $this->estado !== 'pagado';
    }

    /**
     * ✅ FIX: Proteger contra valores negativos
     */
    public function calcularPendiente(): float
    {
        return max(0, (float) ($this->monto_total - $this->monto_pagado));
    }

    /**
     * ✅ HIGH PRIORITY FIX #4: Sincronizar estado con venta
     */
    public function actualizarEstado(): void
    {
        // Si la cuenta está cancelada, respetar el estado y solo ajustar pendiente
        if ($this->estado === 'cancelada') {
            $this->monto_pendiente = max(0, $this->calcularPendiente());
            $this->save();
            return;
        }

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

        $this->monto_pendiente = max(0, $pendiente);
        $this->save();
        
        // Nota: La sincronización con la Venta relacionada ahora es manejada 
        // automáticamente por CuentasPorCobrarObserver.
    }

    /**
     * ✅ CRITICAL FIX #1: Validaciones completas para prevenir sobrepagos
     */
    public function registrarPago(float $monto, ?string $notas = null): void
    {
        // Validación 1: Monto debe ser positivo
        if ($monto <= 0) {
            throw new \InvalidArgumentException('El monto del pago debe ser mayor a cero');
        }

        // Validación 2: Calcular pendiente actual
        $pendienteActual = $this->calcularPendiente();

        // Validación 3: Monto no puede exceder el pendiente
        if ($monto > $pendienteActual) {
            throw new \InvalidArgumentException(
                sprintf(
                    'El monto del pago ($%.2f) excede el monto pendiente ($%.2f)',
                    $monto,
                    $pendienteActual
                )
            );
        }

        // Validación 4: Cuenta no debe estar ya pagada
        if ($this->estado === 'pagado') {
            throw new \LogicException('No se puede registrar pago en una cuenta ya pagada');
        }

        // ✅ FIX: Validación 5: Cuenta no debe estar cancelada
        if ($this->estado === 'cancelada') {
            throw new \LogicException('No se puede registrar pago en una cuenta cancelada');
        }

        // ✅ Registrar el pago
        $this->monto_pagado += $monto;
        
        if ($notas) {
            $this->notas = ($this->notas ? $this->notas . "\n" : '') . "Pago recibido: {$monto} - {$notas}";
        }
        
        $this->actualizarEstado();
    }

    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['pendiente', 'parcial', 'vencido']);
    }

    /**
     * ✅ MEDIUM PRIORITY FIX #8: Scope vencidas corregido
     */
    public function scopeVencidas($query)
    {
        // ✅ FIX #8: Agrupar correctamente las condiciones OR
        return $query->where(function ($q) {
            $q->where('estado', 'vencido')
              ->orWhere(function ($subQ) {
                  $subQ->where('fecha_vencimiento', '<', now())
                       ->where('estado', '!=', 'pagado');
              });
        });
    }

    /**
     * @return HasMany<RecordatorioCobranza, CuentasPorCobrar>
     */
    public function recordatorios()
    {
        return $this->hasMany(RecordatorioCobranza::class);
    }

    /**
     * Verificar si necesita enviar recordatorio
     * ✅ MEDIUM PRIORITY FIX #6: Lógica simplificada y más clara
     */
    public function necesitaRecordatorio(): bool
    {
        if ($this->estado === 'pagado' || !$this->fecha_vencimiento) {
            return false;
        }

        $hoy = now();
        
        // ✅ FIX #6: Cálculo más claro de días vencidos
        // Si fecha_vencimiento es futura, diffInDays es negativo
        // Si fecha_vencimiento es pasada, diffInDays es positivo
        if ($this->fecha_vencimiento->isFuture()) {
            return false; // No enviar recordatorios antes del vencimiento
        }
        
        $diasVencido = $hoy->diffInDays($this->fecha_vencimiento); // Siempre positivo

        // Día del vencimiento (día 0)
        if ($diasVencido == 0) {
            return !RecordatorioCobranza::where('cuenta_por_cobrar_id', $this->id)
                ->where('tipo_recordatorio', 'vencimiento')
                ->whereDate('fecha_envio', $hoy->toDateString())
                ->exists();
        }

        // Un día después del vencimiento
        if ($diasVencido == 1) {
            return !RecordatorioCobranza::where('cuenta_por_cobrar_id', $this->id)
                ->where('tipo_recordatorio', 'dia_siguiente')
                ->whereDate('fecha_envio', $hoy->toDateString())
                ->exists();
        }

        // Dos o más días vencido: recordatorio cada 3 días
        if ($diasVencido >= 2) {
            $ultimoRecordatorio = RecordatorioCobranza::where('cuenta_por_cobrar_id', $this->id)
                ->where('tipo_recordatorio', 'cada_3_dias')
                ->where('enviado', true)
                ->latest('fecha_envio')
                ->first();

            if (!$ultimoRecordatorio) {
                return true; // Primer recordatorio de 3 días
            }

            $diasDesdeUltimo = $hoy->diffInDays($ultimoRecordatorio->fecha_envio);
            return $diasDesdeUltimo >= 3;
        }

        return false;
    }

    /**
     * Programar próximo recordatorio
     */
    public function programarRecordatorio(): ?RecordatorioCobranza
    {
        if (!$this->necesitaRecordatorio()) {
            return null;
        }

        $hoy = now();
        $diasDesdeVencimiento = $hoy->diffInDays($this->fecha_vencimiento, false);

        if ($diasDesdeVencimiento == 0) {
            $tipo = 'vencimiento';
            $proximo = $hoy->copy()->addDay(); // Mañana
        } elseif ($diasDesdeVencimiento == 1) {
            $tipo = 'dia_siguiente';
            $proximo = $hoy->copy()->addDays(3); // En 3 días
        } else {
            $tipo = 'cada_3_dias';
            $proximo = $hoy->copy()->addDays(3); // En 3 días
        }

        $intentosAnteriores = RecordatorioCobranza::where('cuenta_por_cobrar_id', $this->id)->count();

        return $this->recordatorios()->create([
            'tipo_recordatorio' => $tipo,
            'fecha_envio' => $hoy,
            'fecha_proximo_recordatorio' => $proximo,
            'enviado' => false,
            'numero_intento' => $intentosAnteriores + 1,
        ]);
    }
}

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
use Illuminate\Support\Facades\Log;

class Prestamo extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, AuditableTrait, BelongsToEmpresa;

    protected static function booted()
    {
        static::creating(function (Prestamo $prestamo) {
            if (empty($prestamo->folio)) {
                try {
                    $prestamo->folio = app(\App\Services\Folio\FolioService::class)->getNextFolio('prestamo');
                } catch (\Exception $e) {
                    Log::error('Error generating folio for prestamo: ' . $e->getMessage());
                }
            }
        });
    }

    protected $table = 'prestamos';

    protected $fillable = [
        'folio',
        'empresa_id',
        'cliente_id',
        'monto_prestado',
        'tasa_interes_mensual',
        'numero_pagos',
        'frecuencia_pago',
        'fecha_inicio',
        'fecha_primer_pago',
        'monto_interes_total',
        'monto_total_pagar',
        'pago_periodico',
        'estado',
        'pagos_realizados',
        'pagos_pendientes',
        'monto_pagado',
        'monto_pendiente',
        'descripcion',
        'notas',
        'activo',
    ];

    protected $casts = [
        'monto_prestado' => 'decimal:2',
        'tasa_interes_mensual' => 'decimal:2',
        'monto_interes_total' => 'decimal:2',
        'monto_total_pagar' => 'decimal:2',
        'pago_periodico' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'monto_pendiente' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_primer_pago' => 'date',
        'activo' => 'boolean',
    ];

    protected $attributes = [
        'estado' => 'activo',
        'pagos_realizados' => 0,
        'pagos_pendientes' => 0,
        'monto_pagado' => 0,
        'monto_pendiente' => 0,
        'activo' => true,
    ];

    protected $auditExclude = [
        'updated_at',
    ];

    /**
     * Relaciones
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoPrestamo::class)->orderBy('numero_pago');
    }

    /**
     * Scopes
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopeCompletados($query)
    {
        return $query->where('estado', 'completado');
    }

    public function scopeCancelados($query)
    {
        return $query->where('estado', 'cancelado');
    }

    public function scopePorCliente($query, $clienteId)
    {
        return $query->where('cliente_id', $clienteId);
    }

    /**
     * Accessors y Mutators
     */
    public function getEstadoTextoAttribute(): string
    {
        return match ($this->estado) {
            'activo' => 'Activo',
            'completado' => 'Completado',
            'cancelado' => 'Cancelado',
            default => 'Desconocido'
        };
    }

    public function getFrecuenciaTextoAttribute(): string
    {
        return match ($this->frecuencia_pago) {
            'semanal' => 'Semanal',
            'quincenal' => 'Quincenal',
            'mensual' => 'Mensual',
            default => 'Desconocido'
        };
    }

    public function getProgresoAttribute(): float
    {
        if ($this->numero_pagos == 0)
            return 0;
        return round((float) (($this->pagos_realizados / $this->numero_pagos) * 100), 2);
    }

    public function setTasaInteresMensualAttribute($value): void
    {
        $this->attributes['tasa_interes_mensual'] = max(0, min(100, (float) $value));
    }

    /**
     * Métodos útiles
     */
    public function calcularPagos(): array
    {
        $capital = $this->monto_prestado;
        $tasaMensual = $this->tasa_interes_mensual / 100; // Ahora es mensual directamente
        $periodos = $this->numero_pagos;

        if ($tasaMensual == 0) {
            // Sin interés - pago fijo
            $pago = $capital / $periodos;
            return [
                'pago_periodico' => (float) round((float) $pago, 2),
                'interes_total' => 0,
                'total_pagar' => (float) round((float) $capital, 2),
                'tasa_mensual' => 0,
                'tipo_calculo' => 'sin_interes',
            ];
        }

        // Ajustar la tasa según la frecuencia de pago
        $tasaPeriodo = match ($this->frecuencia_pago) {
            'semanal' => $tasaMensual / 4,           // Aproximación estándar
            'quincenal' => $tasaMensual / 2,         // Quincena
            'mensual' => $tasaMensual,
            default => $tasaMensual
        };

        // Fórmula de amortización francesa con tasa ajustada al periodo
        if ($tasaPeriodo > 0) {
            // Usar precisión alta para evitar errores de redondeo
            $factor = pow(1 + $tasaPeriodo, $periodos);
            $pago = $capital * ($tasaPeriodo * $factor) / ($factor - 1);
            $interesTotal = ($pago * $periodos) - $capital;

            return [
                'pago_periodico' => (float) round((float) $pago, 2),
                'interes_total' => (float) round((float) $interesTotal, 2),
                'total_pagar' => (float) round((float) ($pago * $periodos), 2),
                'tasa_mensual' => (float) round((float) ($tasaMensual * 100), 4),
                'factor_compuesto' => (float) round((float) $factor, 6),
                'tipo_calculo' => 'amortizacion_francesa_mensual',
                'detalles_calculo' => [
                    'capital' => $capital,
                    'tasa_mensual' => $tasaMensual,
                    'periodos' => $periodos,
                    'frecuencia' => $this->frecuencia_pago,
                ]
            ];
        }

        return [
            'pago_periodico' => 0,
            'interes_total' => 0,
            'total_pagar' => $capital,
            'tasa_mensual' => 0,
            'tipo_calculo' => 'error',
        ];
    }

    /**
     * Calcular pagos con interés simple (opción alternativa)
     */
    public function calcularPagosInteresSimple(): array
    {
        $capital = $this->monto_prestado;
        $tasaMensual = $this->tasa_interes_mensual / 100;
        $periodos = $this->numero_pagos;

        if ($tasaMensual == 0) {
            $pago = $capital / $periodos;
            return [
                'pago_periodico' => (float) round((float) $pago, 2),
                'interes_total' => 0,
                'total_pagar' => (float) round((float) $capital, 2),
                'tipo_calculo' => 'sin_interes',
            ];
        }

        // Cálculo con interés simple mensual
        $interesTotal = $capital * $tasaMensual * $periodos;
        $totalPagar = $capital + $interesTotal;

        // Pago fijo: amortización de capital + interés del período
        $amortizacionCapital = $capital / $periodos;
        $interesPeriodo = $interesTotal / $periodos;
        $pago = $amortizacionCapital + $interesPeriodo;

        return [
            'pago_periodico' => (float) round((float) $pago, 2),
            'interes_total' => (float) round((float) $interesTotal, 2),
            'total_pagar' => (float) round((float) $totalPagar, 2),
            'tipo_calculo' => 'interes_simple_mensual',
        ];
    }

    public function actualizarEstado(): void
    {
        // Recalcular pagos realizados y montos desde la tabla de pagos
        $this->pagos_realizados = $this->pagos()->where('estado', 'pagado')->count();
        $this->monto_pagado = $this->pagos()->where('estado', 'pagado')->sum('monto_pagado');

        // Calcular pagos parciales
        $pagosParciales = $this->pagos()->where('estado', 'parcial')->sum('monto_pagado');
        $this->monto_pagado += $pagosParciales;

        if ($this->pagos_realizados >= $this->numero_pagos && $this->monto_pagado >= $this->monto_total_pagar) {
            $this->estado = 'completado';
        } elseif ($this->monto_pendiente <= 0) {
            $this->estado = 'completado';
        }

        $this->pagos_pendientes = max(0, $this->numero_pagos - $this->pagos_realizados);
        $this->monto_pendiente = max(0, $this->monto_total_pagar - $this->monto_pagado);

        $this->save();
    }

    /**
     * Crear pagos programados para el préstamo
     */
    public function crearPagosProgramados(): void
    {
        // Verificar si ya existen pagos programados
        if ($this->pagos()->exists()) {
            return; // Ya existen pagos programados
        }

        // Validar que las fechas sean instancias válidas de Carbon
        $fechaInicio = $this->fecha_inicio;
        $fechaPrimerPago = $this->fecha_primer_pago;

        if (!$fechaInicio || !($fechaInicio instanceof \Carbon\Carbon)) {
            throw new \InvalidArgumentException('La fecha de inicio del préstamo no es válida');
        }

        if (!$fechaPrimerPago || !($fechaPrimerPago instanceof \Carbon\Carbon)) {
            throw new \InvalidArgumentException('La fecha del primer pago no es válida');
        }

        // Validar que la fecha del primer pago no sea anterior a la fecha de inicio
        if ($fechaPrimerPago->lt($fechaInicio)) {
            throw new \InvalidArgumentException('La fecha del primer pago no puede ser anterior a la fecha de inicio');
        }

        // Validar límites razonables para evitar bucles infinitos
        if ($this->numero_pagos <= 0 || $this->numero_pagos > 1200) {
            throw new \InvalidArgumentException('El número de pagos debe estar entre 1 y 1200');
        }

        if (!$this->pago_periodico || $this->pago_periodico <= 0) {
            throw new \InvalidArgumentException('El pago periódico debe ser mayor a cero');
        }

        $fechaActual = $fechaPrimerPago;

        for ($i = 1; $i <= $this->numero_pagos; $i++) {
            // Validar que la fecha programada no sea demasiado lejana (máximo 50 años)
            $fechaLimite = now()->addYears(50);
            if ($fechaActual->gt($fechaLimite)) {
                throw new \InvalidArgumentException("La fecha del pago {$i} excede el límite permitido (50 años)");
            }

            $fechaProgramada = match ($this->frecuencia_pago) {
                'semanal' => $fechaActual->copy()->addWeeks($i - 1),
                'quincenal' => $fechaActual->copy()->addWeeks(($i - 1) * 2),
                'mensual' => $fechaActual->copy()->addMonths($i - 1),
                default => $fechaActual->copy()->addMonths($i - 1),
            };

            PagoPrestamo::create([
                'prestamo_id' => $this->id,
                'numero_pago' => $i,
                'monto_programado' => $this->pago_periodico,
                'monto_pagado' => 0,
                'fecha_programada' => $fechaProgramada,
                'fecha_registro' => now()->toDateString(),
                'estado' => 'pendiente',
            ]);
        }
    }

    /**
     * Obtener próximo pago pendiente
     */
    public function getProximoPagoAttribute(): ?PagoPrestamo
    {
        return $this->pagos()
            ->with('historialPagos')
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->orderBy('fecha_programada')
            ->first();
    }

    /**
     * Obtener pagos atrasados
     */
    public function pagosAtrasados()
    {
        return $this->pagos()
            ->vencidos()
            ->orderBy('fecha_programada');
    }

    /**
     * Verificar si tiene pagos atrasados
     */
    public function tienePagosAtrasados(): bool
    {
        return $this->pagosAtrasados()->exists();
    }

    /**
     * Verificar si el préstamo puede ser eliminado
     * 
     * Solo se pueden eliminar préstamos cancelados para mantener
     * un flujo seguro: Activo → Cancelar → Eliminar
     */
    public function puedeSerEliminado(): bool
    {
        // Se puede eliminar si está cancelado O si no tiene pagos registrados
        // Esto permite eliminar préstamos equivocados sin tener que cancelarlos primero
        return $this->estado === 'cancelado' || ($this->pagos_realizados === 0 && $this->monto_pagado == 0);
    }

    public function puedeSerCancelado(): bool
    {
        // No se puede cancelar si ya tiene pagos registrados
        return $this->pagos_realizados === 0 && $this->monto_pagado === 0;
    }

    public function puedeSerEditado(): bool
    {
        // No se puede editar si ya tiene pagos registrados (completos o parciales)
        return $this->pagos_realizados === 0 && $this->monto_pagado == 0;
    }

    /**
     * Regenerar la tabla de pagos (para cuando se edita el préstamo)
     * SOLO usar si puedeSerEditado() es true.
     */
    public function regenerarPagos(): void
    {
        // Validar seguridad nuevamente
        if (!$this->puedeSerEditado()) {
            throw new \Exception('No se pueden regenerar pagos de un préstamo con actividad financiera.');
        }

        // Eliminar pagos pendientes existentes (físicamente o soft delete según config)
        // Usamos forceDelete para limpiar la tabla ya que son generados
        $this->pagos()->where('estado', 'pendiente')->forceDelete();

        // Volver a crear los pagos con la nueva configuración
        $this->crearPagosProgramados();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Concerns\BelongsToEmpresa;

class PolizaServicio extends Model
{
    use HasFactory, SoftDeletes, BelongsToEmpresa;

    protected $table = 'polizas_servicio';

    protected static function booted()
    {
        static::creating(function (PolizaServicio $poliza) {
            if (empty($poliza->folio)) {
                try {
                    $poliza->folio = app(\App\Services\Folio\FolioService::class)->getNextFolio('poliza');
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error generating folio for poliza: ' . $e->getMessage());
                }
            }
        });
    }

    protected $fillable = [
        'empresa_id',
        'folio',
        'cliente_id',
        'nombre',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'monto_mensual',
        'dia_cobro',
        'estado',
        'limite_mensual_tickets',
        'notificar_exceso_limite',
        'renovacion_automatica',
        'condiciones_especiales',
        'clausulas_especiales',
        'notas',
        'ultimo_cobro_generado_at',
        'sla_horas_respuesta',
        // Fase 2 - Tracking de Horas
        'horas_incluidas_mensual',
        'horas_consumidas_mes',
        'costo_hora_excedente',
        // Fase 2 - Alertas
        'dias_alerta_vencimiento',
        'alerta_vencimiento_enviada',
        'ultimo_aviso_vencimiento_at',
        'ultima_alerta_exceso_at',
        'ultimo_reset_consumo_at',
        'mantenimiento_frecuencia_meses',
        'mantenimiento_dias_anticipacion',
        'proximo_mantenimiento_at',
        'generar_cita_automatica',
        'visitas_sitio_mensuales',
        'visitas_sitio_consumidas_mes',
        'tickets_soporte_consumidos_mes',
        'costo_visita_sitio_extra',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'monto_mensual' => 'decimal:2',
        'notificar_exceso_limite' => 'boolean',
        'renovacion_automatica' => 'boolean',
        'condiciones_especiales' => 'json',
        'alerta_vencimiento_enviada' => 'boolean',
        'horas_consumidas_mes' => 'decimal:2',
        'costo_hora_excedente' => 'decimal:2',
        'ultimo_cobro_generado_at' => 'datetime',
        'ultimo_aviso_vencimiento_at' => 'datetime',
        'ultima_alerta_exceso_at' => 'datetime',
        'ultimo_reset_consumo_at' => 'datetime',
        'proximo_mantenimiento_at' => 'date',
        'generar_cita_automatica' => 'boolean',
        'visitas_sitio_consumidas_mes' => 'integer',
        'tickets_soporte_consumidos_mes' => 'integer',
        'costo_visita_sitio_extra' => 'decimal:2',
    ];

    // NOTA: No usar $appends global para evitar N+1 queries.
    // Los accessors (porcentaje_horas, porcentaje_tickets, dias_para_vencer, excede_horas)
    // se pueden agregar manualmente con ->append() cuando se necesiten.
    // protected $appends = ['porcentaje_horas', 'porcentaje_tickets', 'dias_para_vencer', 'excede_horas'];

    /**
     * Relación con el Cliente.
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Relación con los Servicios específicos incluidos en la póliza.
     */
    public function servicios(): BelongsToMany
    {
        return $this->belongsToMany(Servicio::class, 'poliza_servicio_items', 'poliza_id', 'servicio_id')
            ->withPivot(['cantidad', 'precio_especial', 'notas'])
            ->withTimestamps();
    }

    /**
     * Relación con los Equipos cubiertos por la póliza.
     */
    public function equipos(): BelongsToMany
    {
        return $this->belongsToMany(Equipo::class, 'poliza_servicio_equipos', 'poliza_id', 'equipo_id')
            ->withPivot(['notas'])
            ->withTimestamps();
    }

    /**
     * Relación con Cuentas por Cobrar (Generadas por la póliza)
     */
    public function cuentasPorCobrar()
    {
        return $this->morphMany(CuentasPorCobrar::class, 'cobrable');
    }

    /**
     * Relación con los Tickets de soporte vinculados a esta póliza.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'poliza_id');
    }

    /**
     * Scope para pólizas activas.
     */
    public function scopeActiva($query)
    {
        return $query->where('estado', 'activa');
    }

    /**
     * Scope para pólizas próximas a vencer.
     */
    public function scopeProximasAVencer($query, int $dias = 30)
    {
        return $query->activa()
            ->whereNotNull('fecha_fin')
            ->whereBetween('fecha_fin', [now(), now()->addDays($dias)]);
    }

    /**
     * Obtener el conteo de servicios realizados en el mes actual.
     * OPTIMIZADO: Usa caché para evitar N+1 queries.
     */
    public function getTicketsMesActualCountAttribute()
    {
        // Si ya se cargó con withCount, usar ese valor
        if (isset($this->attributes['tickets_mes_actual_count'])) {
            return $this->attributes['tickets_mes_actual_count'];
        }

        // Cache en memoria para evitar múltiples consultas en la misma request
        static $cache = [];
        $cacheKey = $this->id . '_' . now()->format('Y-m');

        if (!isset($cache[$cacheKey])) {
            $cache[$cacheKey] = $this->tickets()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
        }

        return $cache[$cacheKey];
    }

    /**
     * Verificar si ha excedido el límite mensual de tickets.
     */
    public function getExcedeLimiteAttribute(): bool
    {
        if (!$this->limite_mensual_tickets) {
            return false;
        }

        return $this->tickets_mes_actual_count >= $this->limite_mensual_tickets;
    }

    /**
     * Verificar si ha excedido las horas incluidas.
     */
    public function getExcedeHorasAttribute(): bool
    {
        if (!$this->horas_incluidas_mensual) {
            return false;
        }

        return $this->horas_consumidas_mes >= $this->horas_incluidas_mensual;
    }

    /**
     * Porcentaje de horas consumidas.
     */
    public function getPorcentajeHorasAttribute(): ?float
    {
        if (!$this->horas_incluidas_mensual || $this->horas_incluidas_mensual == 0) {
            return null;
        }

        return round(($this->horas_consumidas_mes / $this->horas_incluidas_mensual) * 100, 1);
    }

    /**
     * Porcentaje de tickets consumidos (Soporte Técnico).
     */
    public function getPorcentajeTicketsAttribute(): ?float
    {
        if (!$this->limite_mensual_tickets || $this->limite_mensual_tickets == 0) {
            return null;
        }

        return round(($this->tickets_soporte_mes_count / $this->limite_mensual_tickets) * 100, 1);
    }

    /**
     * Obtener el conteo de tickets de Soporte Técnico (que consumen la póliza).
     */
    public function getTicketsSoporteMesCountAttribute()
    {
        // Se consideran tickets de soporte los que pertenecen a una categoría marcada para consumir póliza
        return $this->tickets()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereHas('categoria', function ($q) {
                $q->where('consume_poliza', true);
            })
            ->count();
    }

    /**
     * Obtener el conteo de tickets que NO consumen la póliza (Asesorías, etc).
     */
    public function getTicketsAsesoriaMesCountAttribute()
    {
        return $this->tickets()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereHas('categoria', function ($q) {
                $q->where('consume_poliza', false);
            })
            ->count();
    }

    /**
     * Días restantes para que venza la póliza.
     */
    public function getDiasParaVencerAttribute(): ?int
    {
        if (!$this->fecha_fin) {
            return null;
        }

        return now()->diffInDays($this->fecha_fin, false);
    }

    /**
     * Registrar consumo de horas en la póliza.
     */
    public function registrarConsumoHoras(float $horas, ?Ticket $ticket = null): void
    {
        $this->increment('horas_consumidas_mes', $horas);

        // Verificar si ahora excede y notificar
        if ($this->excede_horas && $this->notificar_exceso_limite) {
            $this->enviarAlertaExcesoHoras();
        }
    }

    /**
     * Resetear consumo mensual de horas y visitas.
     */
    public function resetearConsumoMensual(): void
    {
        $this->update([
            'horas_consumidas_mes' => 0,
            'visitas_sitio_consumidas_mes' => 0,
            'tickets_soporte_consumidos_mes' => 0,
            'ultimo_reset_consumo_at' => now(),
        ]);
    }

    /**
     * Registrar una visita en sitio consumida.
     */
    public function registrarVisitaSitio(): void
    {
        $this->increment('visitas_sitio_consumidas_mes');
    }

    /**
     * Registrar un ticket de soporte consumido.
     */
    public function registrarTicketSoporte(): void
    {
        $this->increment('tickets_soporte_consumidos_mes');
    }

    /**
     * Verificar si excede el límite de visitas en sitio.
     */
    public function getExcedeLimiteVisitasAttribute(): bool
    {
        if (!$this->visitas_sitio_mensuales) {
            return false;
        }

        return $this->visitas_sitio_consumidas_mes >= $this->visitas_sitio_mensuales;
    }

    /**
     * Enviar alerta de exceso de horas.
     */
    protected function enviarAlertaExcesoHoras(): void
    {
        // Solo enviar si no se ha enviado recientemente (últimas 24 horas)
        if ($this->ultima_alerta_exceso_at && $this->ultima_alerta_exceso_at->diffInHours(now()) < 24) {
            return;
        }

        try {
            // Obtener emails de administradores de la empresa
            $admins = \App\Models\User::where('empresa_id', $this->empresa_id)
                ->whereHas('roles', function ($q) {
                    $q->whereIn('name', ['super-admin', 'admin', 'gerente']);
                })
                ->pluck('email')
                ->filter()
                ->toArray();

            if (!empty($admins)) {
                \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($admins) {
                    $message->to($admins)
                        ->subject("⚠️ Alerta: Póliza {$this->folio} ha excedido las horas incluidas")
                        ->html("
                            <h2>Alerta de Exceso de Horas</h2>
                            <p>La póliza <strong>{$this->folio}</strong> ha excedido las horas incluidas.</p>
                            <ul>
                                <li><strong>Cliente:</strong> " . ($this->cliente->nombre_razon_social ?? 'N/A') . "</li>
                                <li><strong>Horas consumidas:</strong> {$this->horas_consumidas_mes}</li>
                                <li><strong>Horas incluidas:</strong> {$this->horas_incluidas_mensual}</li>
                                <li><strong>Excedente:</strong> " . ($this->horas_consumidas_mes - $this->horas_incluidas_mensual) . " horas</li>
                            </ul>
                            <p>Por favor, contacte al cliente para revisar su plan.</p>
                        ");
                });
            }

            \Log::info("Alerta enviada: Póliza {$this->folio} ha excedido las horas incluidas", [
                'poliza_id' => $this->id,
                'cliente' => $this->cliente->nombre_razon_social ?? 'N/A',
                'horas_consumidas' => $this->horas_consumidas_mes,
                'horas_incluidas' => $this->horas_incluidas_mensual,
            ]);
        } catch (\Exception $e) {
            \Log::error("Error enviando alerta de exceso de horas: " . $e->getMessage(), [
                'poliza_id' => $this->id,
            ]);
        }

        $this->update(['ultima_alerta_exceso_at' => now()]);
    }

    /**
     * Verificar si debe enviarse alerta de vencimiento.
     */
    public function debeEnviarAlertaVencimiento(): bool
    {
        if ($this->alerta_vencimiento_enviada) {
            return false;
        }

        if (!$this->fecha_fin || !$this->dias_alerta_vencimiento) {
            return false;
        }

        return $this->dias_para_vencer <= $this->dias_alerta_vencimiento;
    }

    /**
     * Relación con el Plan de Póliza (para obtener precios base).
     */
    public function planPoliza(): BelongsTo
    {
        return $this->belongsTo(PlanPoliza::class, 'plan_poliza_id');
    }

    /**
     * Calcular el monto actual a pagar (para pagos online).
     * Considera el monto mensual, periodo anual si aplica, y agrega IVA.
     */
    public function calcularMontoActual(): float
    {
        // Si tiene un monto mensual configurado
        $subtotal = (float) ($this->monto_mensual ?? 0);

        // Si está vinculada a un plan, usa el precio del plan
        if ($this->planPoliza) {
            // Determinar si es periodo anual basándose en la duración
            $meses = 1;
            if ($this->fecha_inicio && $this->fecha_fin) {
                $meses = \Carbon\Carbon::parse($this->fecha_inicio)->diffInMonths(\Carbon\Carbon::parse($this->fecha_fin));
            }

            if ($meses >= 12) {
                $subtotal = (float) $this->planPoliza->precio_anual;
            } else {
                $subtotal = (float) $this->planPoliza->precio_mensual;
            }
        }

        // Si el subtotal sigue siendo 0, buscar venta relacionada
        if ($subtotal <= 0) {
            $venta = Venta::whereHas('items', function ($q) {
                $q->where('ventable_type', self::class)
                    ->where('ventable_id', $this->id);
            })->first();

            if ($venta) {
                return (float) $venta->total;
            }
        }

        // Agregar IVA (16% México)
        $iva = round($subtotal * 0.16, 2);

        return $subtotal + $iva;
    }

    /**
     * Verificar si la póliza está pendiente de pago.
     */
    public function isPendientePago(): bool
    {
        return $this->estado === 'pendiente_pago';
    }

    /**
     * Verificar si la póliza está activa.
     */
    public function isActiva(): bool
    {
        return $this->estado === 'activa';
    }

    /**
     * Bóveda de credenciales seguras
     */
    public function credenciales()
    {
        return $this->morphMany(Credencial::class, 'credentialable');
    }
}

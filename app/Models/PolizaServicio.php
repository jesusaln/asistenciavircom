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

    const ESTADO_ACTIVA = 'activa';
    const ESTADO_INACTIVA = 'inactiva';
    const ESTADO_VENCIDA = 'vencida';
    const ESTADO_CANCELADA = 'cancelada';
    const ESTADO_PENDIENTE_PAGO = 'pendiente_pago';

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

    /**
     * Cancela la póliza si no está ya cancelada.
     */
    public function cancelar(): bool
    {
        if ($this->estado === self::ESTADO_CANCELADA) {
            return true; // Ya está cancelada
        }

        $this->estado = self::ESTADO_CANCELADA;
        return $this->save();
    }

    /**
     * Activa la póliza.
     */
    public function activar(): bool
    {
        $this->estado = self::ESTADO_ACTIVA;
        return $this->save();
    }

    /**
     * Desactiva la póliza (la pone inactiva).
     */
    public function desactivar(): bool
    {
        $this->estado = self::ESTADO_INACTIVA;
        return $this->save();
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
     * Relación con las Citas de servicio vinculadas a esta póliza.
     */
    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'poliza_id');
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
                ->where('tipo_servicio', '!=', 'costo') // Excluir tickets con costo extra
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
            ->where('tipo_servicio', '!=', 'costo') // Excluir tickets con costo extra
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
     * @param Model|null $cita La cita asociada para el historial
     */
    public function registrarVisitaSitio($cita = null): void
    {
        $this->increment('visitas_sitio_consumidas_mes');
        $this->refresh();

        // Registrar en historial de consumos (Fase 4)
        if ($cita) {
            PolizaConsumo::registrar($this, PolizaConsumo::TIPO_VISITA, $cita);
        }

        // Verificar alertas de límite
        $this->verificarAlertasLimite('visitas');
    }

    /**
     * Registrar un ticket de soporte consumido.
     * @param Model|null $ticket El ticket asociado para el historial
     */
    public function registrarTicketSoporte($ticket = null): void
    {
        $this->increment('tickets_soporte_consumidos_mes');
        $this->refresh();

        // Registrar en historial de consumos (Fase 4)
        if ($ticket) {
            PolizaConsumo::registrar($this, PolizaConsumo::TIPO_TICKET, $ticket);
        }

        // Verificar alertas de límite
        $this->verificarAlertasLimite('tickets');
    }

    /**
     * Relación con el historial de consumos.
     */
    public function consumos(): HasMany
    {
        return $this->hasMany(PolizaConsumo::class, 'poliza_id');
    }

    /**
     * Relación con las tareas de mantenimiento.
     */
    public function mantenimientos(): HasMany
    {
        return $this->hasMany(PolizaMantenimiento::class, 'poliza_id');
    }

    /**
     * Obtener consumos del mes actual.
     */
    public function consumosMesActual()
    {
        return $this->consumos()
            ->whereMonth('fecha_consumo', now()->month)
            ->whereYear('fecha_consumo', now()->year);
    }

    /**
     * Calcular ahorro total del mes.
     */
    public function getAhorroMesActualAttribute(): float
    {
        return $this->consumosMesActual()->sum('ahorro');
    }


    /**
     * Verificar y enviar alertas de límite (Fase 3).
     */
    public function verificarAlertasLimite(string $tipo): void
    {
        $limite = $tipo === 'tickets'
            ? ($this->limite_mensual_tickets ?? 0)
            : ($this->visitas_sitio_mensuales ?? 0);

        $consumo = $tipo === 'tickets'
            ? ($this->tickets_soporte_consumidos_mes ?? 0)
            : ($this->visitas_sitio_consumidas_mes ?? 0);

        if ($limite <= 0)
            return;

        $porcentaje = round(($consumo / $limite) * 100);

        // Evitar spam: no enviar si ya se envió en las últimas 24 horas
        if ($this->ultima_alerta_exceso_at && $this->ultima_alerta_exceso_at->diffInHours(now()) < 24) {
            return;
        }

        try {
            // Alerta al 80%: notificar al cliente
            if ($porcentaje >= 80 && $porcentaje < 100) {
                $cliente = $this->cliente;
                if ($cliente && $cliente->email) {
                    $cliente->notify(new \App\Notifications\PolizaLimiteProximoNotification($this, $tipo, $porcentaje));
                    \Illuminate\Support\Facades\Log::info("Alerta 80% enviada", [
                        'poliza' => $this->folio,
                        'tipo' => $tipo,
                        'porcentaje' => $porcentaje
                    ]);
                }
            }

            // Alerta al 100%: notificar al admin y generar cobro
            if ($porcentaje >= 100) {
                $excedente = $consumo - $limite;
                $admins = \App\Models\User::role('super-admin')->get();
                foreach ($admins as $admin) {
                    $admin->notify(new \App\Notifications\PolizaLimiteExcedidoNotification($this, $tipo, $excedente));
                }

                // FASE 6: Generar cuenta por cobrar automáticamente
                $this->generarCobroExcedente($tipo, $excedente);

                $this->update(['ultima_alerta_exceso_at' => now()]);

                \Illuminate\Support\Facades\Log::warning("Límite excedido en póliza", [
                    'poliza' => $this->folio,
                    'tipo' => $tipo,
                    'excedente' => $excedente,
                    'cliente' => $this->cliente?->nombre_razon_social
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error enviando alerta de póliza: " . $e->getMessage());
        }
    }

    /**
     * Generar cuenta por cobrar por excedentes (Fase 6).
     */
    public function generarCobroExcedente(string $tipo, int $excedente = 1): ?\App\Models\CuentasPorCobrar
    {
        if ($excedente <= 0)
            return null;

        $costoUnitario = match ($tipo) {
            'tickets' => 150,
            'visitas' => $this->costo_visita_sitio_extra ?? 650,
            'horas' => $this->costo_hora_excedente ?? 350,
            default => 0,
        };

        $montoTotal = $excedente * $costoUnitario;
        if ($montoTotal <= 0)
            return null;

        $tipoLabel = match ($tipo) {
            'tickets' => 'Tickets',
            'visitas' => 'Visitas',
            'horas' => 'Horas',
            default => 'Servicios',
        };

        try {
            $cxc = \App\Models\CuentasPorCobrar::create([
                'empresa_id' => $this->empresa_id,
                'cliente_id' => $this->cliente_id,
                'cobrable_type' => self::class,
                'cobrable_id' => $this->id,
                'folio' => 'EXC-' . $this->folio . '-' . now()->format('Ym'),
                'concepto' => "Excedente de {$tipoLabel} - Póliza {$this->folio} (" . now()->format('M Y') . ")",
                'monto_total' => $montoTotal,
                'monto_pendiente' => $montoTotal,
                'fecha_emision' => now(),
                'fecha_vencimiento' => now()->addDays(15),
                'estado' => 'pendiente',
            ]);

            \Illuminate\Support\Facades\Log::info("Cobro por excedente generado", [
                'poliza' => $this->folio,
                'tipo' => $tipo,
                'excedente' => $excedente,
                'monto' => $montoTotal,
                'cxc_id' => $cxc->id
            ]);

            return $cxc;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error generando cobro de excedente: " . $e->getMessage());
            return null;
        }
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

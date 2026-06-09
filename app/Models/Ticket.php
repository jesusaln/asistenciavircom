<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use OwenIt\Auditing\Contracts\Auditable;
use App\Models\Concerns\BelongsToEmpresa;

class Ticket extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable, BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'folio',
        'cliente_id',
        'user_id',
        'asignado_id',
        'categoria_id',
        'producto_id',
        'venta_id',
        'folio_manual',
        'titulo',
        'descripcion',
        'prioridad',
        'estado',
        'tipo_servicio',
        'origen',
        'poliza_id',
        'telefono_contacto',
        'email_contacto',
        'nombre_contacto',
        'fecha_limite',
        'primera_respuesta_at',
        'resuelto_at',
        'cerrado_at',
        'notas_internas',
        'archivos',
        'horas_trabajadas', // Phase 2
        'servicio_inicio_at',
        'servicio_fin_at',
        'consumo_registrado_at', // Fase 1 - Idempotencia
    ];

    protected $casts = [
        'fecha_limite' => 'datetime',
        'primera_respuesta_at' => 'datetime',
        'resuelto_at' => 'datetime',
        'cerrado_at' => 'datetime',
        'servicio_inicio_at' => 'datetime',
        'servicio_fin_at' => 'datetime',
        'archivos' => 'array',
        'horas_trabajadas' => 'decimal:2',
        'consumo_registrado_at' => 'datetime', // Fase 1 - Idempotencia
    ];

    // Accessors
    public function getDuracionServicioAttribute(): ?float
    {
        if ($this->servicio_inicio_at && $this->servicio_fin_at) {
            return round($this->servicio_inicio_at->diffInMinutes($this->servicio_fin_at) / 60, 2);
        }
        return null;
    }

    protected $appends = ['sla_status', 'tiempo_abierto', 'is_vip'];

    /**
     * Determina si el ticket es VIP (vinculado a una póliza activa)
     */
    public function getIsVipAttribute(): bool
    {
        return $this->poliza_id !== null;
    }

    // Boot - Generar número automático
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->folio)) {
                try {
                    $ticket->folio = app(\App\Services\Folio\FolioService::class)->getNextFolio('ticket');
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error generating folio for ticket: ' . $e->getMessage());
                }
            }

            // Calcular fecha límite según SLA de la Póliza (Prioritario) o Categoría
            if (empty($ticket->fecha_limite)) {
                $horasSla = null;

                // FASE 1: Vinculación automática de póliza si el ticket tiene cliente pero no póliza
                if (!$ticket->poliza_id && $ticket->cliente_id) {
                    $polizaActiva = PolizaServicio::where('cliente_id', $ticket->cliente_id)
                        ->where('estado', 'activa')
                        ->where('fecha_fin', '>=', now())
                        ->orderByDesc('created_at')
                        ->first();

                    if ($polizaActiva) {
                        $ticket->poliza_id = $polizaActiva->id;
                        \Illuminate\Support\Facades\Log::info("Ticket vinculado automáticamente a póliza", [
                            'ticket_folio' => $ticket->folio,
                            'poliza_id' => $polizaActiva->id,
                            'cliente_id' => $ticket->cliente_id
                        ]);
                    }
                }

                // 1. Intentar obtener SLA de la póliza
                if ($ticket->poliza_id) {
                    $poliza = PolizaServicio::find($ticket->poliza_id);
                    if ($poliza) {
                        // Priorizar resolución sobre respuesta para la fecha límite
                        $horasSla = $poliza->sla_horas_resolucion ?: $poliza->sla_horas_respuesta;
                    }
                }

                // 2. Si no hay póliza o no tiene SLA, usar categoría
                if (!$horasSla && $ticket->categoria_id) {
                    $categoria = TicketCategory::find($ticket->categoria_id);
                    if ($categoria) {
                        $horasSla = $categoria->sla_horas;
                    }
                }

                if ($horasSla) {
                    // Usar el nuevo SlaService para cálculo preciso en horario laboral
                    $slaService = app(\App\Services\SlaService::class);
                    $ticket->fecha_limite = $slaService->calculateDeadline(now(), (int) $horasSla);
                }
            }

            // Descontar folio de póliza si aplica al crear (Soporte Técnico)
            if ($ticket->poliza_id && $ticket->categoria_id) {
                // Usamos delay para evitar problemas de racing conditions con la relación
                static::saved(function ($t) {
                    if ($t->wasRecentlyCreated && $t->poliza_id) {
                        $t->registrarConsumoUnitarioEnPoliza();
                    }
                });
            }
        });

        static::created(function ($ticket) {
            \App\Events\TicketCreado::dispatch($ticket);
        });
    }

    // Relaciones
    public function empresa()
    {
        return $this->belongsTo(EmpresaConfiguracion::class, 'empresa_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function asignado()
    {
        return $this->belongsTo(User::class, 'asignado_id');
    }

    public function categoria()
    {
        return $this->belongsTo(TicketCategory::class, 'categoria_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function poliza()
    {
        return $this->belongsTo(PolizaServicio::class, 'poliza_id');
    }

    public function comentarios()
    {
        return $this->hasMany(TicketComment::class)->orderBy('created_at', 'asc');
    }

    public function citas()
    {
        return $this->hasMany(Cita::class);
    }

    // Accessors
    public function getSlaStatusAttribute(): string
    {
        if (in_array($this->estado, ['resuelto', 'cerrado'])) {
            return 'completado';
        }

        if (!$this->fecha_limite) {
            return 'sin_sla';
        }

        $now = now();
        $horasRestantes = $now->diffInHours($this->fecha_limite, false);

        if ($horasRestantes < 0) {
            return 'vencido';
        } elseif ($horasRestantes <= 2) {
            return 'critico';
        } elseif ($horasRestantes <= 8) {
            return 'advertencia';
        }

        return 'ok';
    }

    public function getTiempoAbiertoAttribute(): string
    {
        $desde = $this->created_at;
        if (!$desde) {
            return '0s';
        }
        $hasta = $this->resuelto_at ?? now();

        return $desde->diffForHumans($hasta, true);
    }

    // Scopes
    public function scopeAbiertos($query)
    {
        return $query->whereIn('estado', ['abierto', 'en_progreso', 'pendiente']);
    }

    public function scopeCerrados($query)
    {
        return $query->whereIn('estado', ['resuelto', 'cerrado']);
    }

    public function scopeAsignadoA($query, $userId)
    {
        return $query->where('asignado_id', $userId);
    }

    public function scopeSinAsignar($query)
    {
        return $query->whereNull('asignado_id');
    }

    public function scopeVencidos($query)
    {
        return $query->abiertos()
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', now());
    }

    // Métodos
    public function marcarComoResuelto(?float $horasTrabajadas = null, ?string $inicio = null, ?string $fin = null, bool $incrementar = false): void
    {
        $horasPrevias = (float) ($this->horas_trabajadas ?? 0);

        $datosUpdate = [
            'estado' => 'resuelto',
            'resuelto_at' => now(),
        ];

        if ($horasTrabajadas !== null) {
            $nuevasHoras = $incrementar ? ($horasPrevias + $horasTrabajadas) : $horasTrabajadas;
            $datosUpdate['horas_trabajadas'] = $nuevasHoras;

            // Registrar diferencia en póliza si existe
            if ($this->poliza_id) {
                $diferencia = $incrementar ? $horasTrabajadas : ($horasTrabajadas - $horasPrevias);
                if ($diferencia > 0) {
                    $this->registrarConsumoEnPoliza($diferencia);
                }
            }
        }

        if ($inicio)
            $datosUpdate['servicio_inicio_at'] = $inicio;
        if ($fin)
            $datosUpdate['servicio_fin_at'] = $fin;

        $this->update($datosUpdate);
    }

    public function cerrar(?float $horasTrabajadas = null, ?string $inicio = null, ?string $fin = null, bool $incrementar = false): void
    {
        $horasPrevias = (float) ($this->horas_trabajadas ?? 0);

        $datosUpdate = [
            'estado' => 'cerrado',
            'cerrado_at' => now(),
        ];

        if ($horasTrabajadas !== null) {
            $nuevasHoras = $incrementar ? ($horasPrevias + $horasTrabajadas) : $horasTrabajadas;
            $datosUpdate['horas_trabajadas'] = $nuevasHoras;

            // Registrar diferencia en póliza si existe
            if ($this->poliza_id) {
                $diferencia = $incrementar ? $horasTrabajadas : ($horasTrabajadas - $horasPrevias);
                if ($diferencia > 0) {
                    $this->registrarConsumoEnPoliza($diferencia);
                }
            }
        }

        if ($inicio)
            $datosUpdate['servicio_inicio_at'] = $inicio;
        if ($fin)
            $datosUpdate['servicio_fin_at'] = $fin;

        $this->update($datosUpdate);
    }

    /**
     * Registrar horas consumidas en la póliza asociada.
     * NOTA: Este método solo debe llamarse UNA VEZ por ticket para las horas.
     */
    public function registrarConsumoEnPoliza(?float $horas = null): void
    {
        // Si el ticket es "con costo", no consume horas de la póliza
        if ($this->tipo_servicio === 'costo') {
            return;
        }

        $horasARegistrar = $horas ?? (float) ($this->horas_trabajadas ?? 0);

        if ($horasARegistrar <= 0 || !$this->poliza_id) {
            return;
        }

        $poliza = $this->poliza;
        if ($poliza && $poliza->isActiva()) {
            // Intentamos obtener el servicio_id de la categoría del ticket
            $servicioId = $this->categoria?->servicio_id;

            // Usar el nuevo método profesional que maneja validaciones, excedentes y cobros extra
            $poliza->consumirHoras($horasARegistrar, $servicioId, $this);
        }
    }

    /**
     * Registrar el folio (unidad) consumido en la póliza.
     */
    public function registrarConsumoUnitarioEnPoliza(): void
    {
        // FASE 1 - Mejora 1.2: Idempotencia
        // Si ya se registró el consumo, no volver a hacerlo
        if ($this->consumo_registrado_at) {
            \Illuminate\Support\Facades\Log::debug("Consumo ya registrado para ticket {$this->id}, omitiendo.");
            return;
        }

        // Si el ticket es "con costo", no consume folios de la póliza
        if (!$this->poliza_id || !$this->categoria_id || $this->tipo_servicio === 'costo') {
            return;
        }

        $categoria = $this->categoria;
        if ($categoria && $categoria->consume_poliza) {
            $poliza = $this->poliza;
            if ($poliza && $poliza->isActiva()) {
                // Usar transacción con lock para evitar race conditions
                \Illuminate\Support\Facades\DB::transaction(function () use ($poliza) {
                    // Re-verificar dentro de la transacción
                    $ticketFresh = Ticket::lockForUpdate()->find($this->id);
                    if ($ticketFresh->consumo_registrado_at) {
                        return; // Ya se procesó en otra request concurrente
                    }

                    $poliza->registrarTicketSoporte($this); // Pasar el ticket para historial

                    // Marcar como registrado
                    $ticketFresh->update(['consumo_registrado_at' => now()]);
                });
            }
        }
    }

    /**
     * Revertir el consumo de folio/unidad en la póliza.
     * Útil si un ticket se cambia de "Garantía" a "Con Costo" después de creado.
     */
    public function revertirConsumoUnitarioEnPoliza(): void
    {
        if (!$this->poliza_id) {
            return;
        }

        $poliza = $this->poliza;
        if ($poliza) {
            // Decrementar el contador de tickets consumidos en el mes
            // Solo si es mayor a 0
            if ($poliza->tickets_soporte_consumidos_mes > 0) {
                $poliza->decrement('tickets_soporte_consumidos_mes');

                // Opcionalmente eliminar del historial de consumos si existe
                \App\Models\PolizaConsumo::where('poliza_id', $this->poliza_id)
                    ->where('consumible_type', Ticket::class)
                    ->where('consumible_id', $this->id)
                    ->delete();
            }
        }
    }

    public function asignarA(User $usuario): void
    {
        $this->update(['asignado_id' => $usuario->id]);

        $this->comentarios()->create([
            'user_id' => auth()->id(),
            'contenido' => "Ticket asignado a {$usuario->name}",
            'tipo' => 'asignacion',
            'es_interno' => true,
            'metadata' => ['asignado_id' => $usuario->id],
        ]);
    }

    public function cambiarEstado(string $nuevoEstado): void
    {
        $estadoAnterior = $this->estado;
        $this->update(['estado' => $nuevoEstado]);

        if ($estadoAnterior === 'abierto' && $nuevoEstado === 'en_progreso' && !$this->primera_respuesta_at) {
            $this->update(['primera_respuesta_at' => now()]);
        }

        $this->comentarios()->create([
            'user_id' => auth()->id(),
            'contenido' => "Estado: {$estadoAnterior} → {$nuevoEstado}",
            'tipo' => 'estado',
            'es_interno' => true,
            'metadata' => ['estado_anterior' => $estadoAnterior, 'estado_nuevo' => $nuevoEstado],
        ]);
    }

    public static function getPrioridadColor(string $prioridad): string
    {
        return match ($prioridad) {
            'urgente' => 'red',
            'alta' => 'orange',
            'media' => 'yellow',
            'baja' => 'green',
            default => 'gray',
        };
    }

    public static function getEstadoColor(string $estado): string
    {
        return match ($estado) {
            'abierto' => 'blue',
            'en_progreso' => 'yellow',
            'pendiente' => 'orange',
            'resuelto' => 'green',
            'cerrado' => 'gray',
            default => 'gray',
        };
    }
}

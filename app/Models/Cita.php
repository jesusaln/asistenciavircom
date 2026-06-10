<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Models\Concerns\BelongsToEmpresa;

use OwenIt\Auditing\Auditable;

/**
 * @property int $id
 * @property string $folio
 * @property int|null $empresa_id
 * @property int $tecnico_id
 * @property int $cliente_id
 * @property string $tipo_servicio
 * @property \Carbon\Carbon $fecha_hora
 * @property string|null $descripcion
 * @property string|null $problema_reportado
 * @property string $prioridad
 * @property string $estado
 * @property \Carbon\Carbon|null $fecha_hora_fin
 * @property array|null $evidencias
 * @property string|null $foto_equipo
 * @property string|null $foto_hoja_servicio
 * @property string|null $foto_identificacion
 * @property string $tipo_equipo
 * @property string|null $marca_equipo
 * @property string|null $modelo_equipo
 * @property float $subtotal
 * @property float $descuento_general
 * @property float $descuento_items
 * @property float $iva
 * @property float $total
 * @property string|null $notas
 * @property string|null $notas_internas
 * @property \Carbon\Carbon|null $inicio_servicio
 * @property \Carbon\Carbon|null $fin_servicio
 * @property int|null $tiempo_servicio
 * @property array|null $fotos_finales
 * @property int|null $ticket_id
 * @property string|null $firma_cliente
 * @property string|null $nombre_firmante
 * @property \Carbon\Carbon|null $fecha_firma
 * @property string|null $firma_tecnico
 * @property int|null $poliza_id
 * @property float|null $latitud
 * @property float|null $longitud
 * @property \Carbon\Carbon|null $fecha_gps
 * @property array|null $evidencias_previas
 */
class Cita extends Model implements \OwenIt\Auditing\Contracts\Auditable
{
    use BelongsToEmpresa;

    use HasFactory, SoftDeletes, Auditable;

    protected static function booted()
    {
        static::creating(function (Cita $cita) {
            if (empty($cita->empresa_id)) {
                $cita->empresa_id = auth()->user()?->empresa_id
                    ?? \App\Support\EmpresaResolver::resolveId();
                
                if (empty($cita->empresa_id)) {
                    throw new \RuntimeException("No se puede crear la Cita: empresa_id es requerido y no pudo ser resuelto.");
                }
            }
            if (empty($cita->folio)) {
                try {
                    $folio = app(\App\Services\Folio\FolioService::class)->getNextFolio('cita');
                    if (empty($folio)) {
                        throw new \Exception('El folio generado está vacío.');
                    }
                    $cita->folio = $folio;
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::critical('CRITICAL: Failed to generate folio for cita. Transaction aborted.', [
                        'error' => $e->getMessage(),
                        'tecnico_id' => $cita->tecnico_id,
                        'cliente_id' => $cita->cliente_id
                    ]);
                    // Abortar creación de la cita (Evitar Folio Ghost #609)
                    return false; 
                }
            }
        });

        // Borrado en cascada para items
        static::deleting(function (Cita $cita) {
            if ($cita->isForceDeleting()) {
                $cita->items()->forceDelete();
            } else {
                $cita->items()->delete();
            }
        });

        // Notificaciones Push a Técnicos
        static::created(function (Cita $cita) {
            if ($cita->tecnico_id && $cita->tecnico && $cita->tecnico->fcm_token) {
                \App\Jobs\SendPushNotification::dispatch(
                    $cita->tecnico->fcm_token,
                    '📅 Nueva Cita Asignada',
                    "Tienes una nueva cita para: {$cita->cliente->nombre_razon_social} el {$cita->fecha_hora->format('d/m H:i')}",
                    ['cita_id' => $cita->id, 'type' => 'cita_new']
                );
            }
        });

        static::updated(function (Cita $cita) {
            if ($cita->isDirty('tecnico_id') && $cita->tecnico_id && $cita->tecnico && $cita->tecnico->fcm_token) {
                \App\Jobs\SendPushNotification::dispatch(
                    $cita->tecnico->fcm_token,
                    '🔄 Cita Reasignada',
                    "Se te ha reasignado una cita para: {$cita->cliente->nombre_razon_social}",
                    ['cita_id' => $cita->id, 'type' => 'cita_reasigned']
                );
            }
        });
    }

    // Constantes para estados
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_PENDIENTE_ASIGNACION = 'pendiente_asignacion'; // Nuevo: citas públicas
    const ESTADO_PROGRAMADO = 'programado';
    const ESTADO_EN_PROCESO = 'en_proceso';
    const ESTADO_PAUSADO = 'pausado';
    const ESTADO_COMPLETADO = 'completado';
    const ESTADO_CANCELADO = 'cancelado';
    const ESTADO_REPROGRAMADO = 'reprogramado';
    const ESTADO_REPROGRAMADA = 'reprogramado'; // Alias para compatibilidad
    const ESTADO_NO_PRESENTO = 'no_presento';

    // Constantes para prioridades
    const PRIORIDAD_BAJA = 'baja';
    const PRIORIDAD_MEDIA = 'media';
    const PRIORIDAD_ALTA = 'alta';
    const PRIORIDAD_URGENTE = 'urgente';

    // Constantes para tiendas de origen
    const TIENDAS_ORIGEN = [
        'liverpool' => 'Liverpool',
        'coppel' => 'Coppel',
        'elektra' => 'Elektra',
        'sears' => 'Sears',
        'costco' => 'Costco',
        'home_depot' => 'Home Depot',
        'walmart' => 'Walmart',
        'soriana' => 'Soriana',
        'otro' => 'Otro',
    ];

    // Constantes para horarios preferidos
    const HORARIOS_PREFERIDOS = [
        'manana' => ['nombre' => 'Mañana', 'inicio' => '08:00', 'fin' => '10:00', 'emoji' => '🌅'],
        'mediodia' => ['nombre' => 'Medio día', 'inicio' => '11:00', 'fin' => '13:00', 'emoji' => '☀️'],
        'tarde' => ['nombre' => 'Tarde', 'inicio' => '14:00', 'fin' => '16:00', 'emoji' => '🌤️'],
        'noche' => ['nombre' => 'Noche', 'inicio' => '17:00', 'fin' => '19:00', 'emoji' => '🌙'],
    ];

    protected $fillable = [
        'folio',
        'empresa_id',
        'tecnico_id',
        'ayudante_id',
        'cliente_id',
        'tipo_servicio',
        'fecha_hora',
        'descripcion',
        'problema_reportado',
        'prioridad',
        'estado',
        'fecha_hora_fin',
        'evidencias',
        'foto_equipo',
        'foto_hoja_servicio',
        'foto_identificacion',
        'tipo_equipo',
        'marca_equipo',
        'modelo_equipo',
        'subtotal',
        'descuento_general',
        'descuento_items',
        'iva',
        'total',
        'notas',
        'notas_internas',
        'inicio_servicio',
        'fin_servicio',
        'tiempo_servicio',
        // Campos para agendamiento público
        'es_publica',
        'origen_tienda',
        'numero_ticket_tienda',
        'horario_preferido',
        'dias_preferidos',
        'fecha_confirmada',
        'hora_confirmada',
        'direccion_calle',
        'direccion_colonia',
        'direccion_cp',
        'direccion_referencias',
        'link_seguimiento',
        'whatsapp_recepcion_enviado',
        'whatsapp_confirmacion_enviado',
        'whatsapp_recepcion_at',
        'whatsapp_confirmacion_at',
        'trabajo_realizado',
        'fotos_finales',
        'ticket_id',
        'firma_cliente',
        'nombre_firmante',
        'fecha_firma',
        'firma_tecnico',
        'firma_cliente_hash',
        'firma_tecnico_hash',
        'poliza_id',
        'latitud',
        'longitud',
        'fecha_gps',
        'evidencias_previas',
        'equipos_servicio',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'descuento_general' => 'decimal:2',
        'descuento_items' => 'decimal:2',
        'iva' => 'decimal:2',
        'total' => 'decimal:2',
        'inicio_servicio' => 'datetime',
        'fin_servicio' => 'datetime',
        'equipos_servicio' => 'array',
        'fecha_hora_fin' => 'datetime',
        // Casts para campos públicos
        'es_publica' => 'boolean',
        'dias_preferidos' => 'array',
        'fecha_confirmada' => 'date',
        'whatsapp_recepcion_enviado' => 'boolean',
        'whatsapp_confirmacion_enviado' => 'boolean',
        'whatsapp_recepcion_at' => 'datetime',
        'whatsapp_confirmacion_at' => 'datetime',
        'evidencias' => 'array',
        'fotos_finales' => 'array',
        'evidencias_previas' => 'array',
        'fecha_firma' => 'datetime',
    ];

    /**
     * Atributos ocultos en JSON por defecto.
     * ✅ PERFORMANCE: Evita enviar datos pesados (firmas base64/rutas) en cada serialización.
     */
    protected $hidden = [
        'firma_cliente',
        'firma_tecnico',
    ];

    // Scopes útiles
    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeEnProceso($query)
    {
        return $query->where('estado', self::ESTADO_EN_PROCESO);
    }

    public function scopeCompletadas($query)
    {
        return $query->where('estado', self::ESTADO_COMPLETADO);
    }

    public function scopeCanceladas($query)
    {
        return $query->where('estado', self::ESTADO_CANCELADO);
    }

    /**
     * Listado: activas (no completada/cancelada) por fecha de programación ascendente (la más próxima primero);
     * luego completadas y al final canceladas; dentro de completadas/canceladas, fecha descendente (más recientes primero).
     */
    public function scopeOrderDefaultAgenda(Builder $query): Builder
    {
        $completado = self::ESTADO_COMPLETADO;
        $cancelado = self::ESTADO_CANCELADO;

        return $query
            ->orderByRaw('
                CASE
                    WHEN estado = ? THEN 2
                    WHEN estado = ? THEN 1
                    ELSE 0
                END ASC
            ', [$cancelado, $completado])
            ->orderByRaw('
                CASE
                    WHEN estado NOT IN (?, ?) THEN EXTRACT(EPOCH FROM fecha_hora::timestamptz)
                    ELSE -EXTRACT(EPOCH FROM fecha_hora::timestamptz)
                END ASC
            ', [$completado, $cancelado]);
    }

    public function scopePorTecnico($query, $tecnicoId)
    {
        return $query->where('tecnico_id', $tecnicoId);
    }

    public function scopePorCliente($query, $clienteId)
    {
        return $query->where('cliente_id', $clienteId);
    }

    public function scopeProximas($query)
    {
        return $query->where('fecha_hora', '>', now())->orderBy('fecha_hora');
    }

    public function scopeHoy($query)
    {
        return $query->whereDate('fecha_hora', today());
    }

    public function scopeEstaSemana($query)
    {
        return $query->whereBetween('fecha_hora', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    // Métodos de acceso
    public function getEstadoColorAttribute()
    {
        return match ($this->estado) {
            self::ESTADO_PENDIENTE => 'yellow',
            self::ESTADO_PROGRAMADO => 'blue',
            self::ESTADO_EN_PROCESO => 'indigo',
            self::ESTADO_COMPLETADO => 'green',
            self::ESTADO_CANCELADO => 'red',
            self::ESTADO_REPROGRAMADO => 'purple',
            default => 'gray',
        };
    }

    public function getPrioridadColorAttribute()
    {
        return match ($this->prioridad) {
            self::PRIORIDAD_BAJA => 'green',
            self::PRIORIDAD_MEDIA => 'yellow',
            self::PRIORIDAD_ALTA => 'orange',
            self::PRIORIDAD_URGENTE => 'red',
            default => 'gray',
        };
    }

    public function getEsPasadaAttribute()
    {
        return $this->fecha_hora->isPast();
    }

    public function getEsHoyAttribute()
    {
        return $this->fecha_hora->isToday();
    }

    public function getTiempoServicioFormateadoAttribute()
    {
        if (!$this->tiempo_servicio) {
            return 'No registrado';
        }

        $horas = floor($this->tiempo_servicio / 60);
        $minutos = $this->tiempo_servicio % 60;

        if ($horas > 0) {
            return "{$horas}h {$minutos}m";
        }

        return "{$minutos}m";
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Técnico asignado (Usuario)
     */
    public function tecnico()
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    /**
     * Ayudante asignado (Usuario)
     */
    public function ayudante()
    {
        return $this->belongsTo(User::class, 'ayudante_id');
    }

    /**
     * Ticket de soporte origen (si aplica)
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Póliza de servicio asociada
     */
    public function poliza()
    {
        return $this->belongsTo(PolizaServicio::class, 'poliza_id');
    }

    /**
     * Historial de cambios de estado (Auditoría)
     */
    public function historial()
    {
        return $this->hasMany(CitaHistorial::class, 'cita_id')->latest();
    }

    /**
     * Serie de producto asociada a esta cita de garantía (si aplica)
     */
    public function productoSerieGarantia()
    {
        return $this->hasOne(ProductoSerie::class, 'cita_id');
    }

    /**
     * Ítems de la cita (Usados para generar la Venta posterior)
     */
    public function items()
    {
        return $this->hasMany(CitaItem::class);
    }

    public function venta()
    {
        return $this->hasOne(Venta::class);
    }

    /**
     * Productos en la cita (DESHABILITADO)
     */
    /*
    public function productos()
    {
        return $this->morphedByMany(
            Producto::class,
            'citable',
            'cita_items',
            'cita_id',
            'citable_id'
        )->withPivot('cantidad', 'precio', 'descuento', 'subtotal', 'descuento_monto', 'notas');
    }
    */

    /**
     * Servicios en la cita (DESHABILITADO)
     */
    /*
    public function servicios()
    {
        return $this->morphedByMany(
            Servicio::class,
            'citable',
            'cita_items',
            'cita_id',
            'citable_id'
        )->withPivot('cantidad', 'precio', 'descuento', 'subtotal', 'descuento_monto', 'notas');
    }
    */

    /**
     * Verificar si la cita puede ser modificada
     */
    public function puedeSerModificada(): bool
    {
        // No permitir modificar citas completadas con más de 7 días
        if ($this->estado === self::ESTADO_COMPLETADO) {
            return now()->diffInDays($this->updated_at) < 7;
        }

        // No permitir modificar citas canceladas
        if ($this->estado === self::ESTADO_CANCELADO) {
            return false;
        }

        return true;
    }

    /**
     * Verificar si la cita puede ser eliminada
     */
    public function puedeSerEliminada(): bool
    {
        // No permitir eliminar citas completadas con menos de 30 días
        if ($this->estado === self::ESTADO_COMPLETADO) {
            return now()->diffInDays($this->created_at) >= 30;
        }

        // No permitir eliminar citas en proceso
        if ($this->estado === self::ESTADO_EN_PROCESO) {
            return false;
        }

        return true;
    }

    /**
     * Obtener el siguiente estado válido
     */
    public function getSiguientesEstadosValidos(): array
    {
        return match ($this->estado) {
            self::ESTADO_PENDIENTE => [self::ESTADO_PROGRAMADO, self::ESTADO_EN_PROCESO, self::ESTADO_CANCELADO, self::ESTADO_COMPLETADO],
            self::ESTADO_PROGRAMADO => [self::ESTADO_EN_PROCESO, self::ESTADO_REPROGRAMADO, self::ESTADO_CANCELADO, self::ESTADO_COMPLETADO],
            self::ESTADO_EN_PROCESO => [self::ESTADO_COMPLETADO, self::ESTADO_PAUSADO, self::ESTADO_CANCELADO, self::ESTADO_PROGRAMADO],
            self::ESTADO_PAUSADO => [self::ESTADO_EN_PROCESO, self::ESTADO_COMPLETADO, self::ESTADO_CANCELADO],
            self::ESTADO_COMPLETADO => [], // No se puede cambiar de completado
            self::ESTADO_CANCELADO => [self::ESTADO_PENDIENTE], // Solo se puede reactivar
            self::ESTADO_REPROGRAMADO => [self::ESTADO_PROGRAMADO, self::ESTADO_EN_PROCESO, self::ESTADO_CANCELADO, self::ESTADO_COMPLETADO],
            default => []
        };
    }

    /**
     * Impide completar de inmediato tras "Iniciar": exige un tiempo mínimo desde {@see $this->inicio_servicio}.
     * Los super-administradores omiten esta regla (correcciones / soporte).
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     */
    public function bloqueoMensajePorTiempoMinimoCompletar($user = null): ?string
    {
        $minSec = (int) config('citas.min_segundos_servicio_antes_de_completar', 300);
        if ($minSec <= 0) {
            return null;
        }

        if ($user && method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
            return null;
        }

        if (!$this->inicio_servicio) {
            return 'Primero debes iniciar el servicio (botón Iniciar) y esperar el tiempo mínimo antes de completar.';
        }

        $elapsed = Carbon::parse($this->inicio_servicio)->diffInSeconds(now());
        if ($elapsed < $minSec) {
            $rest = $minSec - $elapsed;
            $minutosRequeridos = max(1, (int) ceil($minSec / 60));
            $aproxEsperaMin = max(1, (int) ceil($rest / 60));

            return "Deben transcurrir al menos {$minutosRequeridos} minuto(s) desde el inicio del servicio antes de completar. Espera aproximadamente {$aproxEsperaMin} minuto(s) más (o contacta a un administrador).";
        }

        return null;
    }

    protected $appends = ['tiempo_servicio_formateado', 'motivo_cancelacion'];

    public function getMotivoCancelacionAttribute()
    {
        if (in_array($this->estado, ['cancelado', 'cancelada'])) {
            if ($this->evidencias && stripos($this->evidencias, 'cancelad') !== false) {
                return $this->evidencias;
            }
            if ($this->notas && stripos($this->notas, 'cancelac') !== false) {
                return $this->notas;
            }
            $historial = $this->historial()->where('estado_nuevo', 'cancelado')->first();
            if ($historial && $historial->comentario) {
                return $historial->comentario;
            }
            return $this->evidencias ?: ($this->notas ?: 'Cancelado por el administrador o técnico');
        }
        return null;
    }



    /**
     * Cambiar estado de la cita con auditoría
     */
    public function cambiarEstado(string $nuevoEstado, ?string $comentario = null): bool
    {
        $estadosValidos = $this->getSiguientesEstadosValidos();

        if (!in_array($nuevoEstado, $estadosValidos)) {
            return false;
        }

        $estadoAnterior = $this->getOriginal('estado');

        // Lógica de Tiempos
        if ($nuevoEstado === self::ESTADO_EN_PROCESO) {
            if (!$this->inicio_servicio) {
                $this->inicio_servicio = now();
            }
        } elseif ($nuevoEstado === self::ESTADO_PROGRAMADO && $this->estado === self::ESTADO_EN_PROCESO) {
            // Resetear el cronómetro si el técnico regresa la cita por error (Fix #1052)
            $this->inicio_servicio = null;
        } elseif ($nuevoEstado === self::ESTADO_COMPLETADO) {
            $this->fin_servicio = now();
            if ($this->inicio_servicio) {
                $inicio = Carbon::parse($this->inicio_servicio);
                $fin = Carbon::parse($this->fin_servicio);
                $this->tiempo_servicio = (int) $inicio->diffInMinutes($fin);
            }

            // Registrar consumo de visita en póliza si aplica
            if ($this->poliza_id) {
                $poliza = $this->poliza;
                if ($poliza && $poliza->isActiva()) {
                    $poliza->registrarVisitaSitio();
                }
            }
        } elseif ($nuevoEstado === self::ESTADO_CANCELADO && $comentario) {
            // Guardar el motivo en las evidencias para que la app móvil pueda mostrarlo
            if (!$this->evidencias) {
                $this->evidencias = $comentario;
            } else {
                $this->evidencias = $comentario . "\n\nNotas previas:\n" . $this->evidencias;
            }
        }

        $this->estado = $nuevoEstado;
        $this->save();

        // Auditoría Automática (History Trail) con DIFF (Fix #720)
        CitaHistorial::create([
            'cita_id' => $this->id,
            'user_id' => auth()->id(),
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $nuevoEstado,
            'comentario' => $comentario,
            'metadatos' => [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'diff' => [
                    'estado' => [
                        'from' => $estadoAnterior,
                        'to' => $nuevoEstado
                    ]
                ]
            ],
        ]);

        \App\Events\CitaEstadoActualizado::dispatch($this);
        
        if ($nuevoEstado === self::ESTADO_COMPLETADO) {
            \App\Events\CitaCompletada::dispatch($this);
        }

        return true;
    }

    const MAX_CITAS_POR_DIA = 8;

    /**
     * Verificar si hay conflicto de horario (Solapamiento)
     * @param int $tecnicoId
     * @param string $fechaHora (Y-m-d H:i:s)
     * @param int|null $excludeId
     * @param int $duracionMin
     * @return Cita|null
     */
    public static function hayConflictoHorario(int $tecnicoId, string $fechaHora, ?int $excludeId = null, int $duracionMin = 120): ?Cita
    {
        $nuevoInicio = Carbon::parse($fechaHora);
        $nuevoFin = $nuevoInicio->copy()->addMinutes($duracionMin);

        $query = self::where(function ($q) use ($tecnicoId) {
                $q->where('tecnico_id', $tecnicoId)
                  ->orWhere('ayudante_id', $tecnicoId);
            })
            ->whereNotIn('estado', [self::ESTADO_CANCELADO, 'cancelada', self::ESTADO_COMPLETADO])
            ->whereDate('fecha_hora', $nuevoInicio->toDateString())
            ->where(function ($q) use ($nuevoInicio, $nuevoFin) {
                // Un traslape ocurre si: (InicioA < FinB) AND (FinA > InicioB)
                $q->where('fecha_hora', '<', $nuevoFin)
                  ->whereRaw("COALESCE(fecha_hora_fin, fecha_hora + interval '120 minutes') > ?", [$nuevoInicio->toDateTimeString()]);
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    // ==================== MÉTODOS PARA CITAS PÚBLICAS ====================

    /**
     * Scope: Citas públicas pendientes de asignación
     */
    public function scopePendientesAsignacion($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE_ASIGNACION)
            ->where('es_publica', true);
    }

    /**
     * Scope: Solo citas públicas
     */
    public function scopePublicas($query)
    {
        return $query->where('es_publica', true);
    }

    /**
     * Obtener dirección completa formateada
     */
    public function getDireccionCompletaAttribute(): string
    {
        return $this->direccion_calle ?: '';
    }

    /**
     * Obtener nombre de la tienda de origen
     */
    public function getNombreTiendaAttribute(): ?string
    {
        if (!$this->origen_tienda) {
            return null;
        }
        return self::TIENDAS_ORIGEN[$this->origen_tienda] ?? $this->origen_tienda;
    }

    /**
     * Obtener información del horario preferido
     */
    public function getHorarioPreferidoInfoAttribute(): ?array
    {
        if (!$this->horario_preferido) {
            return null;
        }
        return self::HORARIOS_PREFERIDOS[$this->horario_preferido] ?? null;
    }

    /**
     * Generar link de seguimiento único
     */
    public function generarLinkSeguimiento(): string
    {
        if (!$this->link_seguimiento) {
            $this->link_seguimiento = (string) \Illuminate\Support\Str::uuid();
            $this->save();
        }
        return $this->link_seguimiento;
    }

    /**
     * Obtener URL completa de seguimiento
     */
    public function getUrlSeguimientoAttribute(): ?string
    {
        if (!$this->link_seguimiento) {
            return null;
        }
        return route('agendar.seguimiento', $this->link_seguimiento);
    }

    /**
     * Verificar si la cita está confirmada
     */
    public function getEstaConfirmadaAttribute(): bool
    {
        return !is_null($this->fecha_confirmada) && !is_null($this->hora_confirmada);
    }

    /**
     * Obtener hora confirmada con rango
     */
    public function getHoraConfirmadaRangoAttribute(): ?string
    {
        if (!$this->hora_confirmada || !$this->horario_preferido) {
            return $this->hora_confirmada;
        }

        $hora = Carbon::parse($this->hora_confirmada);
        
        $duracionMinutos = 120;
        if (isset(self::HORARIOS_PREFERIDOS[$this->horario_preferido])) {
            $pref = self::HORARIOS_PREFERIDOS[$this->horario_preferido];
            $inicioPref = Carbon::parse($pref['inicio']);
            $finPref = Carbon::parse($pref['fin']);
            $duracionMinutos = $inicioPref->diffInMinutes($finPref);
        }
        $horaFin = $hora->copy()->addMinutes($duracionMinutos);

        return $hora->format('h:i A') . ' - ' . $horaFin->format('h:i A');
    }

    /**
     * Verificar si el enlace de seguimiento sigue siendo válido (Privacidad #905)
     */
    public function getLinkSeguimientoValidoAttribute(): bool
    {
        if (!$this->link_seguimiento) return false;
        if ($this->estado !== self::ESTADO_COMPLETADO && $this->estado !== self::ESTADO_CANCELADO) return true;

        // Si está completada o cancelada, expira en 30 días
        $fechaReferencia = $this->fin_servicio ?? $this->updated_at;
        return now()->diffInDays($fechaReferencia) < 30;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Models\Concerns\BelongsToEmpresa;

class Cita extends Model
{
    use HasFactory, SoftDeletes, BelongsToEmpresa;

    protected static function booted()
    {
        static::creating(function (Cita $cita) {
            if (empty($cita->folio)) {
                try {
                    $cita->folio = app(\App\Services\Folio\FolioService::class)->getNextFolio('cita');
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error generating folio for cita: ' . $e->getMessage());
                }
            }
        });
    }

    // Constantes para estados
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_PENDIENTE_ASIGNACION = 'pendiente_asignacion'; // Nuevo: citas públicas
    const ESTADO_PROGRAMADO = 'programado';
    const ESTADO_EN_PROCESO = 'en_proceso';
    const ESTADO_COMPLETADO = 'completado';
    const ESTADO_CANCELADO = 'cancelado';
    const ESTADO_REPROGRAMADO = 'reprogramado';

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
        'manana' => ['nombre' => 'Mañana', 'inicio' => '08:00', 'fin' => '11:00', 'emoji' => '🌅'],
        'mediodia' => ['nombre' => 'Medio día', 'inicio' => '11:00', 'fin' => '14:00', 'emoji' => '☀️'],
        'tarde' => ['nombre' => 'Tarde', 'inicio' => '14:00', 'fin' => '17:00', 'emoji' => '🌤️'],
        'noche' => ['nombre' => 'Noche', 'inicio' => '17:00', 'fin' => '20:00', 'emoji' => '🌙'],
    ];

    protected $fillable = [
        'folio',
        'empresa_id',
        'tecnico_id',
        'cliente_id',
        'tipo_servicio',
        'fecha_hora',
        'descripcion',
        'problema_reportado',
        'prioridad',
        'estado',
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
        // Casts para campos públicos
        'es_publica' => 'boolean',
        'dias_preferidos' => 'array',
        'fecha_confirmada' => 'date',
        'whatsapp_recepcion_enviado' => 'boolean',
        'whatsapp_confirmacion_enviado' => 'boolean',
        'whatsapp_recepcion_at' => 'datetime',
        'whatsapp_confirmacion_at' => 'datetime',
        'fotos_finales' => 'array',
        'fecha_firma' => 'datetime',
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
     * Ticket de soporte origen (si aplica)
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Ítems de la cita (productos y servicios)
     */
    public function items()
    {
        return $this->hasMany(CitaItem::class);
    }

    /**
     * Serie de producto asociada a esta cita de garantía (si aplica)
     * Esta relación se usa cuando la cita fue creada desde el módulo de garantías
     */
    public function productoSerieGarantia()
    {
        return $this->hasOne(ProductoSerie::class, 'cita_id');
    }

    public function venta()
    {
        return $this->hasOne(Venta::class);
    }

    /**
     * Productos en la cita (relación polimórfica a través de cita_items)
     */
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

    /**
     * Servicios en la cita (relación polimórfica a través de cita_items)
     */
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
            self::ESTADO_PENDIENTE => [self::ESTADO_PROGRAMADO, self::ESTADO_EN_PROCESO, self::ESTADO_CANCELADO],
            self::ESTADO_PROGRAMADO => [self::ESTADO_EN_PROCESO, self::ESTADO_REPROGRAMADO, self::ESTADO_CANCELADO],
            self::ESTADO_EN_PROCESO => [self::ESTADO_COMPLETADO, self::ESTADO_CANCELADO],
            self::ESTADO_COMPLETADO => [], // No se puede cambiar de completado
            self::ESTADO_CANCELADO => [self::ESTADO_PENDIENTE], // Solo se puede reactivar
            self::ESTADO_REPROGRAMADO => [self::ESTADO_PROGRAMADO, self::ESTADO_EN_PROCESO, self::ESTADO_CANCELADO],
            default => []
        };
    }

    protected $appends = ['tiempo_servicio_formateado'];



    /**
     * Cambiar estado de la cita
     */
    public function cambiarEstado(string $nuevoEstado): bool
    {
        $estadosValidos = $this->getSiguientesEstadosValidos();

        if (!in_array($nuevoEstado, $estadosValidos)) {
            return false;
        }

        // Lógica de Tiempos
        if ($nuevoEstado === self::ESTADO_EN_PROCESO) {
            if (!$this->inicio_servicio) {
                $this->inicio_servicio = now();
            }
        } elseif ($nuevoEstado === self::ESTADO_COMPLETADO) {
            $this->fin_servicio = now();
            if ($this->inicio_servicio) {
                $inicio = Carbon::parse($this->inicio_servicio);
                $fin = Carbon::parse($this->fin_servicio);
                $this->tiempo_servicio = (int) $inicio->diffInMinutes($fin);
            }
        }

        $this->estado = $nuevoEstado;
        $this->save();

        return true;
    }

    /**
     * Verificar si hay conflicto de horario
     */
    public static function hayConflictoHorario(int $tecnicoId, string $fechaHora, ?int $excludeId = null): bool
    {
        $fecha = Carbon::parse($fechaHora);
        $query = self::where('tecnico_id', $tecnicoId)
            ->where('fecha_hora', $fecha)
            ->where('estado', '!=', self::ESTADO_CANCELADO);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
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
        $partes = array_filter([
            $this->direccion_calle,
            $this->direccion_colonia,
            $this->direccion_cp ? "C.P. {$this->direccion_cp}" : null,
        ]);

        return implode(', ', $partes);
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
        $horaFin = $hora->copy()->addHour();

        return $hora->format('h:i A') . ' - ' . $horaFin->format('h:i A');
    }

    /**
     * Buscar cita por link de seguimiento
     */
    public static function findByLink(string $uuid): ?self
    {
        return self::where('link_seguimiento', $uuid)->first();
    }
}

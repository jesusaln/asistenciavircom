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

                // 1. Intentar obtener de la póliza
                if ($ticket->poliza_id) {
                    $poliza = PolizaServicio::find($ticket->poliza_id);
                    if ($poliza && $poliza->sla_horas_respuesta) {
                        $horasSla = $poliza->sla_horas_respuesta;
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
                    $ticket->fecha_limite = now()->addHours($horasSla);
                }
            }
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
     * NOTA: Este método solo debe llamarse UNA VEZ por ticket.
     */
    public function registrarConsumoEnPoliza(?float $horas = null): void
    {
        $horasARegistrar = $horas ?? (float) ($this->horas_trabajadas ?? 0);

        if ($horasARegistrar <= 0 || !$this->poliza_id) {
            return;
        }

        $poliza = $this->poliza;
        if ($poliza && $poliza->isActiva()) {
            $poliza->registrarConsumoHoras($horasARegistrar, $this);
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class BitacoraActividad extends Model
{
    use HasFactory;

    protected $table = 'bitacora_actividades';

    protected $fillable = [
        'user_id',
        'asignado_id',
        'cliente_id',
        'titulo',
        'descripcion',
        'fecha',
        'hora',
        'inicio_at',
        'fin_at',
        'tipo',
        'estado',
        'prioridad',
        'ubicacion',
        'adjuntos',
        'es_facturable',
        'costo_mxn',
    ];

    protected $casts = [
        'fecha'         => 'date',
        'hora'          => 'string',
        'inicio_at'     => 'datetime',
        'fin_at'        => 'datetime',
        'adjuntos'      => 'array',
        'es_facturable' => 'boolean',
        'costo_mxn'     => 'decimal:2',
    ];

    protected $appends = ['fecha_fmt', 'hora_fmt'];

    // ===== Relaciones =====
    
    /**
     * Usuario que creó la actividad
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Usuario asignado a la actividad (puede ser diferente al creador)
     */
    public function asignado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    // ===== Accessors =====
    public function getDuracionMinutosAttribute(): ?int
    {
        if (!$this->inicio_at || !$this->fin_at) return null;
        return $this->inicio_at->diffInMinutes($this->fin_at);
    }

    public function getFechaFmtAttribute(): ?string
    {
        if (!$this->fecha) return null;
        return $this->fecha->translatedFormat('d M Y');
    }

    public function getHoraFmtAttribute(): ?string
    {
        if ($this->inicio_at) {
            return $this->inicio_at
                ->copy()
                ->setTimezone('America/Hermosillo')
                ->format('H:i');
        }
        return $this->hora ?: null;
    }

    // ===== Scopes (CORREGIDOS) =====

    public function scopeDeUsuario(Builder $q, $userId): Builder
    {
        return $q->when($userId, fn($qq) => $qq->where('user_id', $userId));
    }

    public function scopeDeCliente(Builder $q, $clienteId): Builder
    {
        return $q->when($clienteId, fn($qq) => $qq->where('cliente_id', $clienteId));
    }

    public function scopeRangoFechas(Builder $q, $desde, $hasta): Builder
    {
        if ($desde) {
            try {
                $desde = Carbon::parse($desde)->toDateString();
                $q->whereDate('fecha', '>=', $desde);
            } catch (\Exception $e) {
                // Ignorar filtro si la fecha es inválida
            }
        }

        if ($hasta) {
            try {
                $hasta = Carbon::parse($hasta)->toDateString();
                $q->whereDate('fecha', '<=', $hasta);
            } catch (\Exception $e) {
                // Ignorar filtro si la fecha es inválida
            }
        }

        return $q;
    }

    public function scopeBuscar(Builder $q, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $q;
        }

        $like = '%' . $term . '%';

        return $q->where(function (Builder $w) use ($like) {
            $w->where('titulo', 'like', $like)
                ->orWhere('descripcion', 'like', $like)
                ->orWhere('ubicacion', 'like', $like)
                ->orWhereHas('cliente', fn($cq) => $cq->where('nombre_razon_social', 'like', $like))
                ->orWhereHas('usuario', fn($uq) => $uq->where('name', 'like', $like));
        });
    }

    public function scopeSoloHoyOMantenerEnProceso(Builder $q, string $tz = 'America/Hermosillo'): Builder
    {
        try {
            $hoy = Carbon::now($tz)->toDateString();
        } catch (\Exception $e) {
            $hoy = now()->toDateString(); // fallback
        }

        return $q->where(function (Builder $w) use ($hoy) {
            $w->whereDate('fecha', $hoy)
                ->orWhere('estado', 'en_proceso'); // ← SOLO el valor que existe en tu BD
        });
    }

    // Opcionales, si los usas en otros lugares:
    public function scopeSinCancelados(Builder $q): Builder
    {
        return $q->where('estado', '!=', 'cancelado');
    }

    public function scopeSinCompletados(Builder $q): Builder
    {
        return $q->where('estado', '!=', 'completado');
    }

    /**
     * Scope para tareas pendientes asignadas a un usuario específico
     */
    public function scopePendientesParaUsuario(Builder $q, $userId): Builder
    {
        return $q->where('asignado_id', $userId)
            ->whereIn('estado', ['pendiente', 'en_proceso'])
            ->orderByRaw("CASE WHEN estado = 'en_proceso' THEN 0 ELSE 1 END")
            ->orderBy('prioridad', 'asc')
            ->orderBy('fecha', 'asc');
    }

    /**
     * Scope para tareas asignadas a un usuario
     */
    public function scopeAsignadasA(Builder $q, $userId): Builder
    {
        return $q->where('asignado_id', $userId);
    }
}

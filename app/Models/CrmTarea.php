<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmTarea extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $table = 'crm_tareas';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'prospecto_id',
        'titulo',
        'descripcion',
        'tipo',
        'prioridad',
        'fecha_limite',
        'completada_at',
        'notas_resultado',
        'created_by',
    ];

    protected $casts = [
        'fecha_limite' => 'date',
        'completada_at' => 'datetime',
    ];

    public const TIPOS = [
        'llamar' => 'Llamar',
        'enviar_cotizacion' => 'Enviar Cotización',
        'seguimiento' => 'Seguimiento',
        'visita' => 'Visita',
        'reunion' => 'Reunión',
        'otro' => 'Otro',
    ];

    public const PRIORIDADES = [
        'alta' => 'Alta',
        'media' => 'Media',
        'baja' => 'Baja',
    ];

    // Relaciones
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function prospecto(): BelongsTo
    {
        return $this->belongsTo(CrmProspecto::class, 'prospecto_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->whereNull('completada_at');
    }

    public function scopeCompletadas($query)
    {
        return $query->whereNotNull('completada_at');
    }

    public function scopeVencidas($query)
    {
        return $query->whereNull('completada_at')
            ->where('fecha_limite', '<', now()->startOfDay());
    }

    public function scopeParaHoy($query)
    {
        return $query->whereNull('completada_at')
            ->whereDate('fecha_limite', today());
    }

    public function scopeDelUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Helpers
    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function getPrioridadLabelAttribute(): string
    {
        return self::PRIORIDADES[$this->prioridad] ?? $this->prioridad;
    }

    public function getEstaVencidaAttribute(): bool
    {
        return !$this->completada_at && $this->fecha_limite < now()->startOfDay();
    }

    public function getEstaCompletadaAttribute(): bool
    {
        return $this->completada_at !== null;
    }

    public function completar(?string $notas = null): void
    {
        $this->update([
            'completada_at' => now(),
            'notas_resultado' => $notas,
        ]);
    }

    public function getTipoIconAttribute(): string
    {
        return match($this->tipo) {
            'llamar' => 'phone',
            'enviar_cotizacion' => 'file-invoice-dollar',
            'seguimiento' => 'redo',
            'visita' => 'building',
            'reunion' => 'users',
            default => 'tasks',
        };
    }

    public function getPrioridadColorAttribute(): string
    {
        return match($this->prioridad) {
            'alta' => 'red',
            'media' => 'yellow',
            'baja' => 'green',
            default => 'gray',
        };
    }
}

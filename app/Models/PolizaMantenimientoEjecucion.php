<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PolizaMantenimientoEjecucion extends Model
{
    use HasFactory;

    protected $table = 'poliza_mantenimiento_ejecuciones';

    protected $fillable = [
        'mantenimiento_id',
        'tecnico_id',
        'fecha_programada',
        'fecha_original',
        'reprogramado_count',
        'notas_reprogramacion',
        'fecha_ejecucion',
        'estado', // pendiente, completado, cancelado, vencido
        'resultado', // ok, alerta, critico
        'notas_tecnico',
        'evidencia',
        'notificado_cliente',
    ];

    protected $casts = [
        'fecha_programada' => 'datetime',
        'fecha_original' => 'datetime',
        'fecha_ejecucion' => 'datetime',
        'evidencia' => 'array',
        'notificado_cliente' => 'boolean',
    ];

    /**
     * Relación con la definición de la tarea.
     */
    public function mantenimiento(): BelongsTo
    {
        return $this->belongsTo(PolizaMantenimiento::class, 'mantenimiento_id');
    }

    /**
     * Técnico asignado.
     */
    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    /**
     * Scope para buscar tareas pendientes.
     */
    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['pendiente', 'vencido']);
    }

    /**
     * Scope para buscar tareas de hoy.
     */
    public function scopeParaHoy($query)
    {
        return $query->whereDate('fecha_programada', today());
    }

    /**
     * Lógica para reprogramar la tarea.
     */
    public function reprogramar(Carbon $nuevaFecha, string $motivo, ?User $usuario = null): void
    {
        $notas = $this->notas_reprogramacion ?? '';
        $notas .= "\n[" . now()->format('Y-m-d H:i') . "] Reprogramado de " .
            $this->fecha_programada->format('Y-m-d H:i') . " a " .
            $nuevaFecha->format('Y-m-d H:i') . ". Motivo: $motivo" .
            ($usuario ? " (por {$usuario->name})" : "");

        $this->update([
            'fecha_programada' => $nuevaFecha,
            'reprogramado_count' => $this->reprogramado_count + 1,
            'notas_reprogramacion' => trim($notas),
            'estado' => $nuevaFecha->isPast() ? 'vencido' : 'pendiente',
        ]);

        // TODO: Enviar notificación de nueva fecha (Fase 4)
    }

    /**
     * Helper para verificar si está vencido.
     */
    public function getEstaVencidoAttribute(): bool
    {
        return $this->estado === 'pendiente' && $this->fecha_programada->endOfDay()->isPast();
    }
}

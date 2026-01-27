<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PolizaMantenimiento extends Model
{
    use HasFactory;

    protected $table = 'poliza_mantenimientos';

    protected $fillable = [
        'poliza_id',
        'tipo',
        'nombre',
        'descripcion',
        'checklist', // Nuevo
        'frecuencia',
        'dia_preferido',
        'requiere_visita',
        'activo', // bool
        'ultima_generacion_at',
        'guia_tecnica_id',
    ];

    protected $casts = [
        'checklist' => 'array',
        'activo' => 'boolean',
        'requiere_visita' => 'boolean',
        'ultima_generacion_at' => 'datetime',
    ];

    /**
     * Relación con la póliza padre.
     */
    public function poliza(): BelongsTo
    {
        return $this->belongsTo(PolizaServicio::class, 'poliza_id');
    }

    public function guiaTecnica(): BelongsTo
    {
        return $this->belongsTo(GuiaTecnica::class, 'guia_tecnica_id');
    }

    /**
     * Relación con las ejecuciones (historial y pendientes).
     */
    public function ejecuciones(): HasMany
    {
        return $this->hasMany(PolizaMantenimientoEjecucion::class, 'mantenimiento_id');
    }

    /**
     * Obtener el estado legible de la frecuencia.
     */
    public function getFrecuenciaInfoAttribute(): string
    {
        return match ($this->frecuencia) {
            'semanal' => 'Cada semana',
            'quincenal' => 'Cada 15 días',
            'mensual' => 'Cada mes',
            'bimestral' => 'Cada 2 meses',
            'trimestral' => 'Cada 3 meses',
            'semestral' => 'Cada 6 meses',
            default => $this->frecuencia,
        };
    }
}

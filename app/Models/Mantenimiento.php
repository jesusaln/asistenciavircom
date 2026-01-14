<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Mantenimiento extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::creating(function (Mantenimiento $mantenimiento) {
            if (empty($mantenimiento->folio)) {
                try {
                    $mantenimiento->folio = app(\App\Services\Folio\FolioService::class)->getNextFolio('mantenimiento');
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error generating folio for mantenimiento: ' . $e->getMessage());
                }
            }
        });
    }

    protected $fillable = [
        'folio',
        'carro_id',
        'tipo',
        'fecha',
        'proximo_mantenimiento',
        'descripcion',
        'notas',
        'costo',
        'estado',
        'kilometraje_actual',
        'prioridad',
        'dias_anticipacion_alerta',
        'requiere_aprobacion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'proximo_mantenimiento' => 'date',
        'costo' => 'decimal:2',
        'kilometraje_actual' => 'integer',
        'dias_anticipacion_alerta' => 'integer',
        'requiere_aprobacion' => 'boolean',
    ];

    // Estados del mantenimiento
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_EN_PROCESO = 'en_proceso';
    const ESTADO_COMPLETADO = 'completado';

    // Prioridades
    const PRIORIDAD_BAJA = 'baja';
    const PRIORIDAD_MEDIA = 'media';
    const PRIORIDAD_ALTA = 'alta';
    const PRIORIDAD_CRITICA = 'critica';

    // Tipos de mantenimiento
    const TIPOS = [
        'Cambio de aceite',
        'Revisión periódica',
        'Servicio de frenos',
        'Servicio de llantas',
        'Servicio de batería',
        'Servicio de motor',
        'Revisión de luces',
        'Alineación y balanceo',
        'Cambio de filtros',
        'Revisión de transmisión',
        'Otro servicio'
    ];

    /**
     * Relación con el modelo Carro
     */
    public function carro(): BelongsTo
    {
        return $this->belongsTo(Carro::class);
    }

    /**
     * Relación con el técnico (User)
     */
    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopeEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para filtrar por tipo
     */
    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Scope para filtrar por carro
     */
    public function scopeCarro($query, $carroId)
    {
        return $query->where('carro_id', $carroId);
    }

    /**
     * Scope para mantenimientos activos (no completados)
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', '!=', self::ESTADO_COMPLETADO);
    }

    /**
     * Scope para mantenimientos próximos a vencer
     */
    public function scopeProximosAVencer($query, $dias = 30)
    {
        return $query->where('proximo_mantenimiento', '<=', now()->addDays($dias))
            ->where('estado', '!=', self::ESTADO_COMPLETADO);
    }

    /**
     * Scope para mantenimientos vencidos
     */
    public function scopeVencidos($query)
    {
        return $query->where('proximo_mantenimiento', '<', now())
            ->where('estado', '!=', self::ESTADO_COMPLETADO);
    }

    /**
     * Accessor para obtener días restantes
     */
    public function getDiasRestantesAttribute()
    {
        if (!$this->proximo_mantenimiento) {
            return null;
        }

        return now()->diffInDays($this->proximo_mantenimiento, false);
    }

    /**
     * Accessor para obtener el estado formateado
     */
    public function getEstadoFormateadoAttribute()
    {
        return match ($this->estado) {
            self::ESTADO_PENDIENTE => ['label' => 'Pendiente', 'color' => 'bg-yellow-100 text-yellow-800'],
            self::ESTADO_EN_PROCESO => ['label' => 'En Proceso', 'color' => 'bg-blue-100 text-blue-800'],
            self::ESTADO_COMPLETADO => ['label' => 'Completado', 'color' => 'bg-green-100 text-green-800'],
            default => ['label' => 'Desconocido', 'color' => 'bg-gray-100 text-gray-800']
        };
    }

    /**
     * Accessor para obtener la prioridad formateada
     */
    public function getPrioridadFormateadaAttribute()
    {
        return match ($this->prioridad) {
            self::PRIORIDAD_BAJA => ['label' => 'Baja', 'color' => 'bg-green-100 text-green-800'],
            self::PRIORIDAD_MEDIA => ['label' => 'Media', 'color' => 'bg-blue-100 text-blue-800'],
            self::PRIORIDAD_ALTA => ['label' => 'Alta', 'color' => 'bg-orange-100 text-orange-800'],
            self::PRIORIDAD_CRITICA => ['label' => 'Crítica', 'color' => 'bg-red-100 text-red-800'],
            default => ['label' => 'Media', 'color' => 'bg-blue-100 text-blue-800']
        };
    }

    /**
     * Accessor para formatear costo
     */
    public function getCostoFormateadoAttribute()
    {
        return '$' . number_format($this->costo ?? 0, 2);
    }

    /**
     * Verificar si el mantenimiento requiere alerta
     */
    public function getRequiereAlertaAttribute()
    {
        if ($this->estado === self::ESTADO_COMPLETADO) {
            return false;
        }

        if (!$this->proximo_mantenimiento) {
            return false;
        }

        $diasRestantes = $this->dias_restantes;
        $diasAnticipacion = $this->dias_anticipacion_alerta ?? 30;

        return $diasRestantes !== null && $diasRestantes <= $diasAnticipacion;
    }

    /**
     * Marcar como completado
     */
    public function marcarCompletado($fechaCompletado = null, $notas = null)
    {
        $this->update([
            'estado' => self::ESTADO_COMPLETADO,
            'fecha' => $fechaCompletado ?? now()->format('Y-m-d'),
            'notas' => $notas ? ($this->notas ? $this->notas . ' | ' . $notas : $notas) : $this->notas
        ]);
    }

    /**
     * Cambiar estado
     */
    public function cambiarEstado($nuevoEstado)
    {
        $estadosValidos = [self::ESTADO_PENDIENTE, self::ESTADO_EN_PROCESO, self::ESTADO_COMPLETADO];

        if (!in_array($nuevoEstado, $estadosValidos)) {
            throw new \InvalidArgumentException('Estado no válido');
        }

        $this->update(['estado' => $nuevoEstado]);
    }
}

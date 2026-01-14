<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SatDescargaMasiva extends Model
{
    use HasFactory;

    protected $table = 'sat_descargas_masivas';

    protected $fillable = [
        'direccion',
        'fecha_inicio',
        'fecha_fin',
        'status',
        'request_id',
        'paquetes',
        'total_cfdis',
        'inserted_cfdis',
        'duplicate_cfdis',
        'error_cfdis',
        'last_error',
        'errors',
        'started_at',
        'finished_at',
        'last_checked_at',
        'created_by',
        // Nuevos campos de reintentos
        'retry_count',
        'max_retries',
        'next_retry_at',
        'limite_tipo',
        'mensaje_usuario',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'paquetes' => 'array',
        'errors' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'next_retry_at' => 'datetime',
    ];

    // Constantes para tipos de límite
    const LIMITE_PENDIENTES = 'pendientes';
    const LIMITE_POR_VIDA = 'por_vida';

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(SatDescargaDetalle::class, 'sat_descarga_masiva_id');
    }

    /**
     * Genera un mensaje amigable basado en el error técnico
     */
    public function getMensajeAmigableAttribute(): string
    {
        if ($this->mensaje_usuario) {
            return $this->mensaje_usuario;
        }

        $error = $this->last_error ?? '';

        // Detectar tipo de límite
        if (str_contains($error, '5002') || str_contains($error, 'pendientes')) {
            return '⏳ El SAT tiene demasiadas solicitudes pendientes. El sistema reintentará automáticamente en unas horas.';
        }

        if (str_contains($error, 'por vida') || str_contains($error, 'lifetime')) {
            return '🚫 Se alcanzó el límite de solicitudes del SAT para este RFC. Intenta mañana o usa períodos más pequeños.';
        }

        if (str_contains($error, 'FIEL')) {
            return '🔐 Error con el certificado FIEL. Verifica que esté vigente en Configuración.';
        }

        if (str_contains($error, 'conexión') || str_contains($error, 'timeout')) {
            return '🌐 Error de conexión con el SAT. Se reintentará automáticamente.';
        }

        return $error ?: 'Error desconocido';
    }

    /**
     * Verifica si puede reintentar (no ha excedido límite)
     */
    public function puedeReintentar(): bool
    {
        return $this->retry_count < ($this->max_retries ?? 3);
    }

    /**
     * Verifica si está pausado por límite del SAT
     */
    public function estaPausadoPorLimite(): bool
    {
        return $this->limite_tipo !== null && !$this->puedeReintentar();
    }

    /**
     * Obtiene el tiempo restante para el próximo reintento
     */
    public function getTiempoParaReintentoAttribute(): ?string
    {
        if (!$this->next_retry_at || $this->next_retry_at->isPast()) {
            return null;
        }

        return $this->next_retry_at->diffForHumans();
    }
}

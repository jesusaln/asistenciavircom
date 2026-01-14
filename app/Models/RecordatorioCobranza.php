<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecordatorioCobranza extends Model
{
    use HasFactory;

    protected $table = 'recordatorios_cobranza';

    protected $fillable = [
        'cuenta_por_cobrar_id',
        'tipo_recordatorio',
        'fecha_envio',
        'fecha_proximo_recordatorio',
        'enviado',
        'numero_intento',
        'error_message',
    ];

    protected $casts = [
        'fecha_envio' => 'datetime',
        'fecha_proximo_recordatorio' => 'datetime',
        'enviado' => 'boolean',
        'numero_intento' => 'integer',
    ];

    /**
     * @return BelongsTo<CuentasPorCobrar, RecordatorioCobranza>
     */
    public function cuentaPorCobrar(): BelongsTo
    {
        return $this->belongsTo(CuentasPorCobrar::class, 'cuenta_por_cobrar_id');
    }

    /**
     * Marcar como enviado
     */
    public function marcarComoEnviado(?string $error = null): void
    {
        $this->enviado = true;
        if ($error) {
            $this->error_message = $error;
        }
        $this->save();
    }

    /**
     * Marcar como error
     */
    public function marcarComoError(string $error): void
    {
        $this->enviado = false;
        $this->error_message = $error;
        $this->save();
    }

    /**
     * Obtener etiqueta del tipo de recordatorio
     */
    public function getTipoLabelAttribute(): string
    {
        return match ($this->tipo_recordatorio) {
            'vencimiento' => 'Vencimiento',
            'dia_siguiente' => 'Día siguiente',
            'cada_3_dias' => 'Cada 3 días',
            default => 'Desconocido',
        };
    }

    /**
     * Scope para recordatorios pendientes de envío
     */
    public function scopePendientes($query)
    {
        return $query->where('enviado', false)
            ->whereDate('fecha_envio', '<=', now());
    }

    /**
     * Scope para recordatorios por tipo
     */
    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo_recordatorio', $tipo);
    }
}

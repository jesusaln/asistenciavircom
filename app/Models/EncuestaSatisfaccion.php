<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToEmpresa;
use Carbon\Carbon;

class EncuestaSatisfaccion extends Model
{
    use BelongsToEmpresa;

    protected $table = 'encuesta_satisfaccion';

    protected $fillable = [
        'empresa_id', 'cliente_id', 'cita_id', 'wa_id', 'nombre_cliente_snapshot',
        'folio', 'estado', 'pregunta_actual', 'respuestas',
        'calificacion_global', 'nps_score',
        'codigo_promocional', 'descuento_porcentaje', 'servicio_aplicable',
        'codigo_expires_at', 'codigo_usado', 'codigo_usado_at', 'codigo_usado_cita_id',
        'programada_para', 'enviada_at', 'primera_respuesta_at', 'completada_at',
        'intentos_envio', 'ultimo_error_envio',
        'recordatorios_enviados', 'proximo_recordatorio_at',
    ];

    protected $casts = [
        'respuestas' => 'array',
        'programada_para' => 'datetime',
        'enviada_at' => 'datetime',
        'primera_respuesta_at' => 'datetime',
        'completada_at' => 'datetime',
        'codigo_expires_at' => 'datetime',
        'codigo_usado_at' => 'datetime',
        'proximo_recordatorio_at' => 'datetime',
        'codigo_usado' => 'boolean',
        'calificacion_global' => 'float',
    ];

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_EN_PROGRESO = 'en_progreso';
    public const ESTADO_COMPLETADA = 'completada';
    public const ESTADO_EXPIRADA = 'expirada';
    public const ESTADO_CANCELADA = 'cancelada';
    public const ESTADO_FALLIDA_ENVIO = 'fallida_envio';

    public const TOTAL_PREGUNTAS = 2;
    public const DIAS_VALIDEZ_CODIGO = 90;

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function cita()
    {
        return $this->belongsTo(Cita::class, 'cita_id');
    }

    public function citaDondeSeUso()
    {
        return $this->belongsTo(Cita::class, 'codigo_usado_cita_id');
    }

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Empresa::class, 'empresa_id');
    }

    public function scopePendientes($q)
    {
        return $q->whereIn('estado', [self::ESTADO_PENDIENTE, self::ESTADO_EN_PROGRESO]);
    }

    public function scopeParaEnviar($q)
    {
        return $q->where('estado', self::ESTADO_PENDIENTE)
            ->whereNotNull('programada_para')
            ->where('programada_para', '<=', now());
    }

    public function getTiempoTranscurridoSegundosAttribute(): ?int
    {
        if (! $this->primera_respuesta_at || ! $this->enviada_at) {
            return null;
        }
        return $this->enviada_at->diffInSeconds($this->primera_respuesta_at);
    }

    public function getNpsCategoriaAttribute(): ?string
    {
        if ($this->nps_score === null) {
            return null;
        }
        return match (true) {
            $this->nps_score >= 9 => 'promotor',
            $this->nps_score >= 7 => 'neutral',
            default => 'detractor',
        };
    }

    public function codigoEsValido(): bool
    {
        return $this->codigo_promocional
            && ! $this->codigo_usado
            && $this->codigo_expires_at
            && $this->codigo_expires_at->isFuture();
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\Ticket;
use App\Models\Cita;

class PolizaConsumo extends Model
{
    use HasFactory;

    protected $table = 'poliza_consumos';

    protected $fillable = [
        'poliza_id',
        'tipo',
        'consumible_type',
        'consumible_id',
        'cantidad',
        'valor_unitario',
        'costo_interno',
        'ahorro',
        'descripcion',
        'registrado_por',
        'tecnico_id',
        'fecha_consumo',
    ];

    protected $casts = [
        'valor_unitario' => 'decimal:2',
        'costo_interno' => 'decimal:2',
        'ahorro' => 'decimal:2',
        'fecha_consumo' => 'datetime',
    ];

    /**
     * Tipos de consumo disponibles.
     */
    const TIPO_TICKET = 'ticket';
    const TIPO_VISITA = 'visita';
    const TIPO_HORA = 'hora';

    /**
     * Relación con la póliza.
     */
    public function poliza(): BelongsTo
    {
        return $this->belongsTo(PolizaServicio::class, 'poliza_id');
    }

    /**
     * Relación polimórfica con el consumible (Ticket, Cita, etc.).
     */
    public function consumible(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Usuario que registró el consumo.
     */
    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    /**
     * Técnico que realizó el servicio.
     */
    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    /**
     * Scope para filtrar por tipo.
     */
    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Scope para filtrar por mes actual.
     */
    public function scopeMesActual($query)
    {
        return $query->whereMonth('fecha_consumo', now()->month)
            ->whereYear('fecha_consumo', now()->year);
    }

    /**
     * Scope para filtrar por póliza.
     */
    public function scopeDePoliza($query, int $polizaId)
    {
        return $query->where('poliza_id', $polizaId);
    }

    /**
     * Helper para crear un registro de consumo.
     */
    public static function registrar(
        PolizaServicio $poliza,
        string $tipo,
        Model $consumible,
        int $cantidad = 1,
        ?string $descripcion = null
    ): self {
        $valorUnitario = match ($tipo) {
            self::TIPO_TICKET => 150,
            self::TIPO_VISITA => $poliza->costo_visita_sitio_extra ?? 650,
            self::TIPO_HORA => $poliza->costo_hora_excedente ?? 350,
            default => 0,
        };

        // 1. Determinar el técnico responsable para el costo real
        $tecnico = null;
        if (get_class($consumible) === Ticket::class) {
            $tecnico = $consumible->technician;
        } elseif (get_class($consumible) === Cita::class) {
            $tecnico = $consumible->tecnico;
        }

        // 2. Calcular costo interno
        $costoInterno = 0;
        if ($tecnico && $tecnico->costo_hora_interno > 0) {
            $horas = 1;
            if ($tipo === self::TIPO_HORA) {
                $horas = (float) $cantidad;
            }
            $costoInterno = $tecnico->costo_hora_interno * $horas;
        }

        $consumo = self::create([
            'poliza_id' => $poliza->id,
            'tipo' => $tipo,
            'consumible_type' => get_class($consumible),
            'consumible_id' => $consumible->id,
            'cantidad' => $cantidad,
            'valor_unitario' => $valorUnitario,
            'costo_interno' => $costoInterno,
            'ahorro' => $valorUnitario * $cantidad,
            'descripcion' => $descripcion ?? self::generarDescripcion($tipo, $consumible),
            'registrado_por' => auth()->guard('client')->check() ? null : auth()->id(),
            'tecnico_id' => $tecnico?->id,
            'fecha_consumo' => now(),
        ]);

        // 3. Actualizar costo acumulado en la póliza
        $poliza->increment('costo_acumulado_tecnico', $costoInterno);

        // 4. Verificar rentabilidad proactivamente
        try {
            app(\App\Services\PolizaRentabilidadService::class)->checkHealthAndNotify($poliza);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error en checkHealthAndNotify: " . $e->getMessage());
        }

        // Enviar notificación al cliente
        try {
            if ($poliza->cliente) {
                $poliza->cliente->notify(new \App\Notifications\PolizaConsumoNotification($consumo));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error enviando notificación de consumo: " . $e->getMessage());
        }

        return $consumo;
    }

    /**
     * Generar descripción automática.
     */
    protected static function generarDescripcion(string $tipo, Model $consumible): string
    {
        return match ($tipo) {
            self::TIPO_TICKET => "Ticket #" . ($consumible->folio ?? $consumible->id) . ": " . ($consumible->titulo ?? 'Sin título'),
            self::TIPO_VISITA => "Cita #" . ($consumible->id) . " - " . ($consumible->tipo_servicio ?? 'Visita'),
            self::TIPO_HORA => "Servicio de " . ($consumible->duracion_horas ?? 1) . " hora(s)",
            default => "Consumo registrado",
        };
    }

    /**
     * Obtener el ícono según el tipo.
     */
    public function getIconoAttribute(): string
    {
        return match ($this->tipo) {
            self::TIPO_TICKET => '🎫',
            self::TIPO_VISITA => '🚗',
            self::TIPO_HORA => '⏱️',
            default => '📋',
        };
    }

    /**
     * Obtener etiqueta del tipo.
     */
    public function getTipoLabelAttribute(): string
    {
        return match ($this->tipo) {
            self::TIPO_TICKET => 'Ticket de Soporte',
            self::TIPO_VISITA => 'Visita en Sitio',
            self::TIPO_HORA => 'Hora de Servicio',
            default => 'Otro',
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
        'ahorro',
        'descripcion',
        'registrado_por',
        'fecha_consumo',
    ];

    protected $casts = [
        'valor_unitario' => 'decimal:2',
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
        $valorUnitario = match($tipo) {
            self::TIPO_TICKET => 150,
            self::TIPO_VISITA => $poliza->costo_visita_sitio_extra ?? 650,
            self::TIPO_HORA => $poliza->costo_hora_excedente ?? 350,
            default => 0,
        };

        return self::create([
            'poliza_id' => $poliza->id,
            'tipo' => $tipo,
            'consumible_type' => get_class($consumible),
            'consumible_id' => $consumible->id,
            'cantidad' => $cantidad,
            'valor_unitario' => $valorUnitario,
            'ahorro' => $valorUnitario * $cantidad,
            'descripcion' => $descripcion ?? self::generarDescripcion($tipo, $consumible),
            'registrado_por' => auth()->id(),
            'fecha_consumo' => now(),
        ]);
    }

    /**
     * Generar descripción automática.
     */
    protected static function generarDescripcion(string $tipo, Model $consumible): string
    {
        return match($tipo) {
            self::TIPO_TICKET => "Ticket #{$consumible->folio ?? $consumible->id}: " . ($consumible->titulo ?? 'Sin título'),
            self::TIPO_VISITA => "Cita #{$consumible->id} - " . ($consumible->tipo_servicio ?? 'Visita'),
            self::TIPO_HORA => "Servicio de " . ($consumible->duracion_horas ?? 1) . " hora(s)",
            default => "Consumo registrado",
        };
    }

    /**
     * Obtener el ícono según el tipo.
     */
    public function getIconoAttribute(): string
    {
        return match($this->tipo) {
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
        return match($this->tipo) {
            self::TIPO_TICKET => 'Ticket de Soporte',
            self::TIPO_VISITA => 'Visita en Sitio',
            self::TIPO_HORA => 'Hora de Servicio',
            default => 'Otro',
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleAsignacionMasiva extends Model
{
    use HasFactory;

    protected $table = 'detalle_asignaciones_masivas';

    protected $fillable = [
        'asignacion_masiva_id',
        'herramienta_id',
        'estado_individual',
        'fecha_asignacion_individual',
        'fecha_devolucion_individual',
        'observaciones_asignacion',
        'observaciones_devolucion',
        'estado_herramienta_entrega',
        'estado_herramienta_devolucion',
        'foto_estado_entrega',
        'foto_estado_devolucion'
    ];

    protected $casts = [
        'fecha_asignacion_individual' => 'datetime',
        'fecha_devolucion_individual' => 'datetime',
    ];

    // Estados individuales posibles
    const ESTADO_ASIGNADA = 'asignada';
    const ESTADO_DEVUELTA = 'devuelta';
    const ESTADO_PERDIDA = 'perdida';
    const ESTADO_DAÑADA = 'dañada';

    // Relaciones
    public function asignacionMasiva()
    {
        return $this->belongsTo(AsignacionMasiva::class, 'asignacion_masiva_id');
    }

    public function herramienta()
    {
        return $this->belongsTo(Herramienta::class);
    }

    // Scopes
    public function scopeAsignadas($query)
    {
        return $query->where('estado_individual', self::ESTADO_ASIGNADA);
    }

    public function scopeDevueltas($query)
    {
        return $query->where('estado_individual', self::ESTADO_DEVUELTA);
    }

    public function scopePerdidas($query)
    {
        return $query->where('estado_individual', self::ESTADO_PERDIDA);
    }

    public function scopeDañadas($query)
    {
        return $query->where('estado_individual', self::ESTADO_DAÑADA);
    }

    // Métodos auxiliares
    public function estaAsignada()
    {
        return $this->estado_individual === self::ESTADO_ASIGNADA;
    }

    public function estaDevuelta()
    {
        return $this->estado_individual === self::ESTADO_DEVUELTA;
    }

    public function estaPerdida()
    {
        return $this->estado_individual === self::ESTADO_PERDIDA;
    }

    public function estaDañada()
    {
        return $this->estado_individual === self::ESTADO_DAÑADA;
    }

    public function getDuracionEnDiasAttribute()
    {
        if (!$this->fecha_asignacion_individual) return 0;

        $fechaFin = $this->fecha_devolucion_individual ?? now();
        return $this->fecha_asignacion_individual->diffInDays($fechaFin);
    }

    public function getEstadoLabelAttribute()
    {
        return match($this->estado_individual) {
            self::ESTADO_ASIGNADA => 'Asignada',
            self::ESTADO_DEVUELTA => 'Devuelta',
            self::ESTADO_PERDIDA => 'Perdida',
            self::ESTADO_DAÑADA => 'Dañada',
            default => 'Sin Estado'
        };
    }

    public function getEstadoColorAttribute()
    {
        return match($this->estado_individual) {
            self::ESTADO_ASIGNADA => 'blue',
            self::ESTADO_DEVUELTA => 'green',
            self::ESTADO_PERDIDA => 'red',
            self::ESTADO_DAÑADA => 'orange',
            default => 'gray'
        };
    }

    // Marcar como devuelta
    public function marcarComoDevuelta($observaciones = null, $estadoHerramienta = null, $foto = null)
    {
        $this->update([
            'estado_individual' => self::ESTADO_DEVUELTA,
            'fecha_devolucion_individual' => now(),
            'observaciones_devolucion' => $observaciones,
            'estado_herramienta_devolucion' => $estadoHerramienta,
            'foto_estado_devolucion' => $foto
        ]);

        // Actualizar contador en asignación masiva
        $this->asignacionMasiva->increment('herramientas_devueltas');

        // Actualizar estado de la herramienta
        $this->herramienta->update([
            'estado' => Herramienta::ESTADO_DISPONIBLE,
            'tecnico_id' => null,
            'fecha_recepcion' => now()
        ]);
    }

    // Marcar como perdida
    public function marcarComoPerdida($observaciones = null)
    {
        $this->update([
            'estado_individual' => self::ESTADO_PERDIDA,
            'fecha_devolucion_individual' => now(),
            'observaciones_devolucion' => $observaciones
        ]);

        // Actualizar contador en asignación masiva
        $this->asignacionMasiva->increment('herramientas_devueltas');

        // Actualizar estado de la herramienta
        $this->herramienta->update([
            'estado' => Herramienta::ESTADO_PERDIDA,
            'tecnico_id' => null,
            'fecha_recepcion' => now()
        ]);
    }

    // Marcar como dañada
    public function marcarComoDañada($observaciones = null, $foto = null)
    {
        $this->update([
            'estado_individual' => self::ESTADO_DAÑADA,
            'fecha_devolucion_individual' => now(),
            'observaciones_devolucion' => $observaciones,
            'foto_estado_devolucion' => $foto
        ]);

        // Actualizar contador en asignación masiva
        $this->asignacionMasiva->increment('herramientas_devueltas');

        // Actualizar estado de la herramienta
        $this->herramienta->update([
            'estado' => Herramienta::ESTADO_MANTENIMIENTO,
            'tecnico_id' => null,
            'fecha_recepcion' => now()
        ]);
    }
}

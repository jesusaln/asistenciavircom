<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

class HistorialHerramienta extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'herramienta_id',
        'tecnico_id',
        'fecha_asignacion',
        'fecha_devolucion',
        'asignado_por',
        'recibido_por',
        'observaciones_asignacion',
        'observaciones_devolucion',
        'motivo_devolucion',
        'estado_herramienta_asignacion',
        'estado_herramienta_devolucion',
        'duracion_dias'
    ];

    protected $casts = [
        'fecha_asignacion' => 'datetime',
        'fecha_devolucion' => 'datetime',
        'duracion_dias' => 'integer',
    ];

    // Motivos de devolución
    const MOTIVO_DEVOLUCION_NORMAL = 'normal';
    const MOTIVO_DEVOLUCION_DESGASTE = 'desgaste';
    const MOTIVO_DEVOLUCION_PERDIDA = 'perdida';
    const MOTIVO_DEVOLUCION_DANIO = 'danio';
    const MOTIVO_DEVOLUCION_REEMPLAZO = 'reemplazo';

    // Estados de la herramienta
    const ESTADO_BUENO = 'bueno';
    const ESTADO_REGULAR = 'regular';
    const ESTADO_MALO = 'malo';
    const ESTADO_PERDIDO = 'perdido';
    const ESTADO_DANIADO = 'daniado';

    // Relaciones
    public function herramienta()
    {
        return $this->belongsTo(Herramienta::class);
    }

    public function tecnico()
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    public function asignadoPor()
    {
        return $this->belongsTo(User::class, 'asignado_por');
    }

    public function recibidoPor()
    {
        return $this->belongsTo(User::class, 'recibido_por');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->whereNull('fecha_devolucion');
    }

    public function scopeCompletados($query)
    {
        return $query->whereNotNull('fecha_devolucion');
    }

    public function scopePorTecnico($query, $tecnicoId)
    {
        return $query->where('tecnico_id', $tecnicoId);
    }

    public function scopePorHerramienta($query, $herramientaId)
    {
        return $query->where('herramienta_id', $herramientaId);
    }

    public function scopePorMotivoDevolucion($query, $motivo)
    {
        return $query->where('motivo_devolucion', $motivo);
    }

    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado_herramienta_devolucion', $estado);
    }

    // Métodos auxiliares
    public function estaActivo()
    {
        return $this->fecha_devolucion === null;
    }

    public function estaCompletado()
    {
        return $this->fecha_devolucion !== null;
    }

    public function getDuracionEnDiasAttribute()
    {
        if (!$this->fecha_asignacion)
            return 0;

        $fechaFin = $this->fecha_devolucion ?? now();
        return $this->fecha_asignacion->diffInDays($fechaFin);
    }

    public function getLabelMotivoDevolucionAttribute()
    {
        return match ($this->motivo_devolucion) {
            self::MOTIVO_DEVOLUCION_NORMAL => 'Devolución Normal',
            self::MOTIVO_DEVOLUCION_DESGASTE => 'Desgaste',
            self::MOTIVO_DEVOLUCION_PERDIDA => 'Pérdida',
            self::MOTIVO_DEVOLUCION_DANIO => 'Daño',
            self::MOTIVO_DEVOLUCION_REEMPLAZO => 'Reemplazo',
            default => 'Sin especificar'
        };
    }

    public function getColorMotivoDevolucionAttribute()
    {
        return match ($this->motivo_devolucion) {
            self::MOTIVO_DEVOLUCION_NORMAL => 'green',
            self::MOTIVO_DEVOLUCION_DESGASTE => 'yellow',
            self::MOTIVO_DEVOLUCION_PERDIDA => 'red',
            self::MOTIVO_DEVOLUCION_DANIO => 'orange',
            self::MOTIVO_DEVOLUCION_REEMPLAZO => 'blue',
            default => 'gray'
        };
    }

    public function getLabelEstadoAttribute()
    {
        return match ($this->estado_herramienta_devolucion) {
            self::ESTADO_BUENO => 'Bueno',
            self::ESTADO_REGULAR => 'Regular',
            self::ESTADO_MALO => 'Malo',
            self::ESTADO_PERDIDO => 'Perdido',
            self::ESTADO_DANIADO => 'Dañado',
            default => 'Sin evaluar'
        };
    }

    public function getColorEstadoAttribute()
    {
        return match ($this->estado_herramienta_devolucion) {
            self::ESTADO_BUENO => 'green',
            self::ESTADO_REGULAR => 'yellow',
            self::ESTADO_MALO => 'orange',
            self::ESTADO_PERDIDO => 'red',
            self::ESTADO_DANIADO => 'red',
            default => 'gray'
        };
    }

    public function fueDevueltoPorDesgaste()
    {
        return $this->motivo_devolucion === self::MOTIVO_DEVOLUCION_DESGASTE;
    }

    public function fueDevueltoPorDanio()
    {
        return $this->motivo_devolucion === self::MOTIVO_DEVOLUCION_DANIO;
    }

    public function fueDevueltoPorPerdida()
    {
        return $this->motivo_devolucion === self::MOTIVO_DEVOLUCION_PERDIDA;
    }

    public function necesitaReemplazo()
    {
        return in_array($this->motivo_devolucion, [
            self::MOTIVO_DEVOLUCION_DESGASTE,
            self::MOTIVO_DEVOLUCION_DANIO,
            self::MOTIVO_DEVOLUCION_PERDIDA
        ]);
    }

    // Obtener historial completo de una herramienta
    public static function getHistorialHerramienta($herramientaId)
    {
        return static::where('herramienta_id', $herramientaId)
            ->with(['tecnico', 'asignadoPor', 'recibidoPor'])
            ->orderBy('fecha_asignacion', 'desc')
            ->get();
    }

    // Obtener historial de un técnico
    public static function getHistorialTecnico($tecnicoId)
    {
        return static::where('tecnico_id', $tecnicoId)
            ->with(['herramienta', 'asignadoPor', 'recibidoPor'])
            ->orderBy('fecha_asignacion', 'desc')
            ->get();
    }

    // Calcular estadísticas de uso de una herramienta
    public static function getEstadisticasHerramienta($herramientaId)
    {
        $historial = static::where('herramienta_id', $herramientaId)->get();

        return [
            'total_asignaciones' => $historial->count(),
            'asignaciones_activas' => $historial->where('fecha_devolucion', null)->count(),
            'duracion_promedio_dias' => $historial->whereNotNull('fecha_devolucion')->avg('duracion_dias'),
            'devoluciones_por_desgaste' => $historial->where('motivo_devolucion', self::MOTIVO_DEVOLUCION_DESGASTE)->count(),
            'devoluciones_por_danio' => $historial->where('motivo_devolucion', self::MOTIVO_DEVOLUCION_DANIO)->count(),
            'devoluciones_por_perdida' => $historial->where('motivo_devolucion', self::MOTIVO_DEVOLUCION_PERDIDA)->count(),
        ];
    }
}

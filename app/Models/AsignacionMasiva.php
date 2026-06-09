<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class AsignacionMasiva extends Model
{
    use HasFactory;

    protected $table = 'asignaciones_masivas';

    protected $fillable = [
        'codigo_asignacion',
        'tecnico_id',
        'asignado_por',
        'fecha_asignacion',
        'fecha_devolucion_programada',
        'fecha_devolucion_real',
        'recibido_por',
        'estado',
        'observaciones_asignacion',
        'observaciones_devolucion',
        'herramientas_ids',
        'total_herramientas',
        'herramientas_devueltas',
        'proyecto_trabajo'
    ];

    protected $casts = [
        'fecha_asignacion' => 'datetime',
        'fecha_devolucion_programada' => 'datetime',
        'fecha_devolucion_real' => 'datetime',
        'herramientas_ids' => 'array',
        'total_herramientas' => 'integer',
        'herramientas_devueltas' => 'integer',
    ];

    // Estados posibles
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_ACTIVA = 'activa';
    const ESTADO_COMPLETADA = 'completada';
    const ESTADO_CANCELADA = 'cancelada';

    // Relaciones
    public function tecnico()
    {
        return $this->belongsTo(Tecnico::class);
    }

    public function asignadoPor()
    {
        return $this->belongsTo(User::class, 'asignado_por');
    }

    public function recibidoPor()
    {
        return $this->belongsTo(User::class, 'recibido_por');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleAsignacionMasiva::class, 'asignacion_masiva_id');
    }

    public function herramientas()
    {
        return $this->belongsToMany(Herramienta::class, 'detalle_asignaciones_masivas', 'asignacion_masiva_id', 'herramienta_id')
                    ->withPivot(['estado_individual', 'fecha_asignacion_individual', 'fecha_devolucion_individual', 'observaciones_asignacion', 'observaciones_devolucion'])
                    ->withTimestamps();
    }

    public function historialHerramientas()
    {
        return $this->hasMany(HistorialHerramienta::class, 'asignacion_masiva_id');
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVA);
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeCompletadas($query)
    {
        return $query->where('estado', self::ESTADO_COMPLETADA);
    }

    public function scopePorTecnico($query, $tecnicoId)
    {
        return $query->where('tecnico_id', $tecnicoId);
    }

    // Métodos auxiliares
    public function estaActiva()
    {
        return $this->estado === self::ESTADO_ACTIVA;
    }

    public function estaPendiente()
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    public function estaCompletada()
    {
        return $this->estado === self::ESTADO_COMPLETADA;
    }

    public function estaCancelada()
    {
        return $this->estado === self::ESTADO_CANCELADA;
    }

    public function getPorcentajeCompletadoAttribute()
    {
        if ($this->total_herramientas == 0) return 0;
        return round(($this->herramientas_devueltas / $this->total_herramientas) * 100, 2);
    }

    public function getHerramientasPendientesAttribute()
    {
        return $this->total_herramientas - $this->herramientas_devueltas;
    }

    public function getDuracionEnDiasAttribute()
    {
        if (!$this->fecha_asignacion) return 0;

        $fechaFin = $this->fecha_devolucion_real ?? now();
        return $this->fecha_asignacion->diffInDays($fechaFin);
    }

    public function getEstadoLabelAttribute()
    {
        return match($this->estado) {
            self::ESTADO_PENDIENTE => 'Pendiente',
            self::ESTADO_ACTIVA => 'Activa',
            self::ESTADO_COMPLETADA => 'Completada',
            self::ESTADO_CANCELADA => 'Cancelada',
            default => 'Sin Estado'
        };
    }

    public function getEstadoColorAttribute()
    {
        return match($this->estado) {
            self::ESTADO_PENDIENTE => 'yellow',
            self::ESTADO_ACTIVA => 'blue',
            self::ESTADO_COMPLETADA => 'green',
            self::ESTADO_CANCELADA => 'red',
            default => 'gray'
        };
    }

    // Generar código único para la asignación
    public static function generarCodigoAsignacion()
    {
        do {
            $codigo = 'AM-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        } while (self::where('codigo_asignacion', $codigo)->exists());

        return $codigo;
    }

    // Activar asignación masiva
    public function activar()
    {
        $this->update(['estado' => self::ESTADO_ACTIVA]);

        // Actualizar estado de herramientas a asignadas
        $herramientas = Herramienta::whereIn('id', $this->herramientas_ids)->get();
        foreach ($herramientas as $herramienta) {
            $herramienta->update([
                'estado' => Herramienta::ESTADO_ASIGNADA,
                'tecnico_id' => $this->tecnico_id,
                'fecha_asignacion' => now()
            ]);
        }
    }

    // Completar asignación masiva
    public function completar($observaciones = null)
    {
        $this->update([
            'estado' => self::ESTADO_COMPLETADA,
            'fecha_devolucion_real' => now(),
            'observaciones_devolucion' => $observaciones,
            'recibido_por' => Auth::id()
        ]);

        // Actualizar estado de herramientas a disponibles
        $herramientas = Herramienta::whereIn('id', $this->herramientas_ids)->get();
        foreach ($herramientas as $herramienta) {
            $herramienta->update([
                'estado' => Herramienta::ESTADO_DISPONIBLE,
                'tecnico_id' => null,
                'fecha_recepcion' => now()
            ]);
        }
    }

    // Cancelar asignación masiva
    public function cancelar($motivo = null)
    {
        $this->update([
            'estado' => self::ESTADO_CANCELADA,
            'observaciones_devolucion' => $motivo
        ]);

        // Si estaba activa, liberar herramientas
        if ($this->estaActiva()) {
            $herramientas = Herramienta::whereIn('id', $this->herramientas_ids)->get();
            foreach ($herramientas as $herramienta) {
                $herramienta->update([
                    'estado' => Herramienta::ESTADO_DISPONIBLE,
                    'tecnico_id' => null
                ]);
            }
        }
    }

    // Obtener estadísticas de la asignación
    public function getEstadisticasAttribute()
    {
        $detalles = $this->detalles;

        return [
            'total_herramientas' => $this->total_herramientas,
            'herramientas_asignadas' => $detalles->where('estado_individual', 'asignada')->count(),
            'herramientas_devueltas' => $detalles->where('estado_individual', 'devuelta')->count(),
            'herramientas_perdidas' => $detalles->where('estado_individual', 'perdida')->count(),
            'herramientas_dañadas' => $detalles->where('estado_individual', 'dañada')->count(),
            'porcentaje_completado' => $this->porcentaje_completado,
            'duracion_dias' => $this->duracion_en_dias
        ];
    }
}

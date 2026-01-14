<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResponsabilidadHerramienta extends Model
{
    use HasFactory;

    protected $table = 'responsabilidades_herramientas';

    protected $fillable = [
        'tecnico_id',
        'herramientas_asignadas',
        'total_herramientas',
        'valor_total_herramientas',
        'ultima_actualizacion',
        'tiene_herramientas_vencidas',
        'dias_promedio_uso'
    ];

    protected $casts = [
        'herramientas_asignadas' => 'array',
        'total_herramientas' => 'integer',
        'valor_total_herramientas' => 'decimal:2',
        'ultima_actualizacion' => 'datetime',
        'tiene_herramientas_vencidas' => 'boolean',
        'dias_promedio_uso' => 'integer',
    ];

    // Relaciones
    public function tecnico()
    {
        return $this->belongsTo(Tecnico::class);
    }

    public function herramientas()
    {
        return $this->belongsToMany(Herramienta::class, 'herramientas_asignadas', 'id', 'id');
    }

    // Scopes
    public function scopeConHerramientasVencidas($query)
    {
        return $query->where('tiene_herramientas_vencidas', true);
    }

    public function scopeConMasHerramientas($query, $cantidad)
    {
        return $query->where('total_herramientas', '>', $cantidad);
    }

    public function scopeConValorMayor($query, $valor)
    {
        return $query->where('valor_total_herramientas', '>', $valor);
    }

    // Métodos auxiliares
    public function tieneHerramientasVencidas()
    {
        return $this->tiene_herramientas_vencidas;
    }

    public function getHerramientasActivasAttribute()
    {
        if (!$this->herramientas_asignadas) return collect();

        return Herramienta::whereIn('id', $this->herramientas_asignadas)
                         ->where('estado', Herramienta::ESTADO_ASIGNADA)
                         ->get();
    }

    public function getPromedioValorPorHerramientaAttribute()
    {
        if ($this->total_herramientas == 0) return 0;
        return $this->valor_total_herramientas / $this->total_herramientas;
    }

    public function getHerramientasVencidasAttribute()
    {
        return $this->herramientas_activas->filter(function ($herramienta) {
            return $herramienta->necesitaMantenimiento() || $herramienta->vidaUtilProximaAVencer();
        });
    }

    public function getCantidadHerramientasVencidasAttribute()
    {
        return $this->herramientas_vencidas->count();
    }

    // Actualizar responsabilidad
    public function actualizarResponsabilidad()
    {
        $herramientasAsignadas = Herramienta::where('tecnico_id', $this->tecnico_id)
                                           ->where('estado', Herramienta::ESTADO_ASIGNADA)
                                           ->get();

        $herramientasIds = $herramientasAsignadas->pluck('id')->toArray();
        $valorTotal = $herramientasAsignadas->sum('costo_reemplazo') ?? 0;
        $tieneVencidas = $herramientasAsignadas->filter(function ($herramienta) {
            return $herramienta->necesitaMantenimiento() || $herramienta->vidaUtilProximaAVencer();
        })->count() > 0;

        // Calcular promedio de días de uso
        $historial = HistorialHerramienta::where('tecnico_id', $this->tecnico_id)
                                        ->whereNotNull('fecha_devolucion')
                                        ->get();
        $promedioUso = $historial->avg('duracion_dias') ?? 0;

        $this->update([
            'herramientas_asignadas' => $herramientasIds ?: [],
            'total_herramientas' => $herramientasAsignadas->count(),
            'valor_total_herramientas' => $valorTotal,
            'ultima_actualizacion' => now(),
            'tiene_herramientas_vencidas' => $tieneVencidas,
            'dias_promedio_uso' => round($promedioUso)
        ]);
    }

    // Crear o actualizar responsabilidad para un técnico
    public static function actualizarParaTecnico($tecnicoId)
    {
        $responsabilidad = self::firstOrCreate(
            ['tecnico_id' => $tecnicoId],
            [
                'herramientas_asignadas' => [],
                'total_herramientas' => 0,
                'valor_total_herramientas' => 0,
                'ultima_actualizacion' => now(),
                'tiene_herramientas_vencidas' => false,
                'dias_promedio_uso' => 0
            ]
        );
        $responsabilidad->actualizarResponsabilidad();
        return $responsabilidad;
    }

    // Obtener resumen de responsabilidades
    public static function getResumenGeneral()
    {
        $responsabilidades = self::with('tecnico')->get();

        return [
            'total_tecnicos' => $responsabilidades->count(),
            'tecnicos_con_herramientas' => $responsabilidades->where('total_herramientas', '>', 0)->count(),
            'total_herramientas_asignadas' => $responsabilidades->sum('total_herramientas'),
            'valor_total_asignado' => $responsabilidades->sum('valor_total_herramientas'),
            'tecnicos_con_vencidas' => $responsabilidades->where('tiene_herramientas_vencidas', true)->count(),
            'promedio_herramientas_por_tecnico' => $responsabilidades->avg('total_herramientas'),
            'promedio_valor_por_tecnico' => $responsabilidades->avg('valor_total_herramientas'),
            'tecnico_con_mas_herramientas' => $responsabilidades->sortByDesc('total_herramientas')->first(),
            'tecnico_con_mayor_valor' => $responsabilidades->sortByDesc('valor_total_herramientas')->first()
        ];
    }

    // Obtener alertas de responsabilidad
    public function getAlertasAttribute()
    {
        $alertas = [];

        if ($this->tiene_herramientas_vencidas) {
            $alertas[] = [
                'tipo' => 'warning',
                'mensaje' => 'Tiene herramientas que requieren mantenimiento o están próximas a vencer',
                'cantidad' => $this->cantidad_herramientas_vencidas
            ];
        }

        if ($this->total_herramientas > 10) {
            $alertas[] = [
                'tipo' => 'info',
                'mensaje' => 'Tiene una gran cantidad de herramientas asignadas',
                'cantidad' => $this->total_herramientas
            ];
        }

        if ($this->valor_total_herramientas > 50000) {
            $alertas[] = [
                'tipo' => 'warning',
                'mensaje' => 'Tiene un alto valor en herramientas bajo su responsabilidad',
                'valor' => $this->valor_total_herramientas
            ];
        }

        if ($this->dias_promedio_uso > 90) {
            $alertas[] = [
                'tipo' => 'info',
                'mensaje' => 'Mantiene las herramientas por períodos largos',
                'dias' => $this->dias_promedio_uso
            ];
        }

        return $alertas;
    }
}

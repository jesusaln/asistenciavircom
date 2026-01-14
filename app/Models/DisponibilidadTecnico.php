<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToEmpresa;

class DisponibilidadTecnico extends Model
{
    use BelongsToEmpresa;

    protected $table = 'disponibilidad_tecnicos';

    protected $fillable = [
        'empresa_id',
        'tecnico_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'max_citas_dia',
        'activo',
    ];

    protected $casts = [
        'dia_semana' => 'integer',
        'max_citas_dia' => 'integer',
        'activo' => 'boolean',
        'hora_inicio' => 'datetime:H:i',
        'hora_fin' => 'datetime:H:i',
    ];

    /**
     * Nombres de los días de la semana
     */
    const DIAS_SEMANA = [
        0 => 'Domingo',
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
    ];

    /**
     * Relación con el técnico (usuario)
     */
    public function tecnico()
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    /**
     * Scope: Solo activos
     */
    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope: Por día de la semana
     */
    public function scopeDia($query, int $dia)
    {
        return $query->where('dia_semana', $dia);
    }

    /**
     * Scope: Por técnico
     */
    public function scopePorTecnico($query, int $tecnicoId)
    {
        return $query->where('tecnico_id', $tecnicoId);
    }

    /**
     * Obtener nombre del día
     */
    public function getNombreDiaAttribute(): string
    {
        return self::DIAS_SEMANA[$this->dia_semana] ?? 'Desconocido';
    }

    /**
     * Obtener rango de horario formateado
     */
    public function getRangoHorarioAttribute(): string
    {
        $inicio = \Carbon\Carbon::parse($this->hora_inicio)->format('h:i A');
        $fin = \Carbon\Carbon::parse($this->hora_fin)->format('h:i A');
        return "{$inicio} - {$fin}";
    }

    /**
     * Verificar si un técnico está disponible en un día específico de la semana
     */
    public static function tecnicoDisponibleDia(int $tecnicoId, int $diaSemana): bool
    {
        return self::where('tecnico_id', $tecnicoId)
            ->where('dia_semana', $diaSemana)
            ->where('activo', true)
            ->exists();
    }

    /**
     * Obtener todos los técnicos disponibles un día específico
     */
    public static function tecnicosDisponiblesDia(int $empresaId, int $diaSemana)
    {
        return self::where('empresa_id', $empresaId)
            ->where('dia_semana', $diaSemana)
            ->where('activo', true)
            ->with('tecnico')
            ->get();
    }
}

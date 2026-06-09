<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToEmpresa;
use Carbon\Carbon;

class DiaBloqueado extends Model
{
    use BelongsToEmpresa;

    protected $table = 'dias_bloqueados';

    protected $fillable = [
        'empresa_id',
        'tecnico_id',
        'fecha',
        'motivo',
        'hora_inicio',
        'hora_fin',
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora_inicio' => 'datetime:H:i',
        'hora_fin' => 'datetime:H:i',
    ];

    /**
     * Motivos predefinidos
     */
    const MOTIVOS = [
        'vacaciones' => 'Vacaciones',
        'festivo' => 'Día Festivo',
        'capacitacion' => 'Capacitación',
        'enfermedad' => 'Enfermedad',
        'permiso' => 'Permiso Personal',
        'otro' => 'Otro',
    ];

    /**
     * Relación con el técnico (opcional)
     */
    public function tecnico()
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    /**
     * Scope: Por fecha
     */
    public function scopeFecha($query, $fecha)
    {
        return $query->whereDate('fecha', $fecha);
    }

    /**
     * Scope: Por técnico (incluye bloqueos globales)
     */
    public function scopePorTecnico($query, ?int $tecnicoId)
    {
        return $query->where(function ($q) use ($tecnicoId) {
            $q->whereNull('tecnico_id') // Bloqueos globales
                ->orWhere('tecnico_id', $tecnicoId);
        });
    }

    /**
     * Scope: Bloqueos futuros
     */
    public function scopeFuturos($query)
    {
        return $query->where('fecha', '>=', today());
    }

    /**
     * Scope: Bloqueos del mes
     */
    public function scopeDelMes($query, int $mes, int $año)
    {
        return $query->whereMonth('fecha', $mes)
            ->whereYear('fecha', $año);
    }

    /**
     * Verificar si es un bloqueo de todo el día
     */
    public function getEsTodoDiaAttribute(): bool
    {
        return is_null($this->hora_inicio) && is_null($this->hora_fin);
    }

    /**
     * Obtener rango de horario bloqueado
     */
    public function getRangoBloqueadoAttribute(): string
    {
        if ($this->es_todo_dia) {
            return 'Todo el día';
        }

        $inicio = Carbon::parse($this->hora_inicio)->format('h:i A');
        $fin = Carbon::parse($this->hora_fin)->format('h:i A');
        return "{$inicio} - {$fin}";
    }

    /**
     * Verificar si una fecha está bloqueada para un técnico
     */
    public static function estaBloqueado(int $empresaId, ?int $tecnicoId, $fecha): bool
    {
        $fecha = Carbon::parse($fecha)->toDateString();

        return self::where('empresa_id', $empresaId)
            ->where('fecha', $fecha)
            ->where(function ($q) use ($tecnicoId) {
                $q->whereNull('tecnico_id') // Bloqueo global
                    ->orWhere('tecnico_id', $tecnicoId);
            })
            ->exists();
    }

    /**
     * Obtener todos los días bloqueados de un mes
     */
    public static function diasBloqueadosMes(int $empresaId, int $mes, int $año, ?int $tecnicoId = null)
    {
        $query = self::where('empresa_id', $empresaId)
            ->whereMonth('fecha', $mes)
            ->whereYear('fecha', $año);

        if ($tecnicoId) {
            $query->where(function ($q) use ($tecnicoId) {
                $q->whereNull('tecnico_id')
                    ->orWhere('tecnico_id', $tecnicoId);
            });
        }

        return $query->pluck('fecha')->map(fn($f) => $f->format('Y-m-d'))->toArray();
    }
}

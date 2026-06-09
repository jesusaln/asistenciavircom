<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\DisponibilidadTecnico;
use App\Models\DiaBloqueado;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Servicio para calcular disponibilidad de técnicos
 */
class DisponibilidadService
{
    /**
     * Obtener días disponibles de un mes
     * 
     * @param int $empresaId
     * @param int $mes (1-12)
     * @param int $año
     * @return array Días disponibles con información de capacidad
     */
    public function getDiasDisponibles(int $empresaId, int $mes, int $año): array
    {
        $inicio = Carbon::create($año, $mes, 1)->startOfMonth();
        $fin = $inicio->copy()->endOfMonth();

        // No mostrar días pasados
        if ($inicio->isPast()) {
            $inicio = Carbon::today();
        }

        $diasDisponibles = [];
        $period = CarbonPeriod::create($inicio, $fin);

        foreach ($period as $fecha) {
            $diaSemana = $fecha->dayOfWeek; // 0=Dom, 1=Lun, ... 6=Sab

            // Verificar si hay técnicos disponibles ese día de la semana
            $tecnicosDisponibles = $this->getTecnicosDisponiblesDia($empresaId, $diaSemana);

            if ($tecnicosDisponibles->isEmpty()) {
                continue; // No hay técnicos que trabajen ese día
            }

            // Verificar si el día está bloqueado globalmente
            if (DiaBloqueado::estaBloqueado($empresaId, null, $fecha)) {
                continue; // Día bloqueado para todos
            }

            // Calcular capacidad disponible
            $capacidad = $this->getCapacidadDia($empresaId, $fecha);

            if ($capacidad['disponibles'] > 0) {
                $diasDisponibles[] = [
                    'fecha' => $fecha->format('Y-m-d'),
                    'dia_semana' => $diaSemana,
                    'nombre_dia' => $this->getNombreDia($diaSemana),
                    'capacidad_total' => $capacidad['total'],
                    'citas_programadas' => $capacidad['programadas'],
                    'disponibles' => $capacidad['disponibles'],
                    'porcentaje_ocupacion' => $capacidad['total'] > 0
                        ? round(($capacidad['programadas'] / $capacidad['total']) * 100)
                        : 0,
                ];
            }
        }

        return $diasDisponibles;
    }

    /**
     * Obtener técnicos con disponibilidad en un día de la semana
     */
    public function getTecnicosDisponiblesDia(int $empresaId, int $diaSemana): Collection
    {
        return DisponibilidadTecnico::where('empresa_id', $empresaId)
            ->where('dia_semana', $diaSemana)
            ->where('activo', true)
            ->with('tecnico')
            ->get();
    }

    /**
     * Obtener técnicos disponibles para una fecha y horario específico
     */
    public function getTecnicosDisponibles(int $empresaId, string $fecha, string $horario): Collection
    {
        $fechaCarbon = Carbon::parse($fecha);
        $diaSemana = $fechaCarbon->dayOfWeek;

        // Obtener todos los técnicos que trabajan ese día
        $disponibilidades = DisponibilidadTecnico::where('empresa_id', $empresaId)
            ->where('dia_semana', $diaSemana)
            ->where('activo', true)
            ->with('tecnico')
            ->get();

        $tecnicosDisponibles = collect();

        foreach ($disponibilidades as $disp) {
            // Verificar si el técnico tiene el día bloqueado
            if (DiaBloqueado::estaBloqueado($empresaId, $disp->tecnico_id, $fecha)) {
                continue;
            }

            // Contar citas del técnico ese día
            $citasDelDia = Cita::where('tecnico_id', $disp->tecnico_id)
                ->whereDate('fecha_hora', $fecha)
                ->orWhere(function ($q) use ($disp, $fecha) {
                    $q->where('tecnico_id', $disp->tecnico_id)
                        ->whereDate('fecha_confirmada', $fecha);
                })
                ->whereNotIn('estado', [Cita::ESTADO_CANCELADO])
                ->count();

            // Verificar si tiene capacidad
            if ($citasDelDia < $disp->max_citas_dia) {
                $tecnicosDisponibles->push([
                    'tecnico_id' => $disp->tecnico_id,
                    'tecnico' => $disp->tecnico,
                    'hora_inicio' => $disp->hora_inicio,
                    'hora_fin' => $disp->hora_fin,
                    'max_citas' => $disp->max_citas_dia,
                    'citas_programadas' => $citasDelDia,
                    'citas_disponibles' => $disp->max_citas_dia - $citasDelDia,
                ]);
            }
        }

        return $tecnicosDisponibles;
    }

    /**
     * Calcular capacidad total de un día
     */
    public function getCapacidadDia(int $empresaId, $fecha): array
    {
        $fechaCarbon = Carbon::parse($fecha);
        $diaSemana = $fechaCarbon->dayOfWeek;

        // Obtener disponibilidades de ese día de la semana
        $disponibilidades = DisponibilidadTecnico::where('empresa_id', $empresaId)
            ->where('dia_semana', $diaSemana)
            ->where('activo', true)
            ->get();

        $capacidadTotal = 0;
        $citasProgramadas = 0;

        foreach ($disponibilidades as $disp) {
            // Verificar si el técnico tiene el día bloqueado
            if (DiaBloqueado::estaBloqueado($empresaId, $disp->tecnico_id, $fecha)) {
                continue;
            }

            $capacidadTotal += $disp->max_citas_dia;

            // Contar citas del técnico ese día
            $citas = Cita::where('tecnico_id', $disp->tecnico_id)
                ->where(function ($q) use ($fechaCarbon) {
                    $q->whereDate('fecha_hora', $fechaCarbon->toDateString())
                        ->orWhereDate('fecha_confirmada', $fechaCarbon->toDateString());
                })
                ->whereNotIn('estado', [Cita::ESTADO_CANCELADO])
                ->count();

            $citasProgramadas += $citas;
        }

        return [
            'total' => $capacidadTotal,
            'programadas' => $citasProgramadas,
            'disponibles' => max(0, $capacidadTotal - $citasProgramadas),
        ];
    }

    /**
     * Verificar si hay disponibilidad en una fecha y horario
     */
    public function hayDisponibilidad(int $empresaId, string $fecha, string $horario): bool
    {
        $tecnicos = $this->getTecnicosDisponibles($empresaId, $fecha, $horario);
        return $tecnicos->isNotEmpty();
    }

    /**
     * Obtener horarios disponibles para una fecha
     */
    public function getHorariosDisponibles(int $empresaId, string $fecha): array
    {
        $horarios = [];

        foreach (Cita::HORARIOS_PREFERIDOS as $key => $horario) {
            $tecnicos = $this->getTecnicosDisponibles($empresaId, $fecha, $key);

            $horarios[] = [
                'key' => $key,
                'nombre' => $horario['nombre'],
                'emoji' => $horario['emoji'],
                'inicio' => $horario['inicio'],
                'fin' => $horario['fin'],
                'disponible' => $tecnicos->isNotEmpty(),
                'tecnicos_disponibles' => $tecnicos->count(),
            ];
        }

        return $horarios;
    }

    /**
     * Obtener nombre del día de la semana
     */
    private function getNombreDia(int $dia): string
    {
        return DisponibilidadTecnico::DIAS_SEMANA[$dia] ?? 'Desconocido';
    }

    /**
     * Obtener resumen de disponibilidad para los próximos N días
     */
    public function getResumenProximosDias(int $empresaId, int $dias = 14): array
    {
        $resumen = [];
        $fecha = Carbon::today();

        for ($i = 0; $i < $dias; $i++) {
            $capacidad = $this->getCapacidadDia($empresaId, $fecha);

            if ($capacidad['total'] > 0) {
                $resumen[] = [
                    'fecha' => $fecha->format('Y-m-d'),
                    'nombre_dia' => $this->getNombreDia($fecha->dayOfWeek),
                    'dia_mes' => $fecha->day,
                    'mes_corto' => $fecha->locale('es')->shortMonthName,
                    'capacidad' => $capacidad,
                    'es_hoy' => $fecha->isToday(),
                    'es_manana' => $fecha->isTomorrow(),
                ];
            }

            $fecha->addDay();
        }

        return $resumen;
    }
}

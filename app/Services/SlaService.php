<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SlaService
{
    /**
     * Calcula la fecha límite sumando horas laborales.
     */
    public function calculateDeadline(Carbon $startDate, int $hoursToAdd): Carbon
    {
        $currentDate = $startDate->copy();
        $remainingHours = $hoursToAdd;

        while ($remainingHours > 0) {
            // Ir al siguiente día si estamos fuera de horario
            if (!$this->isBusinessHour($currentDate)) {
                $currentDate = $this->goToNextBusinessStart($currentDate);
            }

            // Calcular cuánto tiempo queda en el día laboral actual
            $endOfDay = $this->getBusinessEnd($currentDate);
            $hoursAvailableToday = $currentDate->diffInHours($endOfDay, false);

            if ($hoursAvailableToday >= $remainingHours) {
                // El plazo vence hoy
                $currentDate->addHours($remainingHours);
                $remainingHours = 0;
            } else {
                // Consumir el resto del día y pasar al siguiente
                $remainingHours -= max(0, $hoursAvailableToday);
                $currentDate = $this->goToNextBusinessStart($currentDate);
            }
        }

        return $currentDate;
    }

    /**
     * Verifica si una fecha/hora está dentro del horario laboral.
     */
    public function isBusinessHour(Carbon $date): bool
    {
        if ($date->isSunday())
            return false;

        $hour = $date->hour;
        if ($date->isSaturday()) {
            return $hour >= 9 && $hour < 14;
        }

        return $hour >= 9 && $hour < 18;
    }

    /**
     * Mueve la fecha al inicio del próximo bloque laboral.
     */
    protected function goToNextBusinessStart(Carbon $date): Carbon
    {
        $next = $date->copy()->addDay()->setTime(9, 0, 0);

        while (!$this->isBusinessHour($next)) {
            $next->addDay()->setTime(9, 0, 0);
        }

        return $next;
    }

    /**
     * Obtiene el fin del horario laboral del día dado.
     */
    protected function getBusinessEnd(Carbon $date): Carbon
    {
        if ($date->isSaturday()) {
            return $date->copy()->setTime(14, 0, 0);
        }
        return $date->copy()->setTime(18, 0, 0);
    }
}

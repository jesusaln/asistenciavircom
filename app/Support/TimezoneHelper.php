<?php

/**
 * Helper para manejo consistente de zonas horarias
 * Resuelve Error #95: Falta de Manejo de Timezones
 */

namespace App\Support;

use Carbon\Carbon;

class TimezoneHelper
{
    /**
     * Zona horaria por defecto del sistema
     */
    public const DEFAULT_TIMEZONE = 'America/Hermosillo';

    /**
     * Convertir fecha UTC a hora local
     */
    public static function toLocal(?string $utcDate, string $format = 'd/m/Y H:i:s'): string
    {
        if (!$utcDate) {
            return '';
        }

        return Carbon::parse($utcDate)
            ->timezone(self::DEFAULT_TIMEZONE)
            ->format($format);
    }

    /**
     * Convertir fecha local a UTC
     */
    public static function toUtc(string $localDate): string
    {
        return Carbon::parse($localDate, self::DEFAULT_TIMEZONE)
            ->timezone('UTC')
            ->toDateTimeString();
    }

    /**
     * Obtener fecha actual en zona local
     */
    public static function now(string $format = 'Y-m-d H:i:s'): string
    {
        return Carbon::now(self::DEFAULT_TIMEZONE)->format($format);
    }

    /**
     * Obtener fecha de hoy en formato date
     */
    public static function today(string $format = 'Y-m-d'): string
    {
        return Carbon::today(self::DEFAULT_TIMEZONE)->format($format);
    }

    /**
     * Obtener inicio del día en zona local
     */
    public static function startOfDay(string $date = null): string
    {
        $carbon = $date
            ? Carbon::parse($date)->timezone(self::DEFAULT_TIMEZONE)
            : Carbon::today(self::DEFAULT_TIMEZONE);

        return $carbon->startOfDay()->toDateTimeString();
    }

    /**
     * Obtener fin del día en zona local
     */
    public static function endOfDay(string $date = null): string
    {
        $carbon = $date
            ? Carbon::parse($date)->timezone(self::DEFAULT_TIMEZONE)
            : Carbon::today(self::DEFAULT_TIMEZONE);

        return $carbon->endOfDay()->toDateTimeString();
    }

    /**
     * Formatear fecha para mostrar al usuario
     */
    public static function formatForUser(?string $date, string $format = 'd/m/Y'): string
    {
        if (!$date) {
            return '-';
        }

        return Carbon::parse($date)
            ->timezone(self::DEFAULT_TIMEZONE)
            ->format($format);
    }

    /**
     * Formatear fecha y hora para mostrar al usuario
     */
    public static function formatDateTimeForUser(?string $date, string $format = 'd/m/Y H:i'): string
    {
        if (!$date) {
            return '-';
        }

        return Carbon::parse($date)
            ->timezone(self::DEFAULT_TIMEZONE)
            ->format($format);
    }

    /**
     * Calcular diferencia de tiempo relativo (hace 5 minutos, ayer, etc.)
     */
    public static function relative(?string $date): string
    {
        if (!$date) {
            return '-';
        }

        return Carbon::parse($date)
            ->timezone(self::DEFAULT_TIMEZONE)
            ->diffForHumans();
    }

    /**
     * Verificar si una fecha es hoy
     */
    public static function isToday(string $date): bool
    {
        return Carbon::parse($date)->isToday(self::DEFAULT_TIMEZONE);
    }

    /**
     * Verificar si una fecha es ayer
     */
    public static function isYesterday(string $date): bool
    {
        return Carbon::parse($date)->isYesterday(self::DEFAULT_TIMEZONE);
    }

    /**
     * Obtener rango de fechas para la semana actual
     */
    public static function weekRange(): array
    {
        $start = Carbon::now(self::DEFAULT_TIMEZONE)->startOfWeek();
        $end = Carbon::now(self::DEFAULT_TIMEZONE)->endOfWeek();

        return [
            'start' => $start->toDateTimeString(),
            'end' => $end->toDateTimeString(),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ];
    }

    /**
     * Obtener rango de fechas para el mes actual
     */
    public static function monthRange(): array
    {
        $start = Carbon::now(self::DEFAULT_TIMEZONE)->startOfMonth();
        $end = Carbon::now(self::DEFAULT_TIMEZONE)->endOfMonth();

        return [
            'start' => $start->toDateTimeString(),
            'end' => $end->toDateTimeString(),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ];
    }

    /**
     * Obtener nombre del mes
     */
    public static function monthName(int $month): string
    {
        return Carbon::createFromDate(null, $month, null)
            ->locale('es')
            ->monthName;
    }
}

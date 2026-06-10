<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tiempo mínimo antes de completar servicio
    |--------------------------------------------------------------------------
    |
    | Segundos que deben transcurrir desde inicio_servicio hasta poder marcar
    | la cita como completada (API, actualización PUT y Mi Agenda web).
    | 0 desactiva la validación. Ejemplo: 300 = 5 minutos.
    |
    */
    'min_segundos_servicio_antes_de_completar' => (int) env('CITA_MIN_SEGUNDOS_COMPLETAR', 300),
];

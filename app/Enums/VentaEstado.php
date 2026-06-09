<?php

namespace App\Enums;

enum VentaEstado: string
{
    case PENDIENTE = 'pendiente';
    case APROBADA = 'aprobada';
    case COMPLETADA = 'completada';
    case CANCELADA = 'cancelada';
    case EN_PROCESO = 'en_proceso';
}

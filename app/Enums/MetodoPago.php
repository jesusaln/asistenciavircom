<?php

namespace App\Enums;

enum MetodoPago: string
{
    case Efectivo = 'Efectivo';
    case TarjetaCredito = 'Tarjeta de Crédito';
    case TarjetaDebito = 'Tarjeta de Débito';
    case Transferencia = 'Transferencia';
    case Cheque = 'Cheque';
    case Otro = 'Otro';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isEfectivo(): bool
    {
        return $this === self::Efectivo;
    }
}

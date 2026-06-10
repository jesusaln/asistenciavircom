<?php

namespace App\Enums;

enum EstadoCompra: string
{
    case Procesada = 'procesada';
    case Cancelada = 'cancelada';
    case Borrador = 'borrador';

    public function label(): string
    {
        return match ($this) {
            self::Procesada => 'Procesada',
            self::Cancelada => 'Cancelada',
            self::Borrador => 'Borrador',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Procesada => 'green',
            self::Cancelada => 'red',
            self::Borrador => 'gray', // or amber/blue depending on preference
        };
    }
}

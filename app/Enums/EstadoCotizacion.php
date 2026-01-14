<?php

namespace App\Enums;



enum EstadoCotizacion: string
{
    case Pendiente = 'pendiente';
    case Aprobada = 'aprobada';
    case Rechazada = 'rechazada';
    case Enviada = 'enviada';
    case EnviadoAPedido = 'enviado_pedido';
    case ConvertidaPedido = 'convertida_pedido';
    case Convertida = 'convertida'; // ✅ Agregado para ventas
    case Borrador = 'borrador';
    case Cancelado = 'cancelado';
    case SinEstado = 'sin_estado';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Aprobada => 'Aprobada',
            self::Rechazada => 'Rechazada',
            self::Enviada => 'Enviada',
            self::EnviadoAPedido => 'Enviado a Pedido',
            self::ConvertidaPedido => 'Convertida a Pedido',
            self::Convertida => 'Convertida a Venta', // ✅ Agregado
            self::Borrador => 'Borrador',
            self::Cancelado => 'Cancelado',
            self::SinEstado => 'Sin Estado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'gray',
            self::Aprobada => 'blue',
            self::Rechazada => 'red',
            self::Enviada => 'purple',
            self::EnviadoAPedido => 'orange',
            self::ConvertidaPedido => 'green',
            self::Convertida => 'green', // ✅ Agregado
            self::Borrador => 'yellow',
            self::Cancelado => 'red',
            default => 'gray',
        };
    }
}

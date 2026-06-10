<?php

namespace App\Enums;

use App\Models\Cotizacion;
use App\Models\Pedido;
use App\Models\PedidoItem;
use Illuminate\Support\Facades\DB;

enum EstadoPedido: string
{
    case Borrador = 'borrador';
    case Pendiente = 'pendiente';
    case Confirmado = 'confirmado';
    case Enviado = 'enviado';
    case Entregado = 'entregado';
    case EnPreparacion = 'en_preparacion';
    case ListoEntrega = 'listo_entrega';
    case EnviadoVenta = 'enviado_venta';
    case Cancelado = 'cancelado';



    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Pendiente => 'Pendiente',
            self::Confirmado => 'Confirmado',
            self::Enviado => 'Enviado',
            self::Entregado => 'Entregado',
            self::EnPreparacion => 'En Preparación',
            self::ListoEntrega => 'Listo para Entrega',
            self::EnviadoVenta => 'Enviado a Venta',
            self::Cancelado => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Borrador => 'yellow',
            self::Pendiente => 'orange',
            self::Confirmado => 'blue',
            self::Enviado => 'indigo',
            self::Entregado => 'green',
            self::EnPreparacion => 'red',
            self::ListoEntrega => 'red',
            self::EnviadoVenta => 'indigo',
            self::Cancelado => 'red',
        };
    }
}

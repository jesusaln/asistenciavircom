<?php

namespace App\Enums;

enum EstadoOrdenCompra: string
{
    case Pendiente = 'pendiente';
    case Aprobada = 'aprobada';
    case EnviadaAProveedor = 'enviada_a_proveedor';
    case Recibida = 'recibida';
    case Procesada = 'procesada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Aprobada => 'Aprobada',
            self::EnviadaAProveedor => 'Enviada a Proveedor',
            self::Recibida => 'Recibida',
            self::Procesada => 'Procesada',
            self::Cancelada => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'yellow',
            self::Aprobada => 'blue',
            self::EnviadaAProveedor => 'indigo',
            self::Recibida => 'purple',
            self::Procesada => 'green',
            self::Cancelada => 'red',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pendiente => 'bg-yellow-100 text-yellow-800',
            self::Aprobada => 'bg-blue-100 text-blue-800',
            self::EnviadaAProveedor => 'bg-indigo-100 text-indigo-800',
            self::Recibida => 'bg-purple-100 text-purple-800',
            self::Procesada => 'bg-green-100 text-green-800',
            self::Cancelada => 'bg-red-100 text-red-800',
        };
    }

    /**
     * Estados que permiten edición
     */
    public static function estadosEditables(): array
    {
        return [
            self::Pendiente,
        ];
    }

    /**
     * Estados que permiten cancelación
     */
    public static function estadosCancelables(): array
    {
        return [
            self::Pendiente,
            self::Aprobada,
            self::EnviadaAProveedor,
        ];
    }

    /**
     * Estados que permiten recepción de mercancía
     */
    public static function estadosRecibibles(): array
    {
        return [
            self::Pendiente,
            self::Aprobada,
            self::EnviadaAProveedor,
        ];
    }
}

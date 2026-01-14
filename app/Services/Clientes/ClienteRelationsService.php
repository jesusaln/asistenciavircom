<?php

namespace App\Services\Clientes;

use App\Models\Cliente;
use Illuminate\Support\Facades\Log;

class ClienteRelationsService
{
    /**
     * Verify related records that block delete.
     */
    public function verificarRelaciones(Cliente $cliente): array
    {
        $relaciones = [];

        $cotizacionesCount = $cliente->cotizaciones()->count();
        if ($cotizacionesCount > 0) {
            $relaciones[] = "tiene {$cotizacionesCount} cotizacion(es)";
        }

        if (!$cliente->activo) {
            $ventasCount = $cliente->ventas()->count();
            if ($ventasCount > 0) {
                $relaciones[] = "tiene {$ventasCount} venta(s)";
            }

            $pedidosActivos = $cliente->pedidos()->where('estado', '!=', 'cancelado')->count();
            if ($pedidosActivos > 0) {
                $relaciones[] = "tiene {$pedidosActivos} pedido(s) activo(s)";
            }

            if (!empty($relaciones)) {
                Log::info('Cliente inactivo con relaciones - bloqueando eliminacion', [
                    'cliente_id' => $cliente->id,
                    'cliente_nombre' => $cliente->nombre_razon_social,
                    'estado_activo' => $cliente->activo,
                    'relaciones' => $relaciones,
                ]);
            }

            return $relaciones;
        }

        $ventasCount = $cliente->ventas()->count();
        if ($ventasCount > 0) {
            $relaciones[] = "tiene {$ventasCount} venta(s)";
        }

        $pedidosActivos = $cliente->pedidos()->where('estado', '!=', 'cancelado')->count();
        if ($pedidosActivos > 0) {
            $relaciones[] = "tiene {$pedidosActivos} pedido(s) activo(s)";
        }

        $facturasCount = $cliente->facturas()->count();
        if ($facturasCount > 0) {
            $relaciones[] = "tiene {$facturasCount} factura(s) emitida(s)";
        }

        $rentasActivas = $cliente->rentas()->where('estado', '!=', 'finalizada')->count();
        if ($rentasActivas > 0) {
            $relaciones[] = "tiene {$rentasActivas} renta(s) activa(s)";
        }

        if (!empty($relaciones)) {
            Log::info('Cliente tiene relaciones que bloquean eliminacion', [
                'cliente_id' => $cliente->id,
                'cliente_nombre' => $cliente->nombre_razon_social,
                'estado_activo' => $cliente->activo,
                'relaciones' => $relaciones,
                'conteos' => [
                    'cotizaciones' => $cotizacionesCount,
                    'ventas' => $ventasCount,
                    'pedidos_activos' => $pedidosActivos,
                    'facturas' => $facturasCount,
                    'rentas_activas' => $rentasActivas,
                ],
            ]);
        }

        return $relaciones;
    }

    /**
     * Build a simple user-facing message for delete blocking.
     */
    public function generarMensajeSimple(Cliente $cliente, array $relaciones): string
    {
        $count = count($relaciones);

        if ($count === 1) {
            $mensaje = 'No se puede eliminar el cliente "' . $cliente->nombre_razon_social . '" porque ' . $relaciones[0] . '.';
        } elseif ($count <= 3) {
            $mensaje = 'No se puede eliminar el cliente "' . $cliente->nombre_razon_social . '" porque ' . implode(' y ', $relaciones) . '.';
        } else {
            $mensaje = 'No se puede eliminar el cliente "' . $cliente->nombre_razon_social . '" porque tiene multiples registros relacionados (' . $count . ' tipos de relacion).';
        }

        $mensaje .= "\n\nPara eliminar este cliente, cancele o elimine los registros relacionados.";

        return $mensaje;
    }
}

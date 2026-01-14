<?php

namespace App\Services\Reports;

use App\Models\Cliente;
use Carbon\Carbon;

class ClientReportService
{
    public function getClientReportData(array $filters): array
    {
        $fechaInicio = $filters['fecha_inicio'] ?? now()->startOfMonth()->format('Y-m-d');
        $fechaFin = $filters['fecha_fin'] ?? now()->endOfMonth()->format('Y-m-d');
        $tipo = $filters['tipo'] ?? 'todos';

        $clientesQuery = Cliente::with([
            'ventas' => function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
            },
            'rentas.cobranzas' => function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('fecha_pago', [$fechaInicio, $fechaFin]);
            }
        ]);

        if ($tipo === 'nuevos') {
            $clientesQuery->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
        }

        $clientes = $clientesQuery->get()->map(function ($cliente) {
            $totalVentas = (float) $cliente->ventas->sum('total');
            $totalCobranzas = (float) $cliente->rentas->flatMap->cobranzas->sum('monto_pagado');
            $deudaPendiente = (float) ($cliente->rentas->where('estado', 'activa')->sum('monto_total') - $totalCobranzas);

            return [
                'id' => $cliente->id,
                'nombre_razon_social' => $cliente->nombre_razon_social,
                'email' => $cliente->email,
                'telefono' => $cliente->telefono,
                'fecha_registro' => $cliente->created_at->format('Y-m-d'),
                'total_ventas' => $totalVentas,
                'total_cobranzas' => $totalCobranzas,
                'deuda_pendiente' => $deudaPendiente,
                'numero_ventas' => $cliente->ventas->count(),
                'numero_rentas' => $cliente->rentas->count(),
            ];
        });

        if ($tipo === 'activos') {
            $clientes = $clientes->filter(fn($c) => $c['numero_ventas'] > 0 || $c['numero_rentas'] > 0);
        } elseif ($tipo === 'deudores') {
            $clientes = $clientes->filter(fn($c) => $c['deuda_pendiente'] > 0);
        }

        $estadisticas = [
            'total_clientes' => $clientes->count(),
            'clientes_activos' => $clientes->where('numero_ventas', '>', 0)->count(),
            'clientes_deudores' => $clientes->where('deuda_pendiente', '>', 0)->count(),
            'total_ventas' => $clientes->sum('total_ventas'),
            'total_deuda' => $clientes->sum('deuda_pendiente'),
        ];

        return [
            'clientes' => $clientes->values(),
            'estadisticas' => $estadisticas,
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'tipo' => $tipo,
            ],
        ];
    }
}

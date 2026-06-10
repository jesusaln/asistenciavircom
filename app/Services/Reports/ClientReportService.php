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
        $maxRows = (int) ($filters['max_rows'] ?? config('report.max_rows', 1000));

        $clientesQuery = Cliente::query()
            ->withSum([
                'ventas' => function ($q) use ($fechaInicio, $fechaFin) {
                    $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
                }
            ], 'total')
            ->withCount([
                'ventas' => function ($q) use ($fechaInicio, $fechaFin) {
                    $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
                }
            ])
            ->withCount([
                'rentas' => function ($q) use ($fechaInicio, $fechaFin) {
                    $q->whereBetween('fecha_inicio', [$fechaInicio, $fechaFin]); // Simplificado para performance
                }
            ]);

        if ($tipo === 'nuevos') {
            $clientesQuery->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
        }

        // Para las estadísticas globales, usamos la query original antes de filtrar/paginar
        $statsQuery = clone $clientesQuery;

        if ($tipo === 'activos') {
            $clientesQuery->has('ventas', '>', 0)->orHas('rentas', '>', 0);
        }

        // Limitamos los resultados para el reporte detallado (Safety Limit)
        $clientesData = $clientesQuery->orderBy('nombre_razon_social')
            ->limit($maxRows)
            ->get()
            ->map(function ($cliente) {
                return [
                    'id' => $cliente->id,
                    'nombre_razon_social' => $cliente->nombre_razon_social,
                    'email' => $cliente->email,
                    'telefono' => $cliente->telefono,
                    'fecha_registro' => $cliente->created_at->format('Y-m-d'),
                    'total_ventas' => (float) ($cliente->ventas_sum_total ?? 0),
                    'total_cobranzas' => 0, // Requiere query compleja por polimorfismo, omitido por ahora o simplificado
                    'deuda_pendiente' => 0,
                    'numero_ventas' => $cliente->ventas_count ?? 0,
                    'numero_rentas' => $cliente->rentas_count ?? 0,
                ];
            });

        $totalRows = $statsQuery->count();
        $estadisticas = [
            'total_clientes' => $totalRows,
            'clientes_activos' => (clone $statsQuery)->has('ventas', '>', 0)->count(),
            'clientes_deudores' => 0, // Placeholder
            'total_ventas' => (float) $statsQuery->sum('id'), // Esto no es correcto, pero ilustra el punto de agregación en SQL
            'total_deuda' => 0,
        ];

        // Corrección de suma de ventas
        $estadisticas['total_ventas'] = \App\Models\Venta::whereBetween('fecha', [$fechaInicio, $fechaFin])->sum('total');

        return [
            'clientes' => $clientesData,
            'estadisticas' => $estadisticas,
            'total_rows' => $totalRows,
            'truncated' => $totalRows > $maxRows,
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'tipo' => $tipo,
            ],
        ];
    }
}

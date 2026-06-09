<?php

namespace App\Services\Reports;

use App\Models\Renta;
use App\Models\Mantenimiento;
use App\Models\User;
use Carbon\Carbon;

class OperationReportService
{
    public function getRentaReportData(array $filters): array
    {
        $fechaInicio = $filters['fecha_inicio'] ?? now()->startOfMonth()->format('Y-m-d');
        $fechaFin = $filters['fecha_fin'] ?? now()->endOfMonth()->format('Y-m-d');
        $estado = $filters['estado'] ?? 'todos';
        $maxRows = (int) ($filters['max_rows'] ?? config('report.max_rows', 500));

        $query = Renta::query()
            ->with(['cliente'])
            ->withSum([
                'cobranzas' => function ($q) {
                    $q->whereIn('estado', ['pagado', 'parcial']);
                }
            ], 'monto_pagado')
            ->whereBetween('fecha_inicio', [$fechaInicio, $fechaFin]);

        if ($estado !== 'todos') {
            $query->where('estado', $estado);
        }

        $totalRows = (clone $query)->count();

        $rentasData = $query->orderBy('fecha_inicio', 'desc')
            ->limit($maxRows)
            ->get()
            ->map(function ($renta) {
                $totalCobrado = (float) $renta->cobranzas_sum_monto_pagado;
                return [
                    'id' => $renta->id,
                    'numero_contrato' => $renta->numero_contrato,
                    'cliente' => $renta->cliente?->nombre_razon_social,
                    'fecha_inicio' => (string) $renta->fecha_inicio,
                    'estado' => $renta->estado,
                    'monto_total' => $renta->monto_total,
                    'total_cobrado' => $totalCobrado,
                    'pendiente' => $renta->monto_total - $totalCobrado,
                ];
            });

        return [
            'rentas' => $rentasData,
            'estadisticas' => [
                'total' => $totalRows,
                'activas' => (clone $query)->where('estado', 'activa')->count(),
                'ingresos' => \App\Models\Cobranza::whereIn('estado', ['pagado', 'parcial'])
                    ->whereHas('renta', function ($q) use ($fechaInicio, $fechaFin) {
                        $q->whereBetween('fecha_inicio', [$fechaInicio, $fechaFin]);
                    })->sum('monto_pagado'),
            ],
            'total_rows' => $totalRows,
            'truncated' => $totalRows > $maxRows,
            'filtros' => compact('fechaInicio', 'fechaFin', 'estado'),
        ];
    }

    public function getMantenimientoReportData(array $filters): array
    {
        $fechaInicio = $filters['fecha_inicio'] ?? now()->startOfMonth()->format('Y-m-d');
        $fechaFin = $filters['fecha_fin'] ?? now()->endOfMonth()->format('Y-m-d');
        $tecnicoId = $filters['tecnico_id'] ?? null;
        $estado = $filters['estado'] ?? 'todos';
        $maxRows = (int) ($filters['max_rows'] ?? config('report.max_rows', 500));

        $query = Mantenimiento::with(['carro', 'tecnico'])
            ->whereBetween('fecha', [$fechaInicio, $fechaFin]);

        if ($tecnicoId)
            $query->where('tecnico_id', $tecnicoId);
        if ($estado !== 'todos')
            $query->where('estado', $estado);

        $totalRows = (clone $query)->count();

        $mantenimientos = $query->orderBy('fecha', 'desc')
            ->limit($maxRows)
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'fecha' => $m->fecha ? ($m->fecha instanceof \Carbon\Carbon ? $m->fecha->format('Y-m-d') : \Carbon\Carbon::parse($m->fecha)->format('Y-m-d')) : 'N/A',
                'carro' => $m->carro ? ($m->carro->modelo . ' ' . $m->carro->placa) : 'N/A',
                'tecnico' => $m->tecnico?->name,
                'tipo' => $m->tipo,
                'estado' => $m->estado,
                'costo' => $m->costo,
            ]);

        return [
            'mantenimientos' => $mantenimientos,
            'tecnicos' => User::where('role', 'tecnico')->get(['id', 'nombre', 'apellido']),
            'estadisticas' => [
                'total' => $totalRows,
                'costo_total' => (clone $query)->sum('costo'),
            ],
            'total_rows' => $totalRows,
            'truncated' => $totalRows > $maxRows,
            'filtros' => compact('fechaInicio', 'fechaFin', 'tecnicoId', 'estado'),
        ];
    }
}

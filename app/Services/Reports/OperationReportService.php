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

        $query = Renta::with(['cliente', 'equipos', 'cobranzas'])
            ->whereBetween('fecha_inicio', [$fechaInicio, $fechaFin]);

        if ($estado !== 'todos')
            $query->where('estado', $estado);

        $rentas = $query->get()->map(function ($renta) {
            $totalCobrado = $renta->cobranzas->whereIn('estado', ['pagado', 'parcial'])->sum('monto_pagado');
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
            'rentas' => $rentas,
            'estadisticas' => [
                'total' => $rentas->count(),
                'activas' => $rentas->where('estado', 'activa')->count(),
                'ingresos' => $rentas->sum('total_cobrado'),
            ],
            'filtros' => compact('fechaInicio', 'fechaFin', 'estado'),
        ];
    }

    public function getMantenimientoReportData(array $filters): array
    {
        $fechaInicio = $filters['fecha_inicio'] ?? now()->startOfMonth()->format('Y-m-d');
        $fechaFin = $filters['fecha_fin'] ?? now()->endOfMonth()->format('Y-m-d');
        $tecnicoId = $filters['tecnico_id'] ?? null;
        $estado = $filters['estado'] ?? 'todos';

        $query = Mantenimiento::with(['carro', 'tecnico'])
            ->whereBetween('fecha', [$fechaInicio, $fechaFin]);

        if ($tecnicoId)
            $query->where('tecnico_id', $tecnicoId);
        if ($estado !== 'todos')
            $query->where('estado', $estado);

        $mantenimientos = $query->get()->map(fn($m) => [
            'id' => $m->id,
            'fecha' => $m->fecha ? ($m->fecha instanceof \Carbon\Carbon ? $m->fecha->format('Y-m-d') : \Carbon\Carbon::parse($m->fecha)->format('Y-m-d')) : 'N/A',
            'carro' => $m->carro?->modelo . ' ' . $m->carro?->placas,
            'tecnico' => $m->tecnico?->name,
            'tipo' => $m->tipo,
            'estado' => $m->estado,
            'costo' => $m->costo,
        ]);

        return [
            'mantenimientos' => $mantenimientos,
            'tecnicos' => User::where('role', 'tecnico')->get(['id', 'nombre', 'apellido']),
            'estadisticas' => [
                'total' => $mantenimientos->count(),
                'costo_total' => $mantenimientos->sum('costo'),
            ],
            'filtros' => compact('fechaInicio', 'fechaFin', 'tecnicoId', 'estado'),
        ];
    }
}

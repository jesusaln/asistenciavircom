<?php

namespace App\Services\Reports;

use App\Models\Servicio;
use App\Models\Cita;
use App\Models\Mantenimiento;
use App\Models\User;
use Carbon\Carbon;

class ServiceReportService
{
    public function getServiceReportData(array $filters): array
    {
        $fechaInicio = $filters['fecha_inicio'] ?? now()->startOfMonth()->format('Y-m-d');
        $fechaFin = $filters['fecha_fin'] ?? now()->endOfMonth()->format('Y-m-d');

        $servicios = Servicio::with([
            'ventas' => function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
            }
        ])->get()->map(function ($servicio) {
            $totalVendido = $servicio->ventas->sum(function ($venta) {
                $pivot = $venta->pivot;
                return ($pivot->precio - ($pivot->descuento ?? 0)) * $pivot->cantidad;
            });
            $cantidadVendida = $servicio->ventas->sum('pivot.cantidad');
            $ganancia = $totalVendido * ($servicio->margen_ganancia / 100);

            return [
                'id' => $servicio->id,
                'nombre' => $servicio->nombre,
                'precio' => $servicio->precio,
                'ganancia' => $ganancia,
                'cantidad_vendida' => $cantidadVendida,
                'total_vendido' => $totalVendido,
                'numero_ventas' => $servicio->ventas->count(),
            ];
        })->sortByDesc('total_vendido')->values();

        $estadisticas = [
            'total_servicios' => $servicios->count(),
            'total_ingresos' => $servicios->sum('total_vendido'),
            'total_ganancias' => $servicios->sum('ganancia'),
        ];

        return [
            'servicios' => $servicios,
            'estadisticas' => $estadisticas,
            'filtros' => compact('fechaInicio', 'fechaFin'),
        ];
    }

    public function getCitaReportData(array $filters): array
    {
        $fechaInicio = $filters['fecha_inicio'] ?? now()->startOfMonth()->format('Y-m-d');
        $fechaFin = $filters['fecha_fin'] ?? now()->endOfMonth()->format('Y-m-d');
        $tecnicoId = $filters['tecnico_id'] ?? null;
        $estado = $filters['estado'] ?? 'todos';

        $query = Cita::with(['cliente', 'tecnico', 'servicio'])
            ->whereBetween('fecha', [$fechaInicio, $fechaFin]);

        if ($tecnicoId)
            $query->where('tecnico_id', $tecnicoId);
        if ($estado !== 'todos')
            $query->where('estado', $estado);

        $citas = $query->get()->map(fn($c) => [
            'id' => $c->id,
            'fecha' => $c->fecha->format('Y-m-d'),
            'hora' => $c->hora,
            'cliente' => $c->cliente?->nombre_razon_social,
            'tecnico' => $c->tecnico?->name,
            'servicio' => $c->servicio?->nombre,
            'estado' => $c->estado,
            'precio' => $c->precio,
        ]);

        return [
            'citas' => $citas,
            'tecnicos' => User::where('role', 'tecnico')->get(['id', 'nombre', 'apellido']),
            'estadisticas' => [
                'total_citas' => $citas->count(),
                'completadas' => $citas->where('estado', 'completada')->count(),
                'ingresos' => $citas->where('estado', 'completada')->sum('precio'),
            ],
            'filtros' => compact('fechaInicio', 'fechaFin', 'tecnicoId', 'estado'),
        ];
    }
}

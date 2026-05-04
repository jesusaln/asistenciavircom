<?php

namespace App\Services\Reports;

use App\Models\Venta;
use App\Models\Compra;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Cita;
use App\Models\Mantenimiento;
use App\Models\Renta;
use App\Models\User;
use App\Models\BitacoraActividad;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardReportService
{
    public function getEstadisticasGenerales($fechaInicio, $fechaFin): array
    {
        $cacheKey = "dashboard_stats_{$fechaInicio}_{$fechaFin}";

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 600, function () use ($fechaInicio, $fechaFin) {
            $totalVentas = Venta::whereBetween('fecha', [$fechaInicio, $fechaFin])
                ->where('estado', \App\Enums\EstadoVenta::Aprobada)
                ->sum('total');
            
            // Cálculo de utilidad (se mantiene el cursor por la complejidad de márgenes técnicos, pero ahora en Redis es más rápido)
            $utilidadVentas = 0;
            Venta::whereBetween('fecha', [$fechaInicio, $fechaFin])
                ->where('estado', \App\Enums\EstadoVenta::Aprobada)
                ->with(['productos', 'servicios', 'vendedor'])
                ->cursor()
                ->each(function ($venta) use (&$utilidadVentas) {
                    $utilidadVentas += (float) $venta->ganancia_total;
                });

            $productosVendidos = DB::table('venta_items')
                ->join('ventas', 'ventas.id', '=', 'venta_items.venta_id')
                ->whereBetween('ventas.fecha', [$fechaInicio, $fechaFin])
                ->where('ventable_type', \App\Models\Producto::class)
                ->sum('cantidad');

            $totalClientes = Cliente::count();
            $clientesActivos = Cliente::whereHas('ventas', function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
            })->count();

            $totalProductos = Producto::count();
            $productosBajos = Producto::whereRaw('stock <= stock_minimo')->count();
            $valorInventario = Producto::sum(DB::raw('stock * COALESCE(precio_compra, 0)'));

            $citasCompletadas = Cita::whereBetween('fecha_hora', [$fechaInicio, $fechaFin])
                ->where('estado', 'completada')->count();
            $mantenimientos = Mantenimiento::whereBetween('fecha', [$fechaInicio, $fechaFin])->count();
            $ingresosServicios = Cita::whereBetween('fecha_hora', [$fechaInicio, $fechaFin])
                ->where('estado', 'completada')->sum('total');

            $rentasActivas = Renta::where('estado', 'activa')->count();
            $totalCobradoRentas = DB::table('cobranzas')
                ->whereIn('estado', ['pagado', 'parcial'])
                ->whereBetween('fecha_pago', [$fechaInicio, $fechaFin])
                ->sum('monto_pagado');

            $pendienteCobrarRentas = Renta::where('estado', 'activa')
                ->sum(DB::raw('ROUND(CAST(monto_mensual * COALESCE(meses_duracion, 1) AS numeric), 2)'));

            $gastosTotales = Compra::whereBetween('fecha_compra', [$fechaInicio, $fechaFin])->sum('total');

            $totalEmpleados = User::count();
            $tecnicosActivos = User::tecnicosActivos()->count();

            $actividadesHoy = BitacoraActividad::whereDate('created_at', Carbon::today())->count();
            $usuariosActivos = User::where('activo', true)->count();

            return [
                'ventas' => [
                    'total' => $totalVentas,
                    'utilidad' => $utilidadVentas,
                    'productos_vendidos' => $productosVendidos,
                ],
                'clientes' => [
                    'total' => $totalClientes,
                    'activos' => $clientesActivos,
                    'deudores' => 0,
                ],
                'inventario' => [
                    'total_productos' => $totalProductos,
                    'productos_bajos' => $productosBajos,
                    'valor_inventario' => $valorInventario,
                ],
                'servicios' => [
                    'citas_completadas' => $citasCompletadas,
                    'mantenimientos' => $mantenimientos,
                    'ingresos_servicios' => $ingresosServicios,
                ],
                'rentas' => [
                    'rentas_activas' => $rentasActivas,
                    'total_cobrado' => $totalCobradoRentas,
                    'pendiente_cobrar' => $pendienteCobrarRentas,
                ],
                'finanzas' => [
                    'ingresos_totales' => $totalVentas + $ingresosServicios + $totalCobradoRentas,
                    'gastos_totales' => $gastosTotales,
                    'ganancia_neta' => ($totalVentas + $ingresosServicios + $totalCobradoRentas) - $gastosTotales,
                ],
                'personal' => [
                    'total_empleados' => $totalEmpleados,
                    'tecnicos_activos' => $tecnicosActivos,
                    'ventas_por_tecnico' => 0,
                ],
                'auditoria' => [
                    'actividades_hoy' => $actividadesHoy,
                    'usuarios_activos' => $usuariosActivos,
                ],
                'grafica_ventas' => $this->getGraficaVentasData($fechaInicio, $fechaFin),
            ];
        });
    }

    public function getGraficaVentasData($fechaInicio, $fechaFin): array
    {
        $ventas = Venta::select(
            DB::raw('DATE(fecha) as fecha'),
            DB::raw('SUM(total) as total')
        )
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        return [
            'labels' => $ventas->pluck('fecha'),
            'datasets' => [
                [
                    'label' => 'Ventas',
                    'data' => $ventas->pluck('total'),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
            ],
        ];
    }
}

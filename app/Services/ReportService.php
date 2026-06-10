<?php

namespace App\Services;

use App\Services\Reports\ClientReportService;
use App\Services\Reports\DashboardReportService;
use App\Services\Reports\FinanceReportService;
use App\Services\Reports\InventoryReportService;
use App\Services\Reports\OperationReportService;
use App\Services\Reports\ServiceReportService;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function __construct(
        protected ClientReportService $clientService,
        protected DashboardReportService $dashboardService,
        protected FinanceReportService $financeService,
        protected InventoryReportService $inventoryService,
        protected OperationReportService $operationService,
        protected ServiceReportService $serviceService
    ) {
    }

    /**
     * Obtener estadísticas generales para el dashboard
     */
    /**
     * Obtener estadísticas generales para el dashboard
     */
    public function getEstadisticasGenerales($fechaInicio, $fechaFin)
    {
        return $this->dashboardService->getEstadisticasGenerales($fechaInicio, $fechaFin);
    }

    private function getGraficaVentasData($fechaInicio, $fechaFin)
    {
        return $this->dashboardService->getGraficaVentasData($fechaInicio, $fechaFin);
    }

    public function getGastosOperativosData($fechaInicio, $fechaFin)
    {
        // Totales - Optimizado
        $stats = DB::table('compras')
            ->where('tipo', 'gasto')
            ->whereBetween('fecha_compra', [$fechaInicio, $fechaFin])
            ->whereNull('deleted_at')
            ->select(DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as count'))
            ->first();

        // Agrupación por categoría - Optimizado SQL
        $porCategoria = DB::table('compras')
            ->leftJoin('categoria_gastos', 'compras.categoria_gasto_id', '=', 'categoria_gastos.id')
            ->where('compras.tipo', 'gasto')
            ->whereBetween('compras.fecha_compra', [$fechaInicio, $fechaFin])
            ->whereNull('compras.deleted_at')
            ->select(
                DB::raw('COALESCE(categoria_gastos.nombre, \'Sin categoría\') as categoria'),
                DB::raw('SUM(compras.total) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('categoria_gastos.nombre')
            ->orderByDesc('total')
            ->get();

        // Limitamos los detalles para prevenir OOM en reportes grandes
        $gastos = Compra::with(['proveedor', 'categoriaGasto'])
            ->where('tipo', 'gasto')
            ->whereBetween('fecha_compra', [$fechaInicio, $fechaFin])
            ->orderByDesc('fecha_compra')
            ->limit(500) // Safety limit per user feedback
            ->get();

        return [
            'gastos' => $gastos,
            'totales' => [
                'total' => $stats->total ?? 0,
                'count' => $stats->count ?? 0,
            ],
            'porCategoria' => $porCategoria,
        ];
    }

    public function getComprasProveedoresData($fechaInicio, $fechaFin, $proveedorId = null)
    {
        $baseQuery = DB::table('compras')
            ->where('tipo', 'compra')
            ->whereBetween('fecha_compra', [$fechaInicio, $fechaFin])
            ->whereNull('deleted_at');

        if ($proveedorId) {
            $baseQuery->where('proveedor_id', $proveedorId);
        }

        // Stats
        $stats = (clone $baseQuery)
            ->select(DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as count'))
            ->first();

        // Top Proveedores - Optimizado SQL
        $topProveedoresQuery = (clone $baseQuery);
        // Si hay proveedorId, la agrupación será de 1 solo, pero funciona igual
        $topProveedores = $topProveedoresQuery
            ->join('proveedores', 'compras.proveedor_id', '=', 'proveedores.id')
            ->select(
                'proveedores.nombre_razon_social as proveedor',
                DB::raw('SUM(compras.total) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('proveedores.id', 'proveedores.nombre_razon_social')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Detalles limitados
        $compras = Compra::with(['proveedor'])
            ->where('tipo', 'compra')
            ->whereBetween('fecha_compra', [$fechaInicio, $fechaFin]);

        if ($proveedorId) {
            $compras->where('proveedor_id', $proveedorId);
        }

        return [
            'compras' => $compras->orderByDesc('fecha_compra')->limit(500)->get(),
            'totales' => [
                'total' => $stats->total ?? 0,
                'count' => $stats->count ?? 0,
            ],
            'topProveedores' => $topProveedores,
        ];
    }

    public function getBalanceComparativoData($fechaInicio, $fechaFin)
    {
        $ventas = Venta::whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->where('estado', '!=', \App\Enums\EstadoVenta::Cancelada->value)
            ->sum('total');
        $compras = Compra::where('tipo', 'compra')->whereBetween('fecha_compra', [$fechaInicio, $fechaFin])->sum('total');
        $gastos = Compra::where('tipo', 'gasto')->whereBetween('fecha_compra', [$fechaInicio, $fechaFin])->sum('total');

        return [
            'balance' => [
                'ventas' => round($ventas, 2),
                'compras' => round($compras, 2),
                'gastos' => round($gastos, 2),
                'total_egresos' => round($compras + $gastos, 2),
                'diferencia' => round($ventas - ($compras + $gastos), 2),
            ],
            'metricas' => [
                'margen' => $ventas > 0 ? (($ventas - $compras) / $ventas) * 100 : 0,
            ],
            'grafica' => [
                'labels' => ['Ventas', 'Compras', 'Gastos'],
                'data' => [$ventas, $compras, $gastos],
            ]
        ];
    }
}

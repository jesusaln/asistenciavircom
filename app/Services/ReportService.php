<?php

namespace App\Services;

use App\Services\Reports\ClientReportService;
use App\Services\Reports\FinanceReportService;
use App\Services\Reports\InventoryReportService;
use App\Services\Reports\OperationReportService;
use App\Services\Reports\ServiceReportService;
use App\Models\Venta;
use App\Models\Compra;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Cita;
use App\Models\Mantenimiento;
use App\Models\Renta;
use App\Models\User;
use App\Models\BitacoraActividad;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function __construct(
        protected ClientReportService $clientService,
        protected FinanceReportService $financeService,
        protected InventoryReportService $inventoryService,
        protected OperationReportService $operationService,
        protected ServiceReportService $serviceService
    ) {
    }

    /**
     * Obtener estadísticas generales para el dashboard
     */
    public function getEstadisticasGenerales($fechaInicio, $fechaFin)
    {
        // Ventas
        $ventas = Venta::whereBetween('fecha', [$fechaInicio, $fechaFin])->get();
        $totalVentas = $ventas->sum('total');
        $utilidadVentas = $ventas->sum('ganancia_total');
        $productosVendidos = DB::table('venta_items')
            ->join('ventas', 'ventas.id', '=', 'venta_items.venta_id')
            ->whereBetween('ventas.fecha', [$fechaInicio, $fechaFin])
            ->where('ventable_type', \App\Models\Producto::class)
            ->sum('cantidad');

        // Clientes
        $totalClientes = Cliente::count();
        $clientesActivos = Cliente::whereHas('ventas', function ($q) use ($fechaInicio, $fechaFin) {
            $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
        })->count();

        // Inventario
        $totalProductos = Producto::count();
        $productosBajos = Producto::whereRaw('stock <= stock_minimo')->count();
        $valorInventario = Producto::sum(DB::raw('stock * COALESCE(precio_compra, 0)'));

        // Servicios
        $citasCompletadas = Cita::whereBetween('fecha_hora', [$fechaInicio, $fechaFin])
            ->where('estado', 'completada')->count();
        $mantenimientos = Mantenimiento::whereBetween('fecha', [$fechaInicio, $fechaFin])->count();
        $ingresosServicios = Cita::whereBetween('fecha_hora', [$fechaInicio, $fechaFin])
            ->where('estado', 'completada')->sum('total'); // Cita also uses 'total' instead of 'precio' based on schema? Wait, let me check.

        // Rentas
        $rentasActivas = Renta::where('estado', 'activa')->count();
        $totalCobradoRentas = DB::table('cobranzas')
            ->whereIn('estado', ['pagado', 'parcial'])
            ->whereBetween('fecha_pago', [$fechaInicio, $fechaFin])
            ->sum('monto_pagado');
        $pendienteCobrarRentas = Renta::where('estado', 'activa')->sum(DB::raw('monto_mensual * COALESCE(meses_duracion, 1)'));

        // Finanzas
        $gastosTotales = Compra::whereBetween('fecha_compra', [$fechaInicio, $fechaFin])->sum('total');

        // Personal
        $totalEmpleados = User::count();
        $tecnicosActivos = User::tecnicosActivos()->count();

        // Auditoría
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
                'deudores' => 0, // Placeholder
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
    }

    private function getGraficaVentasData($fechaInicio, $fechaFin)
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
                ]
            ]
        ];
    }

    public function getGastosOperativosData($fechaInicio, $fechaFin)
    {
        $gastos = Compra::with(['proveedor', 'categoria'])
            ->where('tipo', 'gasto')
            ->whereBetween('fecha_compra', [$fechaInicio, $fechaFin])
            ->get();

        $porCategoria = $gastos->groupBy('categoria_id')->map(function ($items) {
            return [
                'categoria' => $items->first()->categoria?->nombre ?? 'Sin categoría',
                'total' => $items->sum('total'),
                'count' => $items->count(),
            ];
        })->values();

        return [
            'gastos' => $gastos,
            'totales' => [
                'total' => $gastos->sum('total'),
                'count' => $gastos->count(),
            ],
            'porCategoria' => $porCategoria,
        ];
    }

    public function getComprasProveedoresData($fechaInicio, $fechaFin, $proveedorId = null)
    {
        $query = Compra::with(['proveedor'])
            ->where('tipo', 'compra')
            ->whereBetween('fecha_compra', [$fechaInicio, $fechaFin]);

        if ($proveedorId) {
            $query->where('proveedor_id', $proveedorId);
        }

        $compras = $query->get();

        $topProveedores = $compras->groupBy('proveedor_id')->map(function ($items) {
            return [
                'proveedor' => $items->first()->proveedor?->nombre_razon_social ?? 'Sin proveedor',
                'total' => $items->sum('total'),
                'count' => $items->count(),
            ];
        })->sortByDesc('total')->take(5)->values();

        return [
            'compras' => $compras,
            'totales' => [
                'total' => $compras->sum('total'),
                'count' => $compras->count(),
            ],
            'topProveedores' => $topProveedores,
        ];
    }

    public function getBalanceComparativoData($fechaInicio, $fechaFin)
    {
        $ventas = Venta::whereBetween('fecha', [$fechaInicio, $fechaFin])->sum('total');
        $compras = Compra::where('tipo', 'compra')->whereBetween('fecha_compra', [$fechaInicio, $fechaFin])->sum('total');
        $gastos = Compra::where('tipo', 'gasto')->whereBetween('fecha_compra', [$fechaInicio, $fechaFin])->sum('total');

        return [
            'balance' => [
                'ventas' => $ventas,
                'compras' => $compras,
                'gastos' => $gastos,
                'total_egresos' => $compras + $gastos,
                'diferencia' => $ventas - ($compras + $gastos),
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

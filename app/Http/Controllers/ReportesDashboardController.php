<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\Venta;
use App\Models\Compra;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Cita;
use App\Models\Mantenimiento;
use App\Models\Renta;
use App\Models\Cobranza;
use App\Models\User;
use Carbon\Carbon;
use App\Services\ReportService;

class ReportesDashboardController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Mostrar el dashboard de reportes organizado por categorías
     */
    public function index(Request $request)
    {
        $periodo = $request->get('periodo', 'mes'); // dia, semana, mes, trimestre, año

        // Determinar rango de fechas
        $rangoFechas = $this->determinarRangoFechas($periodo);
        $fechaInicio = $rangoFechas['inicio'];
        $fechaFin = $rangoFechas['fin'];

        // Estadísticas generales usando el servicio
        $estadisticasGenerales = $this->reportService->getEstadisticasGenerales($fechaInicio, $fechaFin);

        // Categorías de reportes
        $categorias = [
            'ventas' => [
                'titulo' => 'Ventas',
                'descripcion' => 'Reportes de ventas, productos y utilidades',
                'reportes' => [
                    ['nombre' => 'Ventas Generales', 'ruta' => 'reportes.ventas', 'icono' => 'fas fa-shopping-cart'],
                    ['nombre' => 'Ventas Pendientes', 'ruta' => 'reportes.ventas-pendientes', 'icono' => 'fas fa-clock'],
                    ['nombre' => 'Balance Ventas vs Compras', 'ruta' => 'reportes.balance-comparativo', 'icono' => 'fas fa-balance-scale'],
                    ['nombre' => 'Productos Más Vendidos', 'ruta' => 'reportes.productos', 'icono' => 'fas fa-box'],
                ],
                'estadisticas' => [
                    'total' => $estadisticasGenerales['ventas']['total'],
                    'utilidad' => $estadisticasGenerales['ventas']['utilidad'],
                    'productos_vendidos' => $estadisticasGenerales['ventas']['productos_vendidos'],
                ],
            ],
            'pagos' => [
                'titulo' => 'Pagos y Cobranzas',
                'descripcion' => 'Control de pagos, cortes diarios y cobranzas',
                'reportes' => [
                    ['nombre' => 'Corte de Pagos', 'ruta' => 'reportes.corte-diario', 'icono' => 'fas fa-cash-register'],
                    ['nombre' => 'Cobranzas', 'ruta' => 'reportes.cobranzas', 'icono' => 'fas fa-money-bill-wave'],
                    ['nombre' => 'Gastos Operativos', 'ruta' => 'reportes.gastos-operativos', 'icono' => 'fas fa-file-invoice-dollar'],
                    ['nombre' => 'Antigüedad de Saldos', 'ruta' => 'reportes.antiguedad-saldos', 'icono' => 'fas fa-hourglass-half'],
                ],
                'estadisticas' => [
                    'total_cobrado' => $estadisticasGenerales['rentas']['total_cobrado'],
                    'cobranzas_pendientes' => $estadisticasGenerales['rentas']['pendiente_cobrar'],
                ],
            ],
            'clientes' => [
                'titulo' => 'Clientes',
                'descripcion' => 'Información de clientes y comportamiento',
                'reportes' => [
                    ['nombre' => 'Clientes Activos', 'ruta' => 'reportes.clientes', 'icono' => 'fas fa-users'],
                    ['nombre' => 'Clientes Deudores', 'ruta' => 'reportes.clientes', 'icono' => 'fas fa-user-times'],
                ],
                'estadisticas' => [
                    'total' => $estadisticasGenerales['clientes']['total'],
                    'activos' => $estadisticasGenerales['clientes']['activos'],
                    'deudores' => $estadisticasGenerales['clientes']['deudores'],
                ],
            ],
            'inventario' => [
                'titulo' => 'Inventario',
                'descripcion' => 'Control de stock y productos',
                'reportes' => [
                    ['nombre' => 'Productos en Stock', 'ruta' => 'reportes.inventario', 'icono' => 'fas fa-warehouse'],
                    ['nombre' => 'Productos Bajos', 'ruta' => 'reportes.inventario', 'icono' => 'fas fa-exclamation-triangle'],
                    ['nombre' => 'Movimientos Inventario', 'url' => '/reportes?tab=movimientos', 'icono' => 'fas fa-exchange-alt'],
                ],
                'estadisticas' => [
                    'total_productos' => $estadisticasGenerales['inventario']['total_productos'],
                    'productos_bajos' => $estadisticasGenerales['inventario']['productos_bajos'],
                    'valor_inventario' => $estadisticasGenerales['inventario']['valor_inventario'],
                ],
            ],
            'servicios' => [
                'titulo' => 'Servicios',
                'descripcion' => 'Citas, mantenimientos y servicios técnicos',
                'reportes' => [
                    ['nombre' => 'Servicios Más Vendidos', 'ruta' => 'reportes.servicios', 'icono' => 'fas fa-tools'],
                    ['nombre' => 'Citas Programadas', 'ruta' => 'reportes.citas', 'icono' => 'fas fa-calendar-check'],
                    ['nombre' => 'Mantenimientos', 'ruta' => 'reportes.mantenimientos', 'icono' => 'fas fa-wrench'],
                ],
                'estadisticas' => [
                    'citas_completadas' => $estadisticasGenerales['servicios']['citas_completadas'],
                    'mantenimientos' => $estadisticasGenerales['servicios']['mantenimientos'],
                    'ingresos_servicios' => $estadisticasGenerales['servicios']['ingresos_servicios'],
                ],
            ],
            'rentas' => [
                'titulo' => 'Rentas y Equipos',
                'descripcion' => 'Gestión de rentas de equipos',
                'reportes' => [
                    ['nombre' => 'Rentas Activas', 'ruta' => 'reportes.rentas', 'icono' => 'fas fa-handshake'],
                ],
                'estadisticas' => [
                    'rentas_activas' => $estadisticasGenerales['rentas']['rentas_activas'],
                    'total_cobrado' => $estadisticasGenerales['rentas']['total_cobrado'],
                    'pendiente_cobrar' => $estadisticasGenerales['rentas']['pendiente_cobrar'],
                ],
            ],
            'finanzas' => [
                'titulo' => 'Finanzas',
                'descripcion' => 'Análisis financiero y ganancias',
                'reportes' => [
                    ['nombre' => 'Ganancias Generales', 'ruta' => 'reportes.ganancias', 'icono' => 'fas fa-chart-line'],
                    ['nombre' => 'Corte de Pagos', 'ruta' => 'reportes.corte-diario', 'icono' => 'fas fa-cash-register'],
                    ['nombre' => 'Compras', 'ruta' => 'compras.index', 'icono' => 'fas fa-shopping-bag'],
                    ['nombre' => 'Proveedores', 'ruta' => 'reportes.proveedores', 'icono' => 'fas fa-truck'],
                ],
                'estadisticas' => [
                    'ingresos_totales' => $estadisticasGenerales['finanzas']['ingresos_totales'],
                    'gastos_totales' => $estadisticasGenerales['finanzas']['gastos_totales'],
                    'ganancia_neta' => $estadisticasGenerales['finanzas']['ganancia_neta'],
                ],
            ],
            'compras' => [
                'titulo' => 'Compras y Proveedores',
                'descripcion' => 'Análisis de adquisiciones y proveedores',
                'reportes' => [
                    ['nombre' => 'Compras a Proveedores', 'ruta' => 'reportes.compras-proveedores', 'icono' => 'fas fa-truck-loading'],
                    ['nombre' => 'Proveedores Principales', 'ruta' => 'reportes.proveedores', 'icono' => 'fas fa-truck'],
                ],
                'estadisticas' => [
                    'total_compras' => $estadisticasGenerales['finanzas']['gastos_totales'],
                    'mejor_proveedor' => 'N/A',
                ],
            ],
            'personal' => [
                'titulo' => 'Personal',
                'descripcion' => 'Empleados, técnicos y rendimiento',
                'reportes' => [
                    ['nombre' => 'Técnicos', 'ruta' => 'reportes.tecnicos', 'icono' => 'fas fa-user-cog'],
                    ['nombre' => 'Empleados', 'ruta' => 'reportes.empleados', 'icono' => 'fas fa-users-cog'],
                ],
                'estadisticas' => [
                    'total_empleados' => $estadisticasGenerales['personal']['total_empleados'],
                    'tecnicos_activos' => $estadisticasGenerales['personal']['tecnicos_activos'],
                    'ventas_por_tecnico' => $estadisticasGenerales['personal']['ventas_por_tecnico'],
                ],
            ],
            'auditoria' => [
                'titulo' => 'Auditoría',
                'descripcion' => 'Registro de actividades del sistema',
                'reportes' => [
                    ['nombre' => 'Bitácora de Actividades', 'ruta' => 'reportes.auditoria', 'icono' => 'fas fa-history'],
                ],
                'estadisticas' => [
                    'actividades_hoy' => $estadisticasGenerales['auditoria']['actividades_hoy'],
                    'usuarios_activos' => $estadisticasGenerales['auditoria']['usuarios_activos'],
                ],
            ],
        ];

        return Inertia::render('Reportes/Dashboard', [
            'categorias' => $categorias,
            'periodo' => $periodo,
            'graficas' => $estadisticasGenerales['grafica_ventas'] ?? null,
        ]);
    }

    /**
     * Mostrar el centro de reportes con tabs
     */
    public function indexTabs(Request $request)
    {
        $tab = $request->get('tab', 'ventas');

        // Los datos se cargarán abajo según el tab

        // Inicializar variables
        $ventas = [];
        $compras = [];
        $inventario = [];
        $movimientos = [];
        $prestamos = [];

        // Variables para nuevos reportes
        $servicios = [];
        $citas = [];
        $mantenimientos = [];
        $rentas = [];
        $cobranzas = [];
        $ganancias = [];
        $proveedores = [];
        $personal = [];
        $auditoria = [];
        $productos = [];
        // Nuevas variables
        $gastosOperativos = [];
        $comprasProveedores = [];
        $balanceData = [];

        // Cargar datos según el tab activo
        if ($tab === 'ventas') {
            $ventas = Venta::with(['cliente', 'items.ventable'])->latest()->take(100)->get()->map(function ($venta) {
                $venta->costo_total = $venta->calcularCostoTotal();
                return $venta;
            });
        } elseif ($tab === 'compras') {
            $compras = Compra::with(['proveedor'])->latest()->take(100)->get();
        } elseif ($tab === 'inventario') {
            $inventario = Producto::with('categoria')->take(500)->get();
            $movimientos = \App\Models\InventarioMovimiento::with(['producto', 'user'])->latest()->take(50)->get();
        } elseif ($tab === 'prestamos') {
            $prestamos = \App\Models\Prestamo::with(['cliente', 'pagos'])->latest()->take(100)->get();
        } elseif ($tab === 'servicios') {
            $servicios = \App\Models\VentaItem::select(
                'ventable_id',
                DB::raw('count(*) as total_cantidad'),
                DB::raw('sum(subtotal) as total_ingreso')
            )
                ->where('ventable_type', Servicio::class)
                ->with('ventable')
                ->groupBy('ventable_id')
                ->orderByDesc('total_ingreso')
                ->take(50)
                ->get();
        } elseif ($tab === 'citas') {
            $citas = Cita::with(['cliente', 'tecnico', 'servicio'])->latest()->take(100)->get();
        } elseif ($tab === 'mantenimientos') {
            $mantenimientos = Mantenimiento::with(['carro.cliente', 'servicio'])->latest()->take(100)->get();
        } elseif ($tab === 'rentas') {
            $rentas = Renta::with(['cliente', 'equipo'])->latest()->take(100)->get();
        } elseif ($tab === 'cobranzas') {
            $cobranzas = Cobranza::with(['renta.cliente'])->latest()->take(100)->get();
        } elseif ($tab === 'ganancias') {
            $ganancias = [
                'ventas' => Venta::where('estado', 'aprobada')->sum('total'),
                'compras' => Compra::where('tipo', 'compra')->where('estado', 'procesada')->sum('total'),
                'gastos' => Compra::where('tipo', 'gasto')->where('estado', 'procesada')->sum('total'),
            ];
        } elseif ($tab === 'proveedores') {
            $proveedores = \App\Models\Proveedor::withCount([
                'compras' => function ($query) {
                    $query->where('tipo', '!=', 'gasto');
                }
            ])->orderByDesc('compras_count')->take(100)->get();
        } elseif ($tab === 'personal') {
            $personal = User::withCount(['ventas', 'citas', 'mantenimientos'])->get();
        } elseif ($tab === 'auditoria') {
            $auditoria = \App\Models\BitacoraActividad::with('user')->latest()->take(100)->get();
        } elseif ($tab === 'productos') {
            $productos = \App\Models\VentaItem::select(
                'ventable_id',
                DB::raw('count(*) as count'),
                DB::raw('sum(cantidad) as total_vendidos'),
                DB::raw('sum(subtotal) as ingreso_total')
            )
                ->where('ventable_type', Producto::class)
                ->with('ventable')
                ->groupBy('ventable_id')
                ->orderByDesc('ingreso_total')
                ->take(50)
                ->get();
        } elseif ($tab === 'gastos') {
            $gastosOperativos = app(ReportService::class)->getGastosOperativosData(now()->startOfMonth(), now());
        } elseif ($tab === 'balance') {
            $balanceData = app(ReportService::class)->getBalanceComparativoData(now()->startOfMonth(), now());
        } elseif ($tab === 'corte') {
            $corteResult = $this->getCorteDiarioData($request);
            $cobranzas = $corteResult['pagos'];
            // Opcional: pasar otros datos del corte si son necesarios en Index.vue
        }

        // Obtener valor inventario si es necesario (o pasarlo siempre si es ligero)
        $valorInventario = 0;
        if ($tab === 'inventario') {
            $valorInventario = Producto::sum(DB::raw('stock * COALESCE(precio_compra, 0)'));
        }

        return Inertia::render('Reportes/Index', [
            'activeTab' => $tab, // Pasa el tab activo al frontend
            'reportesVentas' => $ventas,
            'corteVentas' => collect($ventas)->sum('total'), // aprox
            'utilidadVentas' => collect($ventas)->sum('ganancia_total'), // aprox
            'ivaVentas' => collect($ventas)->sum('iva'), // aprox
            'reportesCompras' => $compras,
            'totalCompras' => collect($compras)->sum('total'),
            'inventario' => $inventario,
            'valorInventario' => $valorInventario,
            'movimientosInventario' => $movimientos,
            'corteDiario' => $cobranzas, // Usamos la variable cobranzas que cargamos en el else if
            'corteMeta' => isset($corteResult) ? $corteResult : [], // Para labels y periodos
            'corteDiarioFormatted' => isset($corteResult) ? $corteResult['pagos'] : [],
            'corteDiarioTotales' => isset($corteResult) ? $corteResult['totalesPorMetodo'] : [],
            'corteDiarioTotal' => isset($corteResult) ? $corteResult['totalGeneral'] : 0,
            'corteDiarioFecha' => isset($corteResult) ? $corteResult['fecha'] : now()->format('Y-m-d'),
            'corteDiarioFormateada' => isset($corteResult) ? $corteResult['fechaFormateada'] : '',
            'usuarios' => User::select('id', 'name')->get(),
            'prestamos' => $prestamos,
            // Nuevos datos
            'reportesServicios' => $servicios,
            'reportesCitas' => $citas,
            'reportesMantenimientos' => $mantenimientos,
            'reportesRentas' => $rentas,
            'reportesCobranzas' => $cobranzas,
            'reportesGanancias' => $ganancias,
            'reportesProveedores' => $proveedores,
            'reportesPersonal' => $personal,
            'reportesAuditoria' => $auditoria,
            'reportesProductos' => $productos,
            'gastosOperativos' => $gastosOperativos,
            'balanceData' => $balanceData,
        ]);
    }

    private function determinarRangoFechas(string $periodo): array
    {
        $hoy = Carbon::now();

        switch ($periodo) {
            case 'dia':
                return [
                    'inicio' => $hoy->copy()->startOfDay(),
                    'fin' => $hoy->copy()->endOfDay(),
                ];
            case 'semana':
                return [
                    'inicio' => $hoy->copy()->startOfWeek(),
                    'fin' => $hoy->copy()->endOfWeek(),
                ];
            case 'mes':
                return [
                    'inicio' => $hoy->copy()->startOfMonth(),
                    'fin' => $hoy->copy()->endOfMonth(),
                ];
            case 'trimestre':
                return [
                    'inicio' => $hoy->copy()->startOfQuarter(),
                    'fin' => $hoy->copy()->endOfQuarter(),
                ];
            case 'año':
                return [
                    'inicio' => $hoy->copy()->startOfYear(),
                    'fin' => $hoy->copy()->endOfYear(),
                ];
            default:
                return [
                    'inicio' => $hoy->copy()->startOfMonth(),
                    'fin' => $hoy->copy()->endOfMonth(),
                ];
        }
    }

    /**
     * Obtener datos del corte de pagos por período (sin renderizar vista)
     */
    private function getCorteDiarioData(Request $request)
    {
        $periodo = $request->get('periodo', 'diario');
        $fecha = $request->get('fecha', now()->format('Y-m-d'));

        // Calcular fechas de inicio y fin según el período
        $fecha_inicio = $fecha_fin = $fecha;
        $periodoLabel = 'Diario';

        if ($periodo === 'diario') {
            $fecha_inicio = $fecha_fin = $fecha;
            $periodoLabel = 'Diario';
        } elseif ($periodo === 'semanal') {
            $carbon = Carbon::parse($fecha);
            $fecha_inicio = $carbon->startOfWeek()->format('Y-m-d');
            $fecha_fin = $carbon->endOfWeek()->format('Y-m-d');
            $periodoLabel = 'Semanal';
        } elseif ($periodo === 'mensual') {
            $carbon = Carbon::parse($fecha);
            $fecha_inicio = $carbon->startOfMonth()->format('Y-m-d');
            $fecha_fin = $carbon->endOfMonth()->format('Y-m-d');
            $periodoLabel = 'Mensual';
        } elseif ($periodo === 'anual') {
            $carbon = Carbon::parse($fecha);
            $fecha_inicio = $carbon->startOfYear()->format('Y-m-d');
            $fecha_fin = $carbon->endOfYear()->format('Y-m-d');
            $periodoLabel = 'Anual';
        } elseif ($periodo === 'personalizado') {
            $fecha_inicio = $request->get('fecha_inicio', $fecha);
            $fecha_fin = $request->get('fecha_fin', $fecha);
            $periodoLabel = 'Personalizado';
        }

        // Obtener ventas pagadas en el período especificado
        $ventasPagadas = Venta::with(['cliente', 'items.ventable'])
            ->where('pagado', true)
            ->where('fecha_pago', '>=', $fecha_inicio . ' 00:00:00')
            ->where('fecha_pago', '<=', $fecha_fin . ' 23:59:59')
            ->orderBy('fecha_pago', 'desc')
            ->get();

        // Obtener cobranzas pagadas en el período especificado
        $cobranzasPagadas = Cobranza::with(['renta.cliente', 'responsableCobro'])
            ->whereIn('estado', ['pagado', 'parcial'])
            ->where('fecha_pago', '>=', $fecha_inicio . ' 00:00:00')
            ->where('fecha_pago', '<=', $fecha_fin . ' 23:59:59')
            ->orderBy('fecha_pago', 'desc')
            ->get();

        // Calcular totales por método de pago
        $totalesPorMetodo = [
            'efectivo' => 0,
            'transferencia' => 0,
            'cheque' => 0,
            'tarjeta' => 0,
            'otros' => 0,
        ];

        $totalGeneral = 0;

        // Procesar ventas
        foreach ($ventasPagadas as $venta) {
            $metodo = $venta->metodo_pago ?? 'otros';
            if (isset($totalesPorMetodo[$metodo])) {
                $totalesPorMetodo[$metodo] += $venta->total;
            } else {
                $totalesPorMetodo['otros'] += $venta->total;
            }
            $totalGeneral += $venta->total;
        }

        // Procesar cobranzas
        foreach ($cobranzasPagadas as $cobranza) {
            $metodo = $cobranza->metodo_pago ?? 'otros';
            if (isset($totalesPorMetodo[$metodo])) {
                $totalesPorMetodo[$metodo] += $cobranza->monto_pagado;
            } else {
                $totalesPorMetodo['otros'] += $cobranza->monto_pagado;
            }
            $totalGeneral += $cobranza->monto_pagado;
        }

        // Formatear datos para la vista
        $ventasFormateadas = $ventasPagadas->map(function ($venta) {
            return [
                'id' => $venta->id,
                'tipo' => 'venta',
                'numero' => $venta->numero_venta,
                'cliente' => $venta->cliente->nombre_razon_social ?? 'Sin cliente',
                'concepto' => 'Venta',
                'total' => $venta->total,
                'metodo_pago' => $venta->metodo_pago,
                'fecha_pago' => $venta->fecha_pago ? $venta->fecha_pago->toIso8601String() : null,
                'notas_pago' => $venta->notas_pago,
                'pagado_por' => $venta->pagadoPor?->name ?? 'Sistema',
            ];
        });

        $cobranzasFormateadas = $cobranzasPagadas->map(function ($cobranza) {
            return [
                'id' => $cobranza->id,
                'tipo' => 'cobranza',
                'numero' => $cobranza->renta->numero_contrato ?? 'N/A',
                'cliente' => $cobranza->renta->cliente->nombre_razon_social ?? 'Sin cliente',
                'concepto' => $cobranza->concepto ?? 'Cobranza',
                'total' => $cobranza->monto_pagado,
                'metodo_pago' => $cobranza->metodo_pago,
                'fecha_pago' => $cobranza->fecha_pago ? $cobranza->fecha_pago->toIso8601String() : null,
                'notas_pago' => $cobranza->notas_pago,
                'pagado_por' => $cobranza->responsableCobro?->name ?? 'Sistema',
            ];
        });

        // Combinar y ordenar por fecha de pago
        $pagosFormateados = collect([...$ventasFormateadas, ...$cobranzasFormateadas])
            ->sortByDesc('fecha_pago')
            ->values();

        return [
            'pagos' => $pagosFormateados,
            'totalesPorMetodo' => $totalesPorMetodo,
            'totalGeneral' => $totalGeneral,
            'periodo' => $periodo,
            'periodoLabel' => $periodoLabel,
            'fecha' => $fecha,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'fechaFormateada' => $periodo === 'personalizado'
                ? "Del " . Carbon::parse($fecha_inicio)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') . " al " . Carbon::parse($fecha_fin)->locale('es')->isoFormat('D [de] MMMM [de] YYYY')
                : Carbon::parse($fecha)->locale('es')->isoFormat($periodo === 'diario' ? 'dddd, D [de] MMMM [de] YYYY' : ($periodo === 'semanal' ? '[Semana del] D [de] MMMM [de] YYYY' : ($periodo === 'mensual' ? 'MMMM [de] YYYY' : 'YYYY'))),
        ];
    }
}

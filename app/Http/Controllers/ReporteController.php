<?php

// app/Http/Controllers/ReporteController.php

namespace App\Http\Controllers;

use App\Models\Reporte;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Venta;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Cobranza;
use App\Models\Cliente;
use App\Models\Servicio;
use App\Models\Cita;
use App\Models\Mantenimiento;
use App\Models\Renta;
use App\Models\Equipo;
use App\Models\User;
use App\Models\BitacoraActividad;
use App\Models\Prestamo;
use App\Models\PagoPrestamo;
use App\Models\CuentasPorCobrar;
use App\Models\VentaItem;
use App\Models\CategoriaGasto;
use App\Models\Proveedor;
use App\Services\Reports\InventoryReportService;
use App\Services\Reports\FinanceReportService;
use App\Services\Reports\ClientReportService;
use App\Services\Reports\ServiceReportService;
use App\Services\Reports\OperationReportService;

class ReporteController extends Controller
{
    public function __construct(
        private readonly InventoryReportService $inventoryReportService,
        private readonly FinanceReportService $financeReportService,
        private readonly ClientReportService $clientReportService,
        private readonly ServiceReportService $serviceReportService,
        private readonly OperationReportService $operationReportService
    ) {
    }
    public function index(Request $request)
    {
        // Filtros para ventas
        $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth()->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', now()->endOfMonth()->format('Y-m-d'));
        $clienteId = $request->get('cliente_id');
        $pagado = $request->get('pagado'); // null, true, false

        // Reporte de Ventas con filtros
        $ventasQuery = Venta::with(['productos', 'cliente', 'vendedor', 'items.ventable'])
            ->whereBetween('fecha', [$fechaInicio, $fechaFin]);

        if ($clienteId) {
            $ventasQuery->where('cliente_id', $clienteId);
        }

        if ($pagado !== null) {
            $ventasQuery->where('pagado', $pagado === 'true');
        }

        $ventas = $ventasQuery->get();
        $corteVentas = $ventas->sum('total');

        // Calcular la utilidad total (suma de utilidades por venta)
        $utilidadVentas = $ventas->sum('ganancia_total');

        // Agregar el costo_total a cada venta
        $ventasConCosto = $ventas->map(function ($venta) {
            $venta->costo_total = $venta->calcularCostoTotal();
            return $venta;
        });

        // Reporte de Compras con filtros similares
        $comprasQuery = Compra::with(['productos', 'proveedor'])
            ->whereBetween('fecha_compra', [$fechaInicio, $fechaFin]);

        $compras = $comprasQuery->get();
        $totalCompras = $compras->sum('total');

        // Reporte de Inventarios
        $productos = Producto::with(['categoria', 'marca'])->get();

        // Estadísticas adicionales
        $clientes = Cliente::select('id', 'nombre_razon_social')->get();

        // Obtener datos para el corte diario (ventas y rentas cobradas)
        $corteDiario = $this->obtenerDatosCorteDiario($fechaInicio, $fechaFin);

        // Obtener usuarios activos para el filtro de corte
        $usuarios = User::select('id', 'name')->get();

        // Obtener préstamos para el reporte general
        $prestamos = Prestamo::with(['cliente'])
            ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->get();

        return Inertia::render('Reportes/Index', [
            'reportesVentas' => $ventasConCosto,
            'corteVentas' => $corteVentas,
            'utilidadVentas' => $utilidadVentas,
            'reportesCompras' => $compras,
            'totalCompras' => $totalCompras,
            'inventario' => $productos,
            'clientes' => $clientes,
            'corteDiario' => $corteDiario,
            'usuarios' => $usuarios,
            'prestamos' => $prestamos,
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'cliente_id' => $clienteId,
                'pagado' => $pagado,
            ],
        ]);
    }

    public function ventas()
    {
        // Obtener todas las ventas con sus productos e items
        $ventas = Venta::with(['productos', 'items.ventable'])->get();

        // Calcular el corte total (suma de todos los totales de ventas)
        $corte = $ventas->sum('total');

        // Calcular la utilidad total (suma de utilidades por venta)
        $utilidad = $ventas->sum('ganancia_total');

        // Agregar el costo_total a cada venta
        $ventasConCosto = $ventas->map(function ($venta) {
            $venta->costo_total = $venta->calcularCostoTotal();
            return $venta;
        });

        // Obtener datos para el corte diario
        $corteDiario = $this->obtenerDatosCorteDiario(now()->startOfMonth()->format('Y-m-d'), now()->endOfMonth()->format('Y-m-d'));
        $usuarios = User::select('id', 'name')->get();

        return Inertia::render('Reportes/Index', [
            'reportes' => $ventasConCosto,
            'corte' => $corte,
            'utilidad' => $utilidad,
            'corteDiario' => $corteDiario,
            'usuarios' => $usuarios,
        ]);
    }
    public function create()
    {
        return Inertia::render('Reportes/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha' => 'required|date',
        ]);

        Reporte::create($request->all());

        return redirect()->route('reportes.index')->with('success', 'Reporte creado exitosamente.');
    }

    public function show(Reporte $reporte)
    {
        return Inertia::render('Reportes/Show', ['reporte' => $reporte]);
    }

    public function edit(Reporte $reporte)
    {
        return Inertia::render('Reportes/Edit', ['reporte' => $reporte]);
    }

    public function update(Request $request, Reporte $reporte)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha' => 'required|date',
        ]);

        $reporte->update($request->all());

        return redirect()->route('reportes.index')->with('success', 'Reporte actualizado exitosamente.');
    }

    public function destroy(Reporte $reporte)
    {
        $reporte->delete();
        return redirect()->route('reportes.index')->with('success', 'Reporte eliminado exitosamente.');
    }

    /**
     * Mostrar corte de pagos por período
     */
    public function corteDiario(Request $request)
    {
        $data = $this->financeReportService->getCorteDiarioData($request->all());
        return Inertia::render('Reportes/CorteDiario', $data);
    }

    /**
     * Exportar corte de pagos a Excel/CSV
     */
    public function exportarCorteDiario(Request $request)
    {
        $periodo = $request->get('periodo', 'diario');
        $fecha = $request->get('fecha', now()->format('Y-m-d'));
        $tipo = $request->get('tipo', 'excel'); // excel, csv, pdf

        // Calcular fechas de inicio y fin según el período
        $fecha_inicio = $fecha_fin = $fecha;

        if ($periodo === 'diario') {
            $fecha_inicio = $fecha_fin = $fecha;
        } elseif ($periodo === 'semanal') {
            $carbon = \Carbon\Carbon::parse($fecha);
            $fecha_inicio = $carbon->startOfWeek()->format('Y-m-d');
            $fecha_fin = $carbon->endOfWeek()->format('Y-m-d');
        } elseif ($periodo === 'mensual') {
            $carbon = \Carbon\Carbon::parse($fecha);
            $fecha_inicio = $carbon->startOfMonth()->format('Y-m-d');
            $fecha_fin = $carbon->endOfMonth()->format('Y-m-d');
        } elseif ($periodo === 'anual') {
            $carbon = \Carbon\Carbon::parse($fecha);
            $fecha_inicio = $carbon->startOfYear()->format('Y-m-d');
            $fecha_fin = $carbon->endOfYear()->format('Y-m-d');
        } elseif ($periodo === 'personalizado') {
            $fecha_inicio = $request->get('fecha_inicio', $fecha);
            $fecha_fin = $request->get('fecha_fin', $fecha);
        }

        // Obtener ventas pagadas en el período
        $ventasPagadas = Venta::with(['cliente', 'items.ventable'])
            ->where('pagado', true)
            ->where('fecha_pago', '>=', $fecha_inicio . ' 00:00:00')
            ->where('fecha_pago', '<=', $fecha_fin . ' 23:59:59')
            ->orderBy('fecha_pago', 'desc')
            ->get();

        // Obtener cobranzas pagadas en el período
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

        // Para este ejemplo, devolveremos JSON que puede ser usado por JavaScript para generar Excel
        // En un entorno real, usaríamos librerías como Laravel Excel o Maatwebsite Excel

        // Combinar ventas y cobranzas para exportación
        $ventasFormateadas = $ventasPagadas->map(function ($venta) {
            return [
                'tipo' => 'venta',
                'numero' => $venta->numero_venta,
                'cliente' => $venta->cliente->nombre_razon_social ?? 'Sin cliente',
                'concepto' => 'Venta',
                'metodo_pago' => $venta->metodo_pago,
                'total' => $venta->total,
                'fecha_pago' => $venta->fecha_pago ? $venta->fecha_pago->toIso8601String() : null,
                'notas_pago' => $venta->notas_pago,
                'pagado_por' => $venta->pagadoPor?->name ?? 'Sistema',
            ];
        });

        $cobranzasFormateadas = $cobranzasPagadas->map(function ($cobranza) {
            return [
                'tipo' => 'cobranza',
                'numero' => $cobranza->renta->numero_contrato ?? 'N/A',
                'cliente' => $cobranza->renta->cliente->nombre_razon_social ?? 'Sin cliente',
                'concepto' => $cobranza->concepto ?? 'Cobranza',
                'metodo_pago' => $cobranza->metodo_pago,
                'total' => $cobranza->monto_pagado,
                'fecha_pago' => $cobranza->fecha_pago ? $cobranza->fecha_pago->toIso8601String() : null,
                'notas_pago' => $cobranza->notas_pago,
                'pagado_por' => $cobranza->responsableCobro?->name ?? 'Sistema',
            ];
        });

        $pagosCombinados = collect([...$ventasFormateadas, ...$cobranzasFormateadas])
            ->sortByDesc('fecha_pago')
            ->values();

        $data = [
            'periodo' => $periodo,
            'fecha' => $fecha,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'total_general' => $totalGeneral,
            'totales_por_metodo' => $totalesPorMetodo,
            'pagos' => $pagosCombinados->toArray()
        ];

        return response()->json($data);
    }

    /**
     * Reporte de clientes
     */
    public function clientes(Request $request)
    {
        $data = $this->clientReportService->getClientReportData($request->all());
        return Inertia::render('Reportes/Clientes', $data);
    }

    /**
     * Reporte de inventario
     */
    public function inventario(Request $request)
    {
        $data = $this->inventoryReportService->getInventoryData($request->all());
        return Inertia::render('Reportes/Inventario', $data);
    }

    /**
     * Reporte de servicios
     */
    public function servicios(Request $request)
    {
        $data = $this->serviceReportService->getServiceReportData($request->all());
        return Inertia::render('Reportes/Servicios', $data);
    }

    /**
     * Reporte de citas
     */
    public function citas(Request $request)
    {
        $data = $this->serviceReportService->getCitaReportData($request->all());
        return Inertia::render('Reportes/Citas', $data);
    }

    /**
     * Reporte de ganancias generales
     */
    public function ganancias(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth()->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', now()->endOfMonth()->format('Y-m-d'));

        // Ventas
        $ventas = Venta::with('productos')->whereBetween('fecha', [$fechaInicio, $fechaFin])->get();
        $ingresosVentas = $ventas->sum('total');
        $costosVentas = $ventas->sum(function ($venta) {
            return $venta->calcularCostoTotal();
        });
        $gananciasVentas = $ingresosVentas - $costosVentas;

        // Compras
        $compras = Compra::whereBetween('fecha_compra', [$fechaInicio, $fechaFin])->get();
        $gastosCompras = $compras->sum('total');

        // Servicios (citas completadas)
        $citasCompletadas = Cita::whereBetween('fecha_hora', [$fechaInicio, $fechaFin])
            ->where('estado', 'completada')->get();
        $ingresosServicios = $citasCompletadas->sum('total');

        // Cobranzas
        $cobranzas = Cobranza::whereBetween('fecha_pago', [$fechaInicio, $fechaFin])
            ->whereIn('estado', ['pagado', 'parcial'])->get();
        $ingresosCobranzas = $cobranzas->sum('monto_pagado');

        // Rentas activas (ingresos proyectados)
        $rentasActivas = Renta::where('estado', 'activa')->with('cobranzas')->get();
        $ingresosRentasProyectados = $rentasActivas->sum(function ($renta) {
            $pagado = $renta->cobranzas->whereIn('estado', ['pagado', 'parcial'])->sum('monto_pagado');
            return $renta->monto_total - $pagado;
        });

        $totalIngresos = $ingresosVentas + $ingresosServicios + $ingresosCobranzas;
        $totalGastos = $costosVentas + $gastosCompras;
        $gananciaNeta = $totalIngresos - $totalGastos;

        return Inertia::render('Reportes/Ganancias', [
            'periodo' => [
                'inicio' => $fechaInicio,
                'fin' => $fechaFin,
            ],
            'ingresos' => [
                'ventas' => $ingresosVentas,
                'servicios' => $ingresosServicios,
                'cobranzas' => $ingresosCobranzas,
                'rentas_proyectadas' => $ingresosRentasProyectados,
                'total' => $totalIngresos,
            ],
            'gastos' => [
                'compras' => $gastosCompras,
                'costos_ventas' => $costosVentas,
                'total' => $totalGastos,
            ],
            'ganancias' => [
                'ventas' => $gananciasVentas,
                'neta' => $gananciaNeta,
            ],
        ]);
    }

    /**
     * Reporte de gastos operativos
     */
    public function gastosOperativos(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth()->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', now()->endOfMonth()->format('Y-m-d'));

        $data = app(\App\Services\ReportService::class)->getGastosOperativosData($fechaInicio, $fechaFin);

        return Inertia::render('Reportes/GastosOperativos', [
            'gastos' => $data['gastos'],
            'totales' => $data['totales'],
            'porCategoria' => $data['porCategoria'],
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
            ],
        ]);
    }

    /**
     * Reporte de compras a proveedores
     */
    public function comprasProveedores(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth()->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', now()->endOfMonth()->format('Y-m-d'));
        $proveedorId = $request->get('proveedor_id');

        $data = app(\App\Services\ReportService::class)->getComprasProveedoresData($fechaInicio, $fechaFin, $proveedorId);

        return Inertia::render('Reportes/ComprasProveedores', [
            'compras' => $data['compras'],
            'proveedores' => Proveedor::select('id', 'nombre_razon_social')->get(),
            'totales' => $data['totales'],
            'topProveedores' => $data['topProveedores'],
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'proveedor_id' => $proveedorId,
            ],
        ]);
    }

    /**
     * Reporte de balance comparativo (Ventas vs Compras)
     */
    public function balanceComparativo(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth()->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', now()->endOfMonth()->format('Y-m-d'));

        $data = app(\App\Services\ReportService::class)->getBalanceComparativoData($fechaInicio, $fechaFin);

        return Inertia::render('Reportes/BalanceComparativo', [
            'balance' => $data['balance'],
            'metriacas' => $data['metricas'],
            'grafica' => $data['grafica'],
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
            ],
        ]);
    }

    public function mantenimientos(Request $request)
    {
        $data = $this->operationReportService->getMantenimientoReportData($request->all());
        return Inertia::render('Reportes/Mantenimientos', $data);
    }

    /**
     * Reporte de rentas y equipos
     */
    public function rentas(Request $request)
    {
        $data = $this->operationReportService->getRentaReportData($request->all());
        return Inertia::render('Reportes/Rentas', $data);
    }

    /**
     * Reporte de cobranzas detallado
     */
    public function cobranzas(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth()->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', now()->endOfMonth()->format('Y-m-d'));
        $estado = $request->get('estado', 'todos'); // todos, pagado, parcial, pendiente
        $metodoPago = $request->get('metodo_pago');

        $cobranzasQuery = Cobranza::with(['renta.cliente', 'responsableCobro'])
            ->whereBetween('fecha_pago', [$fechaInicio, $fechaFin]);

        if ($estado !== 'todos') {
            $cobranzasQuery->where('estado', $estado);
        }

        if ($metodoPago) {
            $cobranzasQuery->where('metodo_pago', $metodoPago);
        }

        $cobranzas = $cobranzasQuery->get()->map(function ($cobranza) {
            return [
                'id' => $cobranza->id,
                'numero_contrato' => $cobranza->renta?->numero_contrato,
                'cliente' => $cobranza->renta?->cliente?->nombre_razon_social,
                'concepto' => $cobranza->concepto,
                'monto_total' => $cobranza->monto_total,
                'monto_pagado' => $cobranza->monto_pagado,
                'estado' => $cobranza->estado,
                'metodo_pago' => $cobranza->metodo_pago,
                'fecha_pago' => $cobranza->fecha_pago?->toIso8601String(),
                'notas_pago' => $cobranza->notas_pago,
                'responsable' => $cobranza->responsableCobro?->name,
            ];
        });

        $estadisticas = [
            'total_cobranzas' => $cobranzas->count(),
            'cobranzas_pagadas' => $cobranzas->where('estado', 'pagado')->count(),
            'cobranzas_parciales' => $cobranzas->where('estado', 'parcial')->count(),
            'cobranzas_pendientes' => $cobranzas->where('estado', 'pendiente')->count(),
            'total_cobrado' => $cobranzas->whereIn('estado', ['pagado', 'parcial'])->sum('monto_pagado'),
            'total_pendiente' => $cobranzas->where('estado', 'pendiente')->sum('monto_total'),
        ];

        return Inertia::render('Reportes/Cobranzas', [
            'cobranzas' => $cobranzas,
            'estadisticas' => $estadisticas,
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'estado' => $estado,
                'metodo_pago' => $metodoPago,
            ],
        ]);
    }

    /**
     * Reporte de productos (más vendidos, ganancias)
     */
    public function productos(Request $request)
    {
        // Si no hay fechas especificadas, usar un rango amplio (último año)
        $fechaInicio = $request->get('fecha_inicio', now()->subYear()->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', now()->format('Y-m-d'));
        $categoriaId = $request->get('categoria_id');
        $marcaId = $request->get('marca_id');

        $productosQuery = Producto::with(['categoria', 'marca']);

        if ($categoriaId) {
            $productosQuery->where('categoria_id', $categoriaId);
        }

        if ($marcaId) {
            $productosQuery->where('marca_id', $marcaId);
        }

        $productos = $productosQuery->get()->map(function ($producto) use ($fechaInicio, $fechaFin) {
            // Obtener estadísticas de ventas para este producto en el período
            // Incluir tanto ventas pagadas como no pagadas
            $ventaItems = VentaItem::with('venta')
                ->where('ventable_type', Producto::class)
                ->where('ventable_id', $producto->id)
                ->whereHas('venta', function ($q) use ($fechaInicio, $fechaFin) {
                    $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
                })
                ->get();

            $cantidadVendida = $ventaItems->sum('cantidad');
            $totalVendido = $ventaItems->sum(function ($item) {
                return ($item->precio - ($item->descuento ?? 0)) * $item->cantidad;
            });
            $costoTotal = $ventaItems->sum(function ($item) use ($producto) {
                return ($item->costo_unitario ?? $producto->precio_compra) * $item->cantidad;
            });
            $ganancia = $totalVendido - $costoTotal;
            $numeroVentas = $ventaItems->groupBy('venta_id')->count();

            return [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'codigo' => $producto->codigo,
                'categoria' => $producto->categoria?->nombre,
                'marca' => $producto->marca?->nombre,
                'precio_compra' => $producto->precio_compra,
                'precio_venta' => $producto->precio_venta,
                'stock' => $producto->stock,
                'cantidad_vendida' => $cantidadVendida,
                'total_vendido' => $totalVendido,
                'costo_total' => $costoTotal,
                'ganancia' => $ganancia,
                'numero_ventas' => $numeroVentas,
            ];
        })->sortByDesc('total_vendido')->values();

        $estadisticas = [
            'total_productos' => $productos->count(),
            'productos_vendidos' => $productos->where('numero_ventas', '>', 0)->count(),
            'total_ingresos' => $productos->sum('total_vendido'),
            'total_ganancias' => $productos->sum('ganancia'),
            'total_vendido_unidades' => $productos->sum('cantidad_vendida'),
        ];

        $categorias = \App\Models\Categoria::select('id', 'nombre')->get();
        $marcas = \App\Models\Marca::select('id', 'nombre')->get();

        return Inertia::render('Reportes/Productos', [
            'productos' => $productos,
            'estadisticas' => $estadisticas,
            'categorias' => $categorias,
            'marcas' => $marcas,
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'categoria_id' => $categoriaId,
                'marca_id' => $marcaId,
            ],
        ]);
    }

    /**
     * Reporte de proveedores
     */
    public function proveedores(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth()->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', now()->endOfMonth()->format('Y-m-d'));

        $proveedores = \App\Models\Proveedor::with([
            'productos.compras' => function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
            }
        ])->get()->map(function ($proveedor) {
            $productos = $proveedor->productos;
            $totalComprado = $productos->flatMap->compras->sum('pivot.total');
            $cantidadComprada = $productos->flatMap->compras->sum('pivot.cantidad');

            return [
                'id' => $proveedor->id,
                'nombre_razon_social' => $proveedor->nombre_razon_social,
                'email' => $proveedor->email,
                'telefono' => $proveedor->telefono,
                'productos_count' => $productos->count(),
                'cantidad_comprada' => $cantidadComprada,
                'total_comprado' => $totalComprado,
                'compras_count' => $productos->flatMap->compras->count(),
            ];
        })->sortByDesc('total_comprado')->values();

        $estadisticas = [
            'total_proveedores' => $proveedores->count(),
            'proveedores_activos' => $proveedores->where('compras_count', '>', 0)->count(),
            'total_compras' => $proveedores->sum('total_comprado'),
            'total_productos_comprados' => $proveedores->sum('cantidad_comprada'),
        ];

        return Inertia::render('Reportes/Proveedores', [
            'proveedores' => $proveedores,
            'estadisticas' => $estadisticas,
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
            ],
        ]);
    }

    /**
     * Reporte de empleados/usuarios
     */
    public function empleados(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth()->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', now()->endOfMonth()->format('Y-m-d'));
        $tipo = $request->get('tipo', 'todos'); // todos, tecnicos, usuarios

        $query = User::query();

        if ($tipo === 'tecnicos') {
            $query->tecnicos();
        }

        $empleados = $query->get()->map(function ($user) {
            $esTecnico = $user->es_tecnico;
            $ventasCount = 0;
            $citasCount = 0;
            $mantenimientosCount = 0;

            if ($esTecnico) {
                $ventasCount = Venta::where('vendedor_type', \App\Models\User::class)
                    ->where('vendedor_id', $user->id)->count();
                $citasCount = Cita::where('tecnico_id', $user->id)->count();
                $mantenimientosCount = Mantenimiento::where('tecnico_id', $user->id)->count();
            }

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'activo' => $user->activo,
                'fecha_registro' => $user->created_at->format('Y-m-d'),
                'es_tecnico' => $esTecnico,
                'tecnico_nombre' => $esTecnico ? $user->name : null,
                'ventas_count' => $ventasCount,
                'citas_count' => $citasCount,
                'mantenimientos_count' => $mantenimientosCount,
            ];
        });

        $estadisticas = [
            'total_empleados' => $empleados->count(),
            'empleados_activos' => $empleados->where('activo', true)->count(),
            'tecnicos' => $empleados->where('es_tecnico', true)->count(),
            'total_ventas' => $empleados->sum('ventas_count'),
            'total_citas' => $empleados->sum('citas_count'),
            'total_mantenimientos' => $empleados->sum('mantenimientos_count'),
        ];

        return Inertia::render('Reportes/Empleados', [
            'empleados' => $empleados,
            'estadisticas' => $estadisticas,
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'tipo' => $tipo,
            ],
        ]);
    }

    /**
     * Reporte de auditoría/bitácora
     */
    public function auditoria(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth()->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', now()->endOfMonth()->format('Y-m-d'));
        $usuarioId = $request->get('usuario_id');
        $tipo = $request->get('tipo'); // login, logout, create, update, delete

        $bitacorasQuery = BitacoraActividad::with('usuario')
            ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);

        if ($usuarioId) {
            $bitacorasQuery->where('user_id', $usuarioId);
        }

        if ($tipo) {
            $bitacorasQuery->where('tipo', $tipo);
        }

        $bitacoras = $bitacorasQuery->orderBy('created_at', 'desc')->get()->map(function ($bitacora) {
            return [
                'id' => $bitacora->id,
                'usuario' => $bitacora->usuario?->name,
                'tipo' => $bitacora->tipo,
                'descripcion' => $bitacora->descripcion,
                'modelo' => $bitacora->modelo,
                'modelo_id' => $bitacora->modelo_id,
                'datos_anteriores' => $bitacora->datos_anteriores,
                'datos_nuevos' => $bitacora->datos_nuevos,
                'ip' => $bitacora->ip,
                'fecha' => $bitacora->created_at->toIso8601String(),
            ];
        });

        $estadisticas = [
            'total_actividades' => $bitacoras->count(),
            'actividades_login' => $bitacoras->where('tipo', 'login')->count(),
            'actividades_logout' => $bitacoras->where('tipo', 'logout')->count(),
            'actividades_create' => $bitacoras->where('tipo', 'create')->count(),
            'actividades_update' => $bitacoras->where('tipo', 'update')->count(),
            'actividades_delete' => $bitacoras->where('tipo', 'delete')->count(),
        ];

        $usuarios = User::select('id', 'name')->get();

        return Inertia::render('Reportes/Auditoria', [
            'bitacoras' => $bitacoras,
            'estadisticas' => $estadisticas,
            'usuarios' => $usuarios,
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'usuario_id' => $usuarioId,
                'tipo' => $tipo,
            ],
        ]);
    }

    /**
     * Exportar reporte de clientes a JSON para Excel
     */
    public function exportarClientes(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth()->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', now()->endOfMonth()->format('Y-m-d'));
        $tipo = $request->get('tipo', 'todos');

        $clientesQuery = Cliente::with([
            'ventas' => function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
            },
            'rentas.cobranzas' => function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('fecha_pago', [$fechaInicio, $fechaFin]);
            }
        ]);

        if ($tipo === 'nuevos') {
            $clientesQuery->whereBetween('created_at', [$fechaInicio, $fechaFin]);
        }

        $clientes = $clientesQuery->get()->map(function ($cliente) {
            $totalVentas = $cliente->ventas->sum('total');
            $totalCobranzas = $cliente->rentas->flatMap->cobranzas->sum('monto_pagado');
            $deudaPendiente = $cliente->rentas->where('estado', 'activa')->sum('monto_total') - $totalCobranzas;

            return [
                'Nombre/Razón Social' => $cliente->nombre_razon_social,
                'Email' => $cliente->email,
                'Teléfono' => $cliente->telefono,
                'Fecha Registro' => $cliente->created_at->format('Y-m-d'),
                'Total Ventas' => $totalVentas,
                'Total Cobranzas' => $totalCobranzas,
                'Deuda Pendiente' => $deudaPendiente,
                'Número Ventas' => $cliente->ventas->count(),
                'Número Rentas' => $cliente->rentas->count(),
            ];
        });

        // Filtrar según tipo
        if ($tipo === 'activos') {
            $clientes = $clientes->filter(function ($cliente) {
                return $cliente['Número Ventas'] > 0 || $cliente['Número Rentas'] > 0;
            });
        } elseif ($tipo === 'deudores') {
            $clientes = $clientes->filter(function ($cliente) {
                return $cliente['Deuda Pendiente'] > 0;
            });
        }

        return response()->json([
            'data' => $clientes->values()->toArray(),
            'filename' => 'reporte_clientes_' . now()->format('Y-m-d_H-i-s') . '.json'
        ]);
    }

    /**
     * Exportar reporte de inventario a JSON para Excel
     */
    public function exportarInventario(Request $request)
    {
        $categoriaId = $request->get('categoria_id');
        $marcaId = $request->get('marca_id');
        $tipo = $request->get('tipo', 'todos');

        $productosQuery = Producto::with(['categoria', 'marca', 'proveedor']);

        if ($categoriaId) {
            $productosQuery->where('categoria_id', $categoriaId);
        }

        if ($marcaId) {
            $productosQuery->where('marca_id', $marcaId);
        }

        $productos = $productosQuery->get()->map(function ($producto) {
            return [
                'Nombre' => $producto->nombre,
                'Código' => $producto->codigo,
                'Categoría' => $producto->categoria?->nombre,
                'Marca' => $producto->marca?->nombre,
                'Proveedor' => $producto->proveedor?->nombre_razon_social,
                'Stock' => $producto->stock,
                'Stock Mínimo' => $producto->stock_minimo,
                'Precio Compra' => $producto->precio_compra,
                'Precio Venta' => $producto->precio_venta,
                'Estado' => $producto->stock <= 0 ? 'Sin Stock' : ($producto->stock <= $producto->stock_minimo ? 'Bajo' : 'Normal'),
            ];
        });

        // Filtrar según tipo
        if ($tipo === 'bajos') {
            $productos = $productos->filter(function ($p) {
                return $p['Stock'] <= $p['Stock Mínimo'] && $p['Stock'] > 0;
            });
        } elseif ($tipo === 'sin_stock') {
            $productos = $productos->filter(function ($p) {
                return $p['Stock'] <= 0;
            });
        }

        return response()->json([
            'data' => $productos->values()->toArray(),
            'filename' => 'reporte_inventario_' . now()->format('Y-m-d_H-i-s') . '.json'
        ]);
    }

    /**
     * Reporte de ventas pendientes de pago
     */
    public function ventasPendientes(Request $request)
    {
        $search = $request->get('search');
        $estado = $request->get('estado');
        $perPage = $request->get('per_page', 10);

        $ventasQuery = Venta::with(['cliente:id,nombre_razon_social,email'])
            ->where('estado', '!=', 'cancelada')
            ->where('pagado', false)
            ->orderBy('created_at', 'desc');

        // Aplicar filtros
        if ($search) {
            $ventasQuery->where(function ($q) use ($search) {
                $q->whereHas('cliente', function ($clienteQuery) use ($search) {
                    $clienteQuery->where('nombre_razon_social', 'like', '%' . $search . '%');
                })
                    ->orWhere('numero_venta', 'like', '%' . $search . '%');
            });
        }

        if ($estado) {
            if ($estado === 'borrador') {
                $ventasQuery->where('estado', 'borrador');
            } elseif ($estado === 'pendiente') {
                $ventasQuery->where('estado', 'pendiente');
            } elseif ($estado === 'aprobada') {
                $ventasQuery->where('estado', 'aprobada');
            }
        }

        $ventas = $ventasQuery->paginate($perPage);

        // Calcular estadísticas de ventas pendientes de pago
        $ventasPendientes = Venta::where('pagado', false)->where('estado', '!=', 'cancelada');
        $estadisticas = [
            'total' => $ventasPendientes->count(),
            'total_monto' => $ventasPendientes->sum('total'),
            'aprobadas' => $ventasPendientes->where('estado', 'aprobada')->count(),
            'borrador' => $ventasPendientes->where('estado', 'borrador')->count(),
        ];

        // Obtener datos para el corte diario
        $corteDiario = $this->obtenerDatosCorteDiario(now()->startOfMonth()->format('Y-m-d'), now()->endOfMonth()->format('Y-m-d'));
        $usuarios = User::select('id', 'name')->get();

        return Inertia::render('Reportes/VentasPendientes', [
            'ventas' => $ventas,
            'estadisticas' => $estadisticas,
            'corteDiario' => $corteDiario,
            'usuarios' => $usuarios,
            'filters' => [
                'search' => $search,
                'estado' => $estado,
            ]
        ]);
    }

    /**
     * Exportar reporte de productos a JSON para Excel
     */
    public function exportarProductos(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', now()->subYear()->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', now()->format('Y-m-d'));
        $categoriaId = $request->get('categoria_id');
        $marcaId = $request->get('marca_id');

        $productosQuery = Producto::with(['categoria', 'marca']);

        if ($categoriaId) {
            $productosQuery->where('categoria_id', $categoriaId);
        }

        if ($marcaId) {
            $productosQuery->where('marca_id', $marcaId);
        }

        $productos = $productosQuery->get()->map(function ($producto) use ($fechaInicio, $fechaFin) {
            // Obtener estadísticas de ventas para este producto en el período
            $ventaItemsQuery = VentaItem::with('venta')
                ->where('ventable_type', Producto::class)
                ->where('ventable_id', $producto->id);

            // Solo filtrar por fechas si están especificadas
            if ($fechaInicio && $fechaFin) {
                $ventaItemsQuery->whereHas('venta', function ($q) use ($fechaInicio, $fechaFin) {
                    $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
                });
            }

            $ventaItems = $ventaItemsQuery->get();

            $cantidadVendida = $ventaItems->sum('cantidad');
            $totalVendido = $ventaItems->sum(function ($item) {
                return ($item->precio - ($item->descuento ?? 0)) * $item->cantidad;
            });
            $costoTotal = $ventaItems->sum(function ($item) use ($producto) {
                return ($item->costo_unitario ?? $producto->precio_compra) * $item->cantidad;
            });
            $ganancia = $totalVendido - $costoTotal;
            $numeroVentas = $ventaItems->groupBy('venta_id')->count();

            return [
                'Nombre' => $producto->nombre,
                'Código' => $producto->codigo,
                'Categoría' => $producto->categoria?->nombre,
                'Marca' => $producto->marca?->nombre,
                'Precio Compra' => $producto->precio_compra,
                'Precio Venta' => $producto->precio_venta,
                'Stock' => $producto->stock,
                'Cantidad Vendida' => $cantidadVendida,
                'Total Vendido' => $totalVendido,
                'Costo Total' => $costoTotal,
                'Ganancia' => $ganancia,
                'Número Ventas' => $numeroVentas,
            ];
        })->sortByDesc('Total Vendido')->values();

        return response()->json([
            'data' => $productos->toArray(),
            'filename' => 'reporte_productos_' . now()->format('Y-m-d_H-i-s') . '.json'
        ]);
    }

    /**
     * Obtener datos para el corte diario (ventas y rentas cobradas)
     */
    private function obtenerDatosCorteDiario($fechaInicio, $fechaFin)
    {
        // Ventas pagadas en el período
        $ventasPagadas = Venta::with(['cliente', 'pagadoPor'])
            ->where('pagado', true)
            ->whereBetween('fecha_pago', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->get()
            ->map(function ($venta) {
                return [
                    'id' => $venta->id,
                    'tipo' => 'venta',
                    'numero' => $venta->numero_venta,
                    'cliente' => $venta->cliente->nombre_razon_social ?? 'Sin cliente',
                    'concepto' => 'Venta de productos/servicios',
                    'total' => $venta->total,
                    'metodo_pago' => $venta->metodo_pago,
                    'fecha_pago' => $venta->fecha_pago,
                    'notas_pago' => $venta->notas_pago,
                    'cobrado_por' => $venta->pagadoPor?->name ?? 'Sistema',
                    'pagado_por' => $venta->pagado_por,
                ];
            });

        // Cobranzas de rentas pagadas en el período
        $cobranzasPagadas = Cobranza::with(['renta.cliente', 'responsableCobro'])
            ->whereIn('estado', ['pagado', 'parcial'])
            ->whereBetween('fecha_pago', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->get()
            ->map(function ($cobranza) {
                return [
                    'id' => $cobranza->id,
                    'tipo' => 'renta',
                    'numero' => $cobranza->renta->numero_contrato ?? 'N/A',
                    'cliente' => $cobranza->renta->cliente->nombre_razon_social ?? 'Sin cliente',
                    'concepto' => $cobranza->concepto ?? 'Cobranza de renta',
                    'total' => $cobranza->monto_pagado,
                    'metodo_pago' => $cobranza->metodo_pago,
                    'fecha_pago' => $cobranza->fecha_pago,
                    'notas_pago' => $cobranza->notas_pago,
                    'cobrado_por' => $cobranza->responsableCobro?->name ?? 'Sistema',
                    'pagado_por' => $cobranza->user_id,
                ];
            });

        // Combinar ventas y cobranzas ordenadas por fecha de pago
        return collect([...$ventasPagadas, ...$cobranzasPagadas])
            ->sortByDesc('fecha_pago')
            ->values()
            ->toArray();
    }

    /**
     * Reporte de préstamos por cliente
     */
    public function prestamosPorCliente(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth()->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', now()->endOfMonth()->format('Y-m-d'));
        $clienteId = $request->get('cliente_id');
        $estado = $request->get('estado', 'todos'); // todos, activo, completado, cancelado

        // Obtener préstamos con relaciones necesarias
        $prestamosQuery = Prestamo::with(['cliente', 'pagos.historialPagos'])
            ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);

        if ($clienteId) {
            $prestamosQuery->where('cliente_id', $clienteId);
        }

        if ($estado !== 'todos') {
            $prestamosQuery->where('estado', $estado);
        }

        $prestamos = $prestamosQuery->get();

        // Agrupar préstamos por cliente
        $prestamosPorCliente = $prestamos->groupBy('cliente_id')->map(function ($clientePrestamos, $clienteId) use ($fechaInicio, $fechaFin) {
            $cliente = $clientePrestamos->first()->cliente;

            $totalPrestado = $clientePrestamos->sum('monto_prestado');
            $totalPagado = $clientePrestamos->sum('monto_pagado');
            $totalPendiente = $clientePrestamos->sum('monto_pendiente');
            $totalInteres = $clientePrestamos->sum('monto_interes_total');

            // Obtener pagos del período para este cliente
            $pagosEnPeriodo = PagoPrestamo::with(['prestamo', 'historialPagos'])
                ->whereIn('prestamo_id', $clientePrestamos->pluck('id'))
                ->whereHas('historialPagos', function ($q) use ($fechaInicio, $fechaFin) {
                    $q->whereBetween('fecha_pago', [$fechaInicio, $fechaFin]);
                })
                ->get();

            $totalPagadoEnPeriodo = $pagosEnPeriodo->sum(function ($pago) {
                return $pago->historialPagos->sum('monto_pagado');
            });

            return [
                'cliente_id' => $clienteId,
                'cliente' => [
                    'id' => $cliente->id,
                    'nombre_razon_social' => $cliente->nombre_razon_social,
                    'rfc' => $cliente->rfc,
                    'email' => $cliente->email,
                    'telefono' => $cliente->telefono,
                ],
                'prestamos' => $clientePrestamos->map(function ($prestamo) {
                    return [
                        'id' => $prestamo->id,
                        'monto_prestado' => $prestamo->monto_prestado,
                        'monto_pagado' => $prestamo->monto_pagado,
                        'monto_pendiente' => $prestamo->monto_pendiente,
                        'pago_periodico' => $prestamo->pago_periodico,
                        'tasa_interes_mensual' => $prestamo->tasa_interes_mensual,
                        'numero_pagos' => $prestamo->numero_pagos,
                        'pagos_realizados' => $prestamo->pagos_realizados,
                        'pagos_pendientes' => $prestamo->pagos_pendientes,
                        'estado' => $prestamo->estado,
                        'fecha_inicio' => (string) $prestamo->fecha_inicio,
                        'fecha_primer_pago' => (string) $prestamo->fecha_primer_pago,
                        'progreso' => $prestamo->progreso,
                    ];
                })->values(),
                'resumen_financiero' => [
                    'total_prestado' => $totalPrestado,
                    'total_pagado' => $totalPagado,
                    'total_pendiente' => $totalPendiente,
                    'total_interes' => $totalInteres,
                    'total_pagos_periodo' => $totalPagadoEnPeriodo,
                    'numero_prestamos' => $clientePrestamos->count(),
                    'prestamos_activos' => $clientePrestamos->where('estado', 'activo')->count(),
                    'prestamos_completados' => $clientePrestamos->where('estado', 'completado')->count(),
                    'prestamos_cancelados' => $clientePrestamos->where('estado', 'cancelado')->count(),
                ]
            ];
        })->values();

        // Estadísticas generales
        $estadisticas = [
            'total_clientes' => $prestamosPorCliente->count(),
            'total_prestamos' => $prestamos->count(),
            'total_prestado' => $prestamos->sum('monto_prestado'),
            'total_pagado' => $prestamos->sum('monto_pagado'),
            'total_pendiente' => $prestamos->sum('monto_pendiente'),
            'total_interes' => $prestamos->sum('monto_interes_total'),
            'prestamos_activos' => $prestamos->where('estado', 'activo')->count(),
            'prestamos_completados' => $prestamos->where('estado', 'completado')->count(),
            'prestamos_cancelados' => $prestamos->where('estado', 'cancelado')->count(),
        ];

        // Obtener lista de clientes para filtros
        $clientes = Cliente::select('id', 'nombre_razon_social', 'rfc')->orderBy('nombre_razon_social')->get();

        return Inertia::render('Reportes/PrestamosPorCliente', [
            'prestamosPorCliente' => $prestamosPorCliente,
            'estadisticas' => $estadisticas,
            'clientes' => $clientes,
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'cliente_id' => $clienteId,
                'estado' => $estado,
            ],
        ]);
    }

    /**
     * Exportar reporte de préstamos por cliente a JSON para Excel
     */
    public function exportarPrestamosPorCliente(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth()->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', now()->endOfMonth()->format('Y-m-d'));
        $clienteId = $request->get('cliente_id');
        $estado = $request->get('estado', 'todos');

        // Obtener préstamos con relaciones necesarias
        $prestamosQuery = Prestamo::with(['cliente', 'pagos.historialPagos'])
            ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);

        if ($clienteId) {
            $prestamosQuery->where('cliente_id', $clienteId);
        }

        if ($estado !== 'todos') {
            $prestamosQuery->where('estado', $estado);
        }

        $prestamos = $prestamosQuery->get();

        // Formatear datos para exportación
        $datosExportacion = $prestamos->map(function ($prestamo) {
            return [
                'Cliente' => $prestamo->cliente->nombre_razon_social,
                'RFC Cliente' => $prestamo->cliente->rfc,
                'ID Préstamo' => $prestamo->id,
                'Monto Prestado' => $prestamo->monto_prestado,
                'Monto Pagado' => $prestamo->monto_pagado,
                'Monto Pendiente' => $prestamo->monto_pendiente,
                'Interés Total' => $prestamo->monto_interes_total,
                'Pago Periódico' => $prestamo->pago_periodico,
                'Tasa Interés Mensual' => $prestamo->tasa_interes_mensual . '%',
                'Número Pagos' => $prestamo->numero_pagos,
                'Pagos Realizados' => $prestamo->pagos_realizados,
                'Pagos Pendientes' => $prestamo->pagos_pendientes,
                'Estado' => ucfirst($prestamo->estado),
                'Fecha Inicio' => (string) $prestamo->fecha_inicio,
                'Fecha Primer Pago' => (string) $prestamo->fecha_primer_pago,
                'Progreso' => round($prestamo->progreso, 2) . '%',
                'Fecha Creación' => $prestamo->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'data' => $datosExportacion->toArray(),
            'filename' => 'reporte_prestamos_por_cliente_' . now()->format('Y-m-d_H-i-s') . '.json'
        ]);
    }
    /**
     * Reporte de antigüedad de saldos
     */
    public function antiguedadSaldos(Request $request)
    {
        $fechaCorte = $request->get('fecha_corte', now()->format('Y-m-d'));

        // Obtener clientes con cuentas por cobrar pendientes con sus detalles
        $clientes = Cliente::whereHas('cuentasPorCobrar', function ($q) {
            $q->whereIn('estado', ['pendiente', 'parcial', 'vencida']);
        })->with([
                    'cuentasPorCobrar' => function ($q) {
                        $q->whereIn('estado', ['pendiente', 'parcial', 'vencida']);
                    }
                ])->get();

        $reporte = $clientes->map(function ($cliente) use ($fechaCorte) {
            $porVencer = 0;
            $vencido1_30 = 0;
            $vencido31_60 = 0;
            $vencido61_90 = 0;
            $vencido90_mas = 0;
            $total = 0;

            foreach ($cliente->cuentasPorCobrar as $cxc) {
                // Usamos monto_pendiente que es lo correcto
                $monto = $cxc->monto_pendiente;
                $total += $monto;

                // Calcular días de atraso: (Fecha Corte - Fecha Vencimiento)
                // Si fecha_vencimiento es PASADO con respecto a corte, es positivo (vencido)
                // Si fecha_vencimiento es FUTURO con respecto a corte, es negativo (por vencer)
                $dias = 0;
                if ($cxc->fecha_vencimiento) {
                    $dias = \Carbon\Carbon::parse($cxc->fecha_vencimiento)->diffInDays(\Carbon\Carbon::parse($fechaCorte), false);
                }

                if ($dias < 0) {
                    // Si diff < 0 significa que la fecha de corte es ANTES de la fecha de vencimiento.
                    // Ejemplo: Vence 20-Ene. Corte 10-Ene. Vence-Corte = +10 days future.
                    // Wait, diffInDays(target, false) = target - self.
                    // self->diffInDays(target, false).
                    // Carbon::parse(Vence)->diffInDays(Corte, false) = Corte - Vence.
                    // Example: Vence=Jan20. Corte=Jan10. 10 - 20 = -10. (Correct, negative = not due).
                    // Example: Vence=Jan1. Corte=Jan10. 10 - 1 = +9. (Correct, positive = overdue).
                    $porVencer += $monto;
                } elseif ($dias == 0) {
                    // Vence hoy. Lo contamos como corriente/por vencer o ya vencido?
                    // Usualmente "Corriente" es 0-30... No.
                    // Aging buckets:
                    // Current (Por Vencer / Vence Hoy)
                    // 1-30 days overdue
                    $porVencer += $monto;
                } elseif ($dias <= 30) {
                    $vencido1_30 += $monto;
                } elseif ($dias <= 60) {
                    $vencido31_60 += $monto;
                } elseif ($dias <= 90) {
                    $vencido61_90 += $monto;
                } else {
                    $vencido90_mas += $monto;
                }
            }

            return [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre_razon_social,
                'telefono' => $cliente->telefono,
                'limite_credito' => $cliente->limite_credito,
                'por_vencer' => $porVencer,
                'vencido_1_30' => $vencido1_30,
                'vencido_31_60' => $vencido31_60,
                'vencido_61_90' => $vencido61_90,
                'vencido_90_mas' => $vencido90_mas,
                'total' => $total,
            ];
        })->sortByDesc('total')->values();

        $totales = [
            'por_vencer' => $reporte->sum('por_vencer'),
            'vencido_1_30' => $reporte->sum('vencido_1_30'),
            'vencido_31_60' => $reporte->sum('vencido_31_60'),
            'vencido_61_90' => $reporte->sum('vencido_61_90'),
            'vencido_90_mas' => $reporte->sum('vencido_90_mas'),
            'total' => $reporte->sum('total'),
        ];

        return Inertia::render('Reportes/AntiguedadSaldos', [
            'reporte' => $reporte,
            'totales' => $totales,
            'fecha_corte' => $fechaCorte,
        ]);
    }

    /**
     * Datos para Gráfica de Rendimiento de Técnicos
     */
    public function getRendimientoTecnicos(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth()->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', now()->endOfMonth()->format('Y-m-d'));

        $citas = Cita::with('tecnico')
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->where('estado', 'completada')
            ->get();

        $grouped = $citas->groupBy('tecnico_id');

        $labels = [];
        $data = [];

        foreach ($grouped as $tecnicoId => $items) {
            $tecnico = $items->first()->tecnico;
            $labels[] = $tecnico ? $tecnico->name . ' ' . $tecnico->apellido : 'Sin Técnico';
            $data[] = $items->count();
        }

        return response()->json([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Servicios Completados',
                    'backgroundColor' => '#f87979',
                    'data' => $data
                ]
            ]
        ]);
    }

    /**
     * Datos para Gráfica de Ventas por Sucursal (Almacén)
     */
    public function getVentasPorAlmacen(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth()->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', now()->endOfMonth()->format('Y-m-d'));

        $ventas = Venta::with('almacen')
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->where('estado', 'pagado')
            ->get();

        $grouped = $ventas->groupBy('almacen_id');

        $labels = [];
        $data = [];

        foreach ($grouped as $almacenId => $items) {
            $almacen = $items->first()->almacen;
            $labels[] = $almacen ? $almacen->nombre : 'Sin Almacén';
            $data[] = $items->sum('total');
        }

        return response()->json([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total Ventas',
                    'backgroundColor' => '#36A2EB',
                    'data' => $data
                ]
            ]
        ]);
    }
}

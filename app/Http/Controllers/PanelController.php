<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Services\Panel\PanelStatsService;
use App\Services\Panel\PanelChartsService;
use App\Services\Panel\PanelAlertsService;
use App\Services\Panel\PanelBitacoraService;
use Carbon\Carbon;

class PanelController extends Controller
{
    protected $statsService;
    protected $chartsService;
    protected $alertsService;
    protected $bitacoraService;

    public function __construct(
        PanelStatsService $statsService,
        PanelChartsService $chartsService,
        PanelAlertsService $alertsService,
        PanelBitacoraService $bitacoraService
    ) {
        $this->statsService = $statsService;
        $this->chartsService = $chartsService;
        $this->alertsService = $alertsService;
        $this->bitacoraService = $bitacoraService;
    }

    public function index()
    {
        try {
            $user = Auth::user();
            $now = Carbon::now(config('app.timezone', 'America/Hermosillo'));

            // Obtener datos de estadísticas
            $stats = $this->statsService->getBasicStats();
            $productosBajoStock = $this->statsService->getProductosBajoStock();
            $ordenesCompra = $this->statsService->getOrdenesCompraStats();
            $citasHoy = $this->statsService->getCitasHoy();
            $mantenimientos = $this->statsService->getMantenimientosCriticos();

            // Obtener datos para gráficos
            $ventasMensuales = $this->chartsService->getVentasMensuales();
            $productosMasVendidos = $this->chartsService->getProductosMasVendidos();
            $ordenesEstados = $this->chartsService->getOrdenesEstados();
            $clientesCrecimiento = $this->chartsService->getClientesCrecimiento();

            // Obtener alertas de vencimientos
            $alertasCuentasPagar = $this->alertsService->getCuentasPorPagarAlerts();
            $alertasCuentasCobrar = $this->alertsService->getCuentasPorCobrarAlerts();
            $alertasPrestamos = $this->alertsService->getPrestamosAlerts();

            // Obtener tareas pendientes del usuario actual
            $tareasPendientes = $user ? $this->bitacoraService->getTareasPendientes($user->id) : [
                'tareas' => [], 'total' => 0, 'en_proceso' => 0, 'pendientes' => 0
            ];

            return Inertia::render('Panel', [
                'user' => $this->formatUserData($user),
                // Estadísticas básicas
                'clientesCount' => $stats['clientes'],
                'clientesNuevosCount' => $stats['clientes_nuevos'],
                'productosCount' => $stats['productos'],
                'productosBajoStockCount' => $productosBajoStock['count'],
                'productosBajoStockNombres' => $productosBajoStock['nombres'],
                'proveedoresCount' => $stats['proveedores'],
                'proveedoresPedidosPendientesCount' => $ordenesCompra['pendientes_count'],
                'ordenesPendientesDetalles' => $ordenesCompra['pendientes'],
                'ordenesEnviadasCount' => $ordenesCompra['enviadas_count'],
                'ordenesEnviadasDetalles' => $ordenesCompra['enviadas'],
                'citasCount' => $stats['citas'],
                'citasHoyCount' => $citasHoy['count'],
                'citasHoyDetalles' => $citasHoy['citas'],
                // Métricas de mantenimiento
                'mantenimientosCount' => $stats['mantenimientos'],
                'mantenimientosVencidosCount' => $mantenimientos['vencidos_count'],
                'mantenimientosCriticosCount' => $mantenimientos['criticos_count'],
                'mantenimientosCriticosDetalles' => $mantenimientos['detalles'],
                // Datos para gráficos
                'chartVentasLabels' => $ventasMensuales['labels'],
                'chartVentasData' => $ventasMensuales['data'],
                'chartProductosLabels' => $productosMasVendidos['labels'],
                'chartProductosData' => $productosMasVendidos['data'],
                'chartOrdenesLabels' => $ordenesEstados['labels'],
                'chartOrdenesData' => $ordenesEstados['data'],
                'chartClientesLabels' => $clientesCrecimiento['labels'],
                'chartClientesData' => $clientesCrecimiento['data'],
                // Alertas de vencimientos
                'alertasCuentasPagar' => $alertasCuentasPagar,
                'alertasCuentasCobrar' => $alertasCuentasCobrar,
                'alertasPrestamos' => $alertasPrestamos,
                // Tareas pendientes del usuario
                'tareasPendientes' => $tareasPendientes,
            ]);

        } catch (\Exception $e) {
            \Log::error('PanelController error: ' . $e->getMessage());

            // Retornar datos por defecto seguros en caso de error
            return Inertia::render('Panel', [
                'user' => null,
                'clientesCount' => 0,
                'clientesNuevosCount' => 0,
                'productosCount' => 0,
                'productosBajoStockCount' => 0,
                'productosBajoStockNombres' => [],
                'proveedoresCount' => 0,
                'proveedoresPedidosPendientesCount' => 0,
                'ordenesPendientesDetalles' => [],
                'ordenesEnviadasCount' => 0,
                'ordenesEnviadasDetalles' => [],
                'citasCount' => 0,
                'citasHoyCount' => 0,
                'citasHoyDetalles' => [],
                'mantenimientosCount' => 0,
                'mantenimientosVencidosCount' => 0,
                'mantenimientosCriticosCount' => 0,
                'mantenimientosCriticosDetalles' => [],
                'chartVentasLabels' => [],
                'chartVentasData' => [],
                'chartProductosLabels' => [],
                'chartProductosData' => [],
                'chartOrdenesLabels' => [],
                'chartOrdenesData' => [],
                'chartClientesLabels' => [],
                'chartClientesData' => [],
                'alertasCuentasPagar' => [
                    'vencidas' => [], 'vencidas_count' => 0,
                    'semana' => [], 'semana_count' => 0,
                    'quincena' => [], 'quincena_count' => 0,
                    'mes' => [], 'mes_count' => 0
                ],
                'alertasCuentasCobrar' => [
                    'vencidas' => [], 'vencidas_count' => 0,
                    'semana' => [], 'semana_count' => 0,
                    'quincena' => [], 'quincena_count' => 0,
                    'mes' => [], 'mes_count' => 0
                ],
                'alertasPrestamos' => [
                    'vencidas' => [], 'vencidas_count' => 0,
                    'semana' => [], 'semana_count' => 0,
                    'quincena' => [], 'quincena_count' => 0,
                    'mes' => [], 'mes_count' => 0
                ],
                'tareasPendientes' => [
                    'tareas' => [], 'total' => 0, 'en_proceso' => 0, 'pendientes' => 0
                ],
            ]);
        }
    }

    private function formatUserData($user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'id' => $user->id,
            'nombre' => $user->name,
            'rol' => $user->rol ?? $user->roles->pluck('name')->first() ?? 'Usuario',
        ];
    }
}

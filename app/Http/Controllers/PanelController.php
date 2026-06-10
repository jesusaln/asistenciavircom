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
    protected $contabilidadService;

    public function __construct(
        PanelStatsService $statsService,
        PanelChartsService $chartsService,
        PanelAlertsService $alertsService,
        PanelBitacoraService $bitacoraService,
        \App\Services\Contab\ContabilidadService $contabilidadService
    ) {
        $this->statsService = $statsService;
        $this->chartsService = $chartsService;
        $this->alertsService = $alertsService;
        $this->bitacoraService = $bitacoraService;
        $this->contabilidadService = $contabilidadService;
    }

    public function index()
    {
        $user = Auth::user();
        $userId = $user?->id;
        $empresaId = (int) ($user?->empresa_id ?? \App\Support\EmpresaResolver::resolveId() ?? 1);
        $now = Carbon::now(config('app.timezone', 'America/Hermosillo'));
        $nowIso = $now->toIso8601String();
        $defaults = $this->getSafeDefaults($now);

        // Ejecutar las secciones de carga de datos en paralelo usando Concurrency de Laravel 13
        [$statsData, $chartsData, $miscData] = \Illuminate\Support\Facades\Concurrency::run([
            function () use ($empresaId) {
                try {
                    \App\Support\EmpresaResolver::setContext($empresaId);
                    $statsService = app(\App\Services\Panel\PanelStatsService::class);
                    $stats = $statsService->getBasicStats();
                    $productosBajoStock = $statsService->getProductosBajoStock();
                    $ordenesCompra = $statsService->getOrdenesCompraStats();
                    $citasHoy = $statsService->getCitasHoy();
                    $mantenimientos = $statsService->getMantenimientosCriticos();

                    return [
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
                        'mantenimientosCount' => $stats['mantenimientos'],
                        'mantenimientosVencidosCount' => $mantenimientos['vencidos_count'],
                        'mantenimientosCriticosCount' => $mantenimientos['criticos_count'],
                        'mantenimientosCriticosDetalles' => $mantenimientos['detalles'],
                    ];
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Panel Concurrency [stats]: " . $e->getMessage());
                    return [];
                }
            },
            function () use ($empresaId) {
                try {
                    \App\Support\EmpresaResolver::setContext($empresaId);
                    $chartsService = app(\App\Services\Panel\PanelChartsService::class);
                    $ventasMensuales = $chartsService->getVentasMensuales();
                    $productosMasVendidos = $chartsService->getProductosMasVendidos();
                    $ordenesEstados = $chartsService->getOrdenesEstados();
                    $clientesCrecimiento = $chartsService->getClientesCrecimiento();

                    return [
                        'chartVentasLabels' => $ventasMensuales['labels'],
                        'chartVentasData' => $ventasMensuales['data'],
                        'chartProductosLabels' => $productosMasVendidos['labels'],
                        'chartProductosData' => $productosMasVendidos['data'],
                        'chartOrdenesLabels' => $ordenesEstados['labels'],
                        'chartOrdenesData' => $ordenesEstados['data'],
                        'chartClientesLabels' => $clientesCrecimiento['labels'],
                        'chartClientesData' => $clientesCrecimiento['data'],
                    ];
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Panel Concurrency [charts]: " . $e->getMessage());
                    return [];
                }
            },
            function () use ($empresaId, $userId, $nowIso) {
                try {
                    \App\Support\EmpresaResolver::setContext($empresaId);
                    $alertsService = app(\App\Services\Panel\PanelAlertsService::class);
                    $bitacoraService = app(\App\Services\Panel\PanelBitacoraService::class);
                    $contabilidadService = app(\App\Services\Contab\ContabilidadService::class);
                    $now = \Carbon\Carbon::parse($nowIso);

                    $alerts = [
                        'alertasCuentasPagar' => $alertsService->getCuentasPorPagarAlerts(),
                        'alertasCuentasCobrar' => $alertsService->getCuentasPorCobrarAlerts(),
                        'alertasPrestamos' => $alertsService->getPrestamosAlerts(),
                    ];

                    $tasks = [
                        'tareasPendientes' => $userId
                            ? $bitacoraService->getTareasPendientes($userId)
                            : ['tareas' => [], 'total' => 0, 'en_proceso' => 0, 'pendientes' => 0],
                    ];

                    $ingresosAnuales = $empresaId
                        ? $contabilidadService->obtenerIngresosAnuales($empresaId, $now->year)
                        : 0;

                    $resico = [
                        'resicoStats' => [
                            'ingresos_anuales' => $ingresosAnuales,
                            'limite' => 3500000,
                            'porcentaje' => round(($ingresosAnuales / 3500000) * 100, 2),
                            'anio' => $now->year,
                        ],
                    ];

                    $isClosingPeriod = ($now->day <= 10);
                    $ppdEmitidosCount = 0;
                    $ppdRecibidosCount = 0;

                    if ($isClosingPeriod && $empresaId) {
                        $ppdEmitidosCount = \App\Models\Cfdi::where('empresa_id', $empresaId)
                            ->where('direccion', 'emitido')
                            ->where('metodo_pago', 'PPD')
                            ->where('estado_sat', 'Vigente')
                            ->where('estado', '!=', 'pagado')
                            ->where('fecha_emision', '<', $now->copy()->startOfMonth()->toDateString())
                            ->count();

                        $ppdRecibidosCount = \App\Models\Cfdi::where('empresa_id', $empresaId)
                            ->where('direccion', 'recibido')
                            ->where('metodo_pago', 'PPD')
                            ->where('estado_sat', 'Vigente')
                            ->where('estado', 'pagado')
                            ->where('fecha_emision', '<', $now->copy()->startOfMonth()->toDateString())
                            ->count();
                    }

                    $fiscalAlert = [
                        'fiscalClosingAlert' => [
                            'active' => $isClosingPeriod,
                            'pending_emitidos_count' => $ppdEmitidosCount,
                            'pending_recibidos_count' => $ppdRecibidosCount,
                            'deadline_day' => 5,
                            'month_name' => $now->copy()->subMonth()->translatedFormat('F'),
                        ],
                    ];

                    $cancelacionesRecientes = [];
                    if ($empresaId) {
                        $cancelacionesRecientes = \App\Models\Cfdi::where('empresa_id', $empresaId)
                            ->where('estado_sat', 'Cancelado')
                            ->whereNotNull('datos_adicionales->cancelacion_detectada_monitor')
                            ->where('updated_at', '>', $now->copy()->subDays(7))
                            ->orderBy('updated_at', 'desc')
                            ->get()
                            ->map(function($cfdi) {
                                $poliza = \App\Models\Contab\PolizaContable::where('cfdi_uuid', $cfdi->uuid)
                                    ->orWhere('xml_content', 'ilike', '%' . $cfdi->uuid . '%')
                                    ->first();
                                return [
                                'id' => $cfdi->id,
                                'uuid' => $cfdi->uuid,
                                'folio' => $cfdi->folio,
                                'nombre' => $cfdi->direccion === 'emitido' ? ($cfdi->nombre_receptor ?? $cfdi->receptor_nombre) : ($cfdi->nombre_emisor ?? $cfdi->emisor_nombre),
                                'total' => $cfdi->total,
                                'direccion' => $cfdi->direccion,
                                'fecha_deteccion' => $cfdi->updated_at->diffForHumans(),
                                'tiene_poliza' => $poliza ? true : false,
                                'poliza_id' => $poliza ? $poliza->id : null,
                                'poliza_numero' => $poliza ? $poliza->numero : null,
                                'poliza_tipo' => $poliza ? $poliza->tipo : null,
                                'poliza_total' => $poliza ? (float) $poliza->total : null,
                            ];
                            })->toArray();
                    }

                    $cancellations = [
                        'cancellationAlerts' => [
                            'count' => count($cancelacionesRecientes),
                            'detalles' => $cancelacionesRecientes,
                        ],
                    ];

                    return array_merge($alerts, $tasks, $resico, $fiscalAlert, $cancellations);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Panel Concurrency [misc]: " . $e->getMessage());
                    return [];
                }
            }
        ]);

        $data = array_merge($defaults, $statsData, $chartsData, $miscData);
        $data['user'] = $this->formatUserData($user);

        return Inertia::render('Panel', $data);
    }

    /**
     * Execute a data-loading closure safely. On failure, log the error
     * with full context and return an empty array (defaults remain).
     */
    private function loadSafely(string $section, callable $loader): array
    {
        try {
            return $loader();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::channel('audit')->error(
                "[Panel] Section '{$section}' failed",
                [
                    'error'   => $e->getMessage(),
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                    'user_id' => Auth::id(),
                ]
            );

            // Also log to default channel for visibility
            \Illuminate\Support\Facades\Log::error(
                "PanelController [{$section}]: " . $e->getMessage()
            );

            return [];
        }
    }

    /**
     * Safe defaults for every panel prop — ensures the page always renders.
     */
    private function getSafeDefaults(Carbon $now): array
    {
        $emptyAlerts = [
            'vencidas' => [], 'vencidas_count' => 0,
            'semana' => [], 'semana_count' => 0,
            'quincena' => [], 'quincena_count' => 0,
            'mes' => [], 'mes_count' => 0,
        ];

        return [
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
            'alertasCuentasPagar' => $emptyAlerts,
            'alertasCuentasCobrar' => $emptyAlerts,
            'alertasPrestamos' => $emptyAlerts,
            'tareasPendientes' => [
                'tareas' => [], 'total' => 0, 'en_proceso' => 0, 'pendientes' => 0,
            ],
            'resicoStats' => [
                'ingresos_anuales' => 0,
                'limite' => 3500000,
                'porcentaje' => 0,
                'anio' => $now->year,
            ],
            'fiscalClosingAlert' => [
                'active' => false,
                'pending_emitidos_count' => 0,
                'pending_recibidos_count' => 0,
                'deadline_day' => 5,
                'month_name' => '',
            ],
            'cancellationAlerts' => [
                'count' => 0,
                'detalles' => [],
            ],
        ];
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

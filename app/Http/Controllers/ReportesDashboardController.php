<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Services\Reports\DashboardReportService;

class ReportesDashboardController extends Controller
{
    protected DashboardReportService $reportService;

    public function __construct(DashboardReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Mostrar el dashboard de reportes organizado por categorías
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Si el usuario es técnico y no admin/super-admin, mostrar su dashboard personal
        if ($user->hasRole('tecnico') && !$user->hasRole('admin') && !$user->hasRole('super-admin')) {
            
            $misTicketsUrgentes = \App\Models\Ticket::where('asignado_id', $user->id)
                ->whereIn('estado', ['abierto', 'en_progreso'])
                ->orderByRaw("FIELD(prioridad, 'urgente', 'alta', 'media', 'baja')")
                ->orderBy('fecha_limite', 'asc')
                ->take(5)
                ->get();
            
            $misProximasCitas = \App\Models\Cita::where('tecnico_id', $user->id)
                ->where('fecha_hora', '>=', now())
                ->whereIn('estado', ['programado', 'en_proceso'])
                ->orderBy('fecha_hora', 'asc')
                ->take(3)
                ->get();

            $ticketsVencidosCount = \App\Models\Ticket::where('asignado_id', $user->id)
                ->whereIn('estado', ['abierto', 'en_progreso'])
                ->where('fecha_limite', '<', now())
                ->count();

            return Inertia::render('Reportes/DashboardAgente', [
                'misTicketsUrgentes' => $misTicketsUrgentes,
                'misProximasCitas' => $misProximasCitas,
                'ticketsVencidosCount' => $ticketsVencidosCount,
            ]);
        }

        // --- Lógica existente para Admins y Ventas ---
        $periodo = $request->get('periodo', 'mes'); // dia, semana, mes, trimestre, año

        // Determinar rango de fechas
        $rangoFechas = $this->determinarRangoFechas($periodo);
        $fechaInicio = $rangoFechas['inicio'];
        $fechaFin = $rangoFechas['fin'];

        // Estadísticas generales usando el servicio
        $estadisticasGenerales = $this->reportService->getEstadisticasGenerales($fechaInicio, $fechaFin);

        // Solo enlaces a reportes activos en rutas (citas por técnico + ventas del periodo).
        $categorias = [
            'principal' => [
                'titulo' => 'Reportes operativos',
                'descripcion' => 'Citas por técnico y ventas del periodo (vendedor y cita)',
                'reportes' => [
                    ['nombre' => 'Citas por técnico (detalle)', 'ruta' => 'reportes.citas-por-tecnico', 'icono' => 'fas fa-user-check'],
                    ['nombre' => 'Ventas del periodo (vendedor y cita)', 'ruta' => 'reportes.ventas-semana', 'icono' => 'fas fa-receipt'],
                ],
                'estadisticas' => [
                    'ventas_periodo' => $estadisticasGenerales['ventas']['total'] ?? 0,
                    'utilidad_ventas' => $estadisticasGenerales['ventas']['utilidad'] ?? 0,
                    'citas_completadas' => $estadisticasGenerales['servicios']['citas_completadas'] ?? 0,
                ],
            ],
        ];

        return Inertia::render('Reportes/Dashboard', [
            'categorias' => $categorias,
            'periodo' => $periodo,
            'graficas' => $estadisticasGenerales['grafica_ventas'] ?? null,
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
}

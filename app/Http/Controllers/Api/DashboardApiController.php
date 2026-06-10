<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Venta;
use App\Models\Cita;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $hoy = Carbon::now();
        $inicioMes = $hoy->copy()->startOfMonth();
        $finMes = $hoy->copy()->endOfMonth();

        $stats = [
            'periodo' => $hoy->translatedFormat('F Y'),
        ];

        // 1. Métricas de Citas (Para todos)
        $stats['citas'] = [
            'hoy' => Cita::whereDate('fecha_hora', $hoy)->count(),
            'pendientes' => Cita::whereIn('estado', ['pendiente', 'programado', 'en_proceso'])->count(),
            'mis_proximas' => Cita::where('tecnico_id', $user->id)
                ->where('fecha_hora', '>=', now())
                ->whereIn('estado', ['programado', 'en_proceso'])
                ->orderBy('fecha_hora', 'asc')
                ->take(3)
                ->get(),
        ];

        // 2. Métricas de Ventas (Solo Admins)
        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            $stats['ventas'] = [
                'total_mes' => (float) Venta::whereBetween('fecha', [$inicioMes, $finMes])
                    ->where('estado', '!=', 'cancelada')
                    ->sum('total'),
                'conteo_mes' => Venta::whereBetween('fecha', [$inicioMes, $finMes])
                    ->where('estado', '!=', 'cancelada')
                    ->count(),
                'cobranza_pendiente' => (float) \App\Models\CuentasPorCobrar::where('estado', '!=', 'pagado')
                    ->where('estado', '!=', 'cancelada')
                    ->sum('monto_pendiente'),
            ];
        }

        // 3. Métricas de Soporte / Tickets
        $stats['soporte'] = [
            'abiertos' => Ticket::whereIn('estado', ['abierto', 'en_progreso'])->count(),
            'urgentes' => Ticket::whereIn('estado', ['abierto', 'en_progreso'])
                ->where('prioridad', 'urgente')
                ->count(),
        ];

        // 4. Actividad Reciente (Bitácora)
        $stats['actividad'] = \App\Models\BitacoraActividad::with('usuario:id,name')
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(function ($a) {
                return [
                    'id' => $a->id,
                    'descripcion' => $a->descripcion ?: $a->titulo,
                    'usuario' => $a->usuario->name ?? 'Sistema',
                    'fecha' => $a->created_at->diffForHumans(),
                    'tipo' => $this->inferirTipoActividad($a->descripcion ?: $a->titulo),
                ];
            });

        return response()->json($stats);
    }

    private function inferirTipoActividad($descripcion)
    {
        $d = strtolower($descripcion);
        if (str_contains($d, 'creó') || str_contains($d, 'nueva')) return 'success';
        if (str_contains($d, 'editó') || str_contains($d, 'actualizó')) return 'warning';
        if (str_contains($d, 'eliminó') || str_contains($d, 'canceló')) return 'danger';
        return 'primary';
    }
}

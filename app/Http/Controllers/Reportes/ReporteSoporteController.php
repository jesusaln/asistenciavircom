<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\PolizaServicio;
use App\Models\User;
use App\Models\EmpresaConfiguracion;
use App\Support\EmpresaResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReporteSoporteController extends Controller
{
    /**
     * Reporte de Consumo de Horas por Póliza
     * Para mostrarle al cliente cuánto está usando su póliza
     */
    public function consumoPoliza(Request $request, PolizaServicio $poliza)
    {
        $mes = $request->get('mes', now()->month);
        $anio = $request->get('anio', now()->year);

        // Tickets de esta póliza en el período
        $tickets = Ticket::where('poliza_id', $poliza->id)
            ->whereNotNull('horas_trabajadas')
            ->where('horas_trabajadas', '>', 0)
            ->whereMonth('updated_at', $mes)
            ->whereYear('updated_at', $anio)
            ->with(['asignado:id,name', 'categoria:id,nombre'])
            ->orderByDesc('updated_at')
            ->get();

        $totalHoras = $tickets->sum('horas_trabajadas');
        $horasIncluidas = $poliza->horas_incluidas_mensual ?? 0;
        $exceso = $horasIncluidas > 0 ? max(0, $totalHoras - $horasIncluidas) : 0;
        $porcentajeUso = $horasIncluidas > 0 ? round(($totalHoras / $horasIncluidas) * 100, 1) : 0;

        $empresaId = EmpresaResolver::resolveId();
        $empresa = EmpresaConfiguracion::getConfig($empresaId);

        $periodo = Carbon::createFromDate($anio, $mes, 1)->locale('es')->isoFormat('MMMM YYYY');

        return view('reportes.soporte.consumo-poliza', [
            'poliza' => $poliza->load('cliente'),
            'tickets' => $tickets,
            'totalHoras' => $totalHoras,
            'horasIncluidas' => $horasIncluidas,
            'exceso' => $exceso,
            'porcentajeUso' => $porcentajeUso,
            'periodo' => $periodo,
            'mes' => $mes,
            'anio' => $anio,
            'empresa' => $empresa,
        ]);
    }

    /**
     * Reporte de Horas Trabajadas por Técnico
     * Para evaluar productividad del equipo de soporte
     */
    public function horasTecnico(Request $request, User $usuario = null)
    {
        $mes = $request->get('mes', now()->month);
        $anio = $request->get('anio', now()->year);

        // Si no se especifica usuario, mostrar todos
        $query = Ticket::whereNotNull('asignado_id')
            ->whereNotNull('horas_trabajadas')
            ->where('horas_trabajadas', '>', 0)
            ->whereMonth('updated_at', $mes)
            ->whereYear('updated_at', $anio);

        if ($usuario) {
            $query->where('asignado_id', $usuario->id);
        }

        // Resumen por técnico
        $resumenPorTecnico = (clone $query)
            ->select(
                'asignado_id',
                DB::raw('SUM(horas_trabajadas) as total_horas'),
                DB::raw('COUNT(*) as total_tickets'),
                DB::raw('AVG(horas_trabajadas) as promedio_horas')
            )
            ->groupBy('asignado_id')
            ->with('asignado:id,name,email')
            ->orderByDesc('total_horas')
            ->get();

        // Detalle de tickets
        $tickets = $query
            ->with(['asignado:id,name', 'cliente:id,nombre_razon_social', 'poliza:id,folio,nombre'])
            ->orderByDesc('updated_at')
            ->get();

        $totalGeneral = $tickets->sum('horas_trabajadas');
        $totalTickets = $tickets->count();

        $empresaId = EmpresaResolver::resolveId();
        $empresa = EmpresaConfiguracion::getConfig($empresaId);

        $periodo = Carbon::createFromDate($anio, $mes, 1)->locale('es')->isoFormat('MMMM YYYY');

        return view('reportes.soporte.horas-tecnico', [
            'usuario' => $usuario,
            'resumenPorTecnico' => $resumenPorTecnico,
            'tickets' => $tickets,
            'totalGeneral' => $totalGeneral,
            'totalTickets' => $totalTickets,
            'periodo' => $periodo,
            'mes' => $mes,
            'anio' => $anio,
            'empresa' => $empresa,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\EncuestaSatisfaccion;
use App\Support\EmpresaResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EncuestaSatisfaccionController extends Controller
{
    public function index(Request $request): Response
    {
        $empresaId = EmpresaResolver::resolveId();

        $query = EncuestaSatisfaccion::query()
            ->where('empresa_id', $empresaId)
            ->with(['cliente:id,nombre_razon_social,telefono,wa_user_id', 'cita:id,folio,fecha_hora,tipo_servicio,marca_equipo']);

        // Filtros
        if ($estado = $request->get('estado')) {
            if ($estado === 'activas') {
                $query->whereIn('estado', ['pendiente', 'en_progreso']);
            } elseif ($estado === 'completadas') {
                $query->where('estado', 'completada');
            } elseif ($estado === 'canceladas') {
                $query->where('estado', 'cancelada');
            } else {
                $query->where('estado', $estado);
            }
        }

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('folio', 'ilike', "%{$search}%")
                    ->orWhere('nombre_cliente_snapshot', 'ilike', "%{$search}%")
                    ->orWhere('codigo_promocional', 'ilike', "%{$search}%")
                    ->orWhere('wa_id', 'like', "%{$search}%")
                    ->orWhereHas('cliente', function ($qc) use ($search) {
                        $qc->where('nombre_razon_social', 'ilike', "%{$search}%");
                    });
            });
        }

        if ($maxCalif = $request->get('max_calificacion')) {
            $query->where('calificacion_global', '<=', (float) $maxCalif);
        }

        if ($minCalif = $request->get('min_calificacion')) {
            $query->where('calificacion_global', '>=', (float) $minCalif);
        }

        if ($fechaDesde = $request->get('fecha_desde')) {
            $query->whereDate('created_at', '>=', $fechaDesde);
        }

        if ($fechaHasta = $request->get('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $fechaHasta);
        }

        $soloConQueja = $request->boolean('solo_con_queja');
        if ($soloConQueja) {
            $query->whereNotNull('respuestas')
                ->where(function ($q) {
                    $q->whereRaw("respuestas->>'p2_comentario' IS NOT NULL")
                        ->whereRaw("respuestas->>'p2_comentario' != ''")
                        ->whereRaw("lower(respuestas->>'p2_comentario') NOT IN ('no', 'n', 'nada', '-', '.', 'no.', 'no gracias')");
                });
        }

        $sortBy = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        if (! in_array($sortBy, ['created_at', 'calificacion_global', 'nps_score', 'completada_at'], true)) {
            $sortBy = 'created_at';
        }
        if (! in_array(strtolower($sortDir), ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        $encuestas = $query->orderBy($sortBy, $sortDir)
            ->paginate(20)
            ->withQueryString();

        // Estadísticas para el header
        $stats = $this->calcularStats($empresaId);

        return Inertia::render('EncuestaSatisfaccion/Index', [
            'encuestas' => $encuestas,
            'stats' => $stats,
            'filtros' => $request->only(['estado', 'search', 'max_calificacion', 'min_calificacion', 'fecha_desde', 'fecha_hasta', 'solo_con_queja', 'sort', 'dir']),
        ]);
    }

    public function show(EncuestaSatisfaccion $encuesta): Response
    {
        $empresaId = EmpresaResolver::resolveId();

        if ((int) $encuesta->empresa_id !== (int) $empresaId) {
            abort(403, 'No tienes permiso para ver esta encuesta.');
        }

        $encuesta->load(['cliente', 'cita', 'cita.tecnico']);

        return Inertia::render('EncuestaSatisfaccion/Show', [
            'encuesta' => $encuesta,
        ]);
    }

    protected function calcularStats(int $empresaId): array
    {
        $base = EncuestaSatisfaccion::where('empresa_id', $empresaId);

        $total = (clone $base)->count();
        $enviadas = (clone $base)->whereNotNull('enviada_at')->count();
        $completadas = (clone $base)->where('estado', EncuestaSatisfaccion::ESTADO_COMPLETADA)->count();
        $pendientes = (clone $base)->whereIn('estado', [EncuestaSatisfaccion::ESTADO_PENDIENTE, EncuestaSatisfaccion::ESTADO_EN_PROGRESO])->count();
        $canceladas = (clone $base)->where('estado', EncuestaSatisfaccion::ESTADO_CANCELADA)->count();

        $tasaRespuesta = $enviadas > 0 ? round(($completadas / $enviadas) * 100, 1) : 0;
        $promCalificacion = (clone $base)->whereNotNull('calificacion_global')->avg('calificacion_global');
        $promNps = (clone $base)->whereNotNull('nps_score')->avg('nps_score');

        $promotores = (clone $base)->where('nps_score', '>=', 9)->count();
        $neutrales = (clone $base)->whereBetween('nps_score', [7, 8])->count();
        $detractores = (clone $base)->where('nps_score', '<', 7)->whereNotNull('nps_score')->count();

        $npsScore = $enviadas > 0 ? round((($promotores - $detractores) / max($enviadas, 1)) * 100, 1) : 0;

        $codigosUsados = (clone $base)->where('codigo_usado', true)->count();
        $codigosGenerados = (clone $base)->whereNotNull('codigo_promocional')->count();
        $tasaUsoCupones = $codigosGenerados > 0 ? round(($codigosUsados / $codigosGenerados) * 100, 1) : 0;

        return [
            'total' => $total,
            'enviadas' => $enviadas,
            'completadas' => $completadas,
            'pendientes' => $pendientes,
            'canceladas' => $canceladas,
            'tasa_respuesta' => $tasaRespuesta,
            'prom_calificacion' => $promCalificacion ? round((float) $promCalificacion, 2) : null,
            'prom_nps' => $promNps ? round((float) $promNps, 1) : null,
            'nps_score' => $npsScore,
            'promotores' => $promotores,
            'neutrales' => $neutrales,
            'detractores' => $detractores,
            'codigos_generados' => $codigosGenerados,
            'codigos_usados' => $codigosUsados,
            'tasa_uso_cupones' => $tasaUsoCupones,
        ];
    }
}
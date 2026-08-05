<?php

namespace App\Http\Controllers;

use App\Models\EmpresaConfiguracion;
use App\Models\EncuestaSatisfaccion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EncuestaSatisfaccionController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = \App\Support\EmpresaResolver::resolveId();
        $query = EncuestaSatisfaccion::query()
            ->with(['cliente:id,nombre_razon_social,telefono', 'cita:id,folio,tipo_servicio'])
            ->where('empresa_id', $empresaId)
            ->latest();

        if ($request->filled('calificacion')) {
            $query->where('calificacion', (int) $request->integer('calificacion'));
        }

        $encuestas = $query->paginate(25)->withQueryString();
        $respondidas = EncuestaSatisfaccion::where('empresa_id', $empresaId)->whereNotNull('respondida_at');

        return Inertia::render('Encuestas/Satisfaccion/Index', [
            'encuestas' => $encuestas,
            'filtroCalificacion' => $request->input('calificacion'),
            'brandName' => EmpresaConfiguracion::query()->find($empresaId)?->nombre_empresa
                ?: config('app.name', 'Servicio'),
            'resumen' => [
                'total' => EncuestaSatisfaccion::where('empresa_id', $empresaId)->count(),
                'respondidas' => (clone $respondidas)->count(),
                'promedio' => round((float) ((clone $respondidas)->avg('calificacion') ?? 0), 2),
                'cupones' => (clone $respondidas)->whereNotNull('cupon_codigo')->count(),
            ],
        ]);
    }
}

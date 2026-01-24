<?php

namespace App\Http\Controllers;

use App\Models\PlanPoliza;
use App\Models\Servicio;
use App\Support\EmpresaResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PlanPolizaController extends Controller
{
    /**
     * Display a listing of the resource (Admin).
     */
    public function index(Request $request)
    {
        $planes = PlanPoliza::query()
            ->when($request->search, fn($q, $s) => $q->where('nombre', 'like', "%{$s}%"))
            ->when($request->tipo, fn($q, $t) => $q->where('tipo', $t))
            ->ordenado()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('PlanPoliza/Index', [
            'planes' => $planes,
            'tipos' => PlanPoliza::tipos(),
            'filters' => $request->only(['search', 'tipo']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $servicios = Servicio::active()->get(['id', 'nombre', 'precio']);

        return Inertia::render('PlanPoliza/Edit', [
            'plan' => null,
            'tipos' => PlanPoliza::tipos(),
            'servicios' => $servicios,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'descripcion_corta' => 'nullable|string|max:500',
            'tipo' => 'required|string|in:mantenimiento,soporte,garantia,premium,personalizado',
            'icono' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:20',
            'precio_mensual' => 'required|numeric|min:0',
            'precio_anual' => 'nullable|numeric|min:0',
            'precio_instalacion' => 'nullable|numeric|min:0',
            'horas_incluidas' => 'nullable|integer|min:0',
            'tickets_incluidos' => 'nullable|integer|min:0',
            'sla_horas_respuesta' => 'nullable|integer|min:1',
            'costo_hora_extra' => 'nullable|numeric|min:0',
            'beneficios' => 'nullable|array',
            'incluye_servicios' => 'nullable|array',
            'activo' => 'boolean',
            'destacado' => 'boolean',
            'visible_catalogo' => 'boolean',
            'orden' => 'nullable|integer',
            'min_equipos' => 'nullable|integer|min:1',
            'max_equipos' => 'nullable|integer|min:1',
            'mantenimiento_frecuencia_meses' => 'nullable|integer|min:1|max:24',
            'generar_cita_automatica' => 'nullable|boolean',
            'visitas_sitio_mensuales' => 'nullable|integer|min:0',
            'costo_visita_sitio_extra' => 'nullable|numeric|min:0',
            'costo_ticket_extra' => 'nullable|numeric|min:0',
            'moneda' => 'nullable|string|max:5',
            'precio_trimestral' => 'nullable|numeric|min:0',
            'precio_semestral' => 'nullable|numeric|min:0',
            'iva_tasa' => 'nullable|numeric|min:0',
            'iva_incluido' => 'nullable|boolean',
            'limit_dia_pago' => 'nullable|integer|min:1|max:31',
            'dias_gracia_cobranza' => 'nullable|integer|min:0',
            'recargo_pago_tardio' => 'nullable|numeric|min:0',
            'tipo_recargo' => 'nullable|string|in:fijo,porcentaje',
            'limite_usuarios_soporte' => 'nullable|integer|min:1',
            'limite_ubicaciones' => 'nullable|integer|min:1',
            'soporte_remoto_ilimitado' => 'nullable|boolean',
            'soporte_presencial_incluido' => 'nullable|boolean',
            'requiere_orden_compra' => 'nullable|boolean',
            'metodo_pago_sugerido' => 'nullable|string|max:255',
            'clausulas' => 'nullable|string',
            'terminos_pago' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['nombre']) . '-' . Str::random(6);

        $plan = PlanPoliza::create($validated);

        // Sincronizar servicios elegibles si se enviaron
        if ($request->has('servicios_elegibles')) {
            $plan->serviciosElegibles()->sync($request->input('servicios_elegibles', []));
        }

        return redirect()->route('planes-poliza.index')
            ->with('success', 'Plan de póliza creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(PlanPoliza $planesPoliza)
    {
        return Inertia::render('PlanPoliza/Show', [
            'plan' => $planesPoliza,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PlanPoliza $planesPoliza)
    {
        $servicios = Servicio::active()->get(['id', 'nombre', 'precio']);

        // Cargar los servicios elegibles actuales
        $planesPoliza->load('serviciosElegibles');
        $serviciosElegiblesIds = $planesPoliza->serviciosElegibles->pluck('id')->toArray();

        return Inertia::render('PlanPoliza/Edit', [
            'plan' => $planesPoliza,
            'tipos' => PlanPoliza::tipos(),
            'servicios' => $servicios,
            'serviciosElegiblesIds' => $serviciosElegiblesIds,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PlanPoliza $planesPoliza)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'descripcion_corta' => 'nullable|string|max:500',
            'tipo' => 'required|string|in:mantenimiento,soporte,garantia,premium,personalizado',
            'icono' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:20',
            'precio_mensual' => 'required|numeric|min:0',
            'precio_anual' => 'nullable|numeric|min:0',
            'precio_instalacion' => 'nullable|numeric|min:0',
            'horas_incluidas' => 'nullable|integer|min:0',
            'tickets_incluidos' => 'nullable|integer|min:0',
            'sla_horas_respuesta' => 'nullable|integer|min:1',
            'costo_hora_extra' => 'nullable|numeric|min:0',
            'beneficios' => 'nullable|array',
            'incluye_servicios' => 'nullable|array',
            'activo' => 'boolean',
            'destacado' => 'boolean',
            'visible_catalogo' => 'boolean',
            'orden' => 'nullable|integer',
            'min_equipos' => 'nullable|integer|min:1',
            'max_equipos' => 'nullable|integer|min:1',
            'mantenimiento_frecuencia_meses' => 'nullable|integer|min:1|max:24',
            'generar_cita_automatica' => 'nullable|boolean',
            'visitas_sitio_mensuales' => 'nullable|integer|min:0',
            'costo_visita_sitio_extra' => 'nullable|numeric|min:0',
            'costo_ticket_extra' => 'nullable|numeric|min:0',
            'moneda' => 'nullable|string|max:5',
            'precio_trimestral' => 'nullable|numeric|min:0',
            'precio_semestral' => 'nullable|numeric|min:0',
            'iva_tasa' => 'nullable|numeric|min:0',
            'iva_incluido' => 'nullable|boolean',
            'limit_dia_pago' => 'nullable|integer|min:1|max:31',
            'dias_gracia_cobranza' => 'nullable|integer|min:0',
            'recargo_pago_tardio' => 'nullable|numeric|min:0',
            'tipo_recargo' => 'nullable|string|in:fijo,porcentaje',
            'limite_usuarios_soporte' => 'nullable|integer|min:1',
            'limite_ubicaciones' => 'nullable|integer|min:1',
            'soporte_remoto_ilimitado' => 'nullable|boolean',
            'soporte_presencial_incluido' => 'nullable|boolean',
            'requiere_orden_compra' => 'nullable|boolean',
            'metodo_pago_sugerido' => 'nullable|string|max:255',
            'clausulas' => 'nullable|string',
            'terminos_pago' => 'nullable|string',
        ]);

        $planesPoliza->update($validated);

        // Sincronizar servicios elegibles
        if ($request->has('servicios_elegibles')) {
            $planesPoliza->serviciosElegibles()->sync($request->input('servicios_elegibles', []));
        }

        return redirect()->route('planes-poliza.index')
            ->with('success', 'Plan de póliza actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PlanPoliza $planesPoliza)
    {
        $planesPoliza->delete();

        return redirect()->route('planes-poliza.index')
            ->with('success', 'Plan de póliza eliminado correctamente.');
    }

    /**
     * Toggle estado activo del plan.
     */
    public function toggle(PlanPoliza $planesPoliza)
    {
        $planesPoliza->update(['activo' => !$planesPoliza->activo]);

        return back()->with('success', 'Estado del plan actualizado.');
    }

    /**
     * Toggle estado destacado del plan.
     */
    public function toggleDestacado(PlanPoliza $planesPoliza)
    {
        $planesPoliza->update(['destacado' => !$planesPoliza->destacado]);

        return back()->with('success', 'Visibilidad en index actualizada.');
    }

    /**
     * Catálogo público de planes (sin autenticación).
     */
    public function catalogo()
    {
        $empresaId = EmpresaResolver::resolveId();
        $empresaModel = \App\Models\Empresa::find($empresaId);
        $configuracion = \App\Models\EmpresaConfiguracion::getConfig($empresaId);

        $empresa = $empresaModel ? array_merge($empresaModel->toArray(), [
            'color_principal' => $configuracion->color_principal,
            'color_secundario' => $configuracion->color_secundario,
            'color_terciario' => $configuracion->color_terciario,
            'logo_url' => $configuracion->logo_url,
            'favicon_url' => $configuracion->favicon_url,
            'nombre_comercial_config' => $configuracion->nombre_empresa,
        ]) : null;

        $planes = PlanPoliza::publicos()
            ->ordenado()
            ->get();

        return Inertia::render('Catalogo/Polizas', [
            'empresa' => $empresa,
            'planes' => $planes,
        ]);
    }

    /**
     * Detalle de un plan específico (público).
     */
    public function detallePlan(string $slug)
    {
        $empresaId = EmpresaResolver::resolveId();
        $empresa = \App\Models\Empresa::find($empresaId);

        $plan = PlanPoliza::publicos()
            ->where('slug', $slug)
            ->firstOrFail();

        return Inertia::render('Catalogo/PlanDetalle', [
            'empresa' => $empresa,
            'plan' => $plan->append(['ahorro_anual', 'porcentaje_descuento_anual', 'icono_display']),
        ]);
    }
}

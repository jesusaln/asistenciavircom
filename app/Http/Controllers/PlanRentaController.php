<?php

namespace App\Http\Controllers;

use App\Models\PlanRenta;
use App\Support\EmpresaResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PlanRentaController extends Controller
{
    /**
     * Display a listing of the resource (Admin).
     */
    public function index(Request $request)
    {
        $planes = PlanRenta::query()
            ->when($request->search, fn($q, $s) => $q->where('nombre', 'like', "%{$s}%"))
            ->when($request->tipo, fn($q, $t) => $q->where('tipo', $t))
            ->ordenado()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('PlanRenta/Index', [
            'planes' => $planes,
            'tipos' => PlanRenta::tipos(),
            'filters' => $request->only(['search', 'tipo']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('PlanRenta/Edit', [
            'plan' => null,
            'tipos' => PlanRenta::tipos(),
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
            'tipo' => 'required|string|in:pdv,oficina,gaming,laptop,personalizado',
            'icono' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:20',
            'precio_mensual' => 'required|numeric|min:0',
            'deposito_garantia' => 'nullable|numeric|min:0',
            'meses_minimos' => 'required|integer|min:1',
            'beneficios' => 'nullable|array',
            'equipamiento_incluido' => 'nullable|array',
            'activo' => 'boolean',
            'destacado' => 'boolean',
            'visible_catalogo' => 'boolean',
            'orden' => 'nullable|integer',
        ]);

        $validated['slug'] = Str::slug($validated['nombre']) . '-' . Str::random(6);

        PlanRenta::create($validated);

        return redirect()->route('planes-renta.index')
            ->with('success', 'Plan de renta creado correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PlanRenta $planesRenta)
    {
        return Inertia::render('PlanRenta/Edit', [
            'plan' => $planesRenta,
            'tipos' => PlanRenta::tipos(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PlanRenta $planesRenta)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'descripcion_corta' => 'nullable|string|max:500',
            'tipo' => 'required|string|in:pdv,oficina,gaming,laptop,personalizado',
            'icono' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:20',
            'precio_mensual' => 'required|numeric|min:0',
            'deposito_garantia' => 'nullable|numeric|min:0',
            'meses_minimos' => 'required|integer|min:1',
            'beneficios' => 'nullable|array',
            'equipamiento_incluido' => 'nullable|array',
            'activo' => 'boolean',
            'destacado' => 'boolean',
            'visible_catalogo' => 'boolean',
            'orden' => 'nullable|integer',
        ]);

        $planesRenta->update($validated);

        return redirect()->route('planes-renta.index')
            ->with('success', 'Plan de renta actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PlanRenta $planesRenta)
    {
        $planesRenta->delete();

        return redirect()->route('planes-renta.index')
            ->with('success', 'Plan de renta eliminado correctamente.');
    }

    /**
     * Toggle estado activo del plan.
     */
    public function toggle(PlanRenta $planesRenta)
    {
        $planesRenta->update(['activo' => !$planesRenta->activo]);
        return back()->with('success', 'Estado del plan actualizado.');
    }

    /**
     * Catálogo público de rentas.
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

        $planes = PlanRenta::publicos()
            ->ordenado()
            ->get();

        return Inertia::render('Catalogo/Rentas', [
            'empresa' => $empresa,
            'planes' => $planes,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\PolizaMantenimiento;
use App\Models\PolizaServicio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class PolizaMantenimientoController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, PolizaServicio $poliza)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'checklist' => 'nullable|array',
            'frecuencia' => 'required|in:diario,semanal,quincenal,mensual,bimestral,trimestral,semestral,anual',
            'dia_preferido' => 'nullable|integer|min:1|max:31',
            'requiere_visita' => 'boolean',
            'activo' => 'boolean',
            'guia_tecnica_id' => 'nullable|exists:guia_tecnicas,id',
        ]);

        if (!isset($validated['tipo'])) {
            $validated['tipo'] = 'preventivo';
        }

        $poliza->mantenimientos()->create($validated);

        return back()->with('success', 'Plan de mantenimiento agregado correctamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PolizaMantenimiento $mantenimiento)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'checklist' => 'nullable|array',
            'frecuencia' => 'required|in:diario,semanal,quincenal,mensual,bimestral,trimestral,semestral,anual',
            'dia_preferido' => 'nullable|integer|min:1|max:31',
            'requiere_visita' => 'boolean',
            'activo' => 'boolean',
            'guia_tecnica_id' => 'nullable|exists:guia_tecnicas,id',
        ]);

        $mantenimiento->update($validated);

        return back()->with('success', 'Plan de mantenimiento actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PolizaMantenimiento $mantenimiento)
    {
        $mantenimiento->delete();

        return back()->with('success', 'Plan de mantenimiento eliminado correctamente.');
    }
}

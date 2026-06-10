<?php

namespace App\Http\Controllers;

use App\Models\ContratoPlantilla;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContratoPlantillaController extends Controller
{
    public function index()
    {
        return Inertia::render('Nom035/Contratos/Templates', [
            'templates' => ContratoPlantilla::orderBy('nombre')->get()
        ]);
    }

    public function create()
    {
        return Inertia::render('Nom035/Contratos/TemplateEditor', [
            'template' => null,
            'repse_contracts' => \App\Models\RepseContract::with('cliente')->get()->map(fn($r) => [
                'id' => $r->id,
                'label' => $r->contract_number . ' - ' . $r->cliente->nombre_razon_social,
                'data' => [
                    'cliente_nombre' => $r->cliente->nombre_razon_social,
                    'cliente_rfc' => $r->cliente->rfc,
                    'cliente_domicilio' => $r->cliente->calle . ' ' . $r->cliente->numero_exterior,
                    'contrato_numero' => $r->contract_number,
                    'contrato_objeto' => $r->service_object,
                    'fecha_inicio' => $r->start_date,
                    'fecha_fin' => $r->end_date,
                    'monto' => number_format($r->amount, 2),
                ]
            ])
        ]);
    }

    public function edit(ContratoPlantilla $template)
    {
        return Inertia::render('Nom035/Contratos/TemplateEditor', [
            'template' => $template,
            'repse_contracts' => \App\Models\RepseContract::with('cliente')->get()->map(fn($r) => [
                'id' => $r->id,
                'label' => $r->contract_number . ' - ' . $r->cliente->nombre_razon_social,
                'data' => [
                    'cliente_nombre' => $r->cliente->nombre_razon_social,
                    'cliente_rfc' => $r->cliente->rfc,
                    'cliente_domicilio' => $r->cliente->calle . ' ' . $r->cliente->numero_exterior,
                    'contrato_numero' => $r->contract_number,
                    'contrato_objeto' => $r->service_object,
                    'fecha_inicio' => $r->start_date,
                    'fecha_fin' => $r->end_date,
                    'monto' => number_format($r->amount, 2),
                ]
            ])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|string',
            'contenido' => 'required|string',
            'vigencia_meses' => 'nullable|integer'
        ]);

        ContratoPlantilla::create($validated);

        return redirect()->back()->with('success', 'Plantilla creada correctamente');
    }

    public function update(Request $request, ContratoPlantilla $template)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|string',
            'contenido' => 'required|string',
            'vigencia_meses' => 'nullable|integer'
        ]);

        $template->update($validated);

        return redirect()->back()->with('success', 'Plantilla actualizada correctamente');
    }

    public function destroy(ContratoPlantilla $template)
    {
        $template->delete();
        return redirect()->back()->with('success', 'Plantilla eliminada');
    }
}

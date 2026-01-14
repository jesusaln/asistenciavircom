<?php

namespace App\Http\Controllers;

use App\Models\TicketCategory;
use App\Models\EmpresaConfiguracion;
use App\Models\Empresa;
use App\Support\EmpresaResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TicketCategoryController extends Controller
{
    public function index()
    {
        $empresaId = EmpresaResolver::resolveId();

        $categorias = TicketCategory::where('empresa_id', $empresaId)
            ->withCount('tickets')
            ->ordenadas()
            ->get();

        return Inertia::render('Soporte/Categorias/Index', [
            'categorias' => $categorias,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'color' => 'required|string|max:20',
            'icono' => 'required|string|max:50',
            'sla_horas' => 'required|integer|min:1|max:720',
            'orden' => 'nullable|integer',
        ]);

        $validated['empresa_id'] = EmpresaResolver::resolveId();
        // $validated['empresa_id'] = null; // Fix FK
        $validated['activo'] = true;

        $categoria = TicketCategory::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'categoria' => $categoria,
                'message' => 'Categoría creada correctamente'
            ]);
        }

        return back()->with('success', 'Categoría creada');
    }

    public function update(Request $request, TicketCategory $categoria)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'color' => 'required|string|max:20',
            'icono' => 'required|string|max:50',
            'sla_horas' => 'required|integer|min:1|max:720',
            'orden' => 'nullable|integer',
            'activo' => 'boolean',
        ]);

        $categoria->update($validated);

        return back()->with('success', 'Categoría actualizada');
    }

    public function destroy(TicketCategory $categoria)
    {
        $categoria->delete();
        return back()->with('success', 'Categoría eliminada');
    }
}

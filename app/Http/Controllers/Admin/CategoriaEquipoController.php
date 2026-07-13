<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoriaEquipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoriaEquipoController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin|super-admin']);
    }

    public function index()
    {
        $empresaId = Auth::user()->empresa_id;
        $categorias = CategoriaEquipo::where('empresa_id', $empresaId)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        return inertia('Admin/CategoriasEquipos/Index', [
            'categorias' => $categorias,
        ]);
    }

    public function store(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;

        $request->validate([
            'nombre' => 'required|string|max:100',
            'valor' => 'required|string|max:100|unique:categorias_equipos,valor,NULL,id,empresa_id,' . $empresaId,
            'orden' => 'nullable|integer|min:0',
        ]);

        CategoriaEquipo::create([
            'empresa_id' => $empresaId,
            'nombre' => $request->nombre,
            'valor' => $request->valor,
            'orden' => $request->orden ?? 0,
            'activo' => true,
        ]);

        return redirect()->back()->with('success', 'Categoría creada correctamente.');
    }

    public function update(Request $request, CategoriaEquipo $categoriaEquipo)
    {
        $empresaId = Auth::user()->empresa_id;
        if ($categoriaEquipo->empresa_id !== $empresaId) {
            abort(403);
        }

        $request->validate([
            'nombre' => 'required|string|max:100',
            'valor' => 'required|string|max:100|unique:categorias_equipos,valor,' . $categoriaEquipo->id . ',id,empresa_id,' . $empresaId,
            'orden' => 'nullable|integer|min:0',
            'activo' => 'boolean',
        ]);

        $categoriaEquipo->update($request->only(['nombre', 'valor', 'orden', 'activo']));

        return redirect()->back()->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(CategoriaEquipo $categoriaEquipo)
    {
        $empresaId = Auth::user()->empresa_id;
        if ($categoriaEquipo->empresa_id !== $empresaId) {
            abort(403);
        }

        $categoriaEquipo->delete();

        return redirect()->back()->with('success', 'Categoría eliminada correctamente.');
    }

    // API para cargar categorías en formularios (público)
    public function apiOpciones(Request $request)
    {
        $empresaId = (int) ($request->empresa_id ?: \App\Support\EmpresaResolver::resolveId());
        return response()->json([
            'categorias' => CategoriaEquipo::getOpciones($empresaId),
        ]);
    }
}

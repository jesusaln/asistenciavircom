<?php

namespace App\Http\Controllers;

use App\Models\CategoriaHerramienta;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CategoriaHerramientaController extends Controller
{
    public function index()
    {
        $categorias = CategoriaHerramienta::orderBy('nombre')->get();
        return response()->json($categorias);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
        ]);

        $nombre = trim($request->input('nombre'));
        $slugBase = Str::slug($nombre);
        $slug = $slugBase;
        $i = 1;
        while (CategoriaHerramienta::where('slug', $slug)->exists()) {
            $slug = $slugBase.'-'.$i;
            $i++;
        }

        $categoria = CategoriaHerramienta::create([
            'nombre' => $nombre,
            'slug' => $slug,
            'descripcion' => $request->input('descripcion'),
            'activo' => true,
        ]);

        return response()->json(['success' => true, 'categoria' => $categoria]);
    }

    public function show(CategoriaHerramienta $categoria)
    {
        return response()->json($categoria);
    }

    public function update(Request $request, CategoriaHerramienta $categoria)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'activo' => 'nullable|boolean',
        ]);

        $nombre = trim($request->input('nombre'));

        // Si el nombre cambió, actualizar el slug
        if ($nombre !== $categoria->nombre) {
            $slugBase = Str::slug($nombre);
            $slug = $slugBase;
            $i = 1;
            while (CategoriaHerramienta::where('slug', $slug)->where('id', '!=', $categoria->id)->exists()) {
                $slug = $slugBase.'-'.$i;
                $i++;
            }

            $categoria->slug = $slug;
        }

        $categoria->update([
            'nombre' => $nombre,
            'descripcion' => $request->input('descripcion'),
            'activo' => $request->input('activo', $categoria->activo),
        ]);

        return response()->json(['success' => true, 'categoria' => $categoria]);
    }

    public function destroy(CategoriaHerramienta $categoria)
    {
        // Verificar si la categoría tiene herramientas asociadas
        if ($categoria->tieneHerramientas()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la categoría porque tiene herramientas asociadas.'
            ], 400);
        }

        $categoria->delete();

        return response()->json(['success' => true, 'message' => 'Categoría eliminada correctamente']);
    }

    public function toggle(CategoriaHerramienta $categoria)
    {
        $categoria->update(['activo' => !$categoria->activo]);

        return response()->json([
            'success' => true,
            'categoria' => $categoria,
            'message' => $categoria->activo ? 'Categoría activada' : 'Categoría desactivada'
        ]);
    }
}


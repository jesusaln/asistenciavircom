<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\Categoria;
use Illuminate\Http\Request;


class CategoriaController extends Controller
{
    /**
     * Muestra una lista de todas las categorías en formato JSON.
     */
    public function index()
    {
        try {
            $categorias = Categoria::all();
            return response()->json($categorias);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener las categorías'], 500);
        }
    }

    /**
     * Almacena una nueva categoría en la base de datos.
     */
    public function store(Request $request)
    {
        // Validar los datos recibidos
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:categorias,nombre',
            'descripcion' => 'nullable|string|max:1000',
        ]);

        try {
            // Crear la categoría
            $categoria = Categoria::create($validated);

            // Devolver la categoría creada como respuesta JSON
            return response()->json($categoria, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al crear la categoría'], 500);
        }
    }

    /**
     * Muestra una categoría específica en formato JSON.
     */
    public function show($id)
    {
        try {
            $categoria = Categoria::findOrFail($id);
            return response()->json($categoria);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Categoría no encontrada'], 404);
        }
    }

    /**
     * Actualiza una categoría existente en la base de datos.
     */
    public function update(Request $request, $id)
    {
        // Validar los datos recibidos
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:categorias,nombre,' . $id,
            'descripcion' => 'nullable|string|max:1000',
        ]);

        try {
            $categoria = Categoria::findOrFail($id);
            $categoria->update($validated);

            // Devolver la categoría actualizada como respuesta JSON
            return response()->json($categoria);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar la categoría'], 500);
        }
    }

    /**
     * Elimina una categoría de la base de datos.
     */
    public function destroy($id)
    {
        try {
            $categoria = Categoria::findOrFail($id);

            // Verificar si tiene productos relacionados antes de eliminar
            if ($categoria->productos()->exists()) {
                return response()->json(['error' => 'No se puede eliminar la categoría porque tiene productos asociados'], 400);
            }

            $categoria->delete();

            return response()->json(['message' => 'Categoría eliminada correctamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar la categoría'], 500);
        }
    }
}

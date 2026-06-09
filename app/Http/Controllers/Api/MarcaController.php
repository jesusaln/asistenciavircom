<?php

namespace App\Http\Controllers\Api;


use App\Models\Marca;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MarcaController extends Controller
{
    /**
     * Muestra una lista de todas las marcas en formato JSON.
     */
    public function index()
    {
        try {
            $marcas = Marca::all();
            return response()->json($marcas);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener las marcas'], 500);
        }
    }

    /**
     * Almacena una nueva marca en la base de datos.
     */
    public function store(Request $request)
    {
        // Validar los datos recibidos
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:marcas,nombre',
            'descripcion' => 'nullable|string|max:1000',
        ]);

        try {
            // Crear la marca
            $marca = Marca::create($validated);

            // Devolver la marca creada como respuesta JSON
            return response()->json($marca, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al crear la marca'], 500);
        }
    }

    /**
     * Muestra una marca específica en formato JSON.
     */
    public function show($id)
    {
        try {
            $marca = Marca::findOrFail($id);
            return response()->json($marca);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Marca no encontrada'], 404);
        }
    }

    /**
     * Actualiza una marca existente en la base de datos.
     */
    public function update(Request $request, $id)
    {
        // Validar los datos recibidos
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:marcas,nombre,' . $id,
            'descripcion' => 'nullable|string|max:1000',
        ]);

        try {
            $marca = Marca::findOrFail($id);
            $marca->update($validated);

            // Devolver la marca actualizada como respuesta JSON
            return response()->json($marca);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar la marca'], 500);
        }
    }

    /**
     * Elimina una marca de la base de datos.
     */
    public function destroy($id)
    {
        try {
            $marca = Marca::findOrFail($id);

            // Verificar si tiene productos relacionados antes de eliminar
            if ($marca->productos()->exists()) {
                return response()->json(['error' => 'No se puede eliminar la marca porque tiene productos asociados'], 400);
            }

            $marca->delete();

            return response()->json(['message' => 'Marca eliminada correctamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar la marca'], 500);
        }
    }
}

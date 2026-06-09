<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Almacen;
use App\Http\Controllers\Controller;

class AlmacenController extends Controller
{
    /**
     * Muestra una lista de todos los almacenes en formato JSON.
     */
    public function index()
    {
        try {
            $almacenes = Almacen::all();
            return response()->json([
                'success' => true,
                'data' => $almacenes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los almacenes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Almacena un nuevo almacén en la base de datos.
     */
    public function store(Request $request)
    {
        // Validar los datos recibidos
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:almacenes,nombre',
            'descripcion' => 'nullable|string|max:1000',
            'ubicacion' => 'required|string|max:255',
        ]);

        try {
            $almacen = Almacen::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Almacén creado correctamente',
                'data' => $almacen
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el almacén: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Muestra un almacén específico en formato JSON.
     */
    public function show($id)
    {
        try {
            $almacen = Almacen::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $almacen
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Almacén no encontrado.'
            ], 404);
        }
    }

    /**
     * Actualiza un almacén existente en la base de datos.
     */
    public function update(Request $request, $id)
    {
        // Validar los datos recibidos
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:almacenes,nombre,' . $id,
            'descripcion' => 'nullable|string|max:1000',
            'ubicacion' => 'required|string|max:255',
        ]);

        try {
            $almacen = Almacen::findOrFail($id);
            $almacen->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Almacén actualizado correctamente',
                'data' => $almacen
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el almacén: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Elimina un almacén de la base de datos.
     */
    public function destroy($id)
    {
        try {
            $almacen = Almacen::findOrFail($id);

            if ($almacen->productos()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el almacén porque tiene productos asociados'
                ], 400);
            }

            $almacen->delete();

            return response()->json([
                'success' => true,
                'message' => 'Almacén eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el almacén: ' . $e->getMessage()
            ], 500);
        }
    }
}

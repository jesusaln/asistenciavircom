<?php

namespace App\Http\Controllers\Api;

use App\Models\Servicio;
use App\Models\Categoria;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ServicioController extends Controller
{
    /**
     * Muestra una lista de todos los servicios en formato JSON.
     */
    public function index()
    {
        try {
            $servicios = Servicio::with('categoria')->get();
            return response()->json([
                'success' => true,
                'data' => $servicios
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los servicios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Muestra los detalles de un servicio específico.
     */
    public function show($id)
    {
        try {
            $servicio = Servicio::with('categoria')->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $servicio
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Servicio no encontrado.'
            ], 404);
        }
    }

    /**
     * Crea un nuevo servicio.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'codigo' => 'required|string|unique:servicios,codigo',
                'categoria_id' => 'nullable|exists:categorias,id',
                'precio' => 'required|numeric|min:0',
                'duracion' => 'required|integer|min:0',
                'estado' => 'required|in:activo,inactivo',
                'es_instalacion' => 'nullable|boolean',
                'comision_vendedor' => 'nullable|numeric|min:0',
                'margen_ganancia' => 'nullable|numeric|min:0',
            ]);

            // Asegurar valores por defecto
            $validated['comision_vendedor'] = $validated['comision_vendedor'] ?? 0;
            $validated['margen_ganancia'] = $validated['margen_ganancia'] ?? 0;
            $validated['es_instalacion'] = $validated['es_instalacion'] ?? false;

            $servicio = Servicio::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Servicio creado correctamente',
                'data' => $servicio->load('categoria')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el servicio: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Actualiza un servicio existente.
     */
    public function update(Request $request, $id)
    {
        try {
            $servicio = Servicio::findOrFail($id);

            $validated = $request->validate([
                'nombre' => 'sometimes|required|string|max:255',
                'descripcion' => 'nullable|string',
                'codigo' => 'sometimes|required|string|unique:servicios,codigo,' . $servicio->id,
                'categoria_id' => 'sometimes|required|exists:categorias,id',
                'precio' => 'sometimes|required|numeric|min:0',
                'duracion' => 'sometimes|required|integer|min:0',
                'estado' => 'sometimes|required|in:activo,inactivo',
                'es_instalacion' => 'nullable|boolean',
                'comision_vendedor' => 'nullable|numeric|min:0',
                'margen_ganancia' => 'nullable|numeric|min:0',
            ]);

            // Asegurar valores por defecto si se envían como null
            if (array_key_exists('comision_vendedor', $validated) && $validated['comision_vendedor'] === null) {
                $validated['comision_vendedor'] = 0;
            }

            $servicio->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Servicio actualizado correctamente',
                'data' => $servicio->load('categoria')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el servicio: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Elimina un servicio.
     */
    public function destroy($id)
    {
        try {
            $servicio = Servicio::findOrFail($id);

            // ✅ FIX: Validar antes de eliminar
            if ($servicio->estado === 'activo') {
                return response()->json(['error' => 'No se puede eliminar un servicio activo. Desactívelo primero.'], 422);
            }

            // Verificar documentos asociados
            if (
                $servicio->cotizaciones()->count() > 0 ||
                $servicio->pedidos()->count() > 0 ||
                $servicio->ventas()->count() > 0 ||
                $servicio->citas()->count() > 0
            ) {
                return response()->json(['error' => 'No se puede eliminar el servicio porque tiene documentos asociados.'], 422);
            }

            $servicio->delete();

            return response()->json([
                'success' => true,
                'message' => 'Servicio eliminado correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el servicio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene las categorías disponibles (opcional, para formularios API).
     */
    public function getCategorias()
    {
        try {
            $categorias = Categoria::select('id', 'nombre')->get();
            return response()->json($categorias->toArray(), 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener las categorías: ' . $e->getMessage()], 500);
        }
    }
}

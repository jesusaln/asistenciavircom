<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UnidadMedida;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UnidadMedidaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = UnidadMedida::query();

            // Filtro por estado
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            // Filtro por búsqueda
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('abreviatura', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%");
                });
            }

            // Paginación
            $perPage = min($request->input('per_page', 15), 100);
            $unidades = $query->orderBy('nombre')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $unidades->items(),
                'pagination' => [
                    'current_page' => $unidades->currentPage(),
                    'per_page' => $unidades->perPage(),
                    'total' => $unidades->total(),
                    'last_page' => $unidades->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error en UnidadMedidaController@index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las unidades de medida'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255|unique:unidades_medida,nombre',
                'abreviatura' => 'nullable|string|max:50|unique:unidades_medida,abreviatura',
                'descripcion' => 'nullable|string',
                'estado' => 'in:activo,inactivo',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $unidad = UnidadMedida::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Unidad de medida creada correctamente',
                'data' => $unidad
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error en UnidadMedidaController@store: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la unidad de medida'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(UnidadMedida $unidadMedida): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $unidadMedida
            ]);
        } catch (\Exception $e) {
            Log::error('Error en UnidadMedidaController@show: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la unidad de medida'
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UnidadMedida $unidadMedida): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255|unique:unidades_medida,nombre,' . $unidadMedida->id,
                'abreviatura' => 'nullable|string|max:50|unique:unidades_medida,abreviatura,' . $unidadMedida->id,
                'descripcion' => 'nullable|string',
                'estado' => 'in:activo,inactivo',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $unidadMedida->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Unidad de medida actualizada correctamente',
                'data' => $unidadMedida->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Error en UnidadMedidaController@update: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la unidad de medida'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UnidadMedida $unidadMedida): JsonResponse
    {
        try {
            // Verificar si puede ser eliminada
            if (!$unidadMedida->puedeSerEliminada()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la unidad de medida porque está siendo utilizada por productos existentes'
                ], 422);
            }

            $unidadMedida->delete();

            return response()->json([
                'success' => true,
                'message' => 'Unidad de medida eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error en UnidadMedidaController@destroy: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la unidad de medida'
            ], 500);
        }
    }

    /**
     * Obtener unidades activas para select
     */
    public function getActiveUnits(): JsonResponse
    {
        try {
            $unidades = UnidadMedida::activas()
                ->select('id', 'nombre', 'abreviatura')
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $unidades
            ]);
        } catch (\Exception $e) {
            Log::error('Error en UnidadMedidaController@getActiveUnits: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las unidades activas'
            ], 500);
        }
    }
}

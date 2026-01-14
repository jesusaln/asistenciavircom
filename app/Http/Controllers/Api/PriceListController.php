<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PriceList;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class PriceListController extends Controller
{
    /**
     * Obtener todas las listas de precios activas
     */
    public function index(): JsonResponse
    {
        try {
            $priceLists = PriceList::activas()
                ->get(['id', 'nombre', 'clave', 'descripcion', 'activa', 'orden']);

            return response()->json($priceLists);
        } catch (Exception $e) {
            Log::error('Error al obtener listas de precios: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las listas de precios.'
            ], 500);
        }
    }

    /**
     * Obtener una lista de precios específica
     */
    public function show($id): JsonResponse
    {
        try {
            $priceList = PriceList::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $priceList
            ]);
        } catch (Exception $e) {
            Log::error('Error al mostrar lista de precios: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lista de precios no encontrada.'
            ], 404);
        }
    }

    /**
     * Obtener todas las listas (incluyendo inactivas) para administración
     */
    public function all(): JsonResponse
    {
        try {
            $priceLists = PriceList::orderBy('orden')
                ->get(['id', 'nombre', 'clave', 'descripcion', 'activa', 'orden']);

            return response()->json([
                'success' => true,
                'data' => $priceLists
            ]);
        } catch (Exception $e) {
            Log::error('Error al obtener todas las listas de precios: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las listas de precios.'
            ], 500);
        }
    }
}

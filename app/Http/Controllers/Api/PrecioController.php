<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Servicio;
use App\Services\PrecioService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PrecioController extends Controller
{
    public function __construct(
        private readonly PrecioService $precioService
    ) {}

    /**
     * Recalcular precios para múltiples productos/servicios según lista de precios
     */
    public function recalcular(Request $request): JsonResponse
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'price_list_id' => 'nullable|exists:price_lists,id',
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|integer',
            'productos.*.tipo' => 'required|in:producto,servicio',
        ]);

        try {
            $cliente = Cliente::findOrFail($request->cliente_id);
            $priceList = $request->price_list_id ? \App\Models\PriceList::find($request->price_list_id) : null;

            $precios = [];

            foreach ($request->productos as $productoData) {
                $class = $productoData['tipo'] === 'producto' ? Producto::class : Servicio::class;
                $modelo = $class::find($productoData['id']);

                if (!$modelo) {
                    continue;
                }

                if ($productoData['tipo'] === 'producto') {
                    $detallesPrecio = $this->precioService->resolverPrecioConDetalles(
                        $modelo,
                        $cliente,
                        $priceList
                    );
                    $precio = $detallesPrecio['precio'];
                } else {
                    // Para servicios, usar precio base (no tienen listas de precios)
                    $precio = $modelo->precio ?? 0;
                }

                $key = $productoData['tipo'] . '-' . $productoData['id'];
                $precios[$key] = round($precio, 2);
            }

            return response()->json([
                'success' => true,
                'precios' => $precios
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al recalcular precios: ' . $e->getMessage()
            ], 500);
        }
    }
}

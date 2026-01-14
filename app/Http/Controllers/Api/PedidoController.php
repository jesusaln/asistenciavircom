<?php

namespace App\Http\Controllers\Api;

use App\Models\Pedido;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PedidoController extends Controller
{
    /**
     * Muestra una lista de todos los pedidos en formato JSON.
     */
    public function index()
    {
        try {
            $pedidos = Pedido::with(['cliente', 'productos', 'servicios'])->get()->map(function ($pedido) {
                $items = collect($pedido->productos->map(function ($producto) {
                    return [
                        'id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'tipo' => 'producto',
                        'cantidad' => $producto->pivot->cantidad,
                        'precio' => $producto->pivot->precio,
                    ];
                }))->merge(collect($pedido->servicios->map(function ($servicio) {
                    return [
                        'id' => $servicio->id,
                        'nombre' => $servicio->nombre,
                        'tipo' => 'servicio',
                        'cantidad' => $servicio->pivot->cantidad,
                        'precio' => $servicio->pivot->precio,
                    ];
                })));

                return [
                    'id' => $pedido->id,
                    'cliente' => $pedido->cliente,
                    'items' => $items,
                    'total' => $pedido->total,
                ];
            });

            return response()->json($pedidos, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener los pedidos: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Muestra los detalles de un pedido específico.
     */
    public function show($id)
    {
        try {
            $pedido = Pedido::with(['cliente', 'productos', 'servicios'])->findOrFail($id);

            // Asegúrate de que productos y servicios sean colecciones
            $items = collect($pedido->productos)->map(function ($producto) {
                return [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'tipo' => 'producto',
                    'cantidad' => $producto->pivot->cantidad,
                    'precio' => $producto->pivot->precio,
                ];
            })->merge(collect($pedido->servicios)->map(function ($servicio) {
                return [
                    'id' => $servicio->id,
                    'nombre' => $servicio->nombre,
                    'tipo' => 'servicio',
                    'cantidad' => $servicio->pivot->cantidad,
                    'precio' => $servicio->pivot->precio,
                ];
            }));

            return response()->json([
                'id' => $pedido->id,
                'cliente' => $pedido->cliente,
                'items' => $items,
                'total' => $pedido->total,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener la cotización: ' . $e->getMessage()], 404);
        }
    }


    /**
     * Crea un nuevo pedido.
     */
    public function store(Request $request)
    {
        try {
            // Normalizar 'productos' a 'items' si es necesario
            if ($request->has('productos') && !$request->has('items')) {
                $request->merge(['items' => $request->productos]);
            }

            // Validar los datos de entrada
            $validatedData = $request->validate([
                'cliente_id' => 'required|exists:clientes,id',
                'items' => 'required|array|min:1',
                'items.*.id' => 'required|integer',
                'items.*.tipo' => 'required|in:producto,servicio',
                'items.*.cantidad' => 'required|numeric|min:0.01',
                'items.*.precio' => 'required|numeric|min:0',
                'items.*.descuento' => 'nullable|numeric|min:0|max:100',
                'descuento_general' => 'nullable|numeric|min:0|max:100',
                'notas' => 'nullable|string',
                'aplicar_retencion_iva' => 'nullable|boolean',
                'aplicar_retencion_isr' => 'nullable|boolean',
            ]);

            $subtotal = 0;
            $itemsParaCalculo = [];

            foreach ($validatedData['items'] as $item) {
                $cantidad = (float) $item['cantidad'];
                $precio = (float) $item['precio'];
                $descuentoProg = (float) ($item['descuento'] ?? 0);

                $montoItem = $cantidad * $precio;
                $descuentoMonto = $montoItem * ($descuentoProg / 100);

                $subtotalItem = $montoItem - $descuentoMonto;
                $subtotal += $subtotalItem;

                $itemsParaCalculo[] = [
                    'id' => $item['id'],
                    'tipo' => $item['tipo'],
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'descuento' => $descuentoProg,
                    'subtotal' => $subtotalItem
                ];
            }

            $iva = $subtotal * 0.16;
            $total = $subtotal + $iva;

            $pedido = Pedido::create([
                'cliente_id' => $validatedData['cliente_id'],
                'subtotal' => $subtotal,
                'iva' => $iva,
                'total' => $total,
                'descuento_general' => $request->descuento_general ?? 0,
                'observaciones' => $request->notas ?? $request->observaciones,
                'estado' => 'pendiente',
            ]);

            // Asociar productos y servicios
            foreach ($itemsParaCalculo as $item) {
                if ($item['tipo'] === 'producto') {
                    $pedido->productos()->attach($item['id'], [
                        'cantidad' => $item['cantidad'],
                        'precio' => $item['precio'],
                        'descuento' => $item['descuento'],
                        'subtotal' => $item['subtotal']
                    ]);
                } else {
                    $pedido->servicios()->attach($item['id'], [
                        'cantidad' => $item['cantidad'],
                        'precio' => $item['precio'],
                        'descuento' => $item['descuento'],
                        'subtotal' => $item['subtotal']
                    ]);
                }
            }

            return response()->json($pedido->load(['cliente', 'productos', 'servicios']), 201);
        } catch (\Exception $e) {
            Log::error('Error API Pedido Store: ' . $e->getMessage());
            return response()->json(['error' => 'Error al crear el pedido: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Actualiza un pedido existente.
     */
    public function update(Request $request, $id)
    {
        try {
            $pedido = Pedido::findOrFail($id);

            // Normalizar 'productos' a 'items' si es necesario
            if ($request->has('productos') && !$request->has('items')) {
                $request->merge(['items' => $request->productos]);
            }

            // Validar los datos de entrada
            $validatedData = $request->validate([
                'cliente_id' => 'sometimes|exists:clientes,id',
                'items' => 'sometimes|array',
                'items.*.id' => 'required_with:items|integer',
                'items.*.tipo' => 'required_with:items|in:producto,servicio',
                'items.*.cantidad' => 'required_with:items|numeric|min:0.01',
                'items.*.precio' => 'required_with:items|numeric|min:0',
                'items.*.descuento' => 'nullable|numeric|min:0|max:100',
                'descuento_general' => 'nullable|numeric|min:0|max:100',
                'notas' => 'nullable|string',
                'observaciones' => 'nullable|string',
            ]);

            DB::beginTransaction();

            if (isset($validatedData['items'])) {
                $subtotal = 0;

                // Limpiar items actuales
                $pedido->productos()->detach();
                $pedido->servicios()->detach();

                foreach ($validatedData['items'] as $item) {
                    $cantidad = (float) $item['cantidad'];
                    $precio = (float) $item['precio'];
                    $descuentoProg = (float) ($item['descuento'] ?? 0);

                    $montoItem = $cantidad * $precio;
                    $descuentoMonto = $montoItem * ($descuentoProg / 100);

                    $subitemTotal = $montoItem - $descuentoMonto;
                    $subtotal += $subitemTotal;

                    if ($item['tipo'] === 'producto') {
                        $pedido->productos()->attach($item['id'], [
                            'cantidad' => $cantidad,
                            'precio' => $precio,
                            'descuento' => $descuentoProg,
                            'subtotal' => $subitemTotal
                        ]);
                    } else {
                        $pedido->servicios()->attach($item['id'], [
                            'cantidad' => $cantidad,
                            'precio' => $precio,
                            'descuento' => $descuentoProg,
                            'subtotal' => $subitemTotal
                        ]);
                    }
                }

                $iva = $subtotal * 0.16;
                $total = $subtotal + $iva;

                $pedido->update([
                    'subtotal' => $subtotal,
                    'iva' => $iva,
                    'total' => $total
                ]);
            }

            if (isset($validatedData['cliente_id'])) {
                $pedido->update(['cliente_id' => $validatedData['cliente_id']]);
            }
            if (isset($validatedData['notas']) || isset($validatedData['observaciones'])) {
                $pedido->update(['observaciones' => $validatedData['notas'] ?? $validatedData['observaciones']]);
            }
            if (isset($validatedData['descuento_general'])) {
                $pedido->update(['descuento_general' => $validatedData['descuento_general']]);
            }

            DB::commit();

            return response()->json($pedido->load(['cliente', 'productos', 'servicios']), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error API Pedido Update: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar el pedido: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Elimina un pedido.
     */
    public function destroy($id)
    {
        try {
            $pedido = Pedido::findOrFail($id);
            $pedido->productos()->detach();
            $pedido->servicios()->detach();
            $pedido->delete();

            return response()->json(['message' => 'Pedido eliminado con éxito'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar el pedido: ' . $e->getMessage()], 500);
        }
    }
}

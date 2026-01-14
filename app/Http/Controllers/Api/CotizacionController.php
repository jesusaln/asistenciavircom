<?php

namespace App\Http\Controllers\Api;

use App\Models\Cotizacion;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CotizacionController extends Controller
{
    /**
     * Muestra una lista de todas las cotizaciones en formato JSON.
     */
    /**
     * Muestra una lista de todas las cotizaciones en formato JSON.
     */
    public function index(Request $request)
    {
        try {
            $query = Cotizacion::with(['cliente', 'items.cotizable'])->orderBy('created_at', 'desc');

            // Paginación simple
            $cotizaciones = $query->paginate(20);

            $data = $cotizaciones->getCollection()->map(function ($cotizacion) {
                $items = $cotizacion->items->map(function ($item) {
                    $cotizable = $item->cotizable;
                    return [
                        'id' => $cotizable ? $cotizable->id : $item->cotizable_id,
                        'nombre' => $cotizable ? ($cotizable->nombre ?? $cotizable->descripcion) : 'Ítem no encontrado',
                        'tipo' => $item->cotizable_type === \App\Models\Producto::class ? 'producto' : 'servicio',
                        'cantidad' => $item->cantidad,
                        'precio' => $item->precio,
                    ];
                });

                return [
                    'id' => $cotizacion->id,
                    'numero_cotizacion' => $cotizacion->numero_cotizacion,
                    'cliente' => $cotizacion->cliente,
                    'items' => $items,
                    'subtotal' => $cotizacion->subtotal,
                    'iva' => $cotizacion->iva,
                    'total' => $cotizacion->total,
                    'estado' => $cotizacion->estado,
                    'fecha' => $cotizacion->created_at->format('Y-m-d'),
                ];
            });

            // Mantener estructura de paginación
            $paginated = $cotizaciones->toArray();
            $paginated['data'] = $data;

            return response()->json($paginated, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener las cotizaciones: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Muestra los detalles de una cotización específica.
     */
    public function show($id)
    {
        try {
            $cotizacion = Cotizacion::with(['cliente', 'productos', 'servicios'])->findOrFail($id);
            $items = $cotizacion->productos->map(function ($producto) {
                return [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'tipo' => 'producto',
                    'cantidad' => $producto->pivot->cantidad,
                    'precio' => $producto->pivot->precio,
                ];
            })->merge($cotizacion->servicios->map(function ($servicio) {
                return [
                    'id' => $servicio->id,
                    'nombre' => $servicio->nombre,
                    'tipo' => 'servicio',
                    'cantidad' => $servicio->pivot->cantidad,
                    'precio' => $servicio->pivot->precio,
                ];
            }));

            return response()->json([
                'id' => $cotizacion->id,
                'cliente' => $cotizacion->cliente,
                'items' => $items,
                'total' => $cotizacion->total,
                'fecha' => $cotizacion->created_at->format('Y-m-d'), // Incluir la fecha de creación
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener la cotización: ' . $e->getMessage()], 404);
        }
    }

    /**
     * Crea una nueva cotización.
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
            $descuentoItems = 0;
            $itemsParaCalculo = [];

            foreach ($validatedData['items'] as $item) {
                $cantidad = (float) $item['cantidad'];
                $precio = (float) $item['precio'];
                $descuentoProg = (float) ($item['descuento'] ?? 0);

                $montoItem = $cantidad * $precio;
                $descuentoMonto = $montoItem * ($descuentoProg / 100);

                $subtotal += ($montoItem - $descuentoMonto);
                $descuentoItems += $descuentoMonto;

                $itemsParaCalculo[] = [
                    'id' => $item['id'],
                    'tipo' => $item['tipo'],
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'descuento' => $descuentoProg,
                    'descuento_monto' => $descuentoMonto,
                    'subtotal' => $montoItem - $descuentoMonto
                ];
            }

            // Cálculo de IVA (asumiendo 16% por ahora o usando el servicio si prefieres)
            // Para simplicidad y siguiendo "como en ventas" (que usa 16% fijo en el front)
            $iva = $subtotal * 0.16;
            $total = $subtotal + $iva;

            // Crear la cotización con todos los campos
            $cotizacion = Cotizacion::create([
                'cliente_id' => $validatedData['cliente_id'],
                'subtotal' => $subtotal,
                'iva' => $iva,
                'total' => $total,
                'descuento_items' => $descuentoItems,
                'descuento_general' => $request->descuento_general ?? 0,
                'notas' => $request->notas,
                'estado' => 'pendiente',
            ]);

            // Asociar productos y servicios usando la tabla cotizacion_items (relaciones Morph)
            foreach ($itemsParaCalculo as $item) {
                $cotizacion->items()->create([
                    'cotizable_id' => $item['id'],
                    'cotizable_type' => $item['tipo'] === 'producto' ? \App\Models\Producto::class : \App\Models\Servicio::class,
                    'cantidad' => $item['cantidad'],
                    'precio' => $item['precio'],
                    'descuento' => $item['descuento'],
                    'descuento_monto' => $item['descuento_monto'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            return response()->json($cotizacion->load(['cliente', 'items.cotizable']), 201);
        } catch (\Exception $e) {
            Log::error('Error API Cotizacion Store: ' . $e->getMessage());
            return response()->json(['error' => 'Error al crear la cotización: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Actualiza una cotización existente.
     */
    public function update(Request $request, $id)
    {
        try {
            $cotizacion = Cotizacion::findOrFail($id);

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
            ]);

            DB::beginTransaction();

            if (isset($validatedData['items'])) {
                $subtotal = 0;
                $descuentoItems = 0;

                // Eliminar items actuales para recrearlos (o podrías usar sync si prefieres)
                $cotizacion->items()->delete();

                foreach ($validatedData['items'] as $item) {
                    $cantidad = (float) $item['cantidad'];
                    $precio = (float) $item['precio'];
                    $descuentoProg = (float) ($item['descuento'] ?? 0);

                    $montoItem = $cantidad * $precio;
                    $descuentoMonto = $montoItem * ($descuentoProg / 100);

                    $subitemTotal = $montoItem - $descuentoMonto;
                    $subtotal += $subitemTotal;
                    $descuentoItems += $descuentoMonto;

                    $cotizacion->items()->create([
                        'cotizable_id' => $item['id'],
                        'cotizable_type' => $item['tipo'] === 'producto' ? \App\Models\Producto::class : \App\Models\Servicio::class,
                        'cantidad' => $cantidad,
                        'precio' => $precio,
                        'descuento' => $descuentoProg,
                        'descuento_monto' => $descuentoMonto,
                        'subtotal' => $subitemTotal,
                    ]);
                }

                $iva = $subtotal * 0.16;
                $total = $subtotal + $iva;

                $cotizacion->update([
                    'subtotal' => $subtotal,
                    'iva' => $iva,
                    'total' => $total,
                    'descuento_items' => $descuentoItems
                ]);
            }

            if (isset($validatedData['cliente_id'])) {
                $cotizacion->update(['cliente_id' => $validatedData['cliente_id']]);
            }
            if (isset($validatedData['notas'])) {
                $cotizacion->update(['notas' => $validatedData['notas']]);
            }
            if (isset($validatedData['descuento_general'])) {
                $cotizacion->update(['descuento_general' => $validatedData['descuento_general']]);
            }

            DB::commit();

            return response()->json($cotizacion->load(['cliente', 'items.cotizable']), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error API Cotizacion Update: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar la cotización: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Elimina una cotización.
     */
    public function destroy($id)
    {
        try {
            $cotizacion = Cotizacion::findOrFail($id);
            $cotizacion->productos()->detach();
            $cotizacion->servicios()->detach();
            $cotizacion->delete();

            return response()->json(['message' => 'Cotización eliminada con éxito'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar la cotización: ' . $e->getMessage()], 500);
        }
    }
}

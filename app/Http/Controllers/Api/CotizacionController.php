<?php

namespace App\Http\Controllers\Api;

use App\Models\Cotizacion;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CotizacionController extends Controller
{
    use ApiResponse;

    /**
     * Lista paginada (misma forma que {@see \App\Http\Controllers\Api\VentaController::index} para la app móvil).
     */
    public function index(Request $request)
    {
        try {
            $query = Cotizacion::with(['cliente', 'createdBy'])->orderByDesc('created_at');

            $clienteQ = $request->query('cliente');
            if ($clienteQ) {
                $query->whereHas('cliente', function ($q) use ($clienteQ) {
                    $q->where('nombre_razon_social', 'LIKE', '%'.$clienteQ.'%');
                });
            }

            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
                $tz = config('app.timezone');
                $desde = Carbon::createFromFormat('Y-m-d', $request->fecha_desde, $tz)->startOfDay();
                $hasta = Carbon::createFromFormat('Y-m-d', $request->fecha_hasta, $tz)->endOfDay();
                $query->whereBetween('created_at', [$desde, $hasta]);
            } else {
                if ($request->filled('fecha_desde')) {
                    $query->whereDate('created_at', '>=', $request->fecha_desde);
                }
                if ($request->filled('fecha_hasta')) {
                    $query->whereDate('created_at', '<=', $request->fecha_hasta);
                }
            }

            if ($request->boolean('mis_cotizaciones')) {
                $auth = $request->user();
                if ($auth) {
                    $query->where('created_by', (int) $auth->id);
                }
            } elseif ($request->filled('vendedor_id')) {
                $query->where('created_by', (int) $request->vendedor_id);
            }

            $perPage = (int) $request->query('per_page', 15);
            $paginator = $query->paginate(max(1, $perPage));

            $items = collect($paginator->items())->map(function (Cotizacion $cotizacion) {
                $estadoVal = $cotizacion->estado;
                if ($estadoVal instanceof \BackedEnum) {
                    $estadoVal = $estadoVal->value;
                }

                $vendedor = null;
                if ($cotizacion->createdBy) {
                    $vendedor = ['nombre' => $cotizacion->createdBy->name];
                }

                return [
                    'id' => $cotizacion->id,
                    'numero_cotizacion' => $cotizacion->numero_cotizacion,
                    'created_at' => $cotizacion->created_at,
                    'cliente' => $cotizacion->cliente,
                    'subtotal' => $cotizacion->subtotal,
                    'iva' => $cotizacion->iva,
                    'total' => $cotizacion->total,
                    'estado' => $estadoVal,
                    'fecha' => $cotizacion->created_at->format('Y-m-d'),
                    'vendedor' => $vendedor,
                    'sharing_token' => $cotizacion->sharing_token,
                ];
            });

            return $this->success([
                'items' => $items->values(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CotizacionController@index: '.$e->getMessage());

            return response()->json(['error' => 'Error al obtener las cotizaciones: '.$e->getMessage()], 500);
        }
    }


    /**
     * Muestra los detalles de una cotización específica.
     */
    public function show($id)
    {
        try {
            $cotizacion = Cotizacion::with(['cliente', 'items.cotizable', 'createdBy'])->findOrFail($id);

            $items = $cotizacion->items->map(function ($item) {
                $cotizable = $item->cotizable;
                $nombre = $cotizable ? ($cotizable->nombre ?? $cotizable->descripcion) : 'Ítem';

                return [
                    'id' => $cotizable ? $cotizable->id : $item->cotizable_id,
                    'nombre' => $nombre,
                    'tipo' => $item->cotizable_type === \App\Models\Producto::class ? 'producto' : 'servicio',
                    'cantidad' => $item->cantidad,
                    'precio' => (float) $item->precio,
                    'descuento' => (float) ($item->descuento ?? 0),
                ];
            });

            $estadoVal = $cotizacion->estado;
            if ($estadoVal instanceof \BackedEnum) {
                $estadoVal = $estadoVal->value;
            }

            $vendedor = $cotizacion->createdBy
                ? ['nombre' => $cotizacion->createdBy->name]
                : null;

            $payload = [
                'id' => $cotizacion->id,
                'numero_cotizacion' => $cotizacion->numero_cotizacion,
                'numero_venta' => $cotizacion->numero_cotizacion,
                'created_at' => $cotizacion->created_at,
                'cliente' => $cotizacion->cliente,
                'items' => $items,
                'subtotal' => (float) $cotizacion->subtotal,
                'descuento_general' => (float) ($cotizacion->descuento_general ?? 0),
                'iva' => (float) $cotizacion->iva,
                'total' => (float) $cotizacion->total,
                'notas' => $cotizacion->notas,
                'estado' => $estadoVal,
                'fecha' => $cotizacion->created_at->format('Y-m-d'),
                'moneda' => 'MXN',
                'vendedor' => $vendedor,
                'sharing_token' => $cotizacion->sharing_token,
            ];

            return $this->success($payload);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener la cotización: '.$e->getMessage()], 404);
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
            $iva = $subtotal * (\App\Services\EmpresaConfiguracionService::getIvaPorcentaje() / 100);
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

                $iva = $subtotal * (\App\Services\EmpresaConfiguracionService::getIvaPorcentaje() / 100);
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
    public function destroy(Cotizacion $cotizacion)
    {
        try {
            $cotizacion->items()->delete();
            $cotizacion->delete();

            return response()->json(['message' => 'Cotización eliminada con éxito'], 200);
        } catch (\Exception $e) {
            Log::error('CotizacionController@destroy: '.$e->getMessage());

            return response()->json(['error' => 'Error al eliminar la cotización: '.$e->getMessage()], 500);
        }
    }
}

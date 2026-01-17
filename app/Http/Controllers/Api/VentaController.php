<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Services\InventarioService;
use App\Services\Ventas\VentaCancellationService;
use App\Services\Ventas\VentaUpdateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class VentaController extends Controller
{
    public function __construct(
        private readonly InventarioService $inventarioService,
        private readonly \App\Services\Ventas\VentaCancellationService $cancellationService,
        private readonly \App\Services\Ventas\VentaUpdateService $updateService
    ) {
    }

    /**
     * Muestra una lista de todas las ventas en formato JSON.
     */
    public function index(Request $request)
    {
        try {
            $nombreCliente = $request->query('cliente');
            $limit = $request->query('limit', 15);
            $perPage = $request->query('per_page', $limit);

            $query = Venta::with(['cliente', 'productos', 'servicios']);

            if ($nombreCliente) {
                $query->whereHas('cliente', function ($subQuery) use ($nombreCliente) {
                    $subQuery->where('nombre_razon_social', 'LIKE', '%' . $nombreCliente . '%');
                });
            }

            // Filtros de fecha
            if ($request->filled('fecha_desde')) {
                $query->whereDate('created_at', '>=', $request->fecha_desde);
            }
            if ($request->filled('fecha_hasta')) {
                $query->whereDate('created_at', '<=', $request->fecha_hasta);
            }

            $paginator = $query->orderByDesc('created_at')->paginate((int) $perPage);

            $ventasTransformadas = collect($paginator->items())->map(function ($venta) {
                $items = collect($venta->productos->map(function ($producto) {
                    return [
                        'id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'tipo' => 'producto',
                        'cantidad' => $producto->pivot->cantidad,
                        'precio' => $producto->pivot->precio,
                    ];
                }))->merge(collect($venta->servicios->map(function ($servicio) {
                    return [
                        'id' => $servicio->id,
                        'nombre' => $servicio->nombre,
                        'tipo' => 'servicio',
                        'cantidad' => $servicio->pivot->cantidad,
                        'precio' => $servicio->pivot->precio,
                    ];
                })));

                return [
                    'id' => $venta->id,
                    'numero_venta' => $venta->numero_venta,
                    'created_at' => $venta->created_at,
                    'cliente' => $venta->cliente,
                    'items' => $items,
                    'total' => $venta->total,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $ventasTransformadas,
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error en VentaController@index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las ventas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pre-validate a sale before processing payment.
     * Checks stock availability and series status without creating the sale.
     */
    public function validateSale(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'cliente_id' => 'required|exists:clientes,id',
                'almacen_id' => 'nullable|integer|exists:almacenes,id,estado,activo',
                'items' => 'required|array',
                'items.*.id' => 'required|integer',
                'items.*.tipo' => 'required|in:producto,servicio',
                'items.*.cantidad' => 'required|integer|min:1',
                'items.*.series' => 'nullable|array',
                'metodo_pago' => 'nullable|string',
            ]);

            $errors = [];
            $warnings = [];
            $almacenId = $validatedData['almacen_id'] ?? auth()->user()->almacen_venta_id;

            // Validate each product
            foreach ($validatedData['items'] as $item) {
                if ($item['tipo'] !== 'producto')
                    continue;

                $producto = \App\Models\Producto::find($item['id']);
                if (!$producto) {
                    $errors[] = "Producto ID {$item['id']} no encontrado.";
                    continue;
                }

                // Check stock in the specified warehouse
                $stockDisponible = $producto->getStockEnAlmacen($almacenId);
                if ($stockDisponible < $item['cantidad']) {
                    $errors[] = "Stock insuficiente para '{$producto->nombre}'. Disponible: {$stockDisponible}, Solicitado: {$item['cantidad']}.";
                }

                // Validate series if provided
                if (!empty($item['series'])) {
                    foreach ($item['series'] as $numeroSerie) {
                        $serie = \App\Models\ProductoSerie::where('numero_serie', $numeroSerie)
                            ->where('producto_id', $producto->id)
                            ->first();

                        if (!$serie) {
                            $errors[] = "Serie '{$numeroSerie}' no encontrada para '{$producto->nombre}'.";
                        } elseif ($serie->estado !== 'en_stock') {
                            $errors[] = "Serie '{$numeroSerie}' no está disponible (estado: {$serie->estado}).";
                        } elseif ($serie->almacen_id != $almacenId) {
                            $almacenSerie = \App\Models\Almacen::find($serie->almacen_id);
                            $errors[] = "Serie '{$numeroSerie}' está en almacén '{$almacenSerie->nombre}', no en el almacén de venta.";
                        }
                    }
                }
            }

            // Validate credit limit if payment method is 'credito'
            if (($validatedData['metodo_pago'] ?? '') === 'credito') {
                $cliente = \App\Models\Cliente::find($validatedData['cliente_id']);

                if (!$cliente->credito_activo) {
                    $errors[] = "El cliente '{$cliente->nombre_razon_social}' no tiene el crédito habilitado.";
                } else {
                    // Calculate total
                    $total = 0;
                    foreach ($validatedData['items'] as $item) {
                        $total += ($item['cantidad'] ?? 1) * ($item['precio'] ?? 0);
                    }

                    $saldoPendiente = $cliente->saldo_pendiente ?? 0;
                    $nuevoSaldo = $saldoPendiente + $total;

                    if ($nuevoSaldo > $cliente->limite_credito) {
                        $disponible = max(0, $cliente->limite_credito - $saldoPendiente);
                        $errors[] = "Límite de crédito excedido. Disponible: $" . number_format($disponible, 2) . ". Intentando cargar: $" . number_format($total, 2);
                    }
                }
            }

            if (count($errors) > 0) {
                return response()->json([
                    'valid' => false,
                    'errors' => $errors,
                    'warnings' => $warnings
                ], 422);
            }

            return response()->json([
                'valid' => true,
                'message' => 'Validación exitosa. Puede proceder con la venta.',
                'warnings' => $warnings
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'valid' => false,
                'errors' => collect($e->errors())->flatten()->toArray()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error validating sale: ' . $e->getMessage());
            return response()->json([
                'valid' => false,
                'errors' => ['Error al validar la venta: ' . $e->getMessage()]
            ], 500);
        }
    }

    public function create()
    {
        try {
            $clientes = \App\Models\Cliente::activos()->get();
            $productos = \App\Models\Producto::with('almacen')->get();
            $servicios = \App\Models\Servicio::all();

            return response()->json([
                'success' => true,
                'data' => [
                    'clientes' => $clientes,
                    'productos' => $productos,
                    'servicios' => $servicios,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar datos para nueva venta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the next sales number.
     */
    public function nextNumeroVenta()
    {
        try {
            // Obtener la parte numérica más alta de las ventas existentes
            if (\DB::getDriverName() === 'pgsql') {
                $max = \DB::table('ventas')
                    ->selectRaw("COALESCE(MAX(NULLIF(regexp_replace(numero_venta, '\\\\D', '', 'g'), '')::int), 0) as max_num")
                    ->value('max_num');
            } else {
                $ultimo = \App\Models\Venta::orderByDesc('id')->value('numero_venta');
                $max = 0;
                if ($ultimo && preg_match('/(\\d+)$/', $ultimo, $m)) {
                    $max = (int) $m[1];
                }
            }

            $siguiente = ((int) $max) + 1;
            $numero_venta = 'V' . str_pad($siguiente, 4, '0', STR_PAD_LEFT);

            return response()->json([
                'numero_venta' => $numero_venta,
                'siguiente_numero' => $siguiente
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar número de venta: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Muestra los detalles de una venta específica.
     */
    /**
     * Muestra los detalles de una venta específica.
     */
    public function show($id)
    {
        try {
            $venta = Venta::with([
                'cliente',
                'items.series',
                'items.ventable',
                'almacen'
            ])->findOrFail($id);

            $items = $venta->items->map(function ($item) {
                $ventable = $item->ventable;
                $tipo = $item->ventable_type === \App\Models\Producto::class ? 'producto' : 'servicio';

                $codigo = null;
                $almacenNombre = null;
                if ($tipo === 'producto' && $ventable) {
                    $codigo = $ventable->codigo ?? null;
                    if ($ventable->almacen_id) {
                        $almacen = $ventable->almacen;
                        $almacenNombre = $almacen?->nombre ?? null;
                    }
                }

                return [
                    'id' => $ventable?->id,
                    'nombre' => $ventable?->nombre ?? 'Producto eliminado',
                    'codigo' => $codigo,
                    'tipo' => $tipo,
                    'cantidad' => $item->cantidad,
                    'precio' => $item->precio,
                    'series' => $item->series->pluck('numero_serie')->toArray(),
                    'almacen_nombre' => $almacenNombre,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $venta->id,
                    'numero_venta' => $venta->numero_venta,
                    'fecha' => $venta->created_at,
                    'created_at' => $venta->created_at,
                    'cliente' => $venta->cliente,
                    'items' => $items,
                    'total' => $venta->total,
                    'almacen_id' => $venta->almacen_id,
                    'almacen' => $venta->almacen,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Venta no encontrada: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Crea una nueva venta.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // Validar los datos de entrada
            // Adaptar validación para VentaCreationService
            $validatedData = $request->validate([
                'cliente_id' => 'required|exists:clientes,id',
                'almacen_id' => 'nullable|integer|exists:almacenes,id,estado,activo',
                'items' => 'required|array',
                'items.*.id' => 'required|integer',
                'items.*.tipo' => 'required|in:producto,servicio',
                'items.*.cantidad' => 'required|integer|min:1',
                'items.*.precio' => 'required|numeric|min:0',
                'items.*.series' => 'nullable|array', // Soporte para series del servicio
                // Campos de pago opcionales
                'pago' => 'nullable|array',
                'pago.metodo_pago' => 'required_with:pago|string',
                'pago.cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
                'pago.notas' => 'nullable|string',
                'pago.monto' => 'nullable|numeric', // Opcional, si no se envía se asume total
            ]);

            // Transformar items al formato esperado por VentaCreationService
            $serviceData = [
                'cliente_id' => $validatedData['cliente_id'],
                'almacen_id' => $validatedData['almacen_id'] ?? null,
                'metodo_pago' => $validatedData['pago']['metodo_pago'] ?? 'credito',
                'cuenta_bancaria_id' => $validatedData['pago']['cuenta_bancaria_id'] ?? null, // ✅ Pasar cuenta
                'notas' => $request->input('notas') ?? ($validatedData['pago']['notas'] ?? null),
                'productos' => [],
                'servicios' => []
            ];

            foreach ($validatedData['items'] as $item) {
                if ($item['tipo'] === 'producto') {
                    $serviceData['productos'][] = [
                        'id' => $item['id'],
                        'cantidad' => $item['cantidad'],
                        'precio' => $item['precio'],
                        'series' => $item['series'] ?? []
                    ];
                } elseif ($item['tipo'] === 'servicio') {
                    $serviceData['servicios'][] = [
                        'id' => $item['id'],
                        'cantidad' => $item['cantidad'],
                        'precio' => $item['precio']
                    ];
                }
            }

            // Crear Venta usando el servicio (maneja inventario, series, CxC y PAGO AUTOMÁTICO)
            $venta = app(\App\Services\Ventas\VentaCreationService::class)->createVenta($serviceData, true);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Venta creada correctamente',
                'data' => $venta->load(['cliente', 'productos', 'servicios', 'cuentaPorCobrar'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error API Venta Store: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la venta: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Actualiza una venta existente.
     */
    /**
     * Actualiza una venta existente.
     */
    public function update(Request $request, $id)
    {
        try {
            $venta = Venta::findOrFail($id);

            // Validar datos de entrada
            $validatedData = $request->validate([
                'cliente_id' => 'sometimes|exists:clientes,id',
                'items' => 'sometimes|array',
                'items.*.id' => 'required_with:items|integer',
                'items.*.tipo' => 'required_with:items|in:producto,servicio',
                'items.*.cantidad' => 'required_with:items|integer|min:1',
                'items.*.precio' => 'required_with:items|numeric|min:0',
                'items.*.descuento' => 'nullable|numeric|min:0|max:100',
                'items.*.series' => 'nullable|array',
                'productos' => 'sometimes|array',
                'servicios' => 'sometimes|array',
                'metodo_pago' => 'sometimes|string',
                'descuento_general' => 'nullable|numeric|min:0',
                'notas' => 'nullable|string',
            ]);

            // Transformar 'items' a 'productos' y 'servicios' si es necesario
            if (isset($validatedData['items'])) {
                $validatedData['productos'] = $validatedData['productos'] ?? [];
                $validatedData['servicios'] = $validatedData['servicios'] ?? [];

                foreach ($validatedData['items'] as $item) {
                    if ($item['tipo'] === 'producto') {
                        $validatedData['productos'][] = $item;
                    } else {
                        $validatedData['servicios'][] = $item;
                    }
                }
            }

            // ✅ REFACTORED: Use centralized update service
            $ventaActualizada = $this->updateService->updateVenta($venta, $validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Venta actualizada con éxito',
                'data' => $ventaActualizada->load(['cliente', 'productos', 'servicios'])
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error API Venta Update: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la venta: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Elimina una venta. Solo administradores pueden eliminar.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user || !$user->hasAnyRole(['admin', 'super-admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los administradores pueden eliminar ventas'
                ], 403);
            }

            $venta = Venta::findOrFail($id);
            $this->cancellationService->cancelVenta($venta, 'Eliminación vía API', true);
            $venta->delete();

            return response()->json([
                'success' => true,
                'message' => 'Venta cancelada y eliminada con éxito'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error API Venta Destroy: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la venta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar venta como pagada.
     */
    public function marcarPagado(Request $request, $id)
    {
        try {
            $venta = Venta::findOrFail($id);
            $paymentService = app(\App\Services\Ventas\VentaPaymentService::class);
            $paymentService->markAsPaid($venta, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Venta marcada como pagada correctamente.',
                'data' => $venta->load('cuentaPorCobrar')
            ]);
        } catch (\Exception $e) {
            Log::error('Error API Venta MarcarPagado: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar como pagada: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Facturar una venta (Generar CFDI 4.0)
     */
    public function facturar(Request $request, $id)
    {
        try {
            $venta = Venta::findOrFail($id);
            $cfdiService = app(\App\Services\Cfdi\CfdiService::class);

            $validated = $request->validate([
                'tipo_factura' => 'nullable|in:ingreso,anticipo',
                'cfdi_relacion_tipo' => 'nullable|in:01,02,03,04,05,06,07',
                'cfdi_relacion_uuids' => 'nullable|array',
                'cfdi_relacion_uuids.*' => 'string|uuid',
                'anticipo_monto' => 'nullable|numeric|min:0.01',
                'anticipo_metodo_pago' => 'nullable|in:efectivo,transferencia,cheque,tarjeta,otros',
            ]);

            $tipoFactura = $validated['tipo_factura'] ?? 'ingreso';

            if ($tipoFactura === 'anticipo') {
                $result = $cfdiService->facturarAnticipo(
                    $venta,
                    (float) $validated['anticipo_monto'],
                    $validated['anticipo_metodo_pago']
                );
            } else {
                $options = [
                    'tipo_factura' => 'ingreso',
                    'cfdi_relacion_tipo' => $validated['cfdi_relacion_tipo'] ?? null,
                    'cfdi_relacion_uuids' => $validated['cfdi_relacion_uuids'] ?? [],
                ];
                $result = $cfdiService->facturarVenta($venta, $options);
            }

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $venta->load('cfdis')
            ]);
        } catch (\Exception $e) {
            Log::error('Error API Venta Facturar: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al facturar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar la factura de una venta
     */
    public function cancelarFactura(Request $request, $id)
    {
        try {
            $venta = Venta::findOrFail($id);
            $cancelService = app(\App\Services\Cfdi\CfdiCancelService::class);

            $validated = $request->validate([
                'motivo' => 'required|string|in:01,02,03,04',
                'folio_sustitucion' => 'nullable|string|uuid|required_if:motivo,01',
            ]);

            $cfdi = $venta->cfdi_actual ?? $venta->cfdis()
                ->whereNotNull('uuid')
                ->where('estatus', '!=', 'cancelado')
                ->latest()
                ->first();

            if (!$cfdi) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró una factura válida para cancelar.'
                ], 404);
            }

            $result = $cancelService->cancelar($cfdi, $validated['motivo'], $validated['folio_sustitucion']);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 422);
            }

            // Actualizar estado de la venta
            $venta->estado = \App\Enums\EstadoVenta::Cancelada;
            $venta->save();

            return response()->json([
                'success' => true,
                'message' => $result['message']
            ]);
        } catch (\Exception $e) {
            Log::error('Error API Venta CancelarFactura: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar factura: ' . $e->getMessage()
            ], 500);
        }
    }
}





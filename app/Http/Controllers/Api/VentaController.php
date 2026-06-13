<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\EstadoVenta;
use App\Models\User;
use App\Models\Venta;
use App\Services\Cobros\MiCorteCobrosCalculator;
use Carbon\Carbon;
use App\Services\InventarioService;
use App\Services\Ventas\VentaCancellationService;
use App\Services\Ventas\VentaUpdateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Traits\ApiResponse;

class VentaController extends Controller
{
    use ApiResponse;
    public function __construct(
        private readonly InventarioService $inventarioService,
        private readonly \App\Services\Ventas\VentaCancellationService $cancellationService,
        private readonly \App\Services\Ventas\VentaUpdateService $updateService,
        private readonly \App\Services\StockValidationService $stockValidationService
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

            $query = Venta::with(['cliente', 'vendedor', 'almacen', 'createdBy', 'entregaDinero', 'entregas', 'cuentaPorCobrar.movimientosBancarios']);

            if ($nombreCliente) {
                $query->whereHas('cliente', function ($subQuery) use ($nombreCliente) {
                    $subQuery->where('nombre_razon_social', 'LIKE', '%' . $nombreCliente . '%');
                });
            }

            // Filtros de fecha (mismo criterio que miResumenCobros cuando hay rango completo)
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

            // App móvil: solo ventas del usuario (creó la venta o es vendedor User asignado)
            if ($request->boolean('mis_ventas')) {
                $auth = $request->user();
                if ($auth) {
                    $uid = (int) $auth->id;
                    $query->where(function ($q) use ($uid) {
                        $q->where('created_by', $uid)
                            ->orWhere(function ($q2) use ($uid) {
                                $q2->where('vendedor_id', $uid)
                                    ->where(function ($qq) {
                                        $qq->where('vendedor_type', User::class)
                                            ->orWhere('vendedor_type', 'user');
                                    });
                            });
                    });
                }
            } elseif ($request->filled('vendedor_id')) {
                $uid = (int) $request->vendedor_id;
                $query->where(function ($q) use ($uid) {
                    $q->where('created_by', $uid)
                        ->orWhere(function ($q2) use ($uid) {
                            $q2->where('vendedor_id', $uid)
                                ->where(function ($qq) {
                                    $qq->where('vendedor_type', User::class)
                                        ->orWhere('vendedor_type', 'user');
                                });
                        });
                });
            }

            $paginator = $query->orderByDesc('created_at')->paginate((int) $perPage);

            $ventasTransformadas = collect($paginator->items())->map(function ($venta) {
                return [
                    'id' => $venta->id,
                    'numero_venta' => $venta->numero_venta,
                    'created_at' => $venta->created_at,
                    'pagado' => (bool) $venta->pagado,
                    'metodo_pago' => $venta->metodo_pago,
                    'metodo_pago_etiqueta' => $this->etiquetaMetodoPago($venta->metodo_pago),
                    'vendedor' => $this->resumenVendedor($venta),
                    'almacen' => $venta->almacen ? [
                        'id' => $venta->almacen->id,
                        'nombre' => $venta->almacen->nombre,
                    ] : null,
                    'cliente' => $venta->cliente,
                    'total' => $venta->total,
                    'estado' => $venta->estado,
                    'estado_etiqueta' => $venta->estado instanceof \App\Enums\EstadoVenta ? $venta->estado->label() : (string) $venta->estado,
                    'sharing_token' => $venta->sharing_token,
                    'tiene_entrega_dinero' => $this->determinarEstadoEntrega($venta)['tiene_entrega_dinero'],
                    'entrega_dinero_estado' => $this->determinarEstadoEntrega($venta)['entrega_dinero_estado'],
                ];
            });

            return $this->success([
                'items' => $ventasTransformadas,
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error en VentaController@index: ' . $e->getMessage());
            return $this->serverError('Error al obtener las ventas', $e);
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
                'items.*.componentes_series' => 'nullable|array',
                'metodo_pago' => 'nullable|string',
            ]);

            $errors = [];
            $warnings = [];
            $almacenId = $validatedData['almacen_id'] ?? auth()->user()->almacen_venta_id;

            $user = auth()->user();
            if ($user && !$user->hasAnyRole(['admin', 'super-admin'])) {
                $assignedAlmacenId = $user->almacen_venta_id;
                if (!$assignedAlmacenId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes un almacén de venta asignado en tu perfil. Contacta al administrador.',
                        'errors' => ['No tienes un almacén de venta asignado en tu perfil. Contacta al administrador.']
                    ], 422);
                }

                if ($almacenId && (int) $almacenId !== (int) $assignedAlmacenId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permiso para vender desde un almacén diferente al asignado.',
                        'errors' => ['No tienes permiso para vender desde un almacén diferente al asignado.']
                    ], 422);
                }
            }

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
                if ($producto->esKit()) {
                    $kitErrors = $this->stockValidationService->validateKitStock($producto, $item['cantidad'], (int)$almacenId, $item['componentes_series'] ?? []);
                    if (!empty($kitErrors)) {
                        $errors = array_merge($errors, $kitErrors);
                    }
                } else {
                    $stockDisponible = $this->stockValidationService->getStockDisponible($producto->id, (int)$almacenId);
                    if ($stockDisponible < $item['cantidad']) {
                        $errors[] = "Stock insuficiente para '{$producto->nombre}'. Disponible: {$stockDisponible}, Solicitado: {$item['cantidad']}.";
                    }
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
                return $this->error($errors[0], 422, $errors);
            }

            return $this->success([
                'valid' => true,
                'message' => 'Validación exitosa. Puede proceder con la venta.',
                'warnings' => $warnings
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Error al validar la venta', $e);
        }
    }

    public function create()
    {
        try {
            $clientes = \App\Models\Cliente::where('activo', true)->limit(500)->get();
            $productos = \App\Models\Producto::with(['inventarios.almacen', 'categoria', 'marca'])
                ->where('estado', 'activo')
                ->limit(500)
                ->get();
            $servicios = \App\Models\Servicio::where('estado', 'activo')->get();

            return $this->success([
                'clientes' => $clientes,
                'productos' => $productos,
                'servicios' => $servicios,
            ]);
        } catch (\Exception $e) {
            return $this->serverError('Error al cargar datos para nueva venta', $e);
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

            return $this->success([
                'numero_venta' => $numero_venta,
                'siguiente_numero' => $siguiente
            ]);
        } catch (\Exception $e) {
            return $this->serverError('Error al generar número de venta', $e);
        }
    }

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
                'almacen',
                'vendedor',
                'createdBy',
                'entregaDinero.recibidoPor',
                'entregas',
                'cuentaPorCobrar.movimientosBancarios',
            ])->findOrFail($id);

            $items = $venta->items->map(function ($item) use ($venta) {
                $ventable = $item->ventable;
                $tipo = $item->ventable_type === \App\Models\Producto::class ? 'producto' : 'servicio';

                $codigo = null;
                $almacenNombre = $venta->almacen?->nombre ?? 'Sin almacén';
                if ($tipo === 'producto' && $ventable) {
                    $codigo = $ventable->codigo ?? null;
                }

                return [
                    'id' => $ventable?->id,
                    'nombre' => $ventable?->nombre ?? 'Producto eliminado',
                    'codigo' => $codigo,
                    'tipo' => $tipo,
                    'cantidad' => $item->cantidad,
                    'precio' => (float) $item->precio,
                    'descuento' => (float) ($item->descuento ?? 0),
                    'series' => $item->series->pluck('numero_serie')->toArray(),
                    'almacen_nombre' => $almacenNombre,
                ];
            });

            return $this->success([
                'id' => $venta->id,
                'numero_venta' => $venta->numero_venta,
                'fecha' => $venta->created_at,
                'created_at' => $venta->created_at,
                'pagado' => (bool) $venta->pagado,
                'metodo_pago' => $venta->metodo_pago,
                'metodo_pago_etiqueta' => $this->etiquetaMetodoPago($venta->metodo_pago),
                'vendedor' => $this->resumenVendedor($venta),
                'cliente' => $venta->cliente,
                'items' => $items,
                'subtotal' => (float) $venta->subtotal,
                'descuento_general' => (float) ($venta->descuento_general ?? 0),
                'iva' => (float) ($venta->iva ?? 0),
                'isr' => (float) ($venta->isr ?? 0),
                'total' => (float) $venta->total,
                'notas' => $venta->notas,
                'moneda' => \App\Models\EmpresaConfiguracion::getConfig()?->moneda ?? 'MXN',
                'estado' => $venta->estado,
                'estado_etiqueta' => $venta->estado instanceof \App\Enums\EstadoVenta ? $venta->estado->label() : (string) $venta->estado,
                'almacen_id' => $venta->almacen_id,
                'almacen' => $venta->almacen,
                'sharing_token' => $venta->sharing_token,
                'tiene_entrega_dinero' => $this->determinarEstadoEntrega($venta)['tiene_entrega_dinero'],
                'entrega_dinero_estado' => $this->determinarEstadoEntrega($venta)['entrega_dinero_estado'],
                'entrega_dinero' => $venta->entregaDinero ? [
                    'id' => $venta->entregaDinero->id,
                    'fecha_entrega' => $venta->entregaDinero->fecha_entrega?->toIso8601String(),
                    'fecha_recibido' => $venta->entregaDinero->fecha_recibido?->toIso8601String(),
                    'monto_efectivo' => $venta->entregaDinero->monto_efectivo,
                    'estado' => $venta->entregaDinero->estado,
                    'recibido_por_nombre' => $venta->entregaDinero->recibidoPor?->name ?? 'N/A',
                ] : null,
            ]);
        } catch (\Exception $e) {
            return $this->notFound('Venta no encontrada');
        }
    }

    /**
     * Crea una nueva venta.
     */
    public function store(Request $request)
    {
        try {
            // Validar antes de delegar al servicio. El servicio ya maneja su propia transacción.
            $validatedData = $request->validate([
                'cliente_id' => 'nullable|exists:clientes,id',
                'almacen_id' => 'nullable|integer|exists:almacenes,id,estado,activo',
                'vendedor_id' => 'nullable|integer|exists:users,id',
                'vendedor_type' => 'nullable|string|in:App\\Models\\User',
                'items' => 'required|array',
                'items.*.id' => 'required|integer',
                'items.*.tipo' => 'required|in:producto,servicio',
                'items.*.cantidad' => 'required|integer|min:1',
                'items.*.precio' => 'required|numeric|min:0',
                'items.*.series' => 'nullable|array', // Soporte para series del servicio
                'items.*.componentes_series' => 'nullable|array',
                // Campos de pago opcionales
                'pago' => 'nullable|array',
                'pago.metodo_pago' => 'required_with:pago|string',
                'pago.cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
                'pago.notes' => 'nullable|string',
                'pago.notas' => 'nullable|string',
                'pago.monto' => 'nullable|numeric', // Opcional, si no se envía se asume total
                'pago.pagado_por_user_id' => 'nullable|integer|exists:users,id',
                'ticket_id' => 'nullable|integer|exists:tickets,id',
            ]);

            Log::debug('VentaController@store starting', ['data' => $request->all()]);

            $user = $request->user();
            
            // Lógica de almacén: Los técnicos/vendedores no pueden elegir, se usa su almacén asignado.
            // Solo super-admin y admin pueden elegir uno distinto al suyo.
            $isAdmin = $user->hasRole(['super-admin', 'admin']);
            
            if (!$isAdmin) {
                $almacenId = $user->almacen_venta_id;
            } else {
                $almacenId = $validatedData['almacen_id'] ?? $user->almacen_venta_id;
            }
            
            Log::debug('VentaController: Resolved almacenId', [
                'almacenId' => $almacenId, 
                'user_id' => $user->id,
                'is_admin' => $isAdmin
            ]);
            
            if (empty($almacenId)) {
                return $this->error('Tu usuario no tiene almacén de venta asignado. Contacta al administrador para que te asigne uno.', 422);
            }

            // SEGURIDAD: Validar que el almacén pertenezca a la empresa del usuario
            $almacen = \App\Models\Almacen::withoutGlobalScopes()
                ->where('id', $almacenId)
                ->where('empresa_id', $user->empresa_id)
                ->first();

            if (!$almacen) {
                \Log::warning("SECURITY ALERT: Intento de venta usando almacén de otra empresa", [
                    'user_id' => $user->id,
                    'empresa_id' => $user->empresa_id,
                    'attempted_almacen_id' => $almacenId,
                    'ip' => $request->ip()
                ]);
                return $this->error('El almacén seleccionado no es válido o no pertenece a su empresa.', 403);
            }

            // Transformar items al formato esperado por VentaCreationService
            $serviceData = [
                'cliente_id' => $validatedData['cliente_id'] ?? null,
                'almacen_id' => (int) $almacenId,
                'metodo_pago' => $validatedData['pago']['metodo_pago'] ?? 'credito',
                'cuenta_bancaria_id' => $validatedData['pago']['cuenta_bancaria_id'] ?? null, // ✅ Pasar cuenta
                'notas' => $request->input('notas') ?? ($validatedData['pago']['notas'] ?? null),
                'pagado_por_user_id' => $validatedData['pago']['pagado_por_user_id'] ?? null,
                'productos' => [],
                'servicios' => [],
            ];

            if (! empty($validatedData['vendedor_id'])) {
                $serviceData['vendedor_id'] = (int) $validatedData['vendedor_id'];
                $serviceData['vendedor_type'] = $validatedData['vendedor_type'] ?? \App\Models\User::class;
            }

            foreach ($validatedData['items'] as $item) {
                if ($item['tipo'] === 'producto') {
                    $serviceData['productos'][] = [
                        'id' => $item['id'],
                        'cantidad' => $item['cantidad'],
                        'precio' => $item['precio'],
                        'series' => $item['series'] ?? [],
                        'componentes_series' => $item['componentes_series'] ?? []
                    ];
                } elseif ($item['tipo'] === 'servicio') {
                    $serviceData['servicios'][] = [
                        'id' => $item['id'],
                        'cantidad' => $item['cantidad'],
                        'precio' => $item['precio']
                    ];
                }
            }

            Log::debug('VentaController: Calling VentaCreationService');
            $venta = app(\App\Services\Ventas\VentaCreationService::class)->createVenta($serviceData, true);
            Log::debug('VentaController: Venta created successfully');

            if ($request->filled('ticket_id')) {
                $ticket = \App\Models\Ticket::find($request->ticket_id);
                if ($ticket) {
                    $ticket->update(['venta_id' => $venta->id]);
                }
            }

            return $this->created($venta->load(['cliente', 'productos', 'servicios', 'cuentaPorCobrar', 'vendedor', 'almacen']), 'Venta creada correctamente');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('VentaController validation error', ['errors' => $e->errors(), 'data' => $request->all()]);
            return $this->validationError($e->errors());

        } catch (\Throwable $e) {
            Log::error('CRITICAL ERROR in VentaController@store: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all(),
                'user_id' => auth()->id()
            ]);

            return $this->error('Error al crear la venta: ' . $e->getMessage(), 500);
        }
    }

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
                'vendedor_id' => 'nullable|integer|exists:users,id',
                'vendedor_type' => 'nullable|string|in:App\\Models\\User',
            ]);

            $wantsLineItems = isset($validatedData['items'])
                || ! empty($validatedData['productos'])
                || ! empty($validatedData['servicios']);

            if (! $wantsLineItems && ($request->filled('vendedor_id') || $request->has('notas'))) {
                $ventaActualizada = $this->updateService->patchVentaMeta($venta, $validatedData);

                return $this->success(
                    $ventaActualizada->load(['cliente', 'productos', 'servicios', 'vendedor', 'almacen']),
                    'Venta actualizada con éxito'
                );
            }

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

            return $this->success(
                $ventaActualizada->load(['cliente', 'productos', 'servicios', 'vendedor', 'almacen']),
                'Venta actualizada con éxito'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            Log::error('Error API Venta Update: ' . $e->getMessage());
            return $this->serverError('Error al actualizar la venta', $e);
        }
    }

    /**
     * Permite a un administrador cambiar el vendedor de una venta.
     */
    public function changeVendedor(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user->hasAnyRole(['super-admin', 'admin'])) {
                return $this->error('No tienes permiso para realizar esta acción.', 403);
            }

            $validated = $request->validate([
                'vendedor_id' => 'required|integer',
                'vendedor_type' => 'required|string|in:App\\Models\\User,App\\Models\\Tecnico,user,tecnico',
            ]);

            $venta = Venta::findOrFail($id);

            // Validar si la comisión ya fue pagada
            if ($venta->comision_pagada) {
                return $this->error('No se puede cambiar el vendedor porque la comisión ya ha sido marcada como pagada.', 422);
            }

            // Normalizar vendedor_type
            $type = $validated['vendedor_type'];
            if ($type === 'user') $type = \App\Models\User::class;
            if ($type === 'tecnico') $type = \App\Models\Tecnico::class;

            $venta->vendedor_id = $validated['vendedor_id'];
            $venta->vendedor_type = $type;
            $venta->save();

            // Opcional: Recalcular costos si la lógica de ganancia depende del vendedor (según Venta.php getGananciaTotalAttribute)
            // No es necesario llamar a una función extra porque getGananciaTotalAttribute es un atributo calculado dinámicamente.

            return $this->success($venta->load('vendedor'), 'Vendedor actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error en VentaController@changeVendedor: ' . $e->getMessage());
            return $this->serverError('Error al cambiar el vendedor', $e);
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
     * Resumen de cobros para el usuario autenticado en un rango (app móvil — corte).
     */
    public function miResumenCobros(Request $request)
    {
        try {
            $validated = $request->validate([
                'fecha_desde' => 'required|date',
                'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
            ]);

            $user = $request->user();
            $userId = (int) $user->id;
            
            // Si es super-admin, admin o compras (Luis), puede consultar de otros usuarios o globalmente (null)
            if ($user->hasRole('super-admin') || $user->is_admin || $user->hasRole('compras')) {
                if ($request->has('user_id') || $request->has('tecnico_id')) {
                    $rawId = $request->input('user_id') ?? $request->input('tecnico_id');
                    $userId = $rawId ? (int)$rawId : null;
                } elseif ($request->boolean('global')) {
                    $userId = null;
                }
            }

            $tz = config('app.timezone');
            $desde = Carbon::createFromFormat('Y-m-d', $validated['fecha_desde'], $tz)->startOfDay();
            $hasta = Carbon::createFromFormat('Y-m-d', $validated['fecha_hasta'], $tz)->endOfDay();

            $calc = app(MiCorteCobrosCalculator::class);
            $r = $calc->resumenParaUsuario($userId, $desde, $hasta);

            return $this->success([
                'fecha_desde' => $desde->toIso8601String(),
                'fecha_hasta' => $hasta->toIso8601String(),
                'ventas_count' => $r['ventas_count'],
                'ventas_periodo_cobradas' => $r['ventas_periodo_cobradas'],
                'total_general' => $r['total_general'],
                'por_metodo' => $r['por_metodo'],
                'efectivo_generado' => $r['efectivo_generado'] ?? 0,
                'ya_entregado' => $r['ya_entregado'] ?? 0,
                'efectivo_a_entregar' => $r['efectivo_a_entregar'],
                'descuentos_partidas' => $r['descuentos_partidas'],
                'descuento_general' => $r['descuento_general'],
                'total_descuentos' => $r['total_descuentos'],
                'gastos_efectivo' => $r['gastos_efectivo'] ?? 0,
                'gastos_detalle' => ($r['gastos_detalle'] ?? collect())->map(function($g) {
                    return [
                        'id' => $g->id,
                        'numero_compra' => $g->numero_compra,
                        'total' => (float)$g->total,
                        'fecha_compra' => $g->fecha_compra,
                        'notas' => $g->notas,
                        'categoria_gasto' => [
                            'nombre' => $g->categoriaGasto?->nombre ?? 'General'
                        ],
                        'user' => $g->user ? [
                            'id' => $g->user->id,
                            'nombre' => $g->user->name,
                        ] : null,
                    ];
                }),
                'ventas' => $r['ventas']->map(function($v) {
                    return [
                        'id' => $v->id,
                        'numero_venta' => $v->numero_venta,
                        'total' => (float)$v->total,
                        'metodo_pago' => $v->metodo_pago,
                        'created_at' => $v->created_at,
                        'cliente' => $v->cliente?->nombre_razon_social ?? null,
                        'vendedor' => $this->resumenVendedor($v),
                    ];
                }),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            Log::error('miResumenCobros: '.$e->getMessage());
            return $this->serverError('No se pudo generar el resumen de cobros', $e);
        }
    }


    /**
     * Marcar venta como pagada.
     */
    public function marcarPagado(Request $request, $id)
    {
        try {
            $venta = Venta::findOrFail($id);
            $validated = $request->validate([
                'metodo_pago' => 'nullable|string',
                'cuenta_bancaria_id' => 'nullable|integer',
                'referencia' => 'nullable|string',
                'notas' => 'nullable|string',
                'fecha_pago' => 'nullable|date',
                'monto' => 'nullable|numeric',
                'pagado_por_user_id' => 'nullable|integer|exists:users,id',
            ]);

            $paymentService = app(\App\Services\Ventas\VentaPaymentService::class);
            $paymentService->markAsPaid($venta, $validated);

            return $this->success($venta->load('cuentaPorCobrar'), 'Venta marcada como pagada correctamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Error al marcar como pagada', $e);
        }
    }

    /**
     * Usuarios de la empresa para elegir quién recibió el cobro (app / modal de pago).
     */
    public function usuariosParaRegistrarCobro(Request $request)
    {
        try {
            $empresaId = \App\Support\EmpresaResolver::resolveId()
                ?: (int) ($request->user()?->empresa_id ?? 0);
            if (! $empresaId) {
                return $this->success([]);
            }

            $rows = \App\Models\User::query()
                ->where('empresa_id', $empresaId)
                ->where('activo', true)
                ->orderBy('name')
                ->get(['id', 'name']);

            return $this->success($rows);
        } catch (\Exception $e) {
            return $this->serverError('No se pudieron listar usuarios', $e);
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
                return $this->error($result['message'], 422);
            }

            return $this->success($venta->load('cfdis'), $result['message']);
        } catch (\Exception $e) {
            return $this->serverError('Error al facturar', $e);
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
                return $this->notFound('No se encontró una factura válida para cancelar.');
            }

            $result = $cancelService->cancelar($cfdi, $validated['motivo'], $validated['folio_sustitucion']);

            if (!$result['success']) {
                return $this->error($result['message'], 422);
            }

            // ✅ FIX (A-07): Do NOT cancel the sale when only the invoice is cancelled.
            // Revert to Pagado or Aprobada based on payment status.
            $venta->estado = $venta->pagado ? \App\Enums\EstadoVenta::Pagado : \App\Enums\EstadoVenta::Aprobada;
            $venta->save();

            return $this->success(null, $result['message']);
        } catch (\Exception $e) {
            return $this->serverError('Error al cancelar factura', $e);
        }
    }

    /**
     * Etiqueta legible para método de pago (app móvil / reportes).
     */
    private function etiquetaMetodoPago(?string $metodo): string
    {
        if ($metodo === null || $metodo === '') {
            return '—';
        }
        $k = strtolower(trim($metodo));

        return match ($k) {
            'efectivo' => 'Efectivo',
            'transferencia' => 'Transferencia',
            'tarjeta_credito' => 'Tarjeta de crédito',
            'tarjeta_debito' => 'Tarjeta de débito',
            'cheque' => 'Cheque',
            'credito' => 'Crédito (cuenta por cobrar)',
            default => $metodo,
        };
    }

    private function resumenVendedor(\App\Models\Venta $venta): ?array
    {
        $v = $venta->vendedor;
        if ($v) {
            return [
                'id' => (int) $v->id,
                'nombre' => $v->name,
                'fuente' => 'vendedor',
            ];
        }
        $creador = $venta->createdBy;
        if ($creador) {
            return [
                'id' => (int) $creador->id,
                'nombre' => $creador->name,
                'fuente' => 'created_by',
            ];
        }
        return null;
    }

    /**
     * Determina con precisión el estado de la entrega de dinero para una venta,
     * conciliando si ya existen entregas o movimientos bancarios registrados (tanto legacy como polimórficos).
     */
    private function determinarEstadoEntrega(Venta $venta): array
    {
        if (!$venta->pagado || $venta->estado === 'cancelada' || ($venta->estado instanceof \App\Enums\EstadoVenta && $venta->estado->value === 'cancelada')) {
            return [
                'tiene_entrega_dinero' => false,
                'entrega_dinero_estado' => null,
            ];
        }

        if ($venta->entregaDinero) {
            return [
                'tiene_entrega_dinero' => true,
                'entrega_dinero_estado' => $venta->entregaDinero->estado,
            ];
        }

        // Consultar monto ya entregado en entregas_dinero vinculadas
        $montoYaEntregado = (float) \App\Models\EntregaDinero::where('tipo_origen', 'venta')
            ->where('id_origen', $venta->id)
            ->whereIn('estado', ['pendiente', 'recibido'])
            ->sum('total');

        // Buscar la cuenta por cobrar asociada (cubriendo venta_id legacy y cobrable_id polimórfico)
        $cxcId = \App\Models\CuentasPorCobrar::where('venta_id', $venta->id)
            ->orWhere(function ($q) use ($venta) {
                $q->where('cobrable_id', $venta->id)->where('cobrable_type', \App\Models\Venta::class);
            })
            ->value('id');

        // Consultar monto conciliado en movimientos_bancarios para esa cuenta por cobrar
        $montoConciliado = $cxcId ? (float) \App\Models\MovimientoBancario::where('conciliable_type', \App\Models\CuentasPorCobrar::class)
            ->where('conciliable_id', $cxcId)
            ->sum('monto') : 0.0;

        $saldoPendiente = max(0, round($venta->total - $montoYaEntregado - $montoConciliado, 2));

        if ($saldoPendiente <= 0.01) {
            return [
                'tiene_entrega_dinero' => true,
                'entrega_dinero_estado' => 'recibido',
            ];
        }

        return [
            'tiene_entrega_dinero' => false,
            'entrega_dinero_estado' => 'pendiente',
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\ProductoSerie;
use App\Models\ProductoPrecioHistorial;
use App\Models\Compra;
use App\Models\CompraItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class OrdenCompraController extends Controller
{
    public function __construct(
        private readonly \App\Services\InventarioService $inventarioService,
        private readonly \App\Services\Compras\CompraCuentasPagarService $cuentasPagarService,
        private readonly \App\Services\FinancialService $financialService,
        private readonly \App\Services\Compras\CompraCreationService $compraCreationService
    ) {
    }

    public function index(Request $request)
    {
        $perPage = (int) ($request->integer('per_page') ?: 10);

        // Validar elementos por página
        $validPerPages = [10, 15, 25, 50, 100];
        if (!in_array($perPage, $validPerPages)) {
            $perPage = 10;
        }

        $baseQuery = OrdenCompra::with(['proveedor', 'productos', 'almacen', 'pedido']);

        // Aplicar filtros
        if ($search = trim($request->get('search', ''))) {
            $baseQuery->where(function ($query) use ($search) {
                $query->where('numero_orden', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhereHas('proveedor', function ($q) use ($search) {
                        $q->where('nombre_razon_social', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('estado')) {
            $baseQuery->where('estado', $request->estado);
        }

        if ($request->filled('proveedor_id')) {
            $baseQuery->where('proveedor_id', $request->proveedor_id);
        }

        if ($request->filled('prioridad')) {
            $baseQuery->where('prioridad', $request->prioridad);
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $allowedSorts = ['created_at', 'fecha_orden', 'total', 'estado', 'numero_orden', 'prioridad'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $baseQuery->orderBy($sortBy, $sortDirection === 'asc' ? 'asc' : 'desc')
            ->orderBy('id', 'desc');

        $ordenes = $baseQuery->paginate($perPage)->appends($request->query());

        // Estadísticas
        $stats = [
            'total' => OrdenCompra::count(),
            'pendientes' => OrdenCompra::where('estado', 'pendiente')->count(),
            'aprobadas' => OrdenCompra::where('estado', 'aprobada')->count(),
            'enviadas_a_proveedor' => OrdenCompra::where('estado', 'enviada_a_proveedor')->count(),
            'recibidas' => OrdenCompra::where('estado', 'recibida')->count(),
            'procesadas' => OrdenCompra::where('estado', 'procesada')->count(),
            'canceladas' => OrdenCompra::where('estado', 'cancelada')->count(),
            'urgentes' => OrdenCompra::where('prioridad', 'urgente')->count(),
        ];

        // Información de paginación para el frontend
        $pagination = [
            'current_page' => $ordenes->currentPage(),
            'last_page' => $ordenes->lastPage(),
            'per_page' => $perPage,
            'total' => $ordenes->total(),
            'from' => $ordenes->firstItem(),
            'to' => $ordenes->lastItem(),
        ];

        return Inertia::render('OrdenesCompra/Index', [
            'ordenesCompra' => $ordenes,
            'stats' => $stats,
            'pagination' => $pagination,
            'almacenes' => Almacen::select('id', 'nombre')->where('estado', 'activo')->get(), // ✅ Solo almacenes activos
            'filters' => $request->only(['search', 'estado', 'proveedor_id', 'prioridad']),
            'sorting' => [
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('OrdenesCompra/Create', [
            'proveedores' => Proveedor::select('id', 'nombre_razon_social', 'email', 'telefono')->get(),
            'productos' => Producto::select('id', 'nombre', 'precio_compra', 'precio_venta', 'descripcion')
                ->where('estado', 'activo')->get(), // ✅ Solo productos activos
            'almacenes' => Almacen::select('id', 'nombre')->where('estado', 'activo')->get(), // ✅ Solo almacenes activos
            'proximoNumero' => OrdenCompra::getProximoNumero(),
            'defaults' => [
                'ivaPorcentaje' => \App\Services\EmpresaConfiguracionService::getIvaPorcentaje(),
                'enableRetencionIva' => \App\Services\EmpresaConfiguracionService::isRetencionIvaEnabled(),
                'enableRetencionIsr' => \App\Services\EmpresaConfiguracionService::isRetencionIsrEnabled(),
                'retencionIvaDefault' => \App\Services\EmpresaConfiguracionService::getRetencionIvaDefault(),
                'retencionIsrDefault' => \App\Services\EmpresaConfiguracionService::getRetencionIsrDefault(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        Log::info('OrdenCompraController@store - Datos recibidos:', $request->all());

        DB::beginTransaction();
        try {
            $req = $request->all();
            if (empty($req['almacen_id']) || $req['almacen_id'] === '0' || $req['almacen_id'] === 0) {
                $req['almacen_id'] = null;
            }
            $request->merge($req);

            $validated = $request->validate([
                'numero_orden' => 'nullable|string',
                'fecha_orden' => 'required|date',
                'fecha_entrega_esperada' => 'nullable|date',
                'prioridad' => 'required|in:baja,media,alta,urgente',
                'proveedor_id' => 'required|exists:proveedores,id',
                'almacen_id' => 'nullable|exists:almacenes,id',
                'direccion_entrega' => 'nullable|string',
                'terminos_pago' => 'required|in:contado,15_dias,30_dias,45_dias,60_dias,90_dias',
                'metodo_pago' => 'required|in:transferencia,cheque,efectivo,tarjeta',
                'subtotal' => 'required|numeric|min:0',
                'descuento_items' => 'required|numeric|min:0',
                'descuento_general' => 'required|numeric|min:0|max:100',
                'iva' => 'required|numeric|min:0',
                'retencion_iva' => 'nullable|numeric|min:0',
                'retencion_isr' => 'nullable|numeric|min:0',
                'aplicar_retencion_iva' => 'boolean',
                'aplicar_retencion_isr' => 'boolean',
                'total' => 'required|numeric|min:0',
                'observaciones' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.id' => 'required|integer|exists:productos,id',
                'items.*.tipo' => 'required|in:producto',
                'items.*.cantidad' => 'required|integer|min:1',
                'items.*.precio' => 'required|numeric|min:0',
                'items.*.descuento' => 'required|numeric|min:0|max:100',
            ]);

            // Recalculate totals on backend for integrity
            $fiscalConfig = [
                'aplicar_retencion_iva' => $request->boolean('aplicar_retencion_iva'),
                'aplicar_retencion_isr' => $request->boolean('aplicar_retencion_isr'),
                'mode' => 'purchases'
            ];

            $totales = $this->financialService->calculateDocumentTotals(
                $validated['items'],
                (float) ($validated['descuento_general'] ?? 0),
                null,
                $fiscalConfig
            );

            $data = [
                'numero_orden' => $validated['numero_orden'] ?? null,
                'fecha_orden' => $validated['fecha_orden'],
                'fecha_entrega_esperada' => $validated['fecha_entrega_esperada'] ?? null,
                'prioridad' => $validated['prioridad'],
                'proveedor_id' => $validated['proveedor_id'],
                'direccion_entrega' => $validated['direccion_entrega'] ?? null,
                'terminos_pago' => $validated['terminos_pago'],
                'metodo_pago' => $validated['metodo_pago'],
                'subtotal' => $totales['subtotal'],
                'descuento_items' => $totales['descuento_items'],
                'descuento_general' => $totales['descuento_general'],
                'iva' => $totales['iva'],
                'retencion_iva' => $totales['retencion_iva'],
                'retencion_isr' => $totales['retencion_isr'],
                'aplicar_retencion_iva' => $validated['aplicar_retencion_iva'] ?? false,
                'aplicar_retencion_isr' => $validated['aplicar_retencion_isr'] ?? false,
                'total' => $totales['total'],
                'observaciones' => $validated['observaciones'] ?? null,
                'estado' => 'pendiente',
            ];
            if (Schema::hasColumn('orden_compras', 'almacen_id')) {
                $data['almacen_id'] = $validated['almacen_id'] ?? null;
            }

            $orden = OrdenCompra::create($data);

            foreach ($validated['items'] as $item) {
                if (($item['tipo'] ?? null) === 'producto') {
                    $orden->productos()->attach($item['id'], [
                        'cantidad' => $item['cantidad'],
                        'precio' => $item['precio'],
                        'descuento' => $item['descuento'] ?? 0,
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('ordenescompra.index')->with('success', 'Orden de compra creada exitosamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al crear la orden de compra', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return redirect()->back()->with('error', 'Ocurrió un error al crear la orden de compra.');
        }
    }

    /**
     * Convertir orden de compra directamente a compra
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Convertir orden de compra directamente a compra
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function convertirDirecto(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        // Validar que se hayan enviado las series si son requeridas
        // La validación detallada ocurre más abajo, esto es preventivo

        $orden = OrdenCompra::with(['proveedor', 'productos'])->findOrFail($id);

        // Validar que la orden esté en estado válido para conversión
        if (!in_array($orden->estado, ['pendiente', 'aprobada'])) {
            return response()->json([
                'success' => false,
                'error' => 'Solo órdenes pendientes o aprobadas pueden convertirse a compra'
            ], 400);
        }

        // Validar que tenga productos
        if ($orden->productos->isEmpty()) {
            return response()->json([
                'success' => false,
                'error' => 'La orden no tiene productos asociados'
            ], 400);
        }

        // Detectar productos que requieren serie
        $productosConSerie = $orden->productos->filter(function ($producto) {
            return isset($producto->requiere_serie) && $producto->requiere_serie;
        });

        // Si hay productos con serie y NO se enviaron las series, devolver info para el modal
        if ($productosConSerie->isNotEmpty() && !$request->has('series')) {
            return response()->json([
                'success' => false,
                'requiere_series' => true,
                'message' => 'Esta orden contiene productos que requieren registro de series',
                'productos_con_serie' => $productosConSerie->map(function ($producto) {
                    return [
                        'id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'cantidad' => $producto->pivot->cantidad,
                    ];
                })->values()->toArray()
            ], 200);
        }

        // Validación de series entrantes
        $inputSeries = $request->input('series', []);

        // Si hay productos con serie, validar las series enviadas
        if ($productosConSerie->isNotEmpty()) {
            $validatedSeries = $request->validate([
                'series' => 'required|array',
                'series.*.producto_id' => 'required|exists:productos,id',
                'series.*.series' => 'required|array|min:1',
                'series.*.series.*' => 'required|string|distinct',
            ]);

            // Validaciones específicas de cantidad y duplicados del sistema
            foreach ($productosConSerie as $producto) {
                $cantidadRequerida = $producto->pivot->cantidad;
                $seriesEnviadas = collect($inputSeries)->firstWhere('producto_id', $producto->id);

                if (!$seriesEnviadas) {
                    return response()->json(['success' => false, 'error' => "Faltan series para el producto '{$producto->nombre}'"], 400);
                }

                if (count($seriesEnviadas['series']) !== $cantidadRequerida) {
                    return response()->json(['success' => false, 'error' => "El producto '{$producto->nombre}' requiere {$cantidadRequerida} series, pero se enviaron " . count($seriesEnviadas['series'])], 400);
                }

                // Validar existencia en DB
                $existentes = ProductoSerie::whereIn('numero_serie', $seriesEnviadas['series'])
                    ->where('producto_id', $producto->id)
                    ->pluck('numero_serie')
                    ->toArray();

                if (!empty($existentes)) {
                    return response()->json(['success' => false, 'error' => "Las siguientes series ya existen para '{$producto->nombre}': " . implode(', ', $existentes)], 400);
                }
            }
        }

        // Determinar almacén
        $almacenId = $request->input('almacen_id')
            ?? auth()->user()->almacen_compra_id
            ?? $orden->almacen_id;

        if (!$almacenId) {
            return response()->json(['success' => false, 'error' => 'No se pudo determinar el almacén.'], 400);
        }

        $almacen = Almacen::find($almacenId);
        if (!$almacen || $almacen->estado !== 'activo') {
            return response()->json(['success' => false, 'error' => 'El almacén seleccionado no es válido o no está activo'], 400);
        }

        DB::beginTransaction();
        try {
            // Preparar datos para el servicio
            $compraData = [
                'proveedor_id' => $orden->proveedor_id,
                'almacen_id' => $almacenId,
                'orden_compra_id' => $orden->id,
                'fecha_compra' => now(),
                'subtotal' => $orden->subtotal,
                'descuento_general' => $orden->descuento_general,
                'descuento_items' => $orden->descuento_items,
                'iva' => $orden->iva,
                'retencion_iva' => $orden->retencion_iva,
                'retencion_isr' => $orden->retencion_isr,
                'aplicar_retencion_iva' => $orden->aplicar_retencion_iva,
                'aplicar_retencion_isr' => $orden->aplicar_retencion_isr,
                'total' => $orden->total,
                'notas' => "Generada desde Orden de Compra #{$orden->numero_orden}",
                'estado' => 'procesada',
                'inventario_procesado' => true,
                'metodo_pago' => $orden->metodo_pago,
                'cuenta_bancaria_id' => null, // Opcional, se puede pedir en el modal si es necesario
            ];

            // Preparar items
            $itemsData = [];
            foreach ($orden->productos as $producto) {
                if (!$producto->activo)
                    continue;

                $seriesDelProducto = collect($inputSeries)->firstWhere('producto_id', $producto->id)['series'] ?? [];

                $itemsData[] = [
                    'id' => $producto->id,
                    'cantidad' => $producto->pivot->cantidad,
                    'precio' => $producto->pivot->precio,
                    'descuento' => $producto->pivot->descuento ?? 0,
                    'descripcion' => $producto->descripcion,
                    'unidad_medida' => $producto->unidad_medida,
                    'seriales' => $seriesDelProducto
                ];
            }

            if (empty($itemsData)) {
                throw new \Exception("No hay productos activos para procesar en esta orden.");
            }

            // Usar el servicio para crear la compra
            $compra = $this->compraCreationService->createCompra($compraData, $itemsData);

            // Actualizar estado de la orden
            $orden->update(['estado' => 'recibida']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Compra procesada exitosamente',
                'compra_id' => $compra->id,
                'numero_compra' => $compra->numero_compra,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al convertir orden de compra: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al procesar la compra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener el siguiente número de orden de compra disponible
     */
    public function obtenerSiguienteNumero()
    {
        // Usar preview para no quemar folios
        try {
            $siguienteNumero = app(\App\Services\Folio\FolioService::class)->previewNextFolio('orden_compra');
        } catch (\Exception $e) {
            $siguienteNumero = 'OC000';
        }
        return response()->json(['siguiente_numero' => $siguienteNumero]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $orden = OrdenCompra::with(['proveedor', 'productos', 'almacen', 'pedido'])->findOrFail($id);

        return Inertia::render('OrdenesCompra/Show', [
            'ordenCompra' => $orden,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $orden = OrdenCompra::with(['proveedor', 'productos', 'almacen'])->findOrFail($id);

        // Solo permitir editar órdenes en estado pendiente o borrador
        if (!in_array($orden->estado, ['pendiente', 'borrador'])) {
            return redirect()->route('ordenescompra.index')
                ->with('error', 'Solo se pueden editar órdenes en estado pendiente o borrador');
        }

        return Inertia::render('OrdenesCompra/Edit', [
            'ordenCompra' => $orden,
            'proveedores' => Proveedor::select('id', 'nombre_razon_social', 'email', 'telefono')->get(),
            'productos' => Producto::select('id', 'nombre', 'precio_compra', 'precio_venta', 'descripcion')
                ->where('estado', 'activo')->get(), // ✅ Solo productos activos
            'almacenes' => Almacen::select('id', 'nombre')->where('estado', 'activo')->get(), // ✅ Solo almacenes activos
            'defaults' => [
                'ivaPorcentaje' => \App\Services\EmpresaConfiguracionService::getIvaPorcentaje(),
                'enableRetencionIva' => \App\Services\EmpresaConfiguracionService::isRetencionIvaEnabled(),
                'enableRetencionIsr' => \App\Services\EmpresaConfiguracionService::isRetencionIsrEnabled(),
                'retencionIvaDefault' => \App\Services\EmpresaConfiguracionService::getRetencionIvaDefault(),
                'retencionIsrDefault' => \App\Services\EmpresaConfiguracionService::getRetencionIsrDefault(),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $orden = OrdenCompra::findOrFail($id);

        // Solo permitir actualizar órdenes en estado pendiente o borrador
        if (!in_array($orden->estado, ['pendiente', 'borrador'])) {
            return redirect()->back()
                ->with('error', 'Solo se pueden actualizar órdenes en estado pendiente o borrador');
        }

        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'fecha_orden' => 'required|date',
                'fecha_entrega_esperada' => 'nullable|date',
                'prioridad' => 'required|in:baja,media,alta,urgente',
                'proveedor_id' => 'required|exists:proveedores,id',
                'almacen_id' => 'nullable|exists:almacenes,id',
                'direccion_entrega' => 'nullable|string',
                'terminos_pago' => 'required|in:contado,15_dias,30_dias,45_dias,60_dias,90_dias',
                'metodo_pago' => 'required|in:transferencia,cheque,efectivo,tarjeta',
                'subtotal' => 'required|numeric|min:0',
                'descuento_items' => 'required|numeric|min:0',
                'descuento_general' => 'required|numeric|min:0|max:100',
                'iva' => 'required|numeric|min:0',
                'retencion_iva' => 'nullable|numeric|min:0',
                'retencion_isr' => 'nullable|numeric|min:0',
                'aplicar_retencion_iva' => 'boolean',
                'aplicar_retencion_isr' => 'boolean',
                'total' => 'required|numeric|min:0',
                'observaciones' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.id' => 'required|integer|exists:productos,id',
                'items.*.cantidad' => 'required|integer|min:1',
                'items.*.precio' => 'required|numeric|min:0',
                'items.*.descuento' => 'required|numeric|min:0|max:100',
            ]);

            // Recalculate totals on backend
            $fiscalConfig = [
                'aplicar_retencion_iva' => $request->boolean('aplicar_retencion_iva'),
                'aplicar_retencion_isr' => $request->boolean('aplicar_retencion_isr'),
                'mode' => 'purchases'
            ];

            $totales = $this->financialService->calculateDocumentTotals(
                $validated['items'],
                (float) ($validated['descuento_general'] ?? 0),
                null,
                $fiscalConfig
            );

            $orden->update([
                'fecha_orden' => $validated['fecha_orden'],
                'fecha_entrega_esperada' => $validated['fecha_entrega_esperada'] ?? null,
                'prioridad' => $validated['prioridad'],
                'proveedor_id' => $validated['proveedor_id'],
                'almacen_id' => $validated['almacen_id'] ?? null,
                'direccion_entrega' => $validated['direccion_entrega'] ?? null,
                'terminos_pago' => $validated['terminos_pago'],
                'metodo_pago' => $validated['metodo_pago'],
                'subtotal' => $totales['subtotal'],
                'descuento_items' => $totales['descuento_items'],
                'descuento_general' => $totales['descuento_general'],
                'iva' => $totales['iva'],
                'retencion_iva' => $totales['retencion_iva'],
                'retencion_isr' => $totales['retencion_isr'],
                'aplicar_retencion_iva' => $validated['aplicar_retencion_iva'] ?? false,
                'aplicar_retencion_isr' => $validated['aplicar_retencion_isr'] ?? false,
                'total' => $totales['total'],
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            // Sincronizar productos
            $productosSync = [];
            foreach ($validated['items'] as $item) {
                $productosSync[$item['id']] = [
                    'cantidad' => $item['cantidad'],
                    'precio' => $item['precio'],
                    'descuento' => $item['descuento'] ?? 0,
                ];
            }
            $orden->productos()->sync($productosSync);

            DB::commit();
            return redirect()->route('ordenescompra.index')->with('success', 'Orden de compra actualizada exitosamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al actualizar orden de compra', ['msg' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Ocurrió un error al actualizar la orden de compra.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $orden = OrdenCompra::findOrFail($id);

            // Solo permitir eliminar órdenes en estado pendiente, borrador o cancelada
            if (!in_array($orden->estado, ['pendiente', 'borrador', 'cancelada'])) {
                return redirect()->back()
                    ->with('error', 'Solo se pueden eliminar órdenes en estado pendiente, borrador o cancelada');
            }

            // Eliminar productos asociados (la FK tiene CASCADE, pero lo hacemos explícito)
            $orden->productos()->detach();
            $orden->delete();

            return redirect()->route('ordenescompra.index')
                ->with('success', 'Orden de compra eliminada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar orden de compra: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al eliminar la orden de compra.');
        }
    }

    /**
     * Duplicar una orden de compra
     */
    public function duplicate($id)
    {
        DB::beginTransaction();
        try {
            $ordenOriginal = OrdenCompra::with('productos')->findOrFail($id);

            $nuevaOrden = $ordenOriginal->replicate();
            $nuevaOrden->numero_orden = OrdenCompra::getProximoNumero();
            $nuevaOrden->estado = 'pendiente';
            $nuevaOrden->fecha_orden = now();
            $nuevaOrden->fecha_recepcion = null;
            $nuevaOrden->email_enviado = false;
            $nuevaOrden->email_enviado_fecha = null;
            $nuevaOrden->email_enviado_por = null;
            $nuevaOrden->save();

            // Duplicar productos (solo activos)
            foreach ($ordenOriginal->productos as $producto) {
                // ✅ Solo duplicar productos activos
                if (!$producto->activo) {
                    Log::warning("Producto inactivo omitido en duplicación de orden", [
                        'producto_id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'orden_original_id' => $ordenOriginal->id,
                    ]);
                    continue;
                }

                $nuevaOrden->productos()->attach($producto->id, [
                    'cantidad' => $producto->pivot->cantidad,
                    'precio' => $producto->pivot->precio,
                    'descuento' => $producto->pivot->descuento ?? 0,
                ]);
            }

            // Verificar que la orden duplicada tenga al menos un producto
            if ($nuevaOrden->productos()->count() === 0) {
                DB::rollBack();
                return redirect()->back()
                    ->with('error', 'No se pudo duplicar la orden: todos los productos están inactivos');
            }

            DB::commit();

            return redirect()->route('ordenescompra.index')
                ->with('success', "Orden duplicada exitosamente. Nueva orden: {$nuevaOrden->numero_orden}");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al duplicar orden de compra: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al duplicar la orden de compra.');
        }
    }

    /**
     * Cancelar una orden de compra
     */
    public function cancelar($id)
    {
        try {
            $orden = OrdenCompra::findOrFail($id);

            // Solo permitir cancelar órdenes que no estén ya canceladas o procesadas
            if (in_array($orden->estado, ['cancelada', 'recibida', 'procesada'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Esta orden no puede ser cancelada en su estado actual'
                ], 400);
            }

            $orden->update(['estado' => 'cancelada']);

            Log::info("Orden de compra cancelada", [
                'orden_id' => $orden->id,
                'numero_orden' => $orden->numero_orden,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Orden de compra cancelada exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error al cancelar orden de compra: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al cancelar la orden'
            ], 500);
        }
    }

    /**
     * Cambiar el estado de una orden de compra
     */
    public function cambiarEstado(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'estado' => 'required|in:pendiente,aprobada,enviada_a_proveedor,recibida,procesada,cancelada'
            ]);

            $orden = OrdenCompra::findOrFail($id);
            $estadoAnterior = $orden->estado;
            $nuevoEstado = $validated['estado'];

            // ✅ FIX #6: Validar transiciones de estado permitidas
            $transicionesPermitidas = [
                'pendiente' => ['aprobada', 'cancelada'],
                'aprobada' => ['enviada_a_proveedor', 'pendiente', 'cancelada'],
                'enviada_a_proveedor' => ['recibida', 'cancelada'],
                'recibida' => ['procesada'],
                'procesada' => [], // Estado final, no puede cambiar
                'cancelada' => ['pendiente'], // Solo puede reactivarse a pendiente
            ];

            $permitidos = $transicionesPermitidas[$estadoAnterior] ?? [];

            if (!in_array($nuevoEstado, $permitidos) && $estadoAnterior !== $nuevoEstado) {
                return response()->json([
                    'success' => false,
                    'error' => "No se puede cambiar de estado '{$estadoAnterior}' a '{$nuevoEstado}'",
                    'transiciones_permitidas' => $permitidos
                ], 400);
            }

            $orden->update(['estado' => $nuevoEstado]);

            Log::info("Estado de orden de compra cambiado", [
                'orden_id' => $orden->id,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $nuevoEstado,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado exitosamente',
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $nuevoEstado
            ]);
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado de orden: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al cambiar el estado'
            ], 500);
        }
    }

    /**
     * Obtener el estado actual de una orden
     */
    public function getEstado($id)
    {
        try {
            $orden = OrdenCompra::select('id', 'numero_orden', 'estado')->findOrFail($id);
            return response()->json([
                'success' => true,
                'estado' => $orden->estado,
                'numero_orden' => $orden->numero_orden
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Orden no encontrada'
            ], 404);
        }
    }

    /**
     * Enviar orden a compra (cambiar estado a enviada_a_proveedor)
     */
    public function enviarACompra($id)
    {
        try {
            $orden = OrdenCompra::findOrFail($id);

            if (!in_array($orden->estado, ['pendiente', 'aprobada'])) {
                return redirect()->back()
                    ->with('error', 'Solo órdenes pendientes o aprobadas pueden enviarse a proveedor');
            }

            $orden->update(['estado' => 'enviada_a_proveedor']);

            Log::info("Orden de compra enviada a proveedor", [
                'orden_id' => $orden->id,
                'numero_orden' => $orden->numero_orden,
            ]);

            return redirect()->back()
                ->with('success', 'Orden enviada al proveedor exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al enviar orden a proveedor: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al enviar la orden');
        }
    }

    /**
     * Recibir mercancía de una orden de compra
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function recibirMercancia(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        DB::beginTransaction();

        try {
            $orden = OrdenCompra::with(['proveedor', 'productos'])->findOrFail($id);

            // Validar estado de la orden
            if (!in_array($orden->estado, ['enviada_a_proveedor', 'pendiente', 'aprobada'])) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'error' => 'Esta orden no puede recibirse en su estado actual'
                ], 400);
            }

            // Validar que la orden tenga productos
            if ($orden->productos->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'error' => 'La orden no tiene productos asociados'
                ], 400);
            }

            // Procesar productos con series
            $productosConSerie = $orden->productos->filter(fn($p) => $p->requiere_serie);
            $validatedSeries = [];

            if ($productosConSerie->isNotEmpty()) {
                if (!$request->has('series')) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'requiere_series' => true,
                        'message' => 'Esta orden contiene productos que requieren registro de series',
                        'productos_con_serie' => $productosConSerie->map(fn($p) => [
                            'id' => $p->id,
                            'nombre' => $p->nombre,
                            'cantidad' => $p->pivot->cantidad,
                        ])->values()->toArray()
                    ], 200);
                }

                $validatedSeries = $request->validate([
                    'series' => 'required|array',
                    'series.*.producto_id' => 'required|exists:productos,id',
                    'series.*.series' => 'required|array|min:1',
                    'series.*.series.*' => 'required|string|distinct',
                ]);

                // Validar series para cada producto
                foreach ($productosConSerie as $producto) {
                    if (!isset($producto->pivot) || !isset($producto->pivot->cantidad)) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'error' => 'Datos de producto inválidos'
                        ], 400);
                    }

                    $cantidadRequerida = $producto->pivot->cantidad;
                    $seriesEnviadas = collect($validatedSeries['series'])
                        ->firstWhere('producto_id', $producto->id);

                    if (!$seriesEnviadas) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'error' => "Faltan series para el producto '{$producto->nombre}'"
                        ], 400);
                    }

                    $cantidadSeries = count($seriesEnviadas['series']);
                    if ($cantidadSeries !== $cantidadRequerida) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'error' => "El producto '{$producto->nombre}' requiere {$cantidadRequerida} series, pero se enviaron {$cantidadSeries}"
                        ], 400);
                    }

                    // Validar que las series no existan ya
                    foreach ($seriesEnviadas['series'] as $serie) {
                        $existe = ProductoSerie::where('numero_serie', $serie)
                            ->where('producto_id', $producto->id)
                            ->exists();

                        if ($existe) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'error' => "La serie '{$serie}' ya existe para el producto '{$producto->nombre}'"
                            ], 400);
                        }
                    }
                }
            }

            // Validar y obtener almacén
            $almacenId = $request->input('almacen_id')
                ?? auth()->user()->almacen_compra_id
                ?? $orden->almacen_id;

            if (!$almacenId) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'error' => 'No se pudo determinar el almacén para la recepción'
                ], 400);
            }

            $almacen = Almacen::find($almacenId);
            if (!$almacen || $almacen->estado !== 'activo') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'error' => 'El almacén seleccionado no existe o no está activo'
                ], 400);
            }

            // Crear la compra
            $compra = Compra::create([
                'proveedor_id' => $orden->proveedor_id,
                'almacen_id' => $almacenId,
                'orden_compra_id' => $orden->id,
                'fecha_compra' => now(),
                'subtotal' => $orden->subtotal,
                'descuento_general' => $orden->descuento_general,
                'descuento_items' => $orden->descuento_items,
                'iva' => $orden->iva,
                'total' => $orden->total,
                'notas' => "Generada desde Orden de Compra #{$orden->numero_orden}",
                'estado' => 'procesada',
                'inventario_procesado' => true,
            ]);

            // Crear cuenta por pagar
            $this->cuentasPagarService->crearCuentaPorPagar($compra, (float) $orden->total);

            // Procesar productos y actualizar inventario
            $productosProcesados = 0;
            foreach ($orden->productos as $producto) {
                // Validar producto activo y datos completos
                if (!$producto->activo || !isset($producto->pivot) || !isset($producto->pivot->precio)) {
                    Log::warning("Producto inactivo o con datos incompletos omitido en recepción", [
                        'producto_id' => $producto->id,
                        'nombre' => $producto->nombre ?? 'desconocido',
                        'orden_compra_id' => $orden->id,
                    ]);
                    continue;
                }

                $precio = $producto->pivot->precio;
                $cantidad = $producto->pivot->cantidad;

                // Actualizar precio de compra y registrar historial
                if ($producto->precio_compra != $precio) {
                    $precioAnterior = $producto->precio_compra;
                    $producto->update(['precio_compra' => $precio]);

                    ProductoPrecioHistorial::create([
                        'producto_id' => $producto->id,
                        'precio_compra_anterior' => $precioAnterior,
                        'precio_compra_nuevo' => $precio,
                        'precio_venta_anterior' => $producto->precio_venta ?? 0,
                        'precio_venta_nuevo' => $producto->precio_venta ?? 0,
                        'tipo_cambio' => 'compra',
                        'notas' => "Actualización por recepción de Orden #{$orden->numero_orden}",
                        'user_id' => auth()->id(),
                    ]);
                }

                // Crear item de compra
                CompraItem::create([
                    'compra_id' => $compra->id,
                    'comprable_id' => $producto->id,
                    'comprable_type' => Producto::class,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'descuento' => $producto->pivot->descuento ?? 0,
                    'subtotal' => $cantidad * $precio,
                    'descuento_monto' => ($cantidad * $precio) * (($producto->pivot->descuento ?? 0) / 100),
                ]);

                // Procesar entrada de inventario
                $inventarioResult = $this->inventarioService->entrada($producto, $cantidad, [
                    'almacen_id' => $almacenId,
                    'motivo' => 'Recepción de Orden de Compra',
                    'referencia' => $compra,
                    'detalles' => [
                        'orden_compra_id' => $orden->id,
                        'compra_id' => $compra->id,
                    ],
                ]);

                if (!$inventarioResult) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'error' => "Error al procesar inventario para producto {$producto->id}"
                    ], 500);
                }

                // Registrar series si el producto las requiere
                if ($producto->requiere_serie && !empty($validatedSeries['series'])) {
                    $seriesProducto = collect($validatedSeries['series'])
                        ->firstWhere('producto_id', $producto->id);

                    if ($seriesProducto) {
                        foreach ($seriesProducto['series'] as $numeroSerie) {
                            ProductoSerie::create([
                                'producto_id' => $producto->id,
                                'compra_id' => $compra->id,
                                'almacen_id' => $almacenId,
                                'numero_serie' => $numeroSerie,
                                'estado' => 'en_stock',
                            ]);
                        }

                        Log::info("Series registradas para producto {$producto->id} en recepción", [
                            'producto_id' => $producto->id,
                            'compra_id' => $compra->id,
                            'cantidad_series' => count($seriesProducto['series']),
                        ]);
                    }
                }

                $productosProcesados++;
            }

            // Validar que al menos un producto fue procesado
            if ($productosProcesados === 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'error' => 'No se pudo procesar ningún producto de la orden'
                ], 400);
            }

            // Actualizar estado de la orden
            $orden->update([
                'estado' => 'recibida',
                'fecha_recepcion' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Mercancía recibida exitosamente',
                'compra_id' => $compra->id,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al recibir mercancía: ' . $e->getMessage(), [
                'orden_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Error al recibir la mercancía',
                'details' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Marcar orden como urgente
     */
    public function marcarUrgente($id)
    {
        try {
            $orden = OrdenCompra::findOrFail($id);
            $orden->update(['prioridad' => 'urgente']);

            return response()->json([
                'success' => true,
                'message' => 'Orden marcada como urgente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al marcar como urgente'
            ], 500);
        }
    }

    /**
     * Enviar orden por email al proveedor
     */
    public function enviarEmail($id)
    {
        try {
            $orden = OrdenCompra::with(['proveedor', 'productos'])->findOrFail($id);

            if (!$orden->proveedor?->email) {
                return response()->json([
                    'success' => false,
                    'error' => 'El proveedor no tiene email configurado'
                ], 400);
            }

            // ✅ FIX: Verificar rate limit de correos antes de enviar
            $rateLimiter = app(\App\Services\EmailRateLimiterService::class);
            if (!$rateLimiter->canSendEmail()) {
                $stats = $rateLimiter->getStats();
                $waitMinutes = $stats['burst']['reset_in_minutes'];
                return response()->json([
                    'success' => false,
                    'error' => "Límite de correos alcanzado. Espere {$waitMinutes} minutos."
                ], 429);
            }

            // Enviar email usando el Mailable
            \Illuminate\Support\Facades\Mail::to($orden->proveedor->email)
                ->send(new \App\Mail\OrdenCompraMail($orden));

            // ✅ Registrar el envío en el rate limiter
            $rateLimiter->recordEmailSent();

            // Marcar como enviado
            $orden->update([
                'email_enviado' => true,
                'email_enviado_fecha' => now(),
                'email_enviado_por' => auth()->id(),
            ]);

            Log::info("Email de orden de compra enviado exitosamente", [
                'orden_id' => $orden->id,
                'numero_orden' => $orden->numero_orden,
                'proveedor_email' => $orden->proveedor->email,
                'enviado_por' => auth()->user()?->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Orden enviada por email a {$orden->proveedor->email}"
            ]);
        } catch (\Exception $e) {
            Log::error('Error al enviar email de orden: ' . $e->getMessage(), [
                'orden_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Error al enviar el email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Editar precio de un producto en la orden
     */
    public function editarPrecioProducto(Request $request, $ordenId, $productoId)
    {
        try {
            $validated = $request->validate([
                'precio' => 'required|numeric|min:0',
                'cantidad' => 'nullable|integer|min:1',
                'descuento' => 'nullable|numeric|min:0|max:100',
            ]);

            $orden = OrdenCompra::findOrFail($ordenId);

            if (!in_array($orden->estado, ['pendiente', 'borrador'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Solo se pueden editar precios en órdenes pendientes o borrador'
                ], 400);
            }

            $pivotData = ['precio' => $validated['precio']];
            if (isset($validated['cantidad'])) {
                $pivotData['cantidad'] = $validated['cantidad'];
            }
            if (isset($validated['descuento'])) {
                $pivotData['descuento'] = $validated['descuento'];
            }

            // ✅ Validar que el producto exista en la orden
            $productoEnOrden = $orden->productos()->where('producto_id', $productoId)->first();
            if (!$productoEnOrden) {
                return response()->json([
                    'success' => false,
                    'error' => 'El producto no existe en esta orden de compra'
                ], 404);
            }

            $orden->productos()->updateExistingPivot($productoId, $pivotData);

            // Recalcular totales
            $this->recalcularTotales($orden);

            return response()->json([
                'success' => true,
                'message' => 'Precio actualizado exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error al editar precio de producto: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al actualizar el precio'
            ], 500);
        }
    }

    /**
     * Recalcular totales de una orden
     */
    private function recalcularTotales(OrdenCompra $orden)
    {
        $orden->load('productos');

        // Map products to items array for FinancialService
        $items = $orden->productos->map(function ($producto) {
            return [
                'precio' => $producto->pivot->precio,
                'cantidad' => $producto->pivot->cantidad,
                'descuento' => $producto->pivot->descuento ?? 0
            ];
        })->toArray();

        // Prepare config for purchases mode
        $config = [
            'aplicar_retencion_iva' => (bool) $orden->aplicar_retencion_iva,
            'aplicar_retencion_isr' => (bool) $orden->aplicar_retencion_isr,
            'mode' => 'purchases'
        ];

        // Calculate totals using centralized service
        $totales = $this->financialService->calculateDocumentTotals(
            $items,
            (float) $orden->descuento_general,
            null,
            $config
        );

        $orden->update([
            'subtotal' => $totales['subtotal'],
            'descuento_items' => $totales['descuento_items'],
            'iva' => $totales['iva'],
            'retencion_iva' => $totales['retencion_iva'],
            'retencion_isr' => $totales['retencion_isr'],
            'total' => $totales['total'],
        ]);
    }
}

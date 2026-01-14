<?php

namespace App\Http\Controllers;


use App\Enums\EstadoCompra;
use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\Producto;
use App\Models\ProductoSerie;
use App\Models\ProductoPrecioHistorial;
use App\Models\Proveedor;
use App\Models\Almacen;
use App\Models\CuentasPorPagar;
use App\Models\CuentaBancaria;
use App\Services\InventarioService;
use App\Services\Compras\CompraValidacionService;
use App\Services\Compras\CompraSerieService;
use App\Services\Compras\CompraCuentasPagarService;
use App\Services\Compras\CompraInventarioService;
use App\Services\EmpresaConfiguracionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Illuminate\Pagination\LengthAwarePaginator;

class CompraController extends Controller
{
    public function __construct(
        private readonly InventarioService $inventarioService,
        private readonly \App\Services\FinancialService $financialService, // Replaces CompraCalculosService
        private readonly CompraValidacionService $validacionService,
        private readonly CompraSerieService $serieService,
        private readonly CompraCuentasPagarService $cuentasPagarService,
        private readonly CompraInventarioService $compraInventarioService,
        private readonly \App\Services\Compras\CompraCreationService $compraCreationService
    ) {
    }

    public function index(Request $request)
    {
        try {
            [$perPage, $page] = $this->resolvePagination($request);

            $baseQuery = $this->buildBaseCompraQuery();
            $this->applyCompraFilters($baseQuery, $request);
            [$sortBy, $sortDirection, $allowedSorts] = $this->applyCompraSorting($baseQuery, $request);

            $paginator = $this->buildComprasPaginator($baseQuery, $perPage, $page, $request);
            $stats = $this->buildComprasStats();
            $filterOptions = $this->buildCompraFilterOptions();

            /** @var \App\Models\User|null $user */
            $user = Auth::user();
            Log::info('CompraController Index - User: ' . ($user ? $user->id : 'guest'));
            Log::info('CompraController Index - Is Admin: ' . (Auth::check() && $user && $user->hasAnyRole(['admin', 'super-admin']) ? 'YES' : 'NO'));

            return Inertia::render('Compras/Index', [
                'compras' => $paginator,
                'stats' => $stats,
                'filterOptions' => $filterOptions,
                'filters' => $request->only(['search', 'estado', 'origen', 'proveedor_id', 'almacen_id', 'fecha_desde', 'fecha_hasta']),
                'sorting' => [
                    'sort_by' => $sortBy,
                    'sort_direction' => $sortDirection,
                    'allowed_sorts' => $allowedSorts,
                ],
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $perPage,
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
                'is_admin' => Auth::check() && $user && $user->hasAnyRole(['admin', 'super-admin']),
            ]);
        } catch (\Exception $e) {
            Log::error('Error en CompraController@index: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar la lista de compras.');
        }
    }

    private function resolvePagination(Request $request): array
    {
        $perPage = (int) ($request->integer('per_page') ?: 10);
        $page = max(1, (int) $request->get('page', 1));

        $validPerPages = [10, 15, 25, 50, 100];
        if (!in_array($perPage, $validPerPages)) {
            $perPage = 10;
        }

        return [$perPage, $page];
    }

    private function buildBaseCompraQuery()
    {
        return Compra::with([
            'proveedor',
            'compraItems.comprable', // Cargar items con sus productos
            'almacen',
            'ordenCompra',
            'movimientos', // Cargar movimientos para obtener stock histórico
            'cuentasPorPagar', // <--- Añadido para indicadores REP/PUE
        ])->where('tipo', 'inventario');
    }

    private function applyCompraFilters($baseQuery, Request $request): void
    {
        if ($search = trim($request->get('search', ''))) {
            $baseQuery->where(function ($query) use ($search) {
                $query->where('numero_compra', 'ilike', "%{$search}%")
                    ->orWhere('id', 'ilike', "%{$search}%")
                    ->orWhereHas('proveedor', function ($q) use ($search) {
                        $q->where('nombre_razon_social', 'ilike', "%{$search}%")
                            ->orWhere('rfc', 'ilike', "%{$search}%");
                    })
                    ->orWhereHas('compraItems.comprable', function ($q) use ($search) {
                        $q->where('nombre', 'ilike', "%{$search}%")
                            ->orWhere('descripcion', 'ilike', "%{$search}%");
                    });
            });
        }

        if ($request->filled('estado')) {
            $baseQuery->where('estado', $request->estado);
        }

        if ($request->filled('proveedor_id')) {
            $baseQuery->where('proveedor_id', $request->proveedor_id);
        }

        if ($request->filled('almacen_id')) {
            $baseQuery->where('almacen_id', $request->almacen_id);
        }

        if ($request->filled('origen')) {
            if ($request->origen === 'directa') {
                $baseQuery->whereNull('orden_compra_id');
            } elseif ($request->origen === 'orden_compra') {
                $baseQuery->whereNotNull('orden_compra_id');
            }
        }

        if ($request->filled('fecha_desde')) {
            $baseQuery->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $baseQuery->whereDate('created_at', '<=', $request->fecha_hasta);
        }
    }

    private function applyCompraSorting($baseQuery, Request $request): array
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $allowedSorts = ['created_at', 'total', 'estado', 'numero_compra'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $baseQuery->orderBy($sortBy, $sortDirection === 'asc' ? 'asc' : 'desc')
            ->orderBy('id', 'desc');

        return [$sortBy, $sortDirection, $allowedSorts];
    }

    private function buildComprasPaginator($baseQuery, int $perPage, int $page, Request $request): LengthAwarePaginator
    {
        $paginator = $baseQuery->paginate($perPage, ['*'], 'page', $page);
        $compras = collect($paginator->items());
        $transformed = $compras->map(fn($compra) => $this->transformCompra($compra));

        return new LengthAwarePaginator(
            $transformed,
            $paginator->total(),
            $perPage,
            $page,
            ['path' => $request->url(), 'pageName' => 'page']
        );
    }

    private function buildComprasStats(): array
    {
        $comprasInventario = Compra::where('tipo', 'inventario');
        $totalCompras = (clone $comprasInventario)->count();
        $montoTotal = (clone $comprasInventario)->where('estado', '!=', 'cancelada')->sum('total');

        $pendientesPago = CuentasPorPagar::where('estado', '!=', 'pagado')
            ->where('estado', '!=', 'cancelada')
            ->sum('monto_pendiente');

        return [
            'total' => $totalCompras,
            'procesadas' => Compra::where('tipo', 'inventario')->where('estado', EstadoCompra::Procesada->value)->count(),
            'canceladas' => Compra::where('tipo', 'inventario')->where('estado', 'cancelada')->count(),
            'con_orden_compra' => Compra::where('tipo', 'inventario')->whereNotNull('orden_compra_id')->count(),
            'directas' => Compra::where('tipo', 'inventario')->whereNull('orden_compra_id')->count(),
            'monto_total' => (float) $montoTotal,
            'pendientes_pago' => (float) $pendientesPago,
        ];
    }

    private function buildCompraFilterOptions(): array
    {
        $proveedores = Proveedor::select('id', 'nombre_razon_social', 'rfc')
            ->where('activo', true)
            ->orderBy('nombre_razon_social')
            ->get()
            ->mapWithKeys(function ($proveedor) {
                return [$proveedor->id => $proveedor->nombre_razon_social . ' (' . $proveedor->rfc . ')'];
            });

        $almacenes = Almacen::select('id', 'nombre', 'ubicacion')
            ->where('estado', 'activo')
            ->orderBy('nombre')
            ->get()
            ->mapWithKeys(function ($almacen) {
                return [$almacen->id => $almacen->nombre . ($almacen->ubicacion ? ' - ' . $almacen->ubicacion : '')];
            });

        return [
            'estados' => [
                ['value' => '', 'label' => 'Todos los Estados'],
                ['value' => 'procesada', 'label' => 'Procesadas'],
                ['value' => 'cancelada', 'label' => 'Canceladas'],
            ],
            'proveedores' => $proveedores,
            'almacenes' => $almacenes,
            'per_page_options' => [10, 15, 25, 50, 100],
        ];
    }

    private function normalizeCantidad(mixed $cantidad): int
    {
        if (is_string($cantidad)) {
            $cantidad = str_replace(',', '', trim($cantidad));
        }

        if (!is_numeric($cantidad)) {
            throw new \Exception('Cantidad inválida: se esperaba un número.');
        }

        $valor = (float) $cantidad;
        $redondeado = round($valor);

        if (abs($valor - $redondeado) > 0.0001) {
            throw new \Exception('Cantidad inválida: se requiere un entero para compras. Valor recibido: ' . $valor);
        }

        return (int) $redondeado;
    }

    public function create()
    {
        // ✅ FIX: Solo mostrar proveedores activos
        $proveedores = Proveedor::where('activo', true)->orderBy('nombre_razon_social')->get();

        // Obtener productos con informaciï¿½n de stock por almacï¿½n
        $productosBase = Producto::where('estado', 'activo')->get();
        $almacenes = Almacen::where('estado', 'activo')->get();

        $productos = $productosBase->map(function ($producto) use ($almacenes) {
            // Obtener stock disponible en cada almacï¿½n
            $stockPorAlmacen = [];
            foreach ($almacenes as $almacen) {
                $inventario = \App\Models\Inventario::where('producto_id', $producto->id)
                    ->where('almacen_id', $almacen->id)
                    ->first();

                $stockPorAlmacen[$almacen->id] = [
                    'almacen_id' => $almacen->id,
                    'almacen_nombre' => $almacen->nombre,
                    'cantidad' => $inventario ? $inventario->cantidad : 0,
                ];
            }

            return [
                'id' => $producto->id,
                'codigo' => $producto->codigo,
                'nombre' => $producto->nombre,
                'descripcion' => $producto->descripcion,
                'categoria' => $producto->categoria ? [
                    'id' => $producto->categoria->id,
                    'nombre' => $producto->categoria->nombre,
                ] : null,
                'marca' => $producto->marca ? [
                    'id' => $producto->marca->id,
                    'nombre' => $producto->marca->nombre,
                ] : null,
                'precio_compra' => (float) $producto->precio_compra,
                'precio_venta' => (float) $producto->precio_venta,
                'stock_total' => (int) $producto->stock,
                'stock_por_almacen' => $stockPorAlmacen,
                'expires' => (bool) $producto->expires,
                'requiere_serie' => (bool) ($producto->requiere_serie ?? false),
                'unidad_medida' => $producto->unidad_medida,
                'tipo_producto' => $producto->tipo_producto,
                'estado' => $producto->estado,
            ];
        });

        // Obtener almacï¿½n predeterminado del usuario para compras
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (Auth::check() && $user && $user->almacen_compra_id) {
            $almacenUsuario = Almacen::where('id', $user->almacen_compra_id)
                ->where('estado', 'activo')
                ->first();
        }

        // Si el usuario no tiene almacï¿½n predeterminado, usar el principal o el primero activo
        if (!$almacenUsuario) {
            $almacenUsuario = Almacen::where('estado', 'activo')
                ->where(function ($query) {
                    $query->where('nombre', 'Almacï¿½n Principal')
                        ->orWhere('nombre', 'LIKE', '%Principal%');
                })
                ->orderBy('id', 'asc')
                ->first();

            // Si no encuentra almacï¿½n principal, usar el primero activo
            if (!$almacenUsuario) {
                $almacenUsuario = Almacen::where('estado', 'activo')
                    ->orderBy('id', 'asc')
                    ->first();
            }
        }

        return Inertia::render('Compras/Create', [
            'proveedores' => $proveedores,
            'productos' => $productos,
            'almacenes' => $almacenes,
            'almacen_predeterminado' => $almacenUsuario ? $almacenUsuario->id : null,

            'recordatorio_almacen' => $almacenUsuario ? "Almacen Predeterminado - {$almacenUsuario->ubicacion}" : null,
            'defaults' => [
                'enableIsr' => EmpresaConfiguracionService::isIsrEnabled(),
                'enableRetencionIva' => EmpresaConfiguracionService::isRetencionIvaEnabled(),
                'enableRetencionIsr' => EmpresaConfiguracionService::isRetencionIsrEnabled(),
                'ivaPorcentaje' => EmpresaConfiguracionService::getIvaPorcentaje(),
                'isrPorcentaje' => EmpresaConfiguracionService::getIsrPorcentaje(),
                'retencionIvaDefault' => EmpresaConfiguracionService::getRetencionIvaDefault(),
                'retencionIsrDefault' => EmpresaConfiguracionService::getRetencionIsrDefault(),
            ],
        ]);
    }

    private function validateCompraRequest(Request $request)
    {
        $rules = [
            'proveedor_id' => 'required|exists:proveedores,id',
            'almacen_id' => 'required|exists:almacenes,id',  // ✅ FIX #8: REQUIRED, no nullable
            'metodo_pago' => 'required|string',
            'descuento_general' => 'nullable|numeric|min:0',
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio' => 'required|numeric|min:0',
            'productos.*.descuento' => 'nullable|numeric|min:0|max:100',
        ];

        // Agregar validaciï¿½n de lotes para productos que vencen y series por unidad
        foreach ($request->productos ?? [] as $index => $producto) {
            $productoModel = Producto::find($producto['id']);
            if ($productoModel && $productoModel->expires) {
                $rules["productos.{$index}.numero_lote"] = 'required|string|max:100';
                $rules["productos.{$index}.fecha_caducidad"] = 'nullable|date|after:today';
                $rules["productos.{$index}.costo_unitario"] = 'nullable|numeric|min:0';
            }
            if ($productoModel && ($productoModel->requiere_serie ?? false)) {
                $requiredSize = isset($producto['cantidad']) ? max(1, (int) $producto['cantidad']) : 1;
                $rules["productos.{$index}.seriales"] = ['required', 'array', 'size:' . $requiredSize];
                $rules["productos.{$index}.seriales.*"] = 'required|string|max:191|distinct';
            }
        }

        return $request->validate($rules);
    }

    private function validateSeriesUniqueness(array $productos, ?int $compraId = null)
    {
        $errors = [];

        foreach ($productos as $index => $productoData) {
            if (empty($productoData['seriales']) || !is_array($productoData['seriales'])) {
                continue;
            }

            $producto = Producto::find($productoData['id']);
            if (!$producto || !($producto->requiere_serie ?? false)) {
                continue;
            }

            $seriales = array_map('trim', $productoData['seriales']);

            // Check for duplicates in the request
            if (count($seriales) !== count(array_unique($seriales))) {
                $errors[] = [
                    'producto' => $producto->nombre,
                    'serie' => 'Series duplicadas en la solicitud',
                    'compra_existente' => null,
                    'estado' => null,
                ];
                continue; // Skip further checks for this product
            }

            foreach ($seriales as $serie) {
                $query = ProductoSerie::where('numero_serie', $serie)
                    ->where('producto_id', $producto->id);

                // En ediciÃ³n, excluir series de la compra actual
                if ($compraId) {
                    $query->where('compra_id', '!=', $compraId);
                }

                $serieExistente = $query->first();

                if ($serieExistente) {
                    $errors[] = [
                        'producto' => $producto->nombre,
                        'serie' => $serie,
                        'compra_existente' => $serieExistente->compra_id,
                        'estado' => $serieExistente->estado,
                    ];
                }
            }
        }

        return $errors;
    }

    /**
     * Mostrar el formulario de edición de compra
     */
    public function edit($id)
    {
        \Log::info('=== MÉTODO EDIT LLAMADO ===', ['compra_id' => $id, 'user_id' => Auth::id()]);

        $compra = Compra::with([
            'proveedor',
            'almacen',
            'compraItems.comprable'
        ])->findOrFail($id);

        \Log::info('Compra encontrada', [
            'compra_id' => $compra->id,
            'estado' => $compra->estado,
            'estado_enum' => EstadoCompra::Procesada->value
        ]);

        // ✅ FIX: Comparar con ->value del Enum, no con el Enum directamente
        // Permitir edición si está Procesada O Borrador (para completar importaciones)
        if ($compra->estado !== EstadoCompra::Procesada->value && $compra->estado !== EstadoCompra::Borrador->value) {
            \Log::warning('Compra no editable (ni procesada ni borrador)', [
                'estado_actual' => $compra->estado,
                'permitidos' => [EstadoCompra::Procesada->value, EstadoCompra::Borrador->value]
            ]);
            return redirect()->route('compras.index')
                ->with('error', 'Solo se pueden editar compras procesadas o en borrador.');
        }

        \Log::info('Validación de estado OK, continuando con edit');

        // Formatear productos con sus datos de la compra
        $compra->productos = $compra->compraItems->map(function ($item) use ($compra) {
            $producto = $item->comprable;

            if (!$producto) {
                return null;
            }

            // Obtener series de este producto en esta compra
            $seriales = ProductoSerie::where('compra_id', $compra->id)
                ->where('producto_id', $producto->id)
                ->where('estado', 'en_stock')
                ->orderBy('id', 'desc')
                ->pluck('numero_serie')
                ->values()
                ->toArray();

            return [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'descripcion' => $producto->descripcion,
                'cantidad' => $item->cantidad,
                'precio' => $item->precio,
                'descuento' => $item->descuento,
                'subtotal' => $item->subtotal,
                'descuento_monto' => $item->descuento_monto,
                'requiere_serie' => (bool) ($producto->requiere_serie ?? false),
                'seriales' => $seriales,
                'tipo' => 'producto', // Para compatibilidad con frontend
            ];
        })->filter()->values();

        $proveedores = Proveedor::where('activo', true)->orderBy('nombre_razon_social')->get();
        $productos = Producto::where('estado', 'activo')->get();
        $servicios = [];
        $almacenes = Almacen::where('estado', 'activo')->get();

        return Inertia::render('Compras/Edit', [
            'compra' => $compra,
            'proveedores' => $proveedores,
            'productos' => $productos,
            'servicios' => $servicios,
            'almacenes' => $almacenes,

            'defaults' => [
                'enableIsr' => EmpresaConfiguracionService::isIsrEnabled(),
                'enableRetencionIva' => EmpresaConfiguracionService::isRetencionIvaEnabled(),
                'enableRetencionIsr' => EmpresaConfiguracionService::isRetencionIsrEnabled(),
                'ivaPorcentaje' => EmpresaConfiguracionService::getIvaPorcentaje(),
                'isrPorcentaje' => EmpresaConfiguracionService::getIsrPorcentaje(),
                'retencionIvaDefault' => EmpresaConfiguracionService::getRetencionIvaDefault(),
                'retencionIsrDefault' => EmpresaConfiguracionService::getRetencionIsrDefault(),
            ],
        ]);
    }


    public function update(Request $request, $id)
    {
        \Log::info('=== INICIO ACTUALIZACIÓN DE COMPRA ===', [
            'compra_id' => $id,
            'user_id' => Auth::id(),
            'timestamp' => now()->toDateTimeString()
        ]);

        $compra = Compra::with('compraItems.comprable', 'almacen')->findOrFail($id);

        // ✅ FIX: Permitir Borrador para finalizar importaciones
        if ($compra->estado !== EstadoCompra::Procesada->value && $compra->estado !== EstadoCompra::Borrador->value) {
            \Log::warning('Intento de editar compra no válida', [
                'compra_id' => $id,
                'estado' => $compra->estado
            ]);
            return redirect()->back()->with('error', 'Solo se pueden editar compras procesadas o borradores.');
        }

        // Detectar si estamos finalizando un borrador
        $isFinalizingDraft = $compra->estado === EstadoCompra::Borrador->value;

        // ✅ FIX: Manejo especial para compras desde órdenes de compra (si ya está procesada)
        if ($compra->orden_compra_id && $compra->inventario_procesado && !$isFinalizingDraft) {
            \Log::info('Compra desde orden detectada - modo sin inventario', [
                'compra_id' => $id,
                'orden_compra_id' => $compra->orden_compra_id
            ]);

            $validatedData = $this->validacionService->validarRequest($request);

            try {
                DB::transaction(function () use ($compra, $validatedData) {
                    $fiscalConfig = [
                        'aplicar_retencion_iva' => $validatedData['aplicar_retencion_iva'] ?? false,
                        'aplicar_retencion_isr' => $validatedData['aplicar_retencion_isr'] ?? false,
                        'mode' => 'purchases'
                    ];
                    $totales = $this->financialService->calculateDocumentTotals(
                        $validatedData['productos'],
                        (float) ($validatedData['descuento_general'] ?? 0),
                        null, // Clientes logic not used for compras
                        $fiscalConfig
                    );

                    $compra->update([
                        'proveedor_id' => $validatedData['proveedor_id'],
                        'almacen_id' => $validatedData['almacen_id'],
                        'metodo_pago' => $validatedData['metodo_pago'] ?? 'efectivo',
                        'cuenta_bancaria_id' => $validatedData['cuenta_bancaria_id'] ?? null,
                        'subtotal' => $totales['subtotal'],
                        'descuento_items' => $totales['descuento_items'],
                        'descuento_general' => $totales['descuento_general'],
                        'iva' => $totales['iva'],
                        'retencion_iva' => $totales['retencion_iva'],
                        'retencion_isr' => $totales['retencion_isr'],
                        'isr' => $totales['isr'],
                        'aplicar_retencion_iva' => $validatedData['aplicar_retencion_iva'] ?? false,
                        'aplicar_retencion_isr' => $validatedData['aplicar_retencion_isr'] ?? false,
                        'total' => $totales['total'],
                    ]);

                    $this->cuentasPagarService->actualizarCuentaPorPagar($compra, $totales['total']);

                    // Eliminar y recrear items
                    $compra->compraItems()->delete();

                    foreach ($validatedData['productos'] as $productoData) {
                        $producto = Producto::findOrFail($productoData['id']); // ✅ FIX: Cargar producto
                        $cantidad = $productoData['cantidad'];
                        $precio = $productoData['precio'];
                        $descuento = $productoData['descuento'] ?? 0;
                        $subtotal = $cantidad * $precio;
                        $descuentoMonto = $subtotal * ($descuento / 100);
                        $subtotalFinal = $subtotal - $descuentoMonto;

                        CompraItem::create([
                            'compra_id' => $compra->id,
                            'comprable_id' => $productoData['id'],
                            'comprable_type' => Producto::class,
                            'cantidad' => $cantidad,
                            'precio' => $precio,
                            'descuento' => $descuento,
                            'subtotal' => $subtotalFinal,
                            'descuento_monto' => $descuentoMonto,
                            'descripcion' => $productoData['descripcion'] ?? $producto->descripcion, // ✅ FIX: Guardar descripción
                            'unidad_medida' => $productoData['unidad_medida'] ?? $producto->unidad_medida, // ✅ FIX: Guardar unidad
                        ]);
                    }
                });

                \Log::info('Compra desde orden actualizada (sin inventario)', ['compra_id' => $compra->id]);
                return redirect()->route('compras.index')->with('success', 'Compra actualizada exitosamente.');
            } catch (\Exception $e) {
                \Log::error('Error al actualizar compra desde orden', [
                    'compra_id' => $compra->id,
                    'error' => $e->getMessage()
                ]);
                return redirect()->back()->with('error', $e->getMessage())->withInput();
            }
        }

        $validatedData = $this->validacionService->validarRequest($request);

        // Validar series duplicadas ANTES de la transacción (usando servicio)
        $seriesErrors = $this->validacionService->validarSeriesUnicas($validatedData['productos'], $compra->id);
        if (!empty($seriesErrors)) {
            $errorMessage = "Se encontraron series duplicadas:\n";
            foreach ($seriesErrors as $error) {
                $errorMessage .= "- Serie '{$error['serie']}' del producto '{$error['producto']}' ya existe (Compra #{$error['compra_existente']}, Estado: {$error['estado']})\n";
            }
            return redirect()->back()->with('error', $errorMessage)->withInput();
        }

        try {
            DB::transaction(function () use ($compra, $validatedData, $isFinalizingDraft) {
                $cuentaPorPagar = null;
                $metodoPago = null;
                $pagoInmediato = false;

                // Si NO es borrador (es decir, ya fue procesada), hacer validaciones de stock previas
                if (!$isFinalizingDraft) {
                    // Validar que no existan series vendidas (usando servicio)
                    if ($this->serieService->existenSeriesVendidas($compra->id)) {
                        throw new \Exception('No se puede editar la compra porque algunos productos (series) ya han sido vendidos o dados de baja.');
                    }

                    // Contar series en stock por producto y eliminar (usando servicio)
                    $this->serieService->eliminarSeriesEnStock($compra->id);
                }

                // Guardar items antiguos para calcular deltas (solo si no es borrador)
                $oldItems = $compra->compraItems;
                $oldQtyByProduct = [];
                if (!$isFinalizingDraft) {
                    foreach ($oldItems as $item) {
                        $oldQtyByProduct[$item->comprable_id] = $item->cantidad;
                    }
                }

                // ✅ Guardar total anterior para comparar después
                $totalAnterior = (float) $compra->total;
                $cuentaBancariaAnteriorId = $compra->cuenta_bancaria_id;

                // Calcular totales usando servicio
                $fiscalConfig = [
                    'aplicar_retencion_iva' => $validatedData['aplicar_retencion_iva'] ?? false,
                    'aplicar_retencion_isr' => $validatedData['aplicar_retencion_isr'] ?? false,
                    'mode' => 'purchases'
                ];
                $totales = $this->financialService->calculateDocumentTotals(
                    $validatedData['productos'],
                    (float) ($validatedData['descuento_general'] ?? 0),
                    null,
                    $fiscalConfig
                );

                // Actualizar la compra
                $compra->update([
                    'proveedor_id' => $validatedData['proveedor_id'],
                    'almacen_id' => $validatedData['almacen_id'],
                    'metodo_pago' => $validatedData['metodo_pago'] ?? null,
                    'cuenta_bancaria_id' => $validatedData['cuenta_bancaria_id'] ?? null,
                    'subtotal' => $totales['subtotal'],
                    'descuento_items' => $totales['descuento_items'],
                    'descuento_general' => $totales['descuento_general'],
                    'iva' => $totales['iva'],

                    'retencion_iva' => $totales['retencion_iva'],
                    'retencion_isr' => $totales['retencion_isr'],
                    'isr' => $totales['isr'],
                    'aplicar_retencion_iva' => $validatedData['aplicar_retencion_iva'] ?? false,
                    'aplicar_retencion_isr' => $validatedData['aplicar_retencion_isr'] ?? false,
                    'total' => $totales['total'],

                    // Si era borrador, cambia a procesada
                    'estado' => EstadoCompra::Procesada->value,
                    'inventario_procesado' => true,

                    // Campos CFDI
                    'cfdi_uuid' => $validatedData['cfdi_uuid'] ?? $compra->cfdi_uuid,
                    'cfdi_folio' => $validatedData['cfdi_folio'] ?? $compra->cfdi_folio,
                    'cfdi_serie' => $validatedData['cfdi_serie'] ?? $compra->cfdi_serie,
                    'cfdi_fecha' => $validatedData['cfdi_fecha'] ?? $compra->cfdi_fecha,
                    'cfdi_emisor_rfc' => $validatedData['cfdi_emisor_rfc'] ?? $compra->cfdi_emisor_rfc,
                    'cfdi_emisor_nombre' => $validatedData['cfdi_emisor_nombre'] ?? $compra->cfdi_emisor_nombre,
                ]);

                // Si era borrador y no es importación manual de CFDI, asegurar origen
                if ($isFinalizingDraft && !$compra->origen_importacion) {
                    $compra->update(['origen_importacion' => 'manual_con_cfdi']);
                }

                // ✅ Ajustar movimiento bancario si el total cambió
                $nuevoTotal = (float) $totales['total'];
                $nuevaCuentaBancariaId = $validatedData['cuenta_bancaria_id'] ?? null;

                if ($cuentaBancariaAnteriorId || $nuevaCuentaBancariaId) {
                    $diferencia = $nuevoTotal - $totalAnterior;

                    // Si cambió la cuenta bancaria, manejar como dos operaciones
                    if ($cuentaBancariaAnteriorId != $nuevaCuentaBancariaId) {
                        // Devolver a cuenta anterior
                        if ($cuentaBancariaAnteriorId) {
                            $cuentaAnterior = CuentaBancaria::find($cuentaBancariaAnteriorId);
                            if ($cuentaAnterior) {
                                $cuentaAnterior->registrarMovimiento(
                                    'deposito',
                                    $totalAnterior,
                                    "Devolución por cambio de cuenta en compra #{$compra->numero_compra}",
                                    'devolucion'
                                );
                            }
                        }
                        // Descontar de cuenta nueva
                        if ($nuevaCuentaBancariaId) {
                            $cuentaNueva = CuentaBancaria::find($nuevaCuentaBancariaId);
                            if ($cuentaNueva) {
                                if ($cuentaNueva->saldo_actual < $nuevoTotal) {
                                    throw new \Exception("Saldo insuficiente en cuenta {$cuentaNueva->banco}");
                                }
                                $cuentaNueva->registrarMovimiento(
                                    'retiro',
                                    $nuevoTotal,
                                    "Pago de compra #{$compra->numero_compra} (cambio de cuenta)",
                                    'pago'
                                );
                            }
                        }
                    } elseif ($cuentaBancariaAnteriorId && abs($diferencia) > 0.01) {
                        // Misma cuenta, pero total cambió
                        $cuentaBancaria = CuentaBancaria::find($cuentaBancariaAnteriorId);
                        if ($cuentaBancaria) {
                            if ($diferencia > 0) {
                                // El total aumentó - descontar más
                                if ($cuentaBancaria->saldo_actual < $diferencia) {
                                    throw new \Exception("Saldo insuficiente para cubrir diferencia de \${$diferencia}");
                                }
                                $cuentaBancaria->registrarMovimiento(
                                    'retiro',
                                    $diferencia,
                                    "Ajuste por edición de compra #{$compra->numero_compra} (+\${$diferencia})",
                                    'pago'
                                );
                            } else {
                                // El total disminuyó - devolver diferencia
                                $cuentaBancaria->registrarMovimiento(
                                    'deposito',
                                    abs($diferencia),
                                    "Devolución por edición de compra #{$compra->numero_compra} (-\${" . abs($diferencia) . "})",
                                    'devolucion'
                                );
                            }
                        }
                    }
                }

                // Actualizar Cuentas por Pagar (usando servicio)
                $this->cuentasPagarService->actualizarCuentaPorPagar($compra, $totales['total']);

                // Obtener la cuenta por pagar actualizada
                $cuentaPorPagar = CuentasPorPagar::where('compra_id', $compra->id)->first();

                // Eliminar items antiguos (en borrador también los borramos y recreamos completos)
                $compra->compraItems()->delete();

                // Crear items nuevos y agregar al inventario
                foreach ($validatedData['productos'] as $productoData) {
                    $producto = Producto::findOrFail($productoData['id']);

                    $cantidad = $this->normalizeCantidad($productoData['cantidad'] ?? 1);
                    $precio = $productoData['precio'];
                    $descuento = $productoData['descuento'] ?? 0;
                    $subtotal = $cantidad * $precio;
                    $descuentoMonto = $subtotal * ($descuento / 100);
                    $subtotalFinal = $subtotal - $descuentoMonto;

                    // Update product price if different
                    if ($producto->precio_compra != $precio) {
                        $oldPrecioCompra = $producto->precio_compra;
                        $producto->update(['precio_compra' => $precio]);

                        // Log price change
                        ProductoPrecioHistorial::create([
                            'producto_id' => $producto->id,
                            'precio_compra_anterior' => $oldPrecioCompra,
                            'precio_compra_nuevo' => $precio,
                            'precio_venta_anterior' => null,
                            'precio_venta_nuevo' => $producto->precio_venta,
                            'tipo_cambio' => 'compra',
                            'notas' => "Actualización por edición de compra #{$compra->id}",
                            'user_id' => Auth::id(),
                        ]);
                    }

                    // CALCULAR DELTA (Diferencia de inventario)
                    // Si es borrador, el inventario anterior es 0, así que delta = cantidad completa
                    $oldQty = !$isFinalizingDraft ? ($oldQtyByProduct[$producto->id] ?? 0) : 0;
                    $delta = $cantidad - $oldQty;

                    if ($delta > 0) {
                        $this->inventarioService->entrada($producto, $delta, [
                            'skip_transaction' => true, // Evitar transacciones anidadas
                            'motivo' => $isFinalizingDraft ? 'Importación XML Finalizada' : 'Edición de compra: stock actualizado (delta)',
                            'almacen_id' => $validatedData['almacen_id'],
                            'user_id' => Auth::id(),
                            'referencia_type' => 'App\\Models\\Compra',
                            'referencia_id' => $compra->id,
                            'detalles' => [
                                'compra_id' => $compra->id,
                                'producto_id' => $productoData['id'],
                                'precio_unitario' => $precio,
                                'descuento' => $descuento,
                                'delta' => $delta,
                            ],
                        ]);
                    } elseif ($delta < 0) {
                        $this->inventarioService->salida($producto, abs($delta), [
                            'skip_transaction' => true,
                            'motivo' => 'Edición de compra: ajuste por reducción',
                            'almacen_id' => $compra->almacen_id,
                            'user_id' => Auth::id(),
                            'referencia_type' => 'App\\Models\\Compra',
                            'referencia_id' => $compra->id,
                            'detalles' => [
                                'compra_id' => $compra->id,
                                'producto_id' => $productoData['id'],
                                'precio_unitario' => $precio,
                                'descuento' => $descuento,
                                'delta' => $delta,
                            ],
                        ]);
                    }

                    CompraItem::create([
                        'compra_id' => $compra->id,
                        'comprable_id' => $productoData['id'],
                        'comprable_type' => Producto::class,
                        'cantidad' => $cantidad,
                        'precio' => $precio,
                        'descuento' => $descuento,
                        'subtotal' => $subtotalFinal,
                        'descuento_monto' => $descuentoMonto,
                        'descripcion' => $productoData['descripcion'] ?? $producto->descripcion, // ✅ FIX: Guardar descripción
                        'unidad_medida' => $productoData['unidad_medida'] ?? $producto->unidad_medida, // ✅ FIX: Guardar unidad
                    ]);

                    // Registrar series si el producto lo requiere (NUEVAS SERIES)
                    if (($producto->requiere_serie ?? false) && !empty($productoData['seriales']) && is_array($productoData['seriales'])) {
                        foreach ($productoData['seriales'] as $serie) {
                            // VerificaciÃ³n de seguridad adicional (validaciÃ³n principal ya realizada)
                            $serieExistente = ProductoSerie::where('numero_serie', trim((string) $serie))
                                ->where('producto_id', $producto->id)
                                ->first();

                            if (!$serieExistente) {
                                ProductoSerie::create([
                                    'producto_id' => $producto->id,
                                    'compra_id' => $compra->id,
                                    'almacen_id' => $validatedData['almacen_id'],
                                    'numero_serie' => trim((string) $serie),
                                    'estado' => 'en_stock',
                                ]);
                            }
                        }
                    }
                }

                $metodoPago = $validatedData['metodo_pago'] ?? 'efectivo';
                $pagoInmediato = !empty($validatedData['cuenta_bancaria_id']) || $metodoPago === 'efectivo';

                if ($pagoInmediato && $cuentaPorPagar) {
                    $cuentaPorPagar->update([
                        'pagado' => true,
                        'estado' => 'pagado',
                        'metodo_pago' => $metodoPago,
                        'cuenta_bancaria_id' => $validatedData['cuenta_bancaria_id'] ?? null,
                        'fecha_pago' => now(),
                        'pagado_por' => Auth::id(),
                        'notas_pago' => 'Pagado al registrar la compra',
                        'monto_pagado' => $totales['total'],
                        'monto_pendiente' => 0,
                    ]);
                }
            });

            return redirect()->route('compras.index')->with('success', 'Compra actualizada exitosamente.');

        } catch (\Exception $e) {
            \Log::error('Error al actualizar compra: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }


    public function store(Request $request)
    {
        // Validación completa de datos (usando servicio)
        \Log::info('Store Request Data:', $request->all());
        $validatedData = $this->validacionService->validarRequest($request);

        \Log::info('Datos validados correctamente', [
            'productos_count' => count($validatedData['productos']),
            'metodo_pago' => $validatedData['metodo_pago'] ?? 'no especificado'
        ]);

        // Validar series duplicadas ANTES de la transacción (usando servicio)
        $seriesErrors = $this->validacionService->validarSeriesUnicas($validatedData['productos']);
        if (!empty($seriesErrors)) {
            \Log::error('Series duplicadas detectadas', ['errors' => $seriesErrors]);
            $errorMessage = "Se encontraron series duplicadas:\n";
            foreach ($seriesErrors as $error) {
                $errorMessage .= "- Serie '{$error['serie']}' del producto '{$error['producto']}' ya existe (Compra #{$error['compra_existente']}, Estado: {$error['estado']})\n";
            }
            return redirect()->back()->with('error', $errorMessage)->withInput();
        }

        // Validar que todas las series requeridas estén capturadas (usando servicio)
        $seriesValidation = $this->validacionService->validarSeriesRequeridas($validatedData['productos']);
        if (!$seriesValidation['valid']) {
            \Log::error('Series incompletas detectadas', ['errors' => $seriesValidation['errors']]);
            return redirect()->back()->with('error', $seriesValidation['message'])->withInput();
        }

        try {
            // Calcular totales antes de enviar al servicio (para asegurar consistencia)
            $fiscalConfig = [
                'aplicar_retencion_iva' => $validatedData['aplicar_retencion_iva'] ?? false,
                'aplicar_retencion_isr' => $validatedData['aplicar_retencion_isr'] ?? false,
                'mode' => 'purchases'
            ];
            $totales = $this->financialService->calculateDocumentTotals(
                $validatedData['productos'],
                (float) ($validatedData['descuento_general'] ?? 0),
                null,
                $fiscalConfig
            );

            // Merge calculated totals into data
            $creationData = array_merge($validatedData, $totales);

            $compra = $this->compraCreationService->createCompra($creationData, $validatedData['productos']);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Compra creada exitosamente.',
                    'compra' => $compra,
                    'compra_creada' => true,
                    'redirect' => route('compras.index'),
                ]);
            }

            return redirect()->route('compras.index')->with('success', 'Compra creada exitosamente.');

        } catch (\App\Exceptions\SaldoInsuficienteException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error_code' => 'SALDO_INSUFICIENTE',
                    'details' => $e->getDetails()
                ], 422);
            }
            return redirect()->back()->with('error', $e->getMessage())->withInput();

        } catch (\Exception $e) {
            \Log::error('Error al crear compra', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear compra: ' . $e->getMessage(),
                    'error' => $e->getMessage()
                ], 422);
            }

            return redirect()->back()->with('error', 'Error al crear compra: ' . $e->getMessage())->withInput();
        }
    }



    public function destroy($id)
    {
        try {
            $compra = Compra::findOrFail($id);

            // ✅ FIX: Usar solo ->value para comparación consistente
            if ($compra->estado === EstadoCompra::Cancelada->value) {
                $compra->delete();
                return redirect()->route('compras.index')->with('success', 'Compra eliminada correctamente.');
            }

            // Si está procesada, cancelar primero y luego eliminar
            if ($compra->estado === EstadoCompra::Procesada->value) {
                DB::transaction(function () use ($compra) {
                    // 1. Validar Series Vendidas (usando servicio)
                    if ($this->serieService->existenSeriesVendidas($compra->id)) {
                        throw new \Exception('No se puede eliminar la compra porque algunos productos (series) ya han sido vendidos o dados de baja.');
                    }

                    // 2. Validar Stock usando servicio
                    $erroresStock = $this->compraInventarioService->validarStockParaCancelar($compra);
                    if (!empty($erroresStock)) {
                        throw new \Exception("No se puede eliminar: " . implode(", ", $erroresStock));
                    }

                    // 3. Revertir Stock (usando servicio)
                    $this->compraInventarioService->revertirCompra($compra);

                    // 4. Eliminar Series (usando servicio)
                    $this->serieService->eliminarTodasLasSeries($compra->id);

                    // 5. Cancelar Cuentas por Pagar (usando servicio)
                    $this->cuentasPagarService->cancelarCuentaPorPagar($compra);

                    // 6. Cambiar estado a cancelado
                    $compra->update([
                        'estado' => EstadoCompra::Cancelada->value,
                    ]);

                    // 7. Si viene de orden de compra, regresarla a pendiente
                    if ($compra->orden_compra_id) {
                        $ordenCompra = \App\Models\OrdenCompra::find($compra->orden_compra_id);
                        if ($ordenCompra) {
                            $ordenCompra->update([
                                'estado' => 'pendiente',
                                'observaciones' => ($ordenCompra->observaciones ? $ordenCompra->observaciones . "\n\n" : '') .
                                    '*** COMPRA ELIMINADA - ORDEN REGRESADA A PENDIENTE *** ' . now()->format('d/m/Y H:i')
                            ]);
                        }
                    }

                    // 8. Soft delete de la compra
                    $compra->delete();
                });

                return redirect()->route('compras.index')->with('success', 'Compra eliminada exitosamente.');
            }

            // Si no estÃ¡ ni procesada ni cancelada, no permitir eliminar
            return redirect()->back()->with('error', 'No se puede eliminar esta compra en su estado actual.');

        } catch (\Exception $e) {
            \Log::error('Error al eliminar compra: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }


    /**
     * Genera un nÃºmero de compra Ãºnico secuencial.
     */
    /**
     * Obtiene el siguiente nÃºmero de compra para mostrar en el frontend.
     */
    public function obtenerSiguienteNumero()
    {
        $siguienteNumero = $this->compraCreationService->getNextNumeroCompra();
        return response()->json(['siguiente_numero' => $siguienteNumero]);
    }

    /**
     * ✅ FIX: Validar que todas las series requeridas estén capturadas
     */
    private function validateRequiredSeries(array $productos): array
    {
        $errors = [];

        foreach ($productos as $productoData) {
            $producto = Producto::find($productoData['id']);
            if (!$producto || !($producto->requiere_serie ?? false)) {
                continue;
            }

            $cantidad = $productoData['cantidad'];
            $seriales = $productoData['seriales'] ?? [];

            if (!is_array($seriales) || count($seriales) !== $cantidad) {
                $errors[] = "El producto '{$producto->nombre}' requiere exactamente {$cantidad} series, pero se proporcionaron " . count($seriales);
            }

            // Validar que todas las series sean únicas
            $uniqueSerials = array_unique(array_map('trim', $seriales));
            if (count($uniqueSerials) !== count($seriales)) {
                $errors[] = "El producto '{$producto->nombre}' tiene series duplicadas";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'message' => empty($errors) ? '' : "Errores en series:\n" . implode("\n", $errors)
        ];
    }

    /**
     * ✅ FIX: Procesar productos eliminados de la compra
     */
    // Removed processProductItem and processRemovedProducts (legacy/unused)

    /**
     * Calcular totales de la compra
     */


    /**
     * Actualizar cuentas por pagar
     */
    private function updateAccountsPayable(Compra $compra, float $nuevoTotal): void
    {
        $cuentaPorPagar = CuentasPorPagar::where('compra_id', $compra->id)->first();

        if ($cuentaPorPagar) {
            $montoPagado = $cuentaPorPagar->monto_pagado;
            $nuevoPendiente = $nuevoTotal - $montoPagado;

            $cuentaPorPagar->update([
                'monto_total' => $nuevoTotal,
                'monto_pendiente' => $nuevoPendiente,
                'estado' => $nuevoPendiente <= 0 ? 'pagada' : ($montoPagado > 0 ? 'parcial' : 'pendiente'),
            ]);

            \Log::info('Cuenta por pagar actualizada', [
                'cuenta_id' => $cuentaPorPagar->id,
                'monto_total' => $nuevoTotal,
                'monto_pendiente' => $nuevoPendiente
            ]);
        } else {
            // Crear si no existe
            CuentasPorPagar::create([
                'compra_id' => $compra->id,
                'monto_total' => $nuevoTotal,
                'monto_pagado' => 0,
                'monto_pendiente' => $nuevoTotal,
                'fecha_vencimiento' => now()->addDays(30),
                'estado' => 'pendiente',
                'notas' => 'Cuenta regenerada por edición de compra',
            ]);

            \Log::info('Cuenta por pagar creada', ['monto_total' => $nuevoTotal]);
        }
    }


    /**
     * Transformar una instancia de Compra para el listado.
     */
    private function transformCompra(Compra $compra): array
    {
        $items = $compra->compraItems->map(fn($item) => $this->transformCompraItem($item, $compra))
            ->filter()
            ->values();

        $productosTooltip = $items->map(fn($item) => "{$item['nombre']} ({$item['cantidad']} u)")
            ->join(', ');

        return [
            'id' => $compra->id,
            'numero_compra' => $compra->numero_compra ?? 'N/A',
            'proveedor' => $compra->proveedor ? [
                'id' => $compra->proveedor->id,
                'nombre_razon_social' => $compra->proveedor->nombre_razon_social,
                'rfc' => $compra->proveedor->rfc ?? null,
            ] : null,
            'almacen' => $compra->almacen ? [
                'id' => $compra->almacen->id,
                'nombre' => $compra->almacen->nombre,
            ] : null,
            'productos' => $items,
            'productos_count' => $items->count(),
            'productos_tooltip' => $productosTooltip ?: 'Sin productos',
            'subtotal' => (float) ($compra->subtotal ?? 0),
            'descuento_items' => (float) ($compra->descuento_items ?? 0),
            'descuento_general' => (float) ($compra->descuento_general ?? 0),
            'iva' => (float) ($compra->iva ?? 0),
            'total' => (float) ($compra->total ?? 0),
            'estado' => $compra->estado ?? 'procesada',
            'origen' => $compra->orden_compra_id ? 'orden_compra' : 'directa',
            'orden_compra' => $compra->ordenCompra ? [
                'id' => $compra->ordenCompra->id,
                'numero_orden' => $compra->ordenCompra->numero_orden,
                'estado' => $compra->ordenCompra->estado,
            ] : null,
            'created_at' => optional($compra->created_at)->format('Y-m-d H:i:s'),
            'fecha_registro' => optional($compra->created_at)->format('Y-m-d'),
            'fecha' => optional($compra->fecha_compra ?: $compra->created_at)->format('Y-m-d'),
            'fecha_compra' => optional($compra->fecha_compra ?: $compra->created_at)->format('Y-m-d'),

            // Campos CFDI
            'cfdi_uuid' => $compra->cfdi_uuid,
            'cfdi_folio' => $compra->cfdi_folio,
            'cfdi_serie' => $compra->cfdi_serie,
            'cfdi_fecha' => $compra->cfdi_fecha,
            'cfdi_emisor_rfc' => $compra->cfdi_emisor_rfc,
            'cfdi_emisor_nombre' => $compra->cfdi_emisor_nombre,
            'origen_importacion' => $compra->origen_importacion,
            'pagado_con_rep' => (bool) ($compra->cuentasPorPagar->pagado_con_rep ?? false),
            'pue_pagado' => (bool) ($compra->cuentasPorPagar->pue_pagado ?? false),
            'estatus_pago' => $compra->cuentasPorPagar->estado ?? 'pendiente',
            'rep_info' => ($compra->cuentasPorPagar->pagado_con_rep ?? false) && $compra->cfdi_uuid
                ? $this->findRepInfo($compra->cfdi_uuid)
                : null,
        ];
    }

    /**
     * Buscar información del REP asociado a una factura
     */
    private function findRepInfo(string $facturaUuid): ?array
    {
        // Buscar CFDI de pago que menciona este UUID en sus complementos
        // Usamos whereRaw con ILIKE para buscar en el JSON text asumiendo Postgres o compatibilidad string
        $rep = \App\Models\Cfdi::recibidos()
            ->tipoComprobante('P')
            ->where('estatus', '!=', 'cancelado')
            ->whereRaw("CAST(complementos AS TEXT) ILIKE ?", ['%' . $facturaUuid . '%'])
            ->latest('fecha_emision')
            ->first();

        if ($rep) {
            return [
                'uuid' => $rep->uuid,
                'serie_folio' => ($rep->serie ?? '') . ($rep->folio ?? ''),
                'emisor' => $rep->nombre_emisor ?? 'Desconocido',
                'fecha' => $rep->fecha_emision?->format('Y-m-d')
            ];
        }

        return null;
    }

    /**
     * Transformar un item de compra para el resumen.
     */
    private function transformCompraItem($item, Compra $compra): ?array
    {
        if (!$item->comprable) {
            return null;
        }

        $producto = $item->comprable;

        // Buscar el movimiento de entrada correspondiente
        $movimiento = $compra->movimientos
            ->where('producto_id', $producto->id)
            ->where('tipo', 'entrada')
            ->first();

        $stockActual = $producto->stock ?? 0;
        $cantidadComprada = $item->cantidad;

        if ($movimiento) {
            $stockAntes = $movimiento->stock_anterior;
        } else {
            $stockAntes = $compra->estado === EstadoCompra::Cancelada
                ? $stockActual + $cantidadComprada
                : $stockActual - $cantidadComprada;
        }

        return array_merge($producto->toArray(), [
            'cantidad' => $item->cantidad,
            'precio' => $item->precio,
            'descuento' => $item->descuento,
            'subtotal' => $item->subtotal,
            'descuento_monto' => $item->descuento_monto,
            'stock_antes' => (float) max(0, $stockAntes),
            'stock_despues' => (float) $stockActual,
            'diferencia_stock' => (float) ($stockActual - $stockAntes),
        ]);
    }

}

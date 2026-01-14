<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCotizacion;
use App\Enums\EstadoPedido;
use App\Enums\EstadoVenta;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Venta;
use App\Models\VentaItem;
use App\Models\CuentasPorCobrar;
use App\Services\InventarioService;
use App\Services\MarginService;
use App\Services\PrecioService;
use App\Models\SatEstado;
use App\Models\SatRegimenFiscal;
use App\Models\SatUsoCfdi;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PedidoController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly InventarioService $inventarioService,
        private readonly PrecioService $precioService,
        private readonly \App\Services\Folio\FolioService $folioService,
        private readonly \App\Services\FinancialService $financialService
    ) {
        $this->authorizeResource(Pedido::class);
    }

    // ... (rest of methods)

    // In store():
    // Replace $numero_pedido = $this->generarNumeroPedido();
    // with $numero_pedido = $this->folioService->getNextFolio('pedido');

    // In obtenerSiguienteNumero():
    // Replace $siguienteNumero = $this->generarNumeroPedido();
    // with $siguienteNumero = $this->folioService->getNextFolio('pedido');

    // Remove private function generarNumeroPedido()

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = (int) ($request->integer('per_page') ?: 10);

        // Validar elementos por página
        $validPerPages = [10, 15, 25, 50, 100];
        if (!in_array($perPage, $validPerPages)) {
            $perPage = 10;
        }

        $baseQuery = Pedido::with([
            'cliente',
            // Filtrar ítems a tipos conocidos para evitar fallos por tipos inválidos
            'items' => function ($q) {
                $q->whereIn('pedible_type', [Producto::class, Servicio::class]);
            },
            'items.pedible',
            'createdBy',
            'updatedBy',
            'deletedBy',
            'emailEnviadoPor'
        ]);

        // Aplicar filtros
        if ($search = trim($request->get('search', ''))) {
            $baseQuery->where(function ($query) use ($search) {
                $query->where('numero_pedido', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhereHas('cliente', function ($q) use ($search) {
                        $q->where('nombre_razon_social', 'like', "%{$search}%")
                            ->orWhere('rfc', 'like', "%{$search}%");
                    })
                    ->orWhereHas('items.pedible', function ($q) use ($search) {
                        $q->where('nombre', 'like', "%{$search}%")
                            ->orWhere('descripcion', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('estado')) {
            $baseQuery->where('estado', $request->estado);
        }

        if ($request->filled('cliente_id')) {
            $baseQuery->where('cliente_id', $request->cliente_id);
        }

        if ($request->filled('fecha_desde')) {
            $baseQuery->whereDate('fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $baseQuery->whereDate('fecha', '<=', $request->fecha_hasta);
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $allowedSorts = ['created_at', 'fecha', 'total', 'estado', 'numero_pedido'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $baseQuery->orderBy($sortBy, $sortDirection === 'asc' ? 'asc' : 'desc')
            ->orderBy('id', 'desc');

        $paginator = $baseQuery->paginate($perPage)->appends($request->query());
        $pedidos = collect($paginator->items());

        $transformed = $pedidos->filter(function ($pedido) {
            // Filtrar pedidos con cliente y al menos un item válido
            return $pedido->cliente !== null && $pedido->items->isNotEmpty();
        })->map(function ($pedido) {
            $items = $pedido->items->map(function ($item) {
                $pedible = $item->pedible;

                // Usar nombre almacenado directamente, con fallback a la relación pedible
                $nombre = $item->nombre ?? $pedible?->nombre ?? 'Sin nombre';

                // Usar tipo_item almacenado, con fallback a determinación por tipo de clase
                $tipo = $item->tipo_item ?? (
                    ($item->pedible_type === Producto::class || $item->pedible_type === 'producto') ? 'producto' : 'servicio'
                );

                return [
                    'id' => $pedible?->id ?? $item->pedible_id,
                    'nombre' => $nombre,
                    'tipo' => $tipo,
                    'cantidad' => $item->cantidad,
                    'precio' => $item->precio,
                    'descuento' => $item->descuento ?? 0,
                ];
            });

            $createdAtIso = optional($pedido->created_at)->toIso8601String();
            $updatedAtIso = optional($pedido->updated_at)->toIso8601String();

            return [
                'id' => $pedido->id,
                'fecha' => $pedido->fecha ? $pedido->fecha->format('Y-m-d') : $pedido->created_at->format('Y-m-d'),
                'created_at' => $createdAtIso,
                'updated_at' => $updatedAtIso,
                'cliente' => [
                    'id' => $pedido->cliente->id,
                    'nombre' => $pedido->cliente->nombre_razon_social ?? 'Sin nombre',
                    'email' => $pedido->cliente->email,
                    'telefono' => $pedido->cliente->telefono,
                    'rfc' => $pedido->cliente->rfc,
                    'regimen_fiscal' => $pedido->cliente->regimen_fiscal,
                    'uso_cfdi' => $pedido->cliente->uso_cfdi,
                    'calle' => $pedido->cliente->calle,
                    'numero_exterior' => $pedido->cliente->numero_exterior,
                    'numero_interior' => $pedido->cliente->numero_interior,
                    'colonia' => $pedido->cliente->colonia,
                    'codigo_postal' => $pedido->cliente->codigo_postal,
                    'municipio' => $pedido->cliente->municipio,
                    'estado' => $pedido->cliente->estado,
                    'pais' => $pedido->cliente->pais,
                ],
                'productos' => $items->toArray(),
                'subtotal' => $pedido->subtotal,
                'descuento_general' => $pedido->descuento_general,
                'iva' => $pedido->iva,
                'total' => $pedido->total,
                'estado' => $pedido->estado->value,
                'numero_pedido' => $pedido->numero_pedido,
                'cotizacion_id' => $pedido->cotizacion_id,

                // Información de email
                'email_enviado' => (bool) ($pedido->email_enviado ?? false),
                'email_enviado_fecha' => $pedido->email_enviado_fecha?->format('d/m/Y H:i'),
                'email_enviado_por' => $pedido->emailEnviadoPor?->name,

                // Auditoría
                'creado_por_nombre' => $pedido->createdBy?->name,
                'actualizado_por_nombre' => $pedido->updatedBy?->name,
                'eliminado_por_nombre' => $pedido->deletedBy?->name,

                // Redundancia segura para el modal
                'metadata' => [
                    'creado_por' => $pedido->createdBy?->name,
                    'actualizado_por' => $pedido->updatedBy?->name,
                    'eliminado_por' => $pedido->deletedBy?->name,
                    'creado_en' => $createdAtIso,
                    'actualizado_en' => $updatedAtIso,
                    'eliminado_en' => optional($pedido->deleted_at)->toIso8601String(),
                ],
            ];
        });

        // Estadísticas para el dashboard
        $stats = [
            'total' => Pedido::count(),
            'borradores' => Pedido::where('estado', EstadoPedido::Borrador)->count(),
            'pendientes' => Pedido::where('estado', EstadoPedido::Pendiente)->count(),
            'confirmados' => Pedido::where('estado', EstadoPedido::Confirmado)->count(),
            'enviados_venta' => Pedido::where('estado', EstadoPedido::EnviadoVenta)->count(),
            'cancelados' => Pedido::where('estado', EstadoPedido::Cancelado)->count(),
            'con_cotizacion' => Pedido::whereNotNull('cotizacion_id')->count(),
            'sin_cotizacion' => Pedido::whereNull('cotizacion_id')->count(),
        ];

        // Opciones para filtros
        $clientes = Cliente::select('id', 'nombre_razon_social', 'rfc')
            ->orderBy('nombre_razon_social')
            ->get()
            ->mapWithKeys(function ($cliente) {
                return [$cliente->id => $cliente->nombre_razon_social . ' (' . $cliente->rfc . ')'];
            });

        $filterOptions = [
            'estados' => collect(EstadoPedido::cases())->map(fn($estado) => [
                'value' => $estado->value,
                'label' => $estado->label(),
                'color' => $estado->color()
            ])->toArray(),
            'clientes' => $clientes,
            'per_page_options' => [10, 15, 25, 50, 100],
        ];

        return Inertia::render('Pedidos/Index', [
            'pedidos' => $paginator,
            'stats' => $stats,
            'filterOptions' => $filterOptions,
            'filters' => $request->only(['search', 'estado', 'cliente_id', 'fecha_desde', 'fecha_hasta']),
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
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Pedidos/Create', [
            'clientes' => Cliente::activos()
                ->select('id', 'nombre_razon_social', 'email', 'telefono', 'price_list_id')
                ->with('priceList:id,nombre,clave')  // ✅ Optimización: Evitar N+1 queries
                ->get(),
            'productos' => Producto::with(['categoria:id,nombre', 'inventarios', 'precios'])  // ✅ Agregar 'precios'
                ->select('id', 'nombre', 'codigo', 'categoria_id', 'precio_venta', 'descripcion', 'estado', 'tipo_producto')
                ->active()
                ->get()
                ->map(function ($producto) {
                    $stockTotal = $producto->stock_total ?? 0;
                    $stockDisponible = $producto->stock_disponible ?? 0;
                    $stockReservado = (int) $producto->reservado;

                    return [
                        'id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'codigo' => $producto->codigo ?? 'SIN-CODIGO-' . $producto->id,
                        'categoria' => $producto->categoria?->nombre ?? 'Sin categoría',
                        'categoria_id' => $producto->categoria_id,
                        'precio_venta' => (float) $producto->precio_venta,
                        'precios_listas' => $producto->precios->mapWithKeys(function ($precio) {
                            return [$precio->price_list_id => (float) $precio->precio];
                        }),  // ✅ Precios por lista para resolución en frontend
                        'descripcion' => $producto->descripcion,
                        'estado' => $producto->estado,
                        'tipo_producto' => $producto->tipo_producto,
                        'stock_total' => $stockTotal,
                        'stock_disponible' => $stockDisponible,
                        'stock_reservado' => $stockReservado,
                    ];
                }),
            'servicios' => Servicio::select('id', 'nombre', 'precio', 'descripcion')->get(),
            'priceLists' => \App\Models\PriceList::activas()->select('id', 'nombre')->get(),
            'catalogs' => [
                'tiposPersona' => [
                    ['value' => 'fisica', 'text' => 'Persona Física'],
                    ['value' => 'moral', 'text' => 'Persona Moral'],
                ],
                'estados' => SatEstado::orderBy('nombre')
                    ->get(['clave', 'nombre'])
                    ->map(function ($estado) {
                        return [
                            'value' => $estado->clave,
                            'text' => $estado->clave . ' — ' . $estado->nombre
                        ];
                    })
                    ->toArray(),
                'regimenesFiscales' => SatRegimenFiscal::orderBy('clave')
                    ->get(['clave', 'descripcion', 'persona_fisica', 'persona_moral'])
                    ->toArray(),
                'usosCFDI' => SatUsoCfdi::orderBy('clave')
                    ->get(['clave', 'descripcion'])
                    ->map(function ($uso) {
                        return [
                            'value' => $uso->clave,
                            'text' => $uso->clave . ' — ' . $uso->descripcion
                        ];
                    })
                    ->toArray(),
            ],
            'defaults' => [
                'fecha' => now()->format('Y-m-d'),
                'moneda' => 'MXN',
                'enableIsr' => \App\Services\EmpresaConfiguracionService::isIsrEnabled(),
                'enableRetencionIva' => \App\Services\EmpresaConfiguracionService::isRetencionIvaEnabled(),
                'enableRetencionIsr' => \App\Services\EmpresaConfiguracionService::isRetencionIsrEnabled(),
                'ivaPorcentaje' => \App\Services\EmpresaConfiguracionService::getIvaPorcentaje(),
                'isrPorcentaje' => \App\Services\EmpresaConfiguracionService::getIsrPorcentaje(),
                'retencionIvaDefault' => \App\Services\EmpresaConfiguracionService::getRetencionIvaDefault(),
                'retencionIsrDefault' => \App\Services\EmpresaConfiguracionService::getRetencionIsrDefault(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'price_list_id' => 'nullable|exists:price_lists,id',
            'productos' => 'required|array',
            'productos.*.id' => 'required|integer',
            'productos.*.tipo' => 'required|in:producto,servicio',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio' => 'required|numeric|min:0',
            'productos.*.descuento' => 'required|numeric|min:0|max:100',
            'descuento_general' => 'nullable|numeric|min:0|max:100',
            'notas' => 'nullable|string',
            'ajustar_margen' => 'nullable|boolean',
            'aplicar_retencion_iva' => 'boolean',
            'aplicar_retencion_isr' => 'boolean',
        ]);

        // Validar márgenes de ganancia
        $marginService = new MarginService();
        $validacionMargen = $marginService->validarMargenesProductos($validated['productos']);

        if (!$validacionMargen['todos_validos']) {
            // Si hay productos con margen insuficiente, verificar si el usuario aceptó el ajuste
            if ($request->has('ajustar_margen') && $request->ajustar_margen === 'true') {
                // Ajustar precios automáticamente
                foreach ($validated['productos'] as &$item) {
                    if ($item['tipo'] === 'producto') {
                        $producto = Producto::find($item['id']);
                        if ($producto) {
                            $item['precio'] = $marginService->ajustarPrecioAlMargen($producto, $item['precio']);
                        }
                    }
                }
            } else {
                // Mostrar advertencia y permitir al usuario decidir
                $mensaje = $marginService->generarMensajeAdvertencia($validacionMargen['productos_bajo_margen']);
                return redirect()->back()
                    ->withInput()
                    ->with('warning', $mensaje)
                    ->with('requiere_confirmacion_margen', true)
                    ->with('productos_bajo_margen', $validacionMargen['productos_bajo_margen']);
            }
        }

        return DB::transaction(function () use ($validated, $request, $marginService) {
            // Obtener cliente para resolver precios
            $cliente = Cliente::find($validated['cliente_id']);

            // Optimización N+1: Cargar todos los modelos necesarios en una sola consulta
            $productIds = [];
            $serviceIds = [];

            foreach ($validated['productos'] as $prod) {
                if ($prod['tipo'] === 'producto') {
                    $productIds[] = $prod['id'];
                } else {
                    $serviceIds[] = $prod['id'];
                }
            }

            $productosColeccion = Producto::whereIn('id', $productIds)->get()->keyBy('id');
            $serviciosColeccion = Servicio::whereIn('id', $serviceIds)->get()->keyBy('id');

            // Procesar ítems y calcular totales con precios reales
            $subtotal = 0;
            $descuentoItems = 0;
            $itemData = [];

            foreach ($validated['productos'] as $item) {
                $class = $item['tipo'] === 'producto' ? Producto::class : Servicio::class;

                // Obtener modelo de memoria en lugar de consulta DB
                if ($item['tipo'] === 'producto') {
                    $modelo = $productosColeccion->get($item['id']);
                } else {
                    $modelo = $serviciosColeccion->get($item['id']);
                }

                if (!$modelo) {
                    Log::warning("Ítem no encontrado al crear pedido", [
                        'tipo' => $class,
                        'id' => $item['id']
                    ]);
                    continue;
                }

                $cantidad = (float) ($item['cantidad'] ?? 0);

                // ✅ FIX P0-2: Respetar precio del formulario si está presente
                if (isset($item['precio']) && $item['precio'] !== null) {
                    // Usuario especificó precio manualmente, respetarlo
                    $precio = (float) $item['precio'];
                    $priceListId = $item['price_list_id'] ?? ($validated['price_list_id'] ?? null);
                } else if ($item['tipo'] === 'producto') {
                    // No hay precio, resolver dinámicamente usando PrecioService
                    $detallesPrecio = $this->precioService->resolverPrecioConDetalles(
                        $modelo,
                        $cliente,
                        $validated['price_list_id'] ? \App\Models\PriceList::find($validated['price_list_id']) : null
                    );
                    $precio = $detallesPrecio['precio'];
                    $priceListId = $detallesPrecio['price_list_id'];
                } else {
                    // Para servicios, usar precio enviado desde frontend
                    $precio = (float) ($item['precio'] ?? 0);
                    $priceListId = null; // Servicios no usan listas de precios
                }

                $cantidad = (float) ($item['cantidad'] ?? 0);
                $descuento = (float) ($item['descuento'] ?? 0);

                // Use FinancialService for items
                $itemTotals = $this->financialService->calculateItemTotals($cantidad, $precio, $descuento);
                $subtotalItem = $itemTotals['subtotal_final'];
                $descuentoMontoItem = $itemTotals['descuento_monto'];

                // Guardar datos para crear ítems después
                $itemData[] = [
                    'class' => $class,
                    'modelo' => $modelo,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'descuento' => $descuento,
                    'subtotal' => $subtotalItem,
                    'descuento_monto' => $descuentoMontoItem,
                    'price_list_id' => $priceListId,
                    'item_id' => $item['id']
                ];
            }

            // Calcular totales finales usando FinancialService
            $totales = $this->financialService->calculateDocumentTotals(
                $itemData,
                (float) ($request->descuento_general ?? 0),
                $validated['cliente_id'],
                [
                    'aplicar_retencion_iva' => $request->boolean('aplicar_retencion_iva'),
                    'aplicar_retencion_isr' => $request->boolean('aplicar_retencion_isr'),
                    'mode' => 'sales'
                ]
            );

            // Generar número de pedido usando el servicio centralizado
            $numero_pedido = $this->folioService->getNextFolio('pedido');

            // Crear pedido con totales correctos
            $pedido = Pedido::create([
                'cliente_id' => $validated['cliente_id'],
                'cotizacion_id' => null, // Puede llenarse si se crea desde una cotización
                'numero_pedido' => $numero_pedido,
                'subtotal' => $totales['subtotal'],
                'descuento_general' => $totales['descuento_general'],
                'descuento_items' => $totales['descuento_items'], // Should be sum of item discounts
                'iva' => $totales['iva'],
                'retencion_iva' => $totales['retencion_iva'],
                'retencion_isr' => $totales['retencion_isr'],
                'isr' => $totales['isr'],
                'total' => $totales['total'],
                'fecha' => now(),
                'estado' => EstadoPedido::Borrador,
                'notas' => $request->notas,
            ]);

            // Crear ítems con precios resueltos
            foreach ($itemData as $data) {
                PedidoItem::create([
                    'pedido_id' => $pedido->id,
                    'pedible_id' => $data['item_id'],
                    'pedible_type' => $data['class'],
                    'cantidad' => (int) $data['cantidad'],
                    'precio' => round($data['precio'], 2),
                    'descuento' => round($data['descuento'], 2),
                    'subtotal' => round($data['subtotal'], 2),
                    'descuento_monto' => round($data['descuento_monto'], 2),
                    'price_list_id' => $data['price_list_id'],
                ]);

                Log::info("Ítem agregado a pedido exitosamente", [
                    'pedido_id' => $pedido->id,
                    'tipo' => $data['class'],
                    'id' => $data['item_id'],
                    'nombre' => $data['modelo']->nombre,
                    'categoria' => $data['class'] === Producto::class ? $data['modelo']->categoria?->nombre : 'Servicio',
                    'cantidad' => $data['cantidad'],
                    'precio' => $data['precio'],
                    'price_list_id' => $data['price_list_id']
                ]);
            }

            return redirect()->route('pedidos.index')->with('success', 'Pedido creado con éxito');
        });
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $pedido = Pedido::with([
            'cliente',
            'items' => function ($q) {
                $q->whereIn('pedible_type', [Producto::class, Servicio::class]);
            },
            'items.pedible'
        ])->findOrFail($id);

        $items = $pedido->items->map(function ($item) {
            $pedible = $item->pedible;

            // Usar nombre almacenado directamente, con fallback a la relación pedible
            $nombre = $item->nombre ?? $pedible?->nombre ?? $pedible?->descripcion ?? 'Sin nombre';

            // Usar tipo_item almacenado, con fallback a determinación por tipo de clase
            $tipo = $item->tipo_item ?? (
                ($item->pedible_type === Producto::class || $item->pedible_type === 'producto') ? 'producto' : 'servicio'
            );

            return [
                'id' => $pedible?->id ?? $item->pedible_id,
                'nombre' => $nombre,
                'tipo' => $tipo,
                'pivot' => [
                    'cantidad' => $item->cantidad,
                    'precio' => $item->precio,
                    'descuento' => $item->descuento,
                ],
            ];
        });

        return Inertia::render('Pedidos/Show', [
            'pedido' => [
                'id' => $pedido->id,
                'cliente' => $pedido->cliente,
                'productos' => $items,
                'subtotal' => $pedido->subtotal,
                'descuento_general' => $pedido->descuento_general,
                'iva' => $pedido->iva,
                'isr' => $pedido->isr ?? 0,
                'total' => $pedido->total,
                'fecha' => $pedido->fecha ? $pedido->fecha->format('Y-m-d') : $pedido->created_at->format('Y-m-d'),
                'notas' => $pedido->notas,
                'estado' => $pedido->estado->value,
                'numero_pedido' => $pedido->numero_pedido,
                'cotizacion_id' => $pedido->cotizacion_id,
            ],
            'canEdit' => $pedido->estado === EstadoPedido::Borrador || $pedido->estado === EstadoPedido::Pendiente,
            'canDelete' => $pedido->estado === EstadoPedido::Borrador || $pedido->estado === EstadoPedido::Pendiente,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $pedido = Pedido::with(['cliente', 'items.pedible'])->findOrFail($id);

        // Permitir edición solo si está en Borrador o Pendiente
        if (!in_array($pedido->estado, [EstadoPedido::Borrador, EstadoPedido::Pendiente], true)) {
            return Redirect::route('pedidos.show', $pedido->id)
                ->with('warning', 'Solo pedidos en borrador o pendientes pueden ser editados');
        }

        $items = $pedido->items->map(function ($item) {
            $pedible = $item->pedible;
            return [
                'id' => $pedible->id,
                'nombre' => $pedible->nombre ?? $pedible->descripcion,
                'tipo' => ($item->pedible_type === Producto::class || $item->pedible_type === 'producto') ? 'producto' : 'servicio',
                'pivot' => [
                    'cantidad' => $item->cantidad,
                    'precio' => $item->precio,
                    'descuento' => $item->descuento,
                ],
            ];
        });

        return Inertia::render('Pedidos/Edit', [
            'pedido' => [
                'id' => $pedido->id,
                'cliente_id' => $pedido->cliente_id,
                'price_list_id' => $pedido->price_list_id,  // ✅ Agregar price_list_id
                'cliente' => $pedido->cliente,
                'productos' => $items,
                'subtotal' => $pedido->subtotal,
                'descuento_general' => $pedido->descuento_general,
                'iva' => $pedido->iva,
                'total' => $pedido->total,
                'retencion_iva' => $pedido->retencion_iva ?? 0,
                'retencion_isr' => $pedido->retencion_isr ?? 0,
                'isr' => $pedido->isr ?? 0,
                'fecha' => $pedido->fecha ? $pedido->fecha->format('Y-m-d') : $pedido->created_at->format('Y-m-d'),
                'notas' => $pedido->notas,
                'numero_pedido' => $pedido->numero_pedido,
                'cotizacion_id' => $pedido->cotizacion_id,
                'informacion_general' => [
                    'numero' => [
                        'label' => 'Número de Pedido',
                        'value' => $pedido->numero_pedido,
                        'tipo' => 'fijo',
                        'descripcion' => 'Este número es fijo para todas los pedidos'
                    ],
                    'fecha' => [
                        'label' => 'Fecha de Pedido',
                        'value' => $pedido->fecha ? $pedido->fecha->format('d/m/Y') : now()->format('d/m/Y'),
                        'tipo' => 'automatica',
                        'descripcion' => 'Esta fecha se establece automáticamente con la fecha de creación'
                    ]
                ]
            ],
            'clientes' => Cliente::activos()
                ->select('id', 'nombre_razon_social', 'email', 'telefono', 'price_list_id')
                ->with('priceList:id,nombre,clave')  // ✅ Optimización: Evitar N+1 queries
                ->get(),
            'productos' => Producto::with(['categoria:id,nombre', 'inventarios'])
                ->select('id', 'nombre', 'codigo', 'categoria_id', 'precio_venta', 'descripcion', 'estado', 'tipo_producto')
                ->active()
                ->get()
                ->map(function ($producto) {
                    $stockTotal = $producto->stock_total ?? 0;
                    $stockDisponible = $producto->stock_disponible ?? 0;
                    $stockReservado = (int) $producto->reservado;

                    return [
                        'id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'codigo' => $producto->codigo ?? 'SIN-CODIGO-' . $producto->id,
                        'categoria' => $producto->categoria?->nombre ?? 'Sin categoría',
                        'categoria_id' => $producto->categoria_id,
                        'precio_venta' => (float) $producto->precio_venta,
                        'descripcion' => $producto->descripcion,
                        'estado' => $producto->estado,
                        'tipo_producto' => $producto->tipo_producto,
                        'stock_total' => $stockTotal,
                        'stock_disponible' => $stockDisponible,
                        'stock_reservado' => $stockReservado,
                    ];
                }),
            'servicios' => Servicio::select('id', 'nombre', 'precio', 'descripcion')->get(),
            'defaults' => [
                'enableIsr' => \App\Services\EmpresaConfiguracionService::isIsrEnabled(),
                'enableRetencionIva' => \App\Services\EmpresaConfiguracionService::isRetencionIvaEnabled(),
                'enableRetencionIsr' => \App\Services\EmpresaConfiguracionService::isRetencionIsrEnabled(),
                'ivaPorcentaje' => \App\Services\EmpresaConfiguracionService::getIvaPorcentaje(),
                'isrPorcentaje' => \App\Services\EmpresaConfiguracionService::getIsrPorcentaje(),
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
        $pedido = Pedido::findOrFail($id);

        // Permitir edición solo si está en Borrador o Pendiente
        if (!in_array($pedido->estado, [EstadoPedido::Borrador, EstadoPedido::Pendiente], true)) {
            return Redirect::back()->with('error', 'Solo pedidos en borrador o pendientes pueden ser actualizados');
        }

        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'price_list_id' => 'nullable|exists:price_lists,id',
            'numero_pedido' => 'required|string|unique:pedidos,numero_pedido,' . $pedido->id,
            'productos' => 'required|array',
            'productos.*.id' => 'required|integer',
            'productos.*.tipo' => 'required|in:producto,servicio',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio' => 'required|numeric|min:0',
            'productos.*.descuento' => 'required|numeric|min:0|max:100',
            'descuento_general' => 'nullable|numeric|min:0|max:100',
            'notas' => 'nullable|string',
            'ajustar_margen' => 'nullable|boolean',
            'aplicar_retencion_iva' => 'boolean',
            'aplicar_retencion_isr' => 'boolean',
        ]);

        // Validar márgenes de ganancia antes de calcular totales
        $marginService = new MarginService();
        $validacionMargen = $marginService->validarMargenesProductos($validated['productos']);

        if (!$validacionMargen['todos_validos']) {
            // Si hay productos con margen insuficiente, verificar si el usuario aceptó el ajuste
            if ($request->has('ajustar_margen') && $request->ajustar_margen === 'true') {
                // Ajustar precios automáticamente
                foreach ($validated['productos'] as &$item) {
                    if ($item['tipo'] === 'producto') {
                        $producto = Producto::find($item['id']);
                        if ($producto) {
                            $item['precio'] = $marginService->ajustarPrecioAlMargen($producto, $item['precio']);
                        }
                    }
                }
            } else {
                // Mostrar advertencia y permitir al usuario decidir
                $mensaje = $marginService->generarMensajeAdvertencia($validacionMargen['productos_bajo_margen']);
                return Redirect::back()
                    ->withInput()
                    ->with('warning', $mensaje)
                    ->with('requiere_confirmacion_margen', true)
                    ->with('productos_bajo_margen', $validacionMargen['productos_bajo_margen']);
            }
        }

        // Guarda el estado ANTES de actualizar (clave del fix)
        $estadoAnterior = $pedido->estado;

        // Determinar el nuevo estado: si está en Borrador, cambiarlo a Pendiente
        $nuevoEstado = $pedido->estado === EstadoPedido::Borrador
            ? EstadoPedido::Pendiente
            : $pedido->estado;

        // Atomicidad: actualización + refresco de items
        DB::transaction(function () use (&$pedido, $validated, $nuevoEstado, $request, $marginService) {
            // Obtener cliente para resolver precios
            $cliente = Cliente::find($validated['cliente_id']);

            // Optimización N+1: Cargar todos los modelos necesarios en una sola consulta
            $productIds = [];
            $serviceIds = [];

            foreach ($validated['productos'] as $prod) {
                if ($prod['tipo'] === 'producto') {
                    $productIds[] = $prod['id'];
                } else {
                    $serviceIds[] = $prod['id'];
                }
            }

            $productosColeccion = Producto::whereIn('id', $productIds)->get()->keyBy('id');
            $serviciosColeccion = Servicio::whereIn('id', $serviceIds)->get()->keyBy('id');

            // Procesar ítems y calcular totales con precios reales
            $subtotal = 0;
            $descuentoItems = 0;
            $itemData = [];

            foreach ($validated['productos'] as $itemDataInput) {
                $class = $itemDataInput['tipo'] === 'producto' ? Producto::class : Servicio::class;

                // Obtener modelo de memoria en lugar de consulta DB
                if ($itemDataInput['tipo'] === 'producto') {
                    $modelo = $productosColeccion->get($itemDataInput['id']);
                } else {
                    $modelo = $serviciosColeccion->get($itemDataInput['id']);
                }

                if (!$modelo) {
                    Log::warning("Ítem no encontrado: {$class} con ID {$itemDataInput['id']}");
                    continue;
                }

                // ✅ FIX P0-2: Respetar precio del formulario si está presente
                if (isset($itemDataInput['precio']) && $itemDataInput['precio'] !== null) {
                    // Usuario especificó precio manualmente, respetarlo
                    $precio = (float) $itemDataInput['precio'];
                    $priceListId = $itemDataInput['price_list_id'] ?? ($validated['price_list_id'] ?? null);
                } else if ($itemDataInput['tipo'] === 'producto') {
                    // No hay precio, resolver dinámicamente usando PrecioService
                    $detallesPrecio = $this->precioService->resolverPrecioConDetalles(
                        $modelo,
                        $cliente,
                        $validated['price_list_id'] ? \App\Models\PriceList::find($validated['price_list_id']) : null
                    );
                    $precio = $detallesPrecio['precio'];
                    $priceListId = $detallesPrecio['price_list_id'];
                } else {
                    // Para servicios, usar precio enviado desde frontend
                    $precio = (float) ($itemDataInput['precio'] ?? 0);
                    $priceListId = null; // Servicios no usan listas de precios
                }

                $cantidad = (float) $itemDataInput['cantidad'];
                $descuento = (float) $itemDataInput['descuento'];

                $itemTotals = $this->financialService->calculateItemTotals($cantidad, $precio, $descuento);
                $subtotalItem = $itemTotals['subtotal_final'];
                $descuentoMontoItem = $itemTotals['descuento_monto'];

                // Guardar datos para crear ítems después
                $itemData[] = [
                    'class' => $class,
                    'modelo' => $modelo,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'descuento' => $descuento,
                    'subtotal' => $subtotalItem,
                    'descuento_monto' => $descuentoMontoItem,
                    'price_list_id' => $priceListId,
                    'item_id' => $itemDataInput['id']
                ];
            }

            // Calcular totales finales usando FinancialService
            $totales = $this->financialService->calculateDocumentTotals(
                $itemData,
                (float) ($request->descuento_general ?? 0),
                $validated['cliente_id'],
                [
                    'aplicar_retencion_iva' => $request->boolean('aplicar_retencion_iva'),
                    'aplicar_retencion_isr' => $request->boolean('aplicar_retencion_isr'),
                    'mode' => 'sales'
                ]
            );

            $pedido->update([
                'cliente_id' => $validated['cliente_id'],
                'numero_pedido' => $validated['numero_pedido'],
                'subtotal' => $totales['subtotal'],
                'descuento_general' => $totales['descuento_general'],
                'descuento_items' => $totales['descuento_items'], // Should be sum of item discounts
                'iva' => $totales['iva'],
                'retencion_iva' => $totales['retencion_iva'],
                'retencion_isr' => $totales['retencion_isr'],
                'isr' => $totales['isr'],
                'total' => $totales['total'],
                'fecha' => now(),
                'estado' => $nuevoEstado,
                'notas' => $request->notas,
                'price_list_id' => $validated['price_list_id'] ?? null,
            ]);

            // Eliminar ítems anteriores
            $pedido->items()->delete();

            // Crear nuevos ítems con precios resueltos
            foreach ($itemData as $data) {
                PedidoItem::create([
                    'pedido_id' => $pedido->id,
                    'pedible_id' => $data['item_id'],
                    'pedible_type' => $data['class'],
                    'cantidad' => (int) $data['cantidad'],
                    'precio' => round($data['precio'], 2),
                    'descuento' => round($data['descuento'], 2),
                    'subtotal' => round($data['subtotal'], 2),
                    'descuento_monto' => round($data['descuento_monto'], 2),
                    'price_list_id' => $data['price_list_id'],
                ]);
            }
        });

        // Mensaje basado en el estado ANTERIOR
        $mensajeExito = ($estadoAnterior === EstadoPedido::Borrador && $nuevoEstado === EstadoPedido::Pendiente)
            ? 'Pedido actualizado y cambiado a estado pendiente exitosamente'
            : 'Pedido actualizado exitosamente';

        return Redirect::route('pedidos.index')
            ->with('success', $mensajeExito);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            try {
                $pedido = Pedido::with(['cotizacion', 'ordenesCompra'])->findOrFail($id);

                // Verificar que el pedido puede ser eliminado
                if (!in_array($pedido->estado, [EstadoPedido::Borrador, EstadoPedido::Pendiente, EstadoPedido::Cancelado], true)) {
                    return Redirect::back()->with('error', 'Solo pedidos en borrador, pendientes o cancelados pueden ser eliminados');
                }

                // Guardar información de la cotización antes de eliminar
                $cotizacionId = $pedido->cotizacion_id;
                $cotizacion = $pedido->cotizacion;

                // ✅ Cancelar/eliminar órdenes de compra asociadas que estén en estado pendiente o borrador
                $ordenesCompraCanceladas = 0;
                $estadosCancelables = ['pendiente', 'borrador'];

                foreach ($pedido->ordenesCompra as $ordenCompra) {
                    if (in_array($ordenCompra->estado, $estadosCancelables)) {
                        // Primero eliminar los items de la orden de compra
                        $ordenCompra->productos()->detach();
                        // Luego eliminar la orden de compra
                        $ordenCompra->delete();
                        $ordenesCompraCanceladas++;

                        Log::info("Orden de compra eliminada al eliminar pedido", [
                            'orden_compra_id' => $ordenCompra->id,
                            'numero_orden' => $ordenCompra->numero_orden,
                            'pedido_id' => $pedido->id
                        ]);
                    }
                }

                // Eliminar los items del pedido primero
                $pedido->items()->delete();

                // Eliminar el pedido
                $pedido->delete();

                // Revertir el estado de la cotización asociada DESPUÉS de eliminar el pedido
                if ($cotizacionId && $cotizacion) {
                    $cotizacion->estado = EstadoCotizacion::Pendiente;
                    $cotizacion->save();

                    Log::info("Pedido ID {$id} eliminado y Cotización ID {$cotizacionId} revertida a estado pendiente");
                }

                $mensaje = 'Pedido eliminado exitosamente';
                if ($ordenesCompraCanceladas > 0) {
                    $mensaje .= ". Se eliminaron {$ordenesCompraCanceladas} orden(es) de compra pendiente(s).";
                }
                if ($cotizacion) {
                    $mensaje .= ' Cotización revertida a pendiente.';
                }

                return Redirect::route('pedidos.index')->with('success', $mensaje);
            } catch (\Exception $e) {
                Log::error('Error al eliminar pedido: ' . $e->getMessage());

                // La transacción se revertirá automáticamente
                return Redirect::back()
                    ->with('error', 'Error al eliminar el pedido: ' . $e->getMessage());
            }
        });
    }

    /**
     * Obtener el siguiente número de pedido disponible
     */
    public function obtenerSiguienteNumero()
    {
        // Usar preview para no quemar folios al refrescar
        try {
            $siguienteNumero = app(\App\Services\Folio\FolioService::class)->previewNextFolio('pedido');
        } catch (\Exception $e) {
            $siguienteNumero = 'P000'; // Fallback
        }
        return response()->json(['siguiente_numero' => $siguienteNumero]);
    }
}

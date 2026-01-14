<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use Illuminate\Validation\Rule;

use App\Models\Pedido;
use App\Models\Venta;
use App\Models\Cliente;
use App\Models\Producto;
use App\Enums\EstadoCotizacion;
use App\Enums\EstadoVenta;
use App\Enums\EstadoPedido;
use App\Models\CotizacionItem;
use App\Models\Servicio;
use App\Models\SatEstado;
use App\Models\SatRegimenFiscal;
use App\Models\SatUsoCfdi;
use App\Services\InventarioService;
use App\Services\MarginService;
use App\Services\PrecioService;
use App\Services\VentaCreationService;
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

class CotizacionController extends Controller
{
    use AuthorizesRequests;

    private InventarioService $inventarioService;
    private PrecioService $precioService;
    private \App\Services\FinancialService $financialService;

    public function __construct(
        InventarioService $inventarioService,
        PrecioService $precioService,
        \App\Services\FinancialService $financialService
    ) {
        $this->authorizeResource(Cotizacion::class);
        $this->inventarioService = $inventarioService;
        $this->precioService = $precioService;
        $this->financialService = $financialService;
    }

    /**
     * Display a listing of the resource.
     * Optimizado: Paginación del servidor + filtros + N+1 fix
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search', '');
        $estado = $request->input('estado', '');

        // Query optimizada con eager loading y filtros en base de datos
        $query = Cotizacion::with([
            'cliente:id,nombre_razon_social,email,telefono,rfc,regimen_fiscal,uso_cfdi,calle,numero_exterior,numero_interior,colonia,codigo_postal,municipio,estado,pais',
            'items.cotizable:id,nombre', // Solo campos necesarios
            'createdBy:id,name',
            'updatedBy:id,name',
            'emailEnviadoPor:id,name',
        ])
            // Contar relaciones en lugar de cargarlas completas
            ->withCount([
                'pedidos as pedidos_activos_count' => function ($q) {
                    $q->where('estado', '!=', EstadoPedido::Cancelado);
                },
                'ventas as ventas_activas_count' => function ($q) {
                    $q->where('estado', '!=', EstadoVenta::Cancelada);
                },
            ])
            // Filtrar en BD: cotizaciones con cliente e items
            ->whereHas('cliente')
            ->whereHas('items');

        // Aplicar filtro de búsqueda
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('numero_cotizacion', 'like', "%{$search}%")
                    ->orWhereHas('cliente', function ($clienteQuery) use ($search) {
                        $clienteQuery->where('nombre_razon_social', 'like', "%{$search}%");
                    });
            });
        }

        // Aplicar filtro de estado
        if (!empty($estado)) {
            $query->where('estado', $estado);
        }

        // Paginación del servidor
        $paginatedResult = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Transformar datos para el frontend
        $cotizaciones = collect($paginatedResult->items())->map(function ($cotizacion) {
            $items = $cotizacion->items->map(function ($item) {
                $cotizable = $item->cotizable;
                return [
                    'id' => $cotizable?->id,
                    'nombre' => $cotizable->nombre ?? 'Sin nombre',
                    'tipo' => $item->cotizable_type === Producto::class ? 'producto' : 'servicio',
                    'cantidad' => (int) $item->cantidad,
                    'precio' => (float) $item->precio,
                    'descuento' => (float) ($item->descuento ?? 0),
                ];
            });

            $createdAtIso = optional($cotizacion->created_at)->toIso8601String();
            $updatedAtIso = optional($cotizacion->updated_at)->toIso8601String();

            // Usar contadores en lugar de colecciones (más eficiente)
            $haSidoConvertida = $cotizacion->pedidos_activos_count > 0 || $cotizacion->ventas_activas_count > 0;

            return [
                'id' => $cotizacion->id,
                'numero_cotizacion' => $cotizacion->numero_cotizacion,

                // Fechas
                'fecha' => optional($cotizacion->created_at)->format('Y-m-d'),
                'created_at' => $createdAtIso,
                'updated_at' => $updatedAtIso,

                // Cliente
                'cliente' => [
                    'id' => $cotizacion->cliente->id,
                    'nombre' => $cotizacion->cliente->nombre_razon_social ?? 'Sin nombre',
                    'email' => $cotizacion->cliente->email,
                    'telefono' => $cotizacion->cliente->telefono,
                    'rfc' => $cotizacion->cliente->rfc,
                    'regimen_fiscal' => $cotizacion->cliente->regimen_fiscal,
                    'uso_cfdi' => $cotizacion->cliente->uso_cfdi,
                    'calle' => $cotizacion->cliente->calle,
                    'numero_exterior' => $cotizacion->cliente->numero_exterior,
                    'numero_interior' => $cotizacion->cliente->numero_interior,
                    'colonia' => $cotizacion->cliente->colonia,
                    'codigo_postal' => $cotizacion->cliente->codigo_postal,
                    'municipio' => $cotizacion->cliente->municipio,
                    'estado' => $cotizacion->cliente->estado,
                    'pais' => $cotizacion->cliente->pais,
                ],

                // Ítems
                'productos' => $items->toArray(),

                // Totales/estado
                'total' => (float) $cotizacion->total,
                'estado' => is_object($cotizacion->estado) ? $cotizacion->estado->value : $cotizacion->estado,
                'notas' => $cotizacion->notas,

                // Permisos
                'canEdit' => in_array($cotizacion->estado, [EstadoCotizacion::Borrador, EstadoCotizacion::Pendiente], true) && !$haSidoConvertida,
                'canDelete' => in_array($cotizacion->estado, [EstadoCotizacion::Borrador, EstadoCotizacion::Pendiente, EstadoCotizacion::Aprobada], true) && !$haSidoConvertida,

                // Auditoría
                'creado_por_nombre' => $cotizacion->createdBy?->name,
                'actualizado_por_nombre' => $cotizacion->updatedBy?->name,
                'email_enviado_por_nombre' => $cotizacion->emailEnviadoPor?->name,

                // Información de email
                'email_enviado' => (bool) $cotizacion->email_enviado,
                'email_enviado_fecha' => $cotizacion->email_enviado_fecha?->format('d/m/Y H:i'),

                // Metadata para modal
                'metadata' => [
                    'creado_por' => $cotizacion->createdBy?->name,
                    'actualizado_por' => $cotizacion->updatedBy?->name,
                    'email_enviado_por' => $cotizacion->emailEnviadoPor?->name,
                    'creado_en' => $createdAtIso,
                    'actualizado_en' => $updatedAtIso,
                    'email_enviado_en' => $cotizacion->email_enviado_fecha?->format('d/m/Y H:i'),
                ],
            ];
        });

        return Inertia::render('Cotizaciones/Index', [
            'cotizaciones' => $cotizaciones->values(),
            'pagination' => [
                'current_page' => $paginatedResult->currentPage(),
                'last_page' => $paginatedResult->lastPage(),
                'per_page' => $paginatedResult->perPage(),
                'total' => $paginatedResult->total(),
                'from' => $paginatedResult->firstItem(),
                'to' => $paginatedResult->lastItem(),
            ],
            'estados' => collect(EstadoCotizacion::cases())->map(fn($estado) => [
                'value' => $estado->value,
                'label' => $estado->label(),
                'color' => $estado->color()
            ]),
            'filters' => [
                'search' => $search,
                'estado' => $estado,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Cotizaciones/Create', [
            'clientes' => Cliente::activos()
                ->select('id', 'nombre_razon_social', 'email', 'telefono', 'price_list_id', 'tipo_persona', 'rfc', 'codigo_postal', 'calle')
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
            'servicios' => Servicio::with('categoria:id,nombre')
                ->select('id', 'nombre', 'codigo', 'categoria_id', 'precio', 'descripcion', 'estado')
                ->active()
                ->get()
                ->map(function ($servicio) {
                    return [
                        'id' => $servicio->id,
                        'nombre' => $servicio->nombre,
                        'codigo' => $servicio->codigo ?? 'SIN-CODIGO-SERV-' . $servicio->id,
                        'categoria' => $servicio->categoria?->nombre ?? 'Sin categoría',
                        'categoria_id' => $servicio->categoria_id,
                        'precio' => (float) $servicio->precio,
                        'descripcion' => $servicio->descripcion,
                        'estado' => $servicio->estado,
                    ];
                }),
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
                            'text' => $estado->clave . ' – ' . $estado->nombre
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
                            'text' => $uso->clave . ' – ' . $uso->descripcion
                        ];
                    })
                    ->toArray(),
            ],
            'defaults' => [
                'fecha' => now()->format('Y-m-d'),
                'validez' => 30,
                'moneda' => 'MXN',
                'ivaPorcentaje' => (float) \App\Services\EmpresaConfiguracionService::getIvaPorcentaje(),
                'isrPorcentaje' => (float) \App\Services\EmpresaConfiguracionService::getIsrPorcentaje(),
                'enableIsr' => \App\Services\EmpresaConfiguracionService::isIsrEnabled(),
                'enableRetencionIva' => \App\Services\EmpresaConfiguracionService::isRetencionIvaEnabled(),
                'enableRetencionIsr' => \App\Services\EmpresaConfiguracionService::isRetencionIsrEnabled(),
                'retencionIvaDefault' => (float) \App\Services\EmpresaConfiguracionService::getRetencionIvaDefault(),
                'retencionIsrDefault' => (float) \App\Services\EmpresaConfiguracionService::getRetencionIsrDefault(),
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
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|integer|min:1',
            'productos.*.tipo' => 'required|in:producto,servicio',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio' => 'required|numeric|min:0.01',
            'productos.*.descuento' => 'required|numeric|min:0|max:100',
            'descuento_general' => 'nullable|numeric|min:0|max:100',
            'notas' => 'nullable|string|max:1000',
            'ajustar_margen' => 'nullable|boolean',
            'aplicar_retencion_iva' => 'nullable|boolean',
            'aplicar_retencion_isr' => 'nullable|boolean',
        ]);

        // Validar que los productos/servicios realmente existen
        foreach ($validated['productos'] as $index => $item) {
            $class = $item['tipo'] === 'producto' ? Producto::class : Servicio::class;
            $modelo = $class::find($item['id']);

            if (!$modelo) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(["productos.{$index}.id" => "El " . $item['tipo'] . " con ID {$item['id']} no existe"])
                    ->with('error', 'Algunos productos o servicios seleccionados no existen');
            }

            // Validar que el producto esté activo
            if ($item['tipo'] === 'producto' && $modelo->estado !== 'activo') {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(["productos.{$index}.id" => "El producto '{$modelo->nombre}' no está activo"])
                    ->with('error', 'Algunos productos seleccionados no están activos');
            }
        }

        // Validar márgenes de ganancia
        $marginService = new MarginService();
        $validacionMargen = $marginService->validarMargenesProductos($validated['productos']);

        Log::info('Validación de márgenes en cotización', [
            'productos_count' => count($validated['productos']),
            'todos_validos' => $validacionMargen['todos_validos'],
            'productos_bajo_margen_count' => count($validacionMargen['productos_bajo_margen']),
            'ajustar_margen_request' => $request->has('ajustar_margen') ? $request->ajustar_margen : 'no_presente'
        ]);

        if (!$validacionMargen['todos_validos']) {
            Log::info('Productos con margen insuficiente detectados', [
                'productos_bajo_margen' => $validacionMargen['productos_bajo_margen']
            ]);

            // Si hay productos con margen insuficiente, verificar si el usuario aceptó el ajuste
            // Aceptar bandera booleana para ajustar margen (true/"true"/1)
            if ($request->boolean('ajustar_margen')) {
                Log::info('Usuario aceptó ajuste automático de márgenes');
                // Ajustar precios automáticamente
                foreach ($validated['productos'] as &$item) {
                    if ($item['tipo'] === 'producto') {
                        $producto = Producto::find($item['id']);
                        if ($producto) {
                            $precioOriginal = $item['precio'];
                            $item['precio'] = $marginService->ajustarPrecioAlMargen($producto, $item['precio']);
                            Log::info('Precio ajustado', [
                                'producto_id' => $producto->id,
                                'precio_original' => $precioOriginal,
                                'precio_ajustado' => $item['precio']
                            ]);
                        }
                    }
                }
            } else {
                Log::info('Mostrando modal de confirmación de márgenes insuficientes');
                // Mostrar advertencia y permitir al usuario decidir
                $mensaje = $marginService->generarMensajeAdvertencia($validacionMargen['productos_bajo_margen']);
                return redirect()->back()
                    ->withInput()
                    ->with('warning', $mensaje)
                    ->with('requiere_confirmacion_margen', true)
                    ->with('productos_bajo_margen', $validacionMargen['productos_bajo_margen']);
            }
        } else {
            Log::info('Todos los productos tienen márgenes válidos');
        }

        try {
            DB::transaction(function () use ($validated, $request) {
                // Obtener cliente para resolver precios
                $cliente = Cliente::find($validated['cliente_id']);

                // Procesar ítems y calcular totales con precios reales
                $subtotal = 0;
                $descuentoItems = 0;
                $itemData = [];

                foreach ($validated['productos'] as $item) {
                    $class = $item['tipo'] === 'producto' ? Producto::class : Servicio::class;
                    $modelo = $class::find($item['id']);

                    if (!$modelo) {
                        Log::warning("Ítem no encontrado al crear cotización", [
                            'tipo' => $class,
                            'id' => $item['id']
                        ]);
                        continue;
                    }

                    $cantidad = (float) ($item['cantidad'] ?? 0);

                    // ✅ FIX P0-3: Respetar precio del formulario si está presente
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

                    $descuento = (float) ($item['descuento'] ?? 0);
                    $itemTotals = $this->financialService->calculateItemTotals($cantidad, $precio, $descuento);
                    $subtotalItem = $itemTotals['subtotal_final']; // Total for line after discount
                    $descuentoMontoItem = $itemTotals['descuento_monto'];

                    // Guardar datos para crear ítems después y para cálculo global
                    $itemData[] = [
                        'class' => $class,
                        'modelo' => $modelo,
                        'cantidad' => $cantidad,
                        'precio' => $precio,
                        'descuento' => $descuento,
                        'subtotal' => $subtotalItem, // Line total
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

                // Crear cotización con totales correctos
                $cotizacion = Cotizacion::create([
                    'cliente_id' => $validated['cliente_id'],
                    'subtotal' => $totales['subtotal'],
                    'descuento_general' => $totales['descuento_general'],
                    'descuento_items' => $totales['descuento_items'], // Should be sum of item discounts
                    'iva' => $totales['iva'],
                    'retencion_iva' => $totales['retencion_iva'],
                    'retencion_isr' => $totales['retencion_isr'],
                    'isr' => $totales['isr'],
                    'total' => $totales['total'],
                    'notas' => $request->notas,
                    'estado' => EstadoCotizacion::Pendiente,
                ]);

                Log::info('Cotización creada exitosamente', [
                    'cotizacion_id' => $cotizacion->id,
                    'cliente_id' => $validated['cliente_id'],
                    'productos_count' => count($validated['productos']),
                    'subtotal' => $totales['subtotal'],
                    'total' => $totales['total'],
                    'estado' => 'pendiente'
                ]);

                // Crear ítems con precios resueltos
                foreach ($itemData as $data) {
                    CotizacionItem::create([
                        'cotizacion_id' => $cotizacion->id,
                        'cotizable_id' => $data['item_id'],
                        'cotizable_type' => $data['class'],
                        'cantidad' => (int) $data['cantidad'],
                        'precio' => round($data['precio'], 2),
                        'descuento' => round($data['descuento'], 2),
                        'subtotal' => round($data['subtotal'], 2),
                        'descuento_monto' => round($data['descuento_monto'], 2),
                        'price_list_id' => $data['price_list_id'],
                    ]);

                    Log::info("Ítem agregado a cotización exitosamente", [
                        'cotizacion_id' => $cotizacion->id,
                        'tipo' => $data['class'],
                        'id' => $data['item_id'],
                        'nombre' => $data['modelo']->nombre,
                        'categoria' => $data['class'] === Producto::class ? $data['modelo']->categoria?->nombre : 'Servicio',
                        'cantidad' => $data['cantidad'],
                        'precio' => $data['precio'],
                        'price_list_id' => $data['price_list_id']
                    ]);
                }
            });
        } catch (\Exception $e) {
            Log::error('Error al crear cotización', [
                'cliente_id' => $validated['cliente_id'],
                'productos_count' => count($validated['productos']),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error interno al crear la cotización. Por favor, inténtelo de nuevo.');
        }

        return redirect()->route('cotizaciones.index')->with('success', 'Cotización creada con éxito');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $cotizacion = Cotizacion::with(['cliente', 'items.cotizable'])->findOrFail($id);

        $items = $cotizacion->items->map(function ($item) {
            $cotizable = $item->cotizable;
            return [
                'id' => $cotizable->id,
                'nombre' => $cotizable->nombre ?? $cotizable->descripcion,
                'tipo' => $item->cotizable_type === Producto::class ? 'producto' : 'servicio',
                'pivot' => [
                    'cantidad' => $item->cantidad,
                    'precio' => $item->precio,
                    'descuento' => $item->descuento,
                ],
            ];
        });

        return Inertia::render('Cotizaciones/Show', [
            'cotizacion' => [
                'id' => $cotizacion->id,
                'numero_cotizacion' => $cotizacion->numero_cotizacion,
                'fecha_cotizacion' => $cotizacion->fecha_cotizacion?->format('Y-m-d'),
                'cliente' => $cotizacion->cliente,
                'productos' => $items,
                'subtotal' => $cotizacion->subtotal,
                'descuento_general' => $cotizacion->descuento_general,
                'iva' => $cotizacion->iva,
                'isr' => $cotizacion->isr ?? 0,
                'total' => $cotizacion->total,
                'notas' => $cotizacion->notas,
                'estado' => $cotizacion->estado->value,
            ],
            'canConvert' => $cotizacion->estado === EstadoCotizacion::Aprobada,
            'canEdit' => in_array($cotizacion->estado, [EstadoCotizacion::Borrador, EstadoCotizacion::Pendiente], true),
            'canDelete' => in_array($cotizacion->estado, [EstadoCotizacion::Borrador, EstadoCotizacion::Pendiente], true),
            'ivaPorcentaje' => \App\Services\EmpresaConfiguracionService::getIvaPorcentaje(),
            'isrPorcentaje' => \App\Services\EmpresaConfiguracionService::getIsrPorcentaje(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id The ID of the cotizacion to edit
     * @return \Inertia\Response The edit page with cotizacion data
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If cotizacion not found
     */
    public function edit(int $id): \Inertia\Response
    {
        // Load cotizacion with eager loading to avoid N+1 queries
        $cotizacion = Cotizacion::with(['cliente', 'items.cotizable'])->findOrFail($id);

        // Only allow editing if in Borrador or Pendiente state
        if (!in_array($cotizacion->estado, [EstadoCotizacion::Borrador, EstadoCotizacion::Pendiente], true)) {
            return Redirect::route('cotizaciones.show', $cotizacion->id)
                ->with('warning', 'Solo cotizaciones en borrador o pendientes pueden ser editadas');
        }

        // Transform items for frontend
        $items = $cotizacion->items->map(function ($item) {
            $cotizable = $item->cotizable;
            return [
                'id' => $cotizable->id,
                'nombre' => $cotizable->nombre ?? $cotizable->descripcion,
                'tipo' => $item->cotizable_type === Producto::class ? 'producto' : 'servicio',
                'pivot' => [
                    'cantidad' => $item->cantidad,
                    'precio' => $item->precio,
                    'descuento' => $item->descuento,
                ],
            ];
        });

        // Get active clients with price lists
        $clientes = Cliente::activos()
            ->select('id', 'nombre_razon_social', 'email', 'telefono', 'price_list_id', 'tipo_persona', 'rfc', 'codigo_postal', 'calle')
            ->with('priceList:id,nombre,clave')
            ->get();

        // Get active products with categories and inventory
        $productos = Producto::with(['categoria:id,nombre', 'inventarios'])
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
            });

        // Get active services with categories
        $servicios = Servicio::with('categoria:id,nombre')
            ->select('id', 'nombre', 'codigo', 'categoria_id', 'precio', 'descripcion', 'estado')
            ->active()
            ->get()
            ->map(function ($servicio) {
                return [
                    'id' => $servicio->id,
                    'nombre' => $servicio->nombre,
                    'codigo' => $servicio->codigo ?? 'SIN-CODIGO-SERV-' . $servicio->id,
                    'categoria' => $servicio->categoria?->nombre ?? 'Sin categoría',
                    'categoria_id' => $servicio->categoria_id,
                    'precio' => (float) $servicio->precio,
                    'descripcion' => $servicio->descripcion,
                    'estado' => $servicio->estado,
                ];
            });

        // Get active price lists
        $priceLists = \App\Models\PriceList::activas()->select('id', 'nombre')->get();

        return Inertia::render('Cotizaciones/Edit', [
            'cotizacion' => [
                'id' => $cotizacion->id,
                'cliente_id' => $cotizacion->cliente_id,
                'price_list_id' => $cotizacion->price_list_id,
                'cliente' => $cotizacion->cliente,
                'productos' => $items,
                'subtotal' => $cotizacion->subtotal,
                'descuento_general' => $cotizacion->descuento_general,
                'iva' => $cotizacion->iva,
                'total' => $cotizacion->total,
                'notas' => $cotizacion->notas,
                'informacion_general' => [
                    'numero' => [
                        'label' => 'Número de Cotización',
                        'value' => $cotizacion->numero_cotizacion,
                        'tipo' => 'fijo',
                        'descripcion' => 'Este número es fijo para todas las cotizaciones'
                    ],
                    'fecha' => [
                        'label' => 'Fecha de Cotización',
                        'value' => $cotizacion->fecha_cotizacion ? $cotizacion->fecha_cotizacion->format('d/m/Y') : now()->format('d/m/Y'),
                        'tipo' => 'automatica',
                        'descripcion' => 'Esta fecha se establece automáticamente con la fecha de creación'
                    ]
                ]
            ],
            'clientes' => $clientes,
            'productos' => $productos,
            'servicios' => $servicios,
            'priceLists' => $priceLists,
            'defaults' => [
                'ivaPorcentaje' => \App\Services\EmpresaConfiguracionService::getIvaPorcentaje(),
                'isrPorcentaje' => \App\Services\EmpresaConfiguracionService::getIsrPorcentaje(),
                'enableRetencionIva' => \App\Services\EmpresaConfiguracionService::isRetencionIvaEnabled(),
                'enableRetencionIsr' => \App\Services\EmpresaConfiguracionService::isRetencionIsrEnabled(),
                'retencionIvaDefault' => \App\Services\EmpresaConfiguracionService::getRetencionIvaDefault(),
                'retencionIsrDefault' => \App\Services\EmpresaConfiguracionService::getRetencionIsrDefault(),
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $cotizacion = Cotizacion::findOrFail($id);

        // (Opcional, si tienes Policy configurada)
        // $this->authorize('update', $cotizacion);

        // Solo permitir edición en Borrador o Pendiente
        if (!in_array($cotizacion->estado, [EstadoCotizacion::Borrador, EstadoCotizacion::Pendiente], true)) {
            return Redirect::back()->with('error', 'Solo cotizaciones en borrador o pendientes pueden ser actualizadas');
        }

        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'price_list_id' => 'nullable|exists:price_lists,id',
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|integer',
            'productos.*.tipo' => 'required|in:producto,servicio',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio' => 'required|numeric|min:0.01',
            'productos.*.descuento' => 'required|numeric|min:0|max:100',
            'descuento_general' => 'nullable|numeric|min:0|max:100',
            'notas' => 'nullable|string',
            'ajustar_margen' => 'nullable|boolean',
            'aplicar_retencion_iva' => 'nullable|boolean',
            'aplicar_retencion_isr' => 'nullable|boolean',
            // Validación de estado (si llega desde el front, se controla)
            'estado' => ['sometimes', Rule::in(array_map(fn($c) => $c->value, EstadoCotizacion::cases()))],
        ]);

        // Validar márgenes de ganancia antes de calcular totales
        $marginService = new MarginService();
        $validacionMargen = $marginService->validarMargenesProductos($validated['productos']);

        Log::info('Validación de márgenes en actualización de cotización', [
            'cotizacion_id' => $id,
            'productos_count' => count($validated['productos']),
            'todos_validos' => $validacionMargen['todos_validos'],
            'productos_bajo_margen_count' => count($validacionMargen['productos_bajo_margen']),
            'ajustar_margen_request' => $request->has('ajustar_margen') ? $request->ajustar_margen : 'no_presente'
        ]);

        if (!$validacionMargen['todos_validos']) {
            Log::info('Productos con margen insuficiente detectados en actualización', [
                'cotizacion_id' => $id,
                'productos_bajo_margen' => $validacionMargen['productos_bajo_margen']
            ]);

            // Si hay productos con margen insuficiente, verificar si el usuario aceptó el ajuste
            // Aceptar bandera booleana para ajustar margen (true/"true"/1)
            if ($request->boolean('ajustar_margen')) {
                Log::info('Usuario aceptó ajuste automático de márgenes en actualización', ['cotizacion_id' => $id]);
                // Ajustar precios automáticamente
                foreach ($validated['productos'] as &$item) {
                    if ($item['tipo'] === 'producto') {
                        $producto = Producto::find($item['id']);
                        if ($producto) {
                            $precioOriginal = $item['precio'];
                            $item['precio'] = $marginService->ajustarPrecioAlMargen($producto, $item['precio']);
                            Log::info('Precio ajustado en actualización', [
                                'cotizacion_id' => $id,
                                'producto_id' => $producto->id,
                                'precio_original' => $precioOriginal,
                                'precio_ajustado' => $item['precio']
                            ]);
                        }
                    }
                }
            } else {
                Log::info('Mostrando modal de confirmación de márgenes insuficientes en actualización', ['cotizacion_id' => $id]);
                // Mostrar advertencia y permitir al usuario decidir
                $mensaje = $marginService->generarMensajeAdvertencia($validacionMargen['productos_bajo_margen']);
                return Redirect::back()
                    ->withInput()
                    ->with('warning', $mensaje)
                    ->with('requiere_confirmacion_margen', true)
                    ->with('productos_bajo_margen', $validacionMargen['productos_bajo_margen']);
            }
        } else {
            Log::info('Todos los productos tienen márgenes válidos en actualización', ['cotizacion_id' => $id]);
        }

        // Guardar estado ANTES de actualizar (para mensaje)
        $estadoAnterior = $cotizacion->estado;

        // Si estaba en Borrador, pasa a Pendiente; si no, conserva
        $nuevoEstado = $cotizacion->estado === EstadoCotizacion::Borrador
            ? EstadoCotizacion::Pendiente
            : $cotizacion->estado;

        // Atomicidad: actualizar cabecera + refrescar items
        // Atomicidad: actualizar cabecera + refrescar items
        DB::transaction(function () use ($cotizacion, $validated, $nuevoEstado, $request) {
            // Obtener cliente para resolver precios
            $cliente = Cliente::find($validated['cliente_id']);

            // Procesar ítems y calcular totales con precios reales
            $subtotal = 0;
            $descuentoItems = 0;
            $itemData = [];

            foreach ($validated['productos'] as $itemDataInput) {
                $class = $itemDataInput['tipo'] === 'producto' ? Producto::class : Servicio::class;
                $modelo = $class::find($itemDataInput['id']);

                if (!$modelo) {
                    Log::warning("Ítem no encontrado: {$class} con ID {$itemDataInput['id']}");
                    continue;
                }

                // Respetar precio del formulario si está presente
                if (isset($itemDataInput['precio']) && $itemDataInput['precio'] !== null) {
                    $precio = (float) $itemDataInput['precio'];
                    $priceListId = $itemDataInput['price_list_id'] ?? ($validated['price_list_id'] ?? null);
                } else if ($itemDataInput['tipo'] === 'producto') {
                    $detallesPrecio = $this->precioService->resolverPrecioConDetalles(
                        $modelo,
                        $cliente,
                        $validated['price_list_id'] ? \App\Models\PriceList::find($validated['price_list_id']) : null
                    );
                    $precio = $detallesPrecio['precio'];
                    $priceListId = $detallesPrecio['price_list_id'];
                } else {
                    $precio = (float) ($itemDataInput['precio'] ?? 0);
                    $priceListId = null;
                }

                $cantidad = (float) $itemDataInput['cantidad'];
                $descuento = (float) $itemDataInput['descuento'];
                $itemTotals = $this->financialService->calculateItemTotals($cantidad, $precio, $descuento);
                $subtotalItem = $itemTotals['subtotal_final'];
                $descuentoMontoItem = $itemTotals['descuento_monto'];

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

            $cotizacion->update([
                'cliente_id' => $validated['cliente_id'],
                'subtotal' => $totales['subtotal'],
                'descuento_general' => $totales['descuento_general'],
                'descuento_items' => $totales['descuento_items'],
                'iva' => $totales['iva'],
                'retencion_iva' => $totales['retencion_iva'],
                'retencion_isr' => $totales['retencion_isr'],
                'isr' => $totales['isr'],
                'total' => $totales['total'],
                'notas' => $request->notas,
                'estado' => $nuevoEstado,
            ]);

            // Eliminar ítems anteriores
            $cotizacion->items()->delete();

            // Crear nuevos ítems
            foreach ($itemData as $data) {
                CotizacionItem::create([
                    'cotizacion_id' => $cotizacion->id,
                    'cotizable_id' => $data['item_id'],
                    'cotizable_type' => $data['class'],
                    'cantidad' => (int) $data['cantidad'],
                    'precio' => round($data['precio'], 2),
                    'descuento' => round($data['descuento'], 2),
                    'subtotal' => round($data['subtotal'], 2),
                    'descuento_monto' => round($data['descuento_monto'], 2),
                    'price_list_id' => $data['price_list_id'],
                ]);
            }
        });

        // Mensaje usando estado anterior (no el ya mutado)
        $mensajeExito = ($estadoAnterior === EstadoCotizacion::Borrador && $nuevoEstado === EstadoCotizacion::Pendiente)
            ? 'Cotización actualizada y cambiada a estado pendiente exitosamente'
            : 'Cotización actualizada exitosamente';

        return Redirect::route('cotizaciones.index')->with('success', $mensajeExito);
    }

    /**
     * Cancel the specified resource (soft cancel).
     */
    public function cancel($id)
    {
        $cotizacion = Cotizacion::findOrFail($id);

        // Permitir cancelar en cualquier estado excepto ya cancelado
        if ($cotizacion->estado === EstadoCotizacion::Cancelado) {
            return Redirect::back()->with('error', 'La cotización ya está cancelada');
        }

        // Actualizar estado a cancelado y registrar quién lo canceló
        $cotizacion->update([
            'estado' => EstadoCotizacion::Cancelado,
            'deleted_by' => Auth::id(),
            'deleted_at' => now()
        ]);

        return Redirect::route('cotizaciones.index')
            ->with('success', 'Cotización cancelada exitosamente');
    }

    /**
     * Obtener el siguiente número de cotización disponible
     */
    public function obtenerSiguienteNumero()
    {
        // Usar preview para no quemar folios al refrescar
        try {
            $siguienteNumero = app(\App\Services\Folio\FolioService::class)->previewNextFolio('cotizacion');
        } catch (\Exception $e) {
            $siguienteNumero = 'C000'; // Fallback
        }
        return response()->json(['siguiente_numero' => $siguienteNumero]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $cotizacion = Cotizacion::withTrashed()->findOrFail($id);

        if (!in_array($cotizacion->estado, [EstadoCotizacion::Borrador, EstadoCotizacion::Pendiente, EstadoCotizacion::Aprobada, EstadoCotizacion::Cancelado], true)) {
            return Redirect::back()->with('error', 'Solo cotizaciones pendientes o canceladas pueden ser eliminadas');
        }

        $cotizacion->items()->delete();
        $cotizacion->forceDelete(); // Eliminar permanentemente

        return Redirect::route('cotizaciones.index')
            ->with('success', 'Cotizacion eliminada exitosamente');
    }



    /**
     * Calcular costo FIFO para un producto
     */
    private function calcularCostoFIFO(Producto $producto): float
    {
        // Obtener el costo más antiguo disponible (FIFO)
        $lote = \App\Models\Lote::where('producto_id', $producto->id)
            ->where('cantidad_disponible', '>', 0)
            ->orderBy('fecha_entrada', 'asc')
            ->first();

        if ($lote) {
            return (float) $lote->costo_unitario;
        }

        // Si no hay lotes, usar costo promedio o último costo de compra
        return (float) ($producto->costo_promedio ?? $producto->ultimo_costo ?? 0);
    }
}

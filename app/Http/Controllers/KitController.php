<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\KitItem;
use App\Models\Marca;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\Folio\FolioService;
use App\Services\StockValidationService;
use App\Traits\ImageOptimizerTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Support\EmpresaResolver;

class KitController extends Controller
{
    use ImageOptimizerTrait;

    protected $stockService;

    public function __construct(StockValidationService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(): Response
    {
        return Inertia::render('Kits/Index');
    }

    public function create(): Response
    {
        $productosDisponibles = Producto::where('estado', 'activo')
            ->where('tipo_producto', '!=', 'kit')
            ->orderBy('nombre')
            ->get();

        $serviciosDisponibles = \App\Models\Servicio::where('estado', 'activo')
            ->orderBy('nombre')
            ->get();

        $categorias = \App\Models\Categoria::orderBy('nombre')->get();
        $almacenPrincipal = \App\Models\Almacen::orderBy('id')->first();

        return Inertia::render('Kits/Create', [
            'productosDisponibles' => $productosDisponibles,
            'serviciosDisponibles' => $serviciosDisponibles,
            'categorias' => $categorias,
            'almacenPrincipal' => $almacenPrincipal
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'codigo' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('productos', 'codigo')->where('empresa_id', EmpresaResolver::resolveId())
            ],
            'precio_venta' => 'required|numeric|min:0',
            'categoria_id' => 'nullable|exists:categorias,id',
            'componentes' => 'required|array|min:1',
            'componentes.*.item_type' => 'required|in:producto,servicio',
            'componentes.*.item_id' => 'required|integer',
            'componentes.*.cantidad' => 'required|integer|min:1',
            'componentes.*.precio_unitario' => 'nullable|numeric|min:0',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'destacado' => 'nullable|boolean',
            'incluye_iva' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Validar que los productos no sean kits
            $productosIds = collect($request->componentes)
                ->where('item_type', 'producto')
                ->pluck('item_id');

            if ($productosIds->isNotEmpty()) {
                $kitsEnComponentes = Producto::whereIn('id', $productosIds)
                    ->where('tipo_producto', 'kit')
                    ->count();

                if ($kitsEnComponentes > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se pueden agregar kits como componentes de otro kit.'
                    ], 422);
                }
            }

            // Validar que los item_id existan según el tipo
            $componentErrors = [];
            foreach ($request->componentes as $index => $componente) {
                if ($componente['item_type'] === 'producto') {
                    $producto = Producto::where('id', $componente['item_id'])
                        ->where('tipo_producto', '!=', 'kit')
                        ->first();
                    if (!$producto) {
                        $componentErrors["componentes.$index.item_id"] = ['El producto seleccionado no existe o es un kit.'];
                        continue;
                    }
                    if ($producto->estado !== 'activo') {
                        $componentErrors["componentes.$index.item_id"] = ['El producto seleccionado no está activo.'];
                    }
                } elseif ($componente['item_type'] === 'servicio') {
                    $existeServicio = \App\Models\Servicio::where('id', $componente['item_id'])->exists();
                    if (!$existeServicio) {
                        $componentErrors["componentes.$index.item_id"] = ['El servicio seleccionado no existe.'];
                    }
                }
            }

            if (!empty($componentErrors)) {
                return response()->json([
                    'success' => false,
                    'errors' => $componentErrors
                ], 422);
            }

            $imagenPath = null;
            if ($request->hasFile('imagen')) {
                try {
                    $imagenPath = $this->saveImageAsWebP($request->file('imagen'), 'productos');
                } catch (\Exception $e) {
                    Log::error("Error processing kit image: " . $e->getMessage());
                    $imagenPath = $request->file('imagen')->store('productos', 'public');
                }
            }

            $kit = DB::transaction(function () use ($request, $imagenPath) {
                // Calcular precio de compra (costo) basado en componentes
                // NOTA: Los servicios tienen costo 0 (100% utilidad)
                $costoTotal = 0;
                $almacenPrincipal = \App\Models\Almacen::orderBy('id')->first();
                $almacenId = $almacenPrincipal?->id ?? 1;

                foreach ($request->componentes as $componenteData) {
                    $itemType = $componenteData['item_type'];
                    $itemId = $componenteData['item_id'];
                    $cantidad = $componenteData['cantidad'];

                    if ($itemType === 'producto') {
                        $producto = Producto::find($itemId);
                        if ($producto) {
                            $costoUnitario = $this->stockService->calcularCostoHistorico(
                                $producto,
                                $cantidad,
                                $almacenId
                            );
                            $costoTotal += $costoUnitario * $cantidad;
                        }
                    }
                    // Los servicios no agregan costo (costo = 0, utilidad 100%)
                }

                // Para kits, generar un código único con prefijo KIT si no se proporciona uno
                $codigoKit = $request->codigo;
                if (empty($codigoKit)) {
                    $codigoKit = app(FolioService::class)->getNextFolio('kit');
                }

                $kit = Producto::create([
                    'nombre' => $request->nombre,
                    'descripcion' => $request->descripcion ?? '',
                    'codigo' => $codigoKit,
                    'codigo_barras' => '',
                    'marca_id' => Marca::orderBy('id')->first()?->id ?? 1,
                    'precio_compra' => $costoTotal,
                    'precio_venta' => $request->precio_venta,
                    'tipo_producto' => 'kit',
                    'estado' => 'activo',
                    'categoria_id' => $request->categoria_id ?: \App\Models\Categoria::orderBy('id')->first()?->id ?? 1,
                    'unidad_medida' => 'pieza',
                    'imagen' => $imagenPath,
                    'destacado' => $request->boolean('destacado'),
                    'incluye_iva' => $request->boolean('incluye_iva'),
                ]);

                foreach ($request->componentes as $componenteData) {
                    $itemType = $componenteData['item_type'] === 'producto'
                        ? 'producto'
                        : 'servicio';

                    KitItem::create([
                        'kit_id' => $kit->id,
                        'item_type' => $itemType,
                        'item_id' => $componenteData['item_id'],
                        'cantidad' => $componenteData['cantidad'],
                        'precio_unitario' => $componenteData['precio_unitario'] ?? null,
                        'empresa_id' => $kit->empresa_id,
                    ]);
                }

                return $kit;
            });

            return response()->json([
                'success' => true,
                'message' => 'Kit creado exitosamente.',
                'kit' => $kit->load('kitItems.item')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el kit: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Producto $kit): Response|JsonResponse
    {
        if (!$kit->esKit()) {
            abort(404);
        }

        // Cargar las relaciones necesarias incluyendo la relación polimórfica 'item'
        $kit->load(['kitItems.item', 'categoria']);

        // Calcular costo estimado actual (solo productos, servicios tienen costo 0)
        $costoEstimado = 0;
        $almacenPrincipal = \App\Models\Almacen::orderBy('id')->first();
        $almacenId = $almacenPrincipal?->id ?? 1;

        foreach ($kit->kitItems as $item) {
            if ($item->esProducto() && $item->item) {
                $costoUnitario = $this->stockService->calcularCostoHistorico(
                    $item->item,
                    $item->cantidad,
                    $almacenId
                );
                $costoEstimado += $costoUnitario * $item->cantidad;
            }
            // Los servicios no agregan costo
        }

        // Si es una petición AJAX (pero NO Inertia), devolver JSON
        $isInertia = request()->header('X-Inertia');
        if (!$isInertia && (request()->expectsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest')) {
            return response()->json([
                'id' => $kit->id,
                'nombre' => $kit->nombre,
                'codigo' => $kit->codigo,
                'descripcion' => $kit->descripcion,
                'precio_venta' => $kit->precio_venta,
                'kit_items' => $kit->kitItems->map(function ($item) {
                    // Obtener los datos del producto o servicio desde la relación polimórfica
                    $productoData = null;
                    if ($item->item) {
                        $productoData = [
                            'id' => $item->item->id,
                            'codigo' => $item->item->codigo ?? ($item->esServicio() ? $item->item->codigo_servicio ?? 'N/A' : 'N/A'),
                            'nombre' => $item->item->nombre ?? 'Sin nombre',
                        ];
                    }

                    return [
                        'id' => $item->id,
                        'item_type' => $item->esProducto() ? 'producto' : 'servicio',
                        'item_id' => $item->item_id,
                        'cantidad' => $item->cantidad,
                        'precio_unitario' => $item->precio_unitario,
                        'tipo_item' => $item->esProducto() ? 'Producto' : 'Servicio',
                        'producto' => $productoData, // La clave que espera el frontend
                    ];
                })->values()->toArray(),
                'costo_estimado' => $costoEstimado,
            ]);
        }

        // Preparar los kitItems para el frontend (para Inertia)
        $kit->kitItems->transform(function ($item) {
            $item->item_type_display = $item->esProducto() ? 'Producto' : 'Servicio';
            $item->item_nombre = $item->item ? $item->item->nombre : 'Sin nombre';
            return $item;
        });

        return Inertia::render('Kits/Show', [
            'kit' => $kit,
            'costoEstimado' => $costoEstimado
        ]);
    }

    public function edit(Producto $kit): Response
    {
        if (!$kit->esKit()) {
            abort(404);
        }

        $kit->load('kitItems.item');

        // Normalizar los item_type para el frontend
        $kit->kitItems->transform(function ($item) {
            $item->item_type = $item->esProducto() ? 'producto' : 'servicio';
            return $item;
        });

        $productosDisponibles = Producto::where('estado', 'activo')
            ->where('tipo_producto', '!=', 'kit')
            ->orderBy('nombre')
            ->get();

        $serviciosDisponibles = \App\Models\Servicio::where('estado', 'activo')
            ->orderBy('nombre')
            ->get();

        $almacenPrincipal = \App\Models\Almacen::orderBy('id')->first();

        $categorias = \App\Models\Categoria::orderBy('nombre')->get();

        return Inertia::render('Kits/Edit', [
            'kit' => $kit,
            'productosDisponibles' => $productosDisponibles,
            'serviciosDisponibles' => $serviciosDisponibles,
            'categorias' => $categorias,
            'almacenPrincipal' => $almacenPrincipal
        ]);
    }

    public function update(Request $request, Producto $kit): JsonResponse
    {
        if (!$kit->esKit()) {
            return response()->json([
                'success' => false,
                'message' => 'El producto especificado no es un kit.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'codigo' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('productos', 'codigo')->ignore($kit->id)->where('empresa_id', EmpresaResolver::resolveId())
            ],
            'precio_venta' => 'required|numeric|min:0',
            'estado' => 'required|in:activo,inactivo',
            'categoria_id' => 'nullable|exists:categorias,id',
            'componentes' => 'required|array|min:1',
            'componentes.*.item_type' => 'required|in:producto,servicio',
            'componentes.*.item_id' => 'required|integer',
            'componentes.*.cantidad' => 'required|integer|min:1',
            'componentes.*.precio_unitario' => 'nullable|numeric|min:0',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'incluye_iva' => 'nullable|boolean',
            'destacado' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Validar que los productos no sean kits
            $productosIds = collect($request->componentes)
                ->where('item_type', 'producto')
                ->pluck('item_id');

            if ($productosIds->isNotEmpty()) {
                $kitsEnComponentes = Producto::whereIn('id', $productosIds)
                    ->where('tipo_producto', 'kit')
                    ->count();

                if ($kitsEnComponentes > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se pueden agregar kits como componentes de otro kit.'
                    ], 422);
                }
            }

            $imagenPath = $kit->imagen;
            if ($request->hasFile('imagen')) {
                // Eliminar imagen anterior si existe
                if ($kit->imagen) {
                    Storage::disk('public')->delete($kit->imagen);
                }

                try {
                    $imagenPath = $this->saveImageAsWebP($request->file('imagen'), 'productos');
                } catch (\Exception $e) {
                    Log::error("Error processing kit image update: " . $e->getMessage());
                    $imagenPath = $request->file('imagen')->store('productos', 'public');
                }
            }

            DB::transaction(function () use ($request, $kit, $imagenPath) {
                // Recalcular costo (solo productos, servicios tienen costo 0)
                $costoTotal = 0;
                $almacenPrincipal = \App\Models\Almacen::orderBy('id')->first();
                $almacenId = $almacenPrincipal?->id ?? 1;

                foreach ($request->componentes as $componenteData) {
                    $itemType = $componenteData['item_type'];
                    $itemId = $componenteData['item_id'];
                    $cantidad = $componenteData['cantidad'];

                    if ($itemType === 'producto') {
                        $producto = Producto::find($itemId);
                        if ($producto) {
                            $costoUnitario = $this->stockService->calcularCostoHistorico(
                                $producto,
                                $cantidad,
                                $almacenId
                            );
                            $costoTotal += $costoUnitario * $cantidad;
                        }
                    }
                    // Los servicios no agregan costo
                }

                $kit->update([
                    'nombre' => $request->nombre,
                    'descripcion' => $request->descripcion ?? '',
                    'codigo' => $request->codigo,
                    'codigo_barras' => $kit->codigo_barras ?? '',
                    'precio_venta' => $request->precio_venta,
                    'precio_compra' => $costoTotal,
                    'estado' => $request->estado,
                    'categoria_id' => $request->categoria_id ?: \App\Models\Categoria::orderBy('id')->first()?->id ?? 1,
                    'imagen' => $imagenPath,
                    'destacado' => $request->boolean('destacado'),
                    'incluye_iva' => $request->boolean('incluye_iva'),
                ]);

                $kit->kitItems()->forceDelete();

                foreach ($request->componentes as $componenteData) {
                    $itemType = $componenteData['item_type'] === 'producto'
                        ? 'producto'
                        : 'servicio';

                    KitItem::create([
                        'kit_id' => $kit->id,
                        'item_type' => $itemType,
                        'item_id' => $componenteData['item_id'],
                        'cantidad' => $componenteData['cantidad'],
                        'precio_unitario' => $componenteData['precio_unitario'] ?? null,
                        'empresa_id' => $kit->empresa_id,
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Kit actualizado exitosamente.',
                'kit' => $kit->fresh()->load('kitItems.item')
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el kit: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Producto $kit): JsonResponse
    {
        if (!$kit->esKit()) {
            return response()->json([
                'success' => false,
                'message' => 'El producto especificado no es un kit.'
            ], 404);
        }

        try {
            DB::transaction(function () use ($kit) {
                $kit->kitItems()->forceDelete();
                $kit->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Kit eliminado exitosamente.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el kit: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiIndex(Request $request): JsonResponse
    {
        $query = Producto::with(['kitItems', 'categoria'])
            ->where('tipo_producto', 'kit');

        if ($request->has('search') && !empty($request->search)) {
            $search = is_array($request->search) ? $request->search['value'] : $request->search;
            if (!empty($search)) {
                $terms = array_filter(explode(' ', trim($search)));
                $query->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->where(function ($subQ) use ($term) {
                            $subQ->where('nombre', 'ILIKE', "%{$term}%")
                                ->orWhere('codigo', 'ILIKE', "%{$term}%")
                                ->orWhere('descripcion', 'ILIKE', "%{$term}%");
                        });
                    }
                });
            }
        }

        if ($request->has('order')) {
            $columnIndex = $request->order[0]['column'];
            $columnName = $request->columns[$columnIndex]['data'];
            $direction = $request->order[0]['dir'];
            $query->orderBy($columnName, $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $totalRecords = Producto::where('tipo_producto', 'kit')->count();
        $filteredRecords = $query->count();

        $page = $request->page ?? 1;
        $perPage = $request->length ?? 10;
        $kits = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $data = $kits->map(function ($kit) {
            return [
                'id' => $kit->id,
                'codigo' => $kit->codigo,
                'nombre' => $kit->nombre,
                'descripcion' => $kit->descripcion,
                'precio_venta' => $kit->precio_venta,
                'componentes_count' => $kit->kitItems->count(),
                'categoria' => $kit->categoria?->nombre ?? 'Sin categoría',
                'estado' => $kit->estado,
                'imagen' => $kit->imagen,
                'image_url' => $kit->imagen ? (str_starts_with($kit->imagen, 'http') ? $kit->imagen : asset('storage/' . $kit->imagen)) : null,
                'destacado' => (bool) $kit->destacado,
                'incluye_iva' => (bool) $kit->incluye_iva,
                'created_at' => $kit->created_at->format('d/m/Y'),
            ];
        });

        $stats = [
            'totalKits' => $totalRecords,
            'kitsActivos' => Producto::where('tipo_producto', 'kit')
                ->where('estado', 'activo')->count(),
            'valorTotal' => Producto::where('tipo_producto', 'kit')
                ->sum('precio_venta')
        ];

        return response()->json([
            'draw' => intval($request->draw ?? 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
            'stats' => $stats,
            'current_page' => $page,
            'last_page' => ceil($filteredRecords / $perPage),
            'from' => (($page - 1) * $perPage) + 1,
            'to' => min($page * $perPage, $filteredRecords),
        ]);
    }

    /**
     * Detalle de kit (JSON) con componentes y datos de producto para usar en ventas.
     */
    public function apiShow(Producto $kit): JsonResponse
    {
        if (!$kit->esKit()) {
            abort(404);
        }

        $kit->load(['kitItems.item']);

        return response()->json([
            'id' => $kit->id,
            'nombre' => $kit->nombre,
            'kit_items' => $kit->kitItems->map(function ($item) {
                $producto = $item->esProducto() && $item->item ? [
                    'id' => $item->item->id,
                    'nombre' => $item->item->nombre,
                    'requiere_serie' => (bool) $item->item->requiere_serie,
                    'maneja_series' => (bool) $item->item->maneja_series,
                    'expires' => (bool) $item->item->expires,
                ] : null;

                return [
                    'id' => $item->id,
                    'item_type' => $item->item_type,
                    'item_id' => $item->item_id,
                    'cantidad' => $item->cantidad,
                    'item' => $producto,      // clave usada en frontend
                    'producto' => $producto,  // alias por compatibilidad previa
                ];
            })->values(),
        ]);
    }

    public function apiProductosDisponibles(Request $request): JsonResponse
    {
        $query = Producto::where('estado', 'activo')
            ->where('tipo_producto', '!=', 'kit');

        if ($request->has('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $terms = array_filter(explode(' ', $search));
                $query->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->where(function ($subQ) use ($term) {
                            $subQ->where('nombre', 'ILIKE', "%{$term}%")
                                ->orWhere('codigo', 'ILIKE', "%{$term}%");
                        });
                    }
                });
            }
        }

        $productos = $query->orderBy('nombre')
            ->limit(50)
            ->get(['id', 'codigo', 'nombre', 'precio_venta']);

        return response()->json($productos);
    }

    public function apiCalcularCosto(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'componentes' => 'required|array|min:1',
            'componentes.*.item_type' => 'required|in:producto,servicio',
            'componentes.*.item_id' => 'required|integer',
            'componentes.*.cantidad' => 'required|integer|min:1',
            'almacen_id' => 'required|exists:almacenes,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $costoTotal = 0;
            $stockService = app(\App\Services\StockValidationService::class);

            foreach ($request->componentes as $componenteData) {
                $itemType = $componenteData['item_type'];
                $itemId = $componenteData['item_id'];
                $cantidad = $componenteData['cantidad'];

                if ($itemType === 'producto') {
                    $producto = Producto::find($itemId);
                    if ($producto) {
                        $costoUnitario = $stockService->calcularCostoHistorico(
                            $producto,
                            $cantidad,
                            $request->almacen_id
                        );
                        $costoTotal += $costoUnitario * $cantidad;
                    }
                }
                // Los servicios no agregan costo (costo = 0, utilidad 100%)
            }

            return response()->json([
                'success' => true,
                'costo_total' => $costoTotal,
                'costo_formateado' => '$' . number_format($costoTotal, 2),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function apiVerificarStock(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'kit_id' => 'required|exists:productos,id',
            'cantidad' => 'required|integer|min:1',
            'almacen_id' => 'required|exists:almacenes,id',
            'componentes_series' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $kit = Producto::find($request->kit_id);
        if (!$kit || !$kit->esKit()) {
            return response()->json([
                'success' => false,
                'errors' => ['kit_id' => ['El producto especificado no es un kit.']],
            ], 422);
        }

        $service = app(\App\Services\StockValidationService::class);
        $result = $service->validateKitStock(
            $kit,
            (int) $request->cantidad,
            (int) $request->almacen_id,
            $request->input('componentes_series', [])
        );

        return response()->json([
            'success' => empty($result),
            'errors' => $result,
        ], 200);
    }

    /**
     * Alternar el estado de destacado de un kit.
     */
    public function toggleDestacado(Producto $kit)
    {
        if (!$kit->esKit()) {
            abort(404);
        }

        $kit->update(['destacado' => !$kit->destacado]);

        return back()->with('success', 'Visibilidad del kit actualizada correctamente.');
    }
}

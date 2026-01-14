<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\Inventario;
use App\Models\Traspaso;
use App\Services\InventarioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class TraspasoController extends Controller
{
    public function __construct(private readonly InventarioService $inventarioService)
    {
    }

    public function index(Request $request)
    {
        $perPage = (int) ($request->integer('per_page') ?: 10);
        $page = max(1, (int) $request->get('page', 1));

        $baseQuery = Traspaso::with([
            'producto',
            'almacenOrigen',
            'almacenDestino',
            'usuarioAutoriza',
            'usuarioEnvia',
            'usuarioRecibe',
        ]);

        // Aplicar filtros
        if ($search = trim($request->get('search', ''))) {
            $baseQuery->where(function ($query) use ($search) {
                $query->where('id', 'like', "%{$search}%")
                    ->orWhereHas('producto', function ($q) use ($search) {
                        $q->where('nombre', 'like', "%{$search}%");
                    })
                    ->orWhereHas('almacenOrigen', function ($q) use ($search) {
                        $q->where('nombre', 'like', "%{$search}%");
                    })
                    ->orWhereHas('almacenDestino', function ($q) use ($search) {
                        $q->where('nombre', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('producto_id')) {
            $baseQuery->where('producto_id', $request->producto_id);
        }

        if ($request->filled('almacen_origen_id')) {
            $baseQuery->where('almacen_origen_id', $request->almacen_origen_id);
        }

        if ($request->filled('almacen_destino_id')) {
            $baseQuery->where('almacen_destino_id', $request->almacen_destino_id);
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $allowedSorts = ['created_at', 'cantidad'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $baseQuery->orderBy($sortBy, $sortDirection === 'asc' ? 'asc' : 'desc');

        $paginator = $baseQuery->paginate($perPage, ['*'], 'page', $page);
        $traspasos = collect($paginator->items());

        $transformed = $traspasos->map(function ($traspaso) {
            // Cargar items si no están ya cargados
            $traspaso->load('items.producto');

            // Construir lista de productos (desde items o legacy)
            $productos = [];
            if ($traspaso->items->isNotEmpty()) {
                foreach ($traspaso->items as $item) {
                    $productos[] = [
                        'id' => $item->producto_id,
                        'nombre' => $item->producto?->nombre ?? 'Producto eliminado',
                        'cantidad' => (int) $item->cantidad,
                    ];
                }
            } elseif ($traspaso->producto) {
                $productos[] = [
                    'id' => $traspaso->producto->id,
                    'nombre' => $traspaso->producto->nombre,
                    'cantidad' => (int) $traspaso->cantidad,
                ];
            }

            return [
                'id' => $traspaso->id,
                // Legacy field - mantener para compatibilidad
                'producto' => $traspaso->producto ? [
                    'id' => $traspaso->producto->id,
                    'nombre' => $traspaso->producto->nombre,
                ] : ($productos[0] ?? null),
                'productos' => $productos,
                'productos_count' => count($productos),
                'almacen_origen' => $traspaso->almacenOrigen ? [
                    'id' => $traspaso->almacenOrigen->id,
                    'nombre' => $traspaso->almacenOrigen->nombre,
                ] : null,
                'almacen_destino' => $traspaso->almacenDestino ? [
                    'id' => $traspaso->almacenDestino->id,
                    'nombre' => $traspaso->almacenDestino->nombre,
                ] : null,
                'cantidad_total' => $traspaso->cantidad_total,
                'observaciones' => $traspaso->observaciones,
                'referencia' => $traspaso->referencia,
                'costo_transporte' => $traspaso->costo_transporte,
                'estado' => $traspaso->estado,
                'created_at' => optional($traspaso->created_at)->format('Y-m-d H:i:s'),
                'fecha' => optional($traspaso->created_at)->format('Y-m-d'),
            ];
        });

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $transformed,
            $paginator->total(),
            $perPage,
            $page,
            ['path' => $request->url(), 'pageName' => 'page']
        );

        // Estadísticas
        $stats = [
            'total' => Traspaso::count(),
            'pendientes' => Traspaso::where('estado', 'pendiente')->count(),
            'en_transito' => Traspaso::where('estado', 'en_transito')->count(),
            'completados' => Traspaso::where('estado', 'completado')->count(),
            'productos_trasladados' => Traspaso::distinct('producto_id')->count('producto_id'),
            'almacenes_origen' => Traspaso::distinct('almacen_origen_id')->count('almacen_origen_id'),
            'almacenes_destino' => Traspaso::distinct('almacen_destino_id')->count('almacen_destino_id'),
        ];

        // Datos para filtros
        $productos = Producto::select('id', 'nombre', 'requiere_serie')->orderBy('nombre')->get();
        $almacenes = Almacen::select('id', 'nombre')->where('estado', 'activo')->orderBy('nombre')->get();

        return Inertia::render('Traspasos/Index', [
            'traspasos' => $paginator,
            'stats' => $stats,
            'filters' => $request->only(['search', 'producto_id', 'almacen_origen_id', 'almacen_destino_id']),
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
            'productos' => $productos,
            'almacenes' => $almacenes,
        ]);
    }

    public function create()
    {
        $productos = Producto::select('id', 'nombre', 'requiere_serie')->get();
        $almacenes = Almacen::select('id', 'nombre')->get();
        $inventarios = Inventario::with(['producto', 'almacen'])->get();

        return Inertia::render('Traspasos/Create', [
            'productos' => $productos,
            'almacenes' => $almacenes,
            'inventarios' => $inventarios,
        ]);
    }

    public function store(Request $request)
    {
        // Validación para múltiples productos
        $request->validate([
            'almacen_origen_id' => 'required|exists:almacenes,id',
            'almacen_destino_id' => 'required|exists:almacenes,id|different:almacen_origen_id',
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:productos,id',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.series' => 'nullable|array',
            'items.*.series.*' => 'integer|exists:producto_series,id',
            'observaciones' => 'nullable|string|max:1000',
            'referencia' => 'nullable|string|max:100',
            'costo_transporte' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $almacenOrigenId = $request->almacen_origen_id;
                $almacenDestinoId = $request->almacen_destino_id;
                $items = $request->items;

                // Obtener almacenes
                $almacenOrigen = Almacen::findOrFail($almacenOrigenId);
                $almacenDestino = Almacen::findOrFail($almacenDestinoId);

                // Crear el traspaso (sin producto_id ya que ahora usa items)
                $traspaso = Traspaso::create([
                    'almacen_origen_id' => $almacenOrigenId,
                    'almacen_destino_id' => $almacenDestinoId,
                    'estado' => 'completado',
                    'usuario_autoriza' => auth()->id(),
                    'usuario_envia' => auth()->id(),
                    'fecha_envio' => now(),
                    'fecha_recepcion' => now(),
                    'observaciones' => $request->observaciones,
                    'referencia' => $request->referencia,
                    'costo_transporte' => $request->costo_transporte,
                ]);

                // Procesar cada item
                foreach ($items as $itemData) {
                    $productoId = $itemData['producto_id'];
                    $producto = Producto::findOrFail($productoId);
                    $seriesIds = collect($itemData['series'] ?? [])
                        ->filter()
                        ->map(fn($id) => (int) $id)
                        ->values()
                        ->toArray();

                    // Determinar cantidad
                    if ($producto->requiere_serie) {
                        if (empty($seriesIds)) {
                            throw new \Exception("Debe seleccionar las series a traspasar para el producto: {$producto->nombre}");
                        }

                        // Validar que las series estén en el almacén origen
                        $seriesEnOrigen = \App\Models\ProductoSerie::whereIn('id', $seriesIds)
                            ->where('producto_id', $productoId)
                            ->where('almacen_id', $almacenOrigenId)
                            ->where('estado', 'en_stock')
                            ->pluck('id')
                            ->toArray();

                        if (count($seriesEnOrigen) !== count($seriesIds)) {
                            throw new \Exception("Algunas series del producto '{$producto->nombre}' no están disponibles en el almacén origen.");
                        }

                        $cantidad = count($seriesIds);
                    } else {
                        $cantidad = (int) $itemData['cantidad'];
                    }

                    // Verificar stock en origen
                    $inventarioOrigen = Inventario::where('producto_id', $productoId)
                        ->where('almacen_id', $almacenOrigenId)
                        ->first();

                    if (!$inventarioOrigen || $inventarioOrigen->cantidad < $cantidad) {
                        throw new \Exception("Stock insuficiente del producto '{$producto->nombre}' en el almacén origen.");
                    }

                    // Crear el item del traspaso
                    $traspasoItem = \App\Models\TraspasoItem::create([
                        'traspaso_id' => $traspaso->id,
                        'producto_id' => $productoId,
                        'cantidad' => $cantidad,
                        'series_ids' => $producto->requiere_serie ? $seriesIds : null,
                    ]);

                    // Mover series o inventario según el tipo de producto
                    if ($producto->requiere_serie && !empty($seriesIds)) {
                        // Mover series usando Eloquent para disparar el Observer
                        $seriesModelos = \App\Models\ProductoSerie::whereIn('id', $seriesIds)->get();
                        foreach ($seriesModelos as $serie) {
                            $serie->update(['almacen_id' => $almacenDestinoId]);
                        }

                        Log::info('Traspaso - Series movidas', [
                            'traspaso_id' => $traspaso->id,
                            'producto_id' => $productoId,
                            'series_count' => count($seriesIds),
                        ]);
                    } else {
                        // Para productos NO serializados, usar InventarioService
                        $lotesUsados = $this->inventarioService->salida($producto, $cantidad, [
                            'almacen_id' => $almacenOrigenId,
                            'motivo' => 'Traspaso a ' . $almacenDestino->nombre,
                            'referencia' => $traspaso,
                            'detalles' => ['traspaso_id' => $traspaso->id, 'item_id' => $traspasoItem->id],
                            'skip_transaction' => true,
                        ]);

                        // Entrada al almacén destino
                        if ($producto->expires && !empty($lotesUsados)) {
                            foreach ($lotesUsados as $loteData) {
                                $lote = $loteData['lote'];
                                $cantidadLote = $loteData['cantidad'];

                                $this->inventarioService->entrada($producto, $cantidadLote, [
                                    'almacen_id' => $almacenDestinoId,
                                    'motivo' => 'Traspaso desde ' . $almacenOrigen->nombre,
                                    'referencia' => $traspaso,
                                    'detalles' => ['traspaso_id' => $traspaso->id, 'item_id' => $traspasoItem->id],
                                    'numero_lote' => $lote->numero_lote,
                                    'fecha_caducidad' => $lote->fecha_caducidad,
                                    'costo_unitario' => $lote->costo_unitario,
                                    'skip_transaction' => true,
                                ]);
                            }
                        } else {
                            $this->inventarioService->entrada($producto, $cantidad, [
                                'almacen_id' => $almacenDestinoId,
                                'motivo' => 'Traspaso desde ' . $almacenOrigen->nombre,
                                'referencia' => $traspaso,
                                'detalles' => ['traspaso_id' => $traspaso->id, 'item_id' => $traspasoItem->id],
                                'skip_transaction' => true,
                            ]);
                        }
                    }
                }

                Log::info('Traspaso creado con múltiples productos', [
                    'traspaso_id' => $traspaso->id,
                    'items_count' => count($items),
                    'origen' => $almacenOrigenId,
                    'destino' => $almacenDestinoId,
                ]);
            });

            return redirect()->route('traspasos.index')->with('success', 'Traspaso realizado correctamente');
        } catch (\Exception $e) {
            Log::error('Error en traspaso: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(Traspaso $traspaso)
    {
        $traspaso->load(['producto', 'almacenOrigen', 'almacenDestino', 'usuarioAutoriza', 'usuarioEnvia', 'usuarioRecibe', 'items.producto']);

        // Construir lista de productos
        $productos = [];
        if ($traspaso->items->isNotEmpty()) {
            foreach ($traspaso->items as $item) {
                $productos[] = [
                    'id' => $item->producto_id,
                    'nombre' => $item->producto?->nombre ?? 'Producto eliminado',
                    'cantidad' => (int) $item->cantidad,
                ];
            }
        } elseif ($traspaso->producto) {
            $productos[] = [
                'id' => $traspaso->producto->id,
                'nombre' => $traspaso->producto->nombre,
                'cantidad' => (int) $traspaso->cantidad,
            ];
        }

        return Inertia::render('Traspasos/Show', [
            'traspaso' => [
                'id' => $traspaso->id,
                'producto' => $traspaso->producto?->only(['id', 'nombre']),
                'productos' => $productos,
                'productos_count' => count($productos),
                'almacen_origen' => $traspaso->almacenOrigen?->only(['id', 'nombre']),
                'almacen_destino' => $traspaso->almacenDestino?->only(['id', 'nombre']),
                'cantidad_total' => $traspaso->cantidad_total,
                'observaciones' => $traspaso->observaciones,
                'referencia' => $traspaso->referencia,
                'costo_transporte' => $traspaso->costo_transporte,
                'estado' => $traspaso->estado,
                'created_at' => optional($traspaso->created_at)->format('Y-m-d H:i:s'),
                'fecha_envio' => optional($traspaso->fecha_envio)->format('Y-m-d H:i:s'),
                'fecha_recepcion' => optional($traspaso->fecha_recepcion)->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    public function edit(Traspaso $traspaso)
    {
        $traspaso->load(['producto', 'almacenOrigen', 'almacenDestino', 'items.producto']);

        // Construir lista de productos
        $productos = [];
        if ($traspaso->items->isNotEmpty()) {
            foreach ($traspaso->items as $item) {
                $productos[] = [
                    'id' => $item->producto_id,
                    'nombre' => $item->producto?->nombre ?? 'Producto eliminado',
                    'cantidad' => (int) $item->cantidad,
                ];
            }
        } elseif ($traspaso->producto) {
            $productos[] = [
                'id' => $traspaso->producto->id,
                'nombre' => $traspaso->producto->nombre,
                'cantidad' => (int) $traspaso->cantidad,
            ];
        }

        return Inertia::render('Traspasos/Edit', [
            'traspaso' => [
                'id' => $traspaso->id,
                'productos' => $productos,
                'productos_count' => count($productos),
                'almacen_origen' => $traspaso->almacenOrigen?->only(['id', 'nombre']),
                'almacen_destino' => $traspaso->almacenDestino?->only(['id', 'nombre']),
                'cantidad_total' => $traspaso->cantidad_total,
                'observaciones' => $traspaso->observaciones,
                'referencia' => $traspaso->referencia,
                'costo_transporte' => $traspaso->costo_transporte,
                'estado' => $traspaso->estado,
                'created_at' => optional($traspaso->created_at)->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    public function update(Request $request, Traspaso $traspaso)
    {
        $data = $request->validate([
            'observaciones' => 'nullable|string|max:1000',
            'referencia' => 'nullable|string|max:100',
            'costo_transporte' => 'nullable|numeric|min:0',
        ]);

        $traspaso->update($data);

        return redirect()->route('traspasos.index')->with('success', 'Traspaso actualizado.');
    }

    public function destroy(Traspaso $traspaso)
    {
        try {
            DB::transaction(function () use ($traspaso) {
                $almacenOrigenId = $traspaso->almacen_origen_id;
                $almacenDestinoId = $traspaso->almacen_destino_id;

                // Cargar items del traspaso
                $items = $traspaso->items;

                // Si no hay items (traspaso legacy), intentar con producto_id directo
                if ($items->isEmpty() && $traspaso->producto_id) {
                    $items = collect([
                        (object) [
                            'producto_id' => $traspaso->producto_id,
                            'cantidad' => $traspaso->cantidad,
                            'series_ids' => null,
                        ]
                    ]);
                }

                if ($items->isEmpty()) {
                    throw new \RuntimeException('No hay productos para revertir en este traspaso.');
                }

                // Revertir cada item
                foreach ($items as $item) {
                    $producto = Producto::find($item->producto_id);
                    if (!$producto) {
                        Log::warning('Producto no encontrado al revertir traspaso', [
                            'traspaso_id' => $traspaso->id,
                            'producto_id' => $item->producto_id,
                        ]);
                        continue;
                    }

                    $cantidad = (int) $item->cantidad;

                    if ($producto->requiere_serie) {
                        // Para productos serializados, buscar series en destino y revertir a origen
                        $seriesDisponibles = \App\Models\ProductoSerie::where('producto_id', $producto->id)
                            ->where('almacen_id', $almacenDestinoId)
                            ->where('estado', 'en_stock')
                            ->limit($cantidad)
                            ->get();

                        if ($seriesDisponibles->isEmpty()) {
                            throw new \RuntimeException("No hay series del producto '{$producto->nombre}' disponibles en el almacén destino para revertir.");
                        }

                        foreach ($seriesDisponibles as $serie) {
                            $serie->update(['almacen_id' => $almacenOrigenId]);
                        }

                        Log::info('Traspaso - Series revertidas', [
                            'traspaso_id' => $traspaso->id,
                            'producto_id' => $producto->id,
                            'series_count' => $seriesDisponibles->count(),
                        ]);
                    } else {
                        // Verificar inventario en destino
                        $inventarioDestino = Inventario::where('producto_id', $producto->id)
                            ->where('almacen_id', $almacenDestinoId)
                            ->first();

                        $cantidadRevertir = min($cantidad, $inventarioDestino->cantidad ?? 0);

                        if ($cantidadRevertir <= 0) {
                            throw new \RuntimeException("No hay stock del producto '{$producto->nombre}' en el almacén destino para revertir.");
                        }

                        // Revertir inventario
                        $lotesUsados = $this->inventarioService->salida($producto, $cantidadRevertir, [
                            'almacen_id' => $almacenDestinoId,
                            'motivo' => 'Reverso de traspaso (eliminación)',
                            'referencia' => $traspaso,
                            'detalles' => ['traspaso_id' => $traspaso->id, 'item_producto_id' => $producto->id],
                            'skip_transaction' => true,
                        ]);

                        if ($producto->expires && !empty($lotesUsados)) {
                            foreach ($lotesUsados as $loteData) {
                                $loteStored = $loteData['lote'];
                                $cantLote = $loteData['cantidad'];

                                $this->inventarioService->entrada($producto, $cantLote, [
                                    'almacen_id' => $almacenOrigenId,
                                    'motivo' => 'Reverso de traspaso (eliminación)',
                                    'referencia' => $traspaso,
                                    'detalles' => ['traspaso_id' => $traspaso->id, 'item_producto_id' => $producto->id],
                                    'numero_lote' => $loteStored->numero_lote,
                                    'fecha_caducidad' => $loteStored->fecha_caducidad,
                                    'costo_unitario' => $loteStored->costo_unitario,
                                    'skip_transaction' => true,
                                ]);
                            }
                        } else {
                            $this->inventarioService->entrada($producto, $cantidadRevertir, [
                                'almacen_id' => $almacenOrigenId,
                                'motivo' => 'Reverso de traspaso (eliminación)',
                                'referencia' => $traspaso,
                                'detalles' => ['traspaso_id' => $traspaso->id, 'item_producto_id' => $producto->id],
                                'skip_transaction' => true,
                            ]);
                        }
                    }
                }

                // Eliminar el traspaso (cascade eliminará los items)
                $traspaso->delete();

                Log::info('Traspaso eliminado y revertido', [
                    'traspaso_id' => $traspaso->id,
                    'items_count' => $items->count(),
                ]);
            });

            return redirect()->route('traspasos.index')->with('success', 'Traspaso eliminado y el inventario ha sido revertido.');
        } catch (\Throwable $e) {
            Log::error('Error al eliminar traspaso: ' . $e->getMessage());
            return redirect()->back()->with('error', 'No se pudo eliminar el traspaso: ' . $e->getMessage());
        }
    }
}


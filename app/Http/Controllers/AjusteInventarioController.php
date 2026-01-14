<?php

namespace App\Http\Controllers;

use App\Models\AjusteInventario;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\Inventario;
use App\Models\ProductoSerie;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Validation\ValidationException;

class AjusteInventarioController extends Controller
{
    public function __construct(private readonly InventarioService $inventarioService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = (int) ($request->integer('per_page') ?: 10);
        $page = max(1, (int) $request->get('page', 1));

        $baseQuery = AjusteInventario::with([
            'producto',
            'almacen',
            'usuario',
        ]);

        // Aplicar filtros
        if ($search = trim($request->get('search', ''))) {
            $baseQuery->where(function ($query) use ($search) {
                $query->where('id', 'like', "%{$search}%")
                    ->orWhere('motivo', 'like', "%{$search}%")
                    ->orWhereHas('producto', function ($q) use ($search) {
                        $q->where('nombre', 'like', "%{$search}%");
                    })
                    ->orWhereHas('almacen', function ($q) use ($search) {
                        $q->where('nombre', 'like', "%{$search}%");
                    })
                    ->orWhereHas('usuario', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('producto_id')) {
            $baseQuery->where('producto_id', $request->producto_id);
        }

        if ($request->filled('almacen_id')) {
            $baseQuery->where('almacen_id', $request->almacen_id);
        }

        if ($request->filled('tipo')) {
            $baseQuery->where('tipo', $request->tipo);
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $allowedSorts = ['created_at', 'cantidad_ajuste'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $baseQuery->orderBy($sortBy, $sortDirection === 'asc' ? 'asc' : 'desc');

        $paginator = $baseQuery->paginate($perPage, ['*'], 'page', $page);
        $ajustes = collect($paginator->items());

        $transformed = $ajustes->map(function ($ajuste) {
            return [
                'id' => $ajuste->id,
                'producto' => $ajuste->producto ? [
                    'id' => $ajuste->producto->id,
                    'nombre' => $ajuste->producto->nombre,
                    'codigo' => $ajuste->producto->codigo,
                ] : null,
                'almacen' => $ajuste->almacen ? [
                    'id' => $ajuste->almacen->id,
                    'nombre' => $ajuste->almacen->nombre,
                ] : null,
                'usuario' => $ajuste->usuario ? [
                    'id' => $ajuste->usuario->id,
                    'name' => $ajuste->usuario->name,
                ] : null,
                'tipo' => $ajuste->tipo,
                'cantidad_anterior' => (int) $ajuste->cantidad_anterior,
                'cantidad_ajuste' => (int) $ajuste->cantidad_ajuste,
                'cantidad_nueva' => (int) $ajuste->cantidad_nueva,
                'motivo' => $ajuste->motivo,
                'observaciones' => $ajuste->observaciones,
                'created_at' => optional($ajuste->created_at)->format('Y-m-d H:i:s'),
                'fecha' => optional($ajuste->created_at)->format('Y-m-d'),
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
            'total' => AjusteInventario::count(),
            'incrementos' => AjusteInventario::where('tipo', 'incremento')->count(),
            'decrementos' => AjusteInventario::where('tipo', 'decremento')->count(),
            'productos_ajustados' => AjusteInventario::distinct('producto_id')->count('producto_id'),
            'almacenes_afectados' => AjusteInventario::distinct('almacen_id')->count('almacen_id'),
        ];

        // Datos para filtros
        $productos = Producto::select('id', 'nombre')->orderBy('nombre')->get();
        $almacenes = Almacen::select('id', 'nombre')->where('estado', 'activo')->orderBy('nombre')->get();

        return Inertia::render('AjustesInventario/Index', [
            'ajustes' => $paginator,
            'stats' => $stats,
            'filters' => $request->only(['search', 'producto_id', 'almacen_id', 'tipo']),
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

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $productos = Producto::select('id', 'nombre', 'codigo', 'requiere_serie')->orderBy('nombre')->get();
        $almacenes = Almacen::select('id', 'nombre')->where('estado', 'activo')->orderBy('nombre')->get();

        return Inertia::render('AjustesInventario/Create', [
            'productos' => $productos,
            'almacenes' => $almacenes,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'almacen_id' => 'required|exists:almacenes,id',
            'tipo' => 'required|in:incremento,decremento',
            'cantidad_ajuste' => 'required|integer|min:1',
            'motivo' => 'required|string|max:255',
            'observaciones' => 'nullable|string|max:1000',
            // ✅ CRITICAL FIX: Validate lot info if product expires
            'numero_lote' => 'nullable|string|max:50',
            'fecha_caducidad' => 'nullable|date',
        ]);

        $producto = Producto::findOrFail($request->producto_id);
        $cantidadAjuste = (int) $request->cantidad_ajuste;

        // Validaciones previas para evitar 500 y devolver 422 con mensajes claros
        $productoId = $request->producto_id;
        $almacenId = $request->almacen_id;
        $tipo = $request->tipo;

        // Validar que no quede stock negativo
        $inventarioPrev = Inventario::where('producto_id', $productoId)
            ->where('almacen_id', $almacenId)
            ->first();
        $cantidadAnteriorPrev = $inventarioPrev ? $inventarioPrev->cantidad : 0;
        $cantidadNuevaPrev = $tipo === 'incremento'
            ? $cantidadAnteriorPrev + $cantidadAjuste
            : $cantidadAnteriorPrev - $cantidadAjuste;
        if ($cantidadNuevaPrev < 0) {
            throw ValidationException::withMessages([
                'cantidad_ajuste' => 'El ajuste resultaría en stock negativo. Stock actual: ' . $cantidadAnteriorPrev,
            ]);
        }

        // Validar series si aplica
        if ($producto->requiere_serie) {
            $seriales = array_map(function ($s) {
                return trim((string) $s);
            }, (array) $request->input('seriales', []));

            if ($tipo === 'incremento') {
                $existentes = DB::table('producto_series')
                    ->whereIn('numero_serie', $seriales)
                    ->pluck('numero_serie')
                    ->all();
                if (!empty($existentes)) {
                    throw ValidationException::withMessages([
                        'seriales' => 'Los siguientes números de serie ya existen: ' . implode(', ', $existentes),
                    ]);
                }
            } else {
                $seriesEnStock = ProductoSerie::where('producto_id', $producto->id)
                    ->whereIn('numero_serie', $seriales)
                    ->where('estado', 'en_stock')
                    ->where(function ($q) use ($almacenId) {
                        $q->whereNull('almacen_id')->orWhere('almacen_id', $almacenId);
                    })
                    ->pluck('id');
                if (count($seriales) !== $seriesEnStock->count()) {
                    throw ValidationException::withMessages([
                        'seriales' => 'Algunas series no existen o no están en stock para el producto y almacén seleccionados.',
                    ]);
                }
            }
        }

        // Si el producto requiere serie, validar seriales según tipo
        if ($producto->requiere_serie) {
            $rules = [
                'seriales' => ['required', 'array', 'size:' . $cantidadAjuste],
                'seriales.*' => ['required', 'string', 'max:191', 'distinct'],
            ];
            $request->validate($rules);
        }

        if ($producto->expires && $tipo === 'incremento') {
            if (empty($request->numero_lote)) {
                throw ValidationException::withMessages([
                    'numero_lote' => 'El número de lote es obligatorio para productos que caducan.',
                ]);
            }
            if (empty($request->fecha_caducidad)) {
                throw ValidationException::withMessages([
                    'fecha_caducidad' => 'La fecha de caducidad es obligatoria para productos que caducan.',
                ]);
            }
        }

        // Validar campos opcionales para paridad con MovimientoManual
        $request->validate([
            'costo_unitario' => 'nullable|numeric|min:0',
            'categoria' => 'nullable|string|max:100',
            'referencia' => 'nullable|string|max:100',
        ]);

        try {
            DB::transaction(function () use ($request, $producto, $cantidadAjuste) {
                $productoId = $request->producto_id;
                $almacenId = $request->almacen_id;
                $tipo = $request->tipo;
                $cantidadAjuste = (int) $cantidadAjuste;

                // Extra fields
                $costoUnitario = $request->input('costo_unitario');
                $categoria = $request->input('categoria');
                $referencia = $request->input('referencia');

                // Obtener el inventario actual
                $inventario = Inventario::where('producto_id', $productoId)
                    ->where('almacen_id', $almacenId)
                    ->first();

                $cantidadAnterior = $inventario ? $inventario->cantidad : 0;

                // Calcular nueva cantidad
                $cantidadNueva = $tipo === 'incremento'
                    ? $cantidadAnterior + $cantidadAjuste
                    : $cantidadAnterior - $cantidadAjuste;

                // Validar que no quede stock negativo
                if ($cantidadNueva < 0) {
                    throw new \Exception('El ajuste resultaría en stock negativo. Stock actual: ' . $cantidadAnterior);
                }

                // Manejo de series si aplica
                if ($producto->requiere_serie) {
                    $seriales = array_map(function ($s) {
                        return trim((string) $s);
                    }, (array) $request->input('seriales', []));

                    if ($tipo === 'incremento') {
                        // Validar que no existan ya (únicos por tabla)
                        $existentes = DB::table('producto_series')
                            ->whereIn('numero_serie', $seriales)
                            ->pluck('numero_serie')
                            ->all();

                        if (!empty($existentes)) {
                            throw new \Exception('Los siguientes números de serie ya existen: ' . implode(', ', $existentes));
                        }

                        foreach ($seriales as $serieStr) {
                            ProductoSerie::create([
                                'producto_id' => $producto->id,
                                'compra_id' => null,
                                'almacen_id' => $almacenId,
                                'numero_serie' => $serieStr,
                                'estado' => 'en_stock',
                            ]);
                        }
                    } else { // decremento
                        $seriesEnStock = ProductoSerie::where('producto_id', $producto->id)
                            ->whereIn('numero_serie', $seriales)
                            ->where('estado', 'en_stock')
                            ->where(function ($q) use ($almacenId) {
                                // Permitir series legacy sin almacén, o que coincidan con el almacén seleccionado
                                $q->whereNull('almacen_id')->orWhere('almacen_id', $almacenId);
                            })
                            ->pluck('id');

                        if (count($seriales) !== $seriesEnStock->count()) {
                            throw new \Exception('Algunas series no existen o no están en stock para el producto seleccionado en el almacén elegido.');
                        }

                        // ✅ FIX: Iterar para disparar eventos del Observer
                        foreach ($seriesEnStock as $serieId) {
                            $serie = ProductoSerie::find($serieId);
                            if ($serie) {
                                $serie->update(['estado' => 'ajuste_baja', 'almacen_id' => $almacenId]);
                            }
                        }
                    }
                }

                // Usar el servicio para ajustar inventario SOLO si no requiere serie
                // Si requiere serie, el ProductoSerieObserver ya disparó los movimientos vía InventarioService
                if (!$producto->requiere_serie) {
                    $detalles = [
                        'tipo_ajuste' => $tipo,
                        'cantidad_anterior' => $cantidadAnterior,
                        'cantidad_nueva' => $cantidadNueva,
                        'observaciones' => $request->observaciones,
                        // Campos consolidados
                        'categoria' => $categoria,
                        'referencia' => $referencia,
                        'costo_unitario' => $costoUnitario,
                    ];

                    if ($tipo === 'incremento') {
                        $this->inventarioService->entrada($producto, $cantidadAjuste, [
                            'almacen_id' => $almacenId,
                            'motivo' => 'Ajuste de inventario: ' . $request->motivo,
                            'detalles' => $detalles,
                            // ✅ CRITICAL FIX: Pass lot info
                            'numero_lote' => $request->numero_lote,
                            'fecha_caducidad' => $request->fecha_caducidad,
                            'costo_unitario' => $costoUnitario ?? ($producto->precio_compra ?? 0),
                        ]);
                    } else {
                        // En salidas no re-inyectamos costo, usamos FIFO/Promedio implícito, 
                        // pero guardamos el costo unitario de referencia si se proveyó para registro
                        $this->inventarioService->salida($producto, $cantidadAjuste, [
                            'almacen_id' => $almacenId,
                            'motivo' => 'Ajuste de inventario: ' . $request->motivo,
                            'detalles' => $detalles,
                        ]);
                    }
                }

                // Registrar el ajuste
                AjusteInventario::create([
                    'producto_id' => $productoId,
                    'almacen_id' => $almacenId,
                    'user_id' => auth()->id(),
                    'tipo' => $tipo,
                    'cantidad_anterior' => $cantidadAnterior,
                    'cantidad_ajuste' => $cantidadAjuste,
                    'cantidad_nueva' => $cantidadNueva,
                    'motivo' => $request->motivo,
                    'observaciones' => $request->observaciones,
                ]);

                Log::info('Ajuste de inventario realizado', [
                    'producto_id' => $productoId,
                    'almacen_id' => $almacenId,
                    'tipo' => $tipo,
                    'cantidad_anterior' => $cantidadAnterior,
                    'cantidad_ajuste' => $cantidadAjuste,
                    'cantidad_nueva' => $cantidadNueva,
                ]);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withErrors(['seriales' => $e->getMessage()])->withInput();
        }

        return redirect()->route('ajustes-inventario.index')->with('success', 'Ajuste de inventario realizado correctamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $ajuste = AjusteInventario::with(['producto', 'almacen', 'usuario'])->findOrFail($id);

        return Inertia::render('AjustesInventario/Show', [
            'ajuste' => $ajuste,
        ]);
    }
}

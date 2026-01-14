<?php

namespace App\Http\Controllers;

use App\Models\MovimientoManual;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\Inventario;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class MovimientoManualController extends Controller
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

        $baseQuery = MovimientoManual::with(['producto', 'almacen', 'usuario']);

        // Aplicar filtros
        if ($search = trim($request->get('search', ''))) {
            $baseQuery->where(function ($query) use ($search) {
                $query->where('id', 'like', "%{$search}%")
                      ->orWhere('motivo', 'like', "%{$search}%")
                      ->orWhere('referencia', 'like', "%{$search}%")
                      ->orWhereHas('producto', function ($q) use ($search) {
                          $q->where('nombre', 'like', "%{$search}%");
                      })
                      ->orWhereHas('almacen', function ($q) use ($search) {
                          $q->where('nombre', 'like', "%{$search}%");
                      });
            });
        }

        if ($request->filled('tipo')) {
            $baseQuery->where('tipo', $request->tipo);
        }

        if ($request->filled('categoria')) {
            $baseQuery->where('categoria', $request->categoria);
        }

        $baseQuery->orderBy('created_at', 'desc');

        $paginator = $baseQuery->paginate($perPage);

        $stats = [
            'total' => MovimientoManual::count(),
            'entradas' => MovimientoManual::where('tipo', 'entrada')->count(),
            'salidas' => MovimientoManual::where('tipo', 'salida')->count(),
            'productos_afectados' => MovimientoManual::distinct('producto_id')->count('producto_id'),
        ];

        return Inertia::render('MovimientosManuales/Index', [
            'movimientos' => $paginator,
            'stats' => $stats,
            'filters' => $request->only(['search', 'tipo', 'categoria']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $productos = Producto::select('id', 'nombre', 'codigo')->orderBy('nombre')->get();
        $almacenes = Almacen::select('id', 'nombre')->where('estado', 'activo')->orderBy('nombre')->get();

        return Inertia::render('MovimientosManuales/Create', [
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
            'tipo' => 'required|in:entrada,salida',
            'cantidad' => 'required|integer|min:1',
            'costo_unitario' => 'nullable|numeric|min:0',
            'categoria' => 'nullable|string|max:50',
            'motivo' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string|max:1000',
            'referencia' => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($request) {
            $producto = Producto::findOrFail($request->producto_id);
            $tipo = $request->tipo;
            $cantidad = $request->cantidad;

            // Calcular total si hay costo unitario
            $total = null;
            if ($request->costo_unitario && $request->costo_unitario > 0) {
                $total = $request->costo_unitario * $cantidad;
            }

            // Registrar movimiento manual
            MovimientoManual::create([
                'producto_id' => $request->producto_id,
                'almacen_id' => $request->almacen_id,
                'user_id' => auth()->id(),
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'costo_unitario' => $request->costo_unitario,
                'total' => $total,
                'categoria' => $request->categoria,
                'motivo' => $request->motivo,
                'observaciones' => $request->observaciones,
                'referencia' => $request->referencia,
            ]);

            // Usar el servicio para ajustar inventario
            if ($tipo === 'entrada') {
                $this->inventarioService->entrada($producto, $cantidad, [
                    'almacen_id' => $request->almacen_id,
                    'motivo' => $request->motivo ?: 'Movimiento manual',
                    'detalles' => [
                        'categoria' => $request->categoria,
                        'referencia' => $request->referencia,
                        'costo_unitario' => $request->costo_unitario,
                        'total' => $total,
                        'observaciones' => $request->observaciones,
                    ],
                ]);
            } else {
                $this->inventarioService->salida($producto, $cantidad, [
                    'almacen_id' => $request->almacen_id,
                    'motivo' => $request->motivo ?: 'Movimiento manual',
                    'detalles' => [
                        'categoria' => $request->categoria,
                        'referencia' => $request->referencia,
                        'costo_unitario' => $request->costo_unitario,
                        'total' => $total,
                        'observaciones' => $request->observaciones,
                    ],
                ]);
            }

            Log::info('Movimiento manual registrado', [
                'producto_id' => $request->producto_id,
                'almacen_id' => $request->almacen_id,
                'tipo' => $tipo,
                'cantidad' => $cantidad,
            ]);
        });

        return redirect()->route('movimientos-manuales.index')->with('success', 'Movimiento manual registrado correctamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $movimiento = MovimientoManual::with(['producto', 'almacen', 'usuario'])->findOrFail($id);

        return Inertia::render('MovimientosManuales/Show', [
            'movimiento' => $movimiento,
        ]);
    }
}

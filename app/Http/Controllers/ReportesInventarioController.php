<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Almacen;
use App\Models\MovimientoManual;
use App\Models\AjusteInventario;
use App\Models\Traspaso;
use App\Models\InventarioMovimiento;
use App\Support\EmpresaResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportesInventarioController extends Controller
{
    /**
     * Dashboard principal de reportes de inventario
     */
    public function index()
    {
        $empresaId = EmpresaResolver::resolveId();

        // Estadísticas generales
        $stats = [
            'total_productos' => Producto::count(),
            'productos_activos' => Producto::where('estado', 'activo')->count(),
            'total_almacenes' => Almacen::where('estado', 'activo')->count(),
            'total_stock' => DB::table('inventarios')
                ->when($empresaId, fn($query) => $query->where('empresa_id', $empresaId))
                ->sum('cantidad'),
            'productos_sin_stock' => Producto::where('stock', 0)->count(),
            'productos_bajo_stock' => Producto::whereRaw('stock <= stock_minimo AND stock_minimo > 0')->count(),
        ];

        // Valor total del inventario (aproximado)
        $valorTotal = DB::table('productos')
            ->join('inventarios', 'productos.id', '=', 'inventarios.producto_id')
            ->when($empresaId, fn($query) => $query->where('productos.empresa_id', $empresaId)
                ->where('inventarios.empresa_id', $empresaId))
            ->where('productos.estado', 'activo')
            ->sum(DB::raw('productos.precio_venta * inventarios.cantidad'));

        $stats['valor_total_inventario'] = $valorTotal;

        return Inertia::render('ReportesInventario/Index', [
            'stats' => $stats,
        ]);
    }

    /**
     * Reporte de stock por almacén
     */
    public function stockPorAlmacen(Request $request)
    {
        $empresaId = EmpresaResolver::resolveId();
        $almacenId = $request->get('almacen_id');

        $query = DB::table('inventarios')
            ->join('productos', 'inventarios.producto_id', '=', 'productos.id')
            ->join('almacenes', 'inventarios.almacen_id', '=', 'almacenes.id')
            ->when($empresaId, fn($query) => $query->where('inventarios.empresa_id', $empresaId))
            ->where('productos.estado', 'activo')
            ->where('almacenes.estado', 'activo')
            ->select([
                'inventarios.*',
                'productos.nombre as producto_nombre',
                'productos.codigo as producto_codigo',
                'productos.precio_venta',
                'productos.stock_minimo',
                'almacenes.nombre as almacen_nombre'
            ]);

        if ($almacenId) {
            $query->where('inventarios.almacen_id', $almacenId);
        }

        $inventarios = $query->get()->groupBy('almacen_nombre');

        $reporte = [];
        foreach ($inventarios as $almacenNombre => $items) {
            $reporte[] = [
                'almacen' => $almacenNombre,
                'productos' => $items->map(function ($item) {
                    return [
                        'producto' => $item->producto_nombre,
                        'codigo' => $item->producto_codigo,
                        'cantidad' => $item->cantidad,
                        'stock_minimo' => $item->stock_minimo,
                        'precio_venta' => $item->precio_venta,
                        'valor_total' => $item->cantidad * $item->precio_venta,
                        'estado' => $item->cantidad <= $item->stock_minimo ? 'bajo_stock' : 'normal',
                    ];
                }),
                'total_productos' => $items->count(),
                'total_cantidad' => $items->sum('cantidad'),
                'valor_total' => $items->sum(function ($item) {
                    return $item->cantidad * $item->precio_venta;
                }),
            ];
        }

        $almacenes = Almacen::where('estado', 'activo')->select('id', 'nombre')->get();

        return Inertia::render('ReportesInventario/StockPorAlmacen', [
            'reporte' => $reporte,
            'almacenes' => $almacenes,
            'filtros' => $request->only(['almacen_id']),
        ]);
    }

    /**
     * Reporte de productos con bajo stock
     */
    public function productosBajoStock()
    {
        $empresaId = EmpresaResolver::resolveId();

        $productos = Producto::with(['categoria', 'marca'])
            ->where('estado', 'activo')
            ->whereRaw('stock <= stock_minimo')
            ->where('stock_minimo', '>', 0)
            ->orderBy('stock', 'asc')
            ->get()
            ->map(function ($producto) {
                // Obtener distribución por almacén
                $distribucion = DB::table('inventarios')
                    ->join('almacenes', 'inventarios.almacen_id', '=', 'almacenes.id')
                    ->where('inventarios.producto_id', $producto->id)
                    ->when($empresaId, fn($query) => $query->where('inventarios.empresa_id', $empresaId))
                    ->where('inventarios.cantidad', '>', 0)
                    ->select('almacenes.nombre as almacen', 'inventarios.cantidad')
                    ->get()
                    ->map(function ($inv) {
                        return [
                            'almacen' => $inv->almacen,
                            'cantidad' => $inv->cantidad,
                        ];
                    });

                return [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'codigo' => $producto->codigo,
                    'categoria' => $producto->categoria?->nombre,
                    'marca' => $producto->marca?->nombre,
                    'stock_actual' => $producto->stock,
                    'stock_minimo' => $producto->stock_minimo,
                    'deficit' => $producto->stock_minimo - $producto->stock,
                    'precio_venta' => $producto->precio_venta,
                    'distribucion_almacenes' => $distribucion,
                    'ultimo_movimiento' => $this->getUltimoMovimiento($producto->id),
                ];
            });

        return Inertia::render('ReportesInventario/ProductosBajoStock', [
            'productos' => $productos,
        ]);
    }

    /**
     * Reporte de movimientos por período
     */
    public function movimientosPorPeriodo(Request $request)
    {
        $empresaId = EmpresaResolver::resolveId();
        $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth()->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', now()->format('Y-m-d'));
        $tipo = $request->get('tipo'); // entrada, salida, todos

        // Movimientos de inventario_movimientos
        $query = InventarioMovimiento::with(['producto', 'almacen', 'user'])
            ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);

        if ($tipo && $tipo !== 'todos') {
            $query->where('tipo', $tipo);
        }

        $movimientos = $query->orderBy('created_at', 'desc')->get()->map(function ($movimiento) {
            return [
                'id' => $movimiento->id,
                'tipo' => $movimiento->tipo,
                'cantidad' => $movimiento->cantidad,
                'motivo' => $movimiento->motivo,
                'created_at' => $movimiento->created_at,
                'producto_id' => $movimiento->producto_id,
                'almacen_id' => $movimiento->almacen_id,
                'user_id' => $movimiento->user_id,
                'producto_nombre' => $movimiento->producto->nombre ?? 'Producto no encontrado',
                'producto_codigo' => $movimiento->producto->codigo ?? '',
                'almacen_nombre' => $movimiento->almacen->nombre ?? 'Almacén no encontrado',
                'usuario_nombre' => $movimiento->user->name ?? 'Usuario no encontrado',
            ];
        });

        // Estadísticas del período
        $stats = [
            'total_movimientos' => $movimientos->count(),
            'entradas' => $movimientos->where('tipo', 'entrada')->count(),
            'salidas' => $movimientos->where('tipo', 'salida')->count(),
            'productos_afectados' => $movimientos->pluck('producto_id')->unique()->count(),
            'almacenes_afectados' => $movimientos->pluck('almacen_id')->unique()->count(),
            'total_cantidad_entrada' => $movimientos->where('tipo', 'entrada')->sum('cantidad'),
            'total_cantidad_salida' => $movimientos->where('tipo', 'salida')->sum('cantidad'),
        ];

        return Inertia::render('ReportesInventario/MovimientosPorPeriodo', [
            'movimientos' => $movimientos,
            'stats' => $stats,
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'tipo' => $tipo,
            ],
        ]);
    }

    /**
     * Reporte de costos de inventario
     */
    public function costosInventario(Request $request)
    {
        $empresaId = EmpresaResolver::resolveId();
        $tipoCosto = $request->get('tipo_costo', 'promedio'); // promedio, ultimo, total

        $productos = Producto::with(['categoria', 'marca'])
            ->where('estado', 'activo')
            ->get()
            ->map(function ($producto) use ($tipoCosto) {
                // Calcular costo según el tipo seleccionado
                $costo = $this->calcularCostoProducto($producto->id, $tipoCosto);

                // Stock total
                $stockTotal = $producto->stock;

                // Valor total del inventario para este producto
                $valorTotal = $stockTotal * $costo;

                // Distribucion por almacén
                $distribucion = DB::table('inventarios')
                    ->join('almacenes', 'inventarios.almacen_id', '=', 'almacenes.id')
                    ->where('inventarios.producto_id', $producto->id)
                    ->when($empresaId, fn($query) => $query->where('inventarios.empresa_id', $empresaId))
                    ->where('inventarios.cantidad', '>', 0)
                    ->select('almacenes.nombre as almacen', 'inventarios.cantidad')
                    ->get()
                    ->map(function ($inv) use ($costo) {
                        return [
                            'almacen' => $inv->almacen,
                            'cantidad' => $inv->cantidad,
                            'valor' => $inv->cantidad * $costo,
                        ];
                    });

                return [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'codigo' => $producto->codigo,
                    'categoria' => $producto->categoria?->nombre,
                    'marca' => $producto->marca?->nombre,
                    'stock_total' => $stockTotal,
                    'costo_unitario' => $costo,
                    'precio_venta' => $producto->precio_venta,
                    'valor_total_inventario' => $valorTotal,
                    'margen_ganancia' => $producto->precio_venta > 0 ? (($producto->precio_venta - $costo) / $producto->precio_venta) * 100 : 0,
                    'distribucion_almacenes' => $distribucion,
                ];
            })
            ->filter(function ($producto) {
                return $producto['stock_total'] > 0;
            })
            ->sortByDesc('valor_total_inventario');

        $totales = [
            'valor_total_inventario' => $productos->sum('valor_total_inventario'),
            'productos_con_stock' => $productos->count(),
            'margen_promedio' => $productos->avg('margen_ganancia'),
        ];

        return Inertia::render('ReportesInventario/CostosInventario', [
            'productos' => $productos,
            'totales' => $totales,
            'filtros' => ['tipo_costo' => $tipoCosto],
        ]);
    }

    /**
     * Calcular costo de un producto según el tipo
     */
    private function calcularCostoProducto($productoId, $tipoCosto)
    {
        switch ($tipoCosto) {
            case 'ultimo':
                // Último costo de entrada
                $ultimoMovimiento = InventarioMovimiento::where('producto_id', $productoId)
                    ->where('tipo', 'entrada')
                    ->whereJsonContains('detalles', ['costo_unitario'])
                    ->orderBy('created_at', 'desc')
                    ->first();

                return $ultimoMovimiento ? $ultimoMovimiento->detalles['costo_unitario'] ?? 0 : 0;

            case 'promedio':
            default:
                // Costo promedio ponderado
                $movimientosEntrada = InventarioMovimiento::where('producto_id', $productoId)
                    ->where('tipo', 'entrada')
                    ->whereJsonContains('detalles', ['costo_unitario'])
                    ->get();

                if ($movimientosEntrada->isEmpty()) {
                    return 0;
                }

                $totalCantidad = 0;
                $totalCosto = 0;

                foreach ($movimientosEntrada as $movimiento) {
                    $cantidad = $movimiento->cantidad;
                    $costo = $movimiento->detalles['costo_unitario'] ?? 0;

                    $totalCantidad += $cantidad;
                    $totalCosto += ($cantidad * $costo);
                }

                return $totalCantidad > 0 ? $totalCosto / $totalCantidad : 0;
        }
    }

    /**
     * Obtener último movimiento de un producto
     */
    private function getUltimoMovimiento($productoId)
    {
        $ultimo = InventarioMovimiento::where('producto_id', $productoId)
            ->orderBy('created_at', 'desc')
            ->first();

        return $ultimo ? [
            'tipo' => $ultimo->tipo,
            'cantidad' => $ultimo->cantidad,
            'fecha' => $ultimo->created_at,
            'motivo' => $ultimo->motivo,
        ] : null;
    }
}

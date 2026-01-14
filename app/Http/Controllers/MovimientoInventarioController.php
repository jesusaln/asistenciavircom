<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\InventarioMovimiento;

class MovimientoInventarioController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) ($request->integer('per_page') ?: 15);

        // Obtener movimientos de inventario
        $query = InventarioMovimiento::select(
                'id',
                'tipo',
                'cantidad',
                'motivo',
                'created_at',
                'producto_id',
                'producto_nombre',
                'almacen_id',
                'almacen_nombre',
                'user_id',
                'usuario_nombre'
            )
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }

        if ($request->filled('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($search = trim($request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('producto', function ($subQ) use ($search) {
                    $subQ->where('nombre', 'like', "%{$search}%");
                })
                ->orWhereHas('almacen', function ($subQ) use ($search) {
                    $subQ->where('nombre', 'like', "%{$search}%");
                })
                ->orWhere('motivo', 'like', "%{$search}%");
            });
        }

        $movimientos = $query->paginate($perPage)->through(function ($movimiento) {
            return [
                'id' => $movimiento->id,
                'tipo' => $movimiento->tipo,
                'cantidad' => $movimiento->cantidad,
                'motivo' => $movimiento->motivo,
                'created_at' => $movimiento->created_at,
                'producto_id' => $movimiento->producto_id,
                'almacen_id' => $movimiento->almacen_id,
                'user_id' => $movimiento->user_id,
                // Usar los nombres almacenados directamente
                'producto_nombre' => $movimiento->producto_nombre ?: 'Producto eliminado',
                'almacen_nombre' => $movimiento->almacen_nombre ?: 'Almacén eliminado',
                'usuario_nombre' => $movimiento->usuario_nombre ?: 'Usuario eliminado',
            ];
        });

        // Estadísticas
        $stats = [
            'total_movimientos' => InventarioMovimiento::count(),
            'entradas' => InventarioMovimiento::where('tipo', 'entrada')->count(),
            'salidas' => InventarioMovimiento::where('tipo', 'salida')->count(),
            'traspasos' => InventarioMovimiento::where('motivo', 'like', '%traspaso%')->count(),
        ];

        return Inertia::render('MovimientosInventario/Index', [
            'movimientos' => $movimientos,
            'stats' => $stats,
            'productos' => Producto::select('id', 'nombre')->get(),
            'almacenes' => Almacen::select('id', 'nombre')->get(),
            'filters' => $request->only(['search', 'producto_id', 'almacen_id', 'tipo']),
        ]);
    }

    /**
     * Limpiar movimientos de inventario huérfanos
     */
    public function limpiarHuérfanos()
    {
        try {
            $movimientosEliminados = 0;

            // Eliminar movimientos con productos inexistentes
            $productosInexistentes = DB::table('inventario_movimientos')
                ->leftJoin('productos', 'inventario_movimientos.producto_id', '=', 'productos.id')
                ->whereNull('productos.id')
                ->pluck('inventario_movimientos.id');

            if ($productosInexistentes->count() > 0) {
                DB::table('inventario_movimientos')
                    ->whereIn('id', $productosInexistentes)
                    ->delete();
                $movimientosEliminados += $productosInexistentes->count();
            }

            // Eliminar movimientos con almacenes inexistentes
            $almacenesInexistentes = DB::table('inventario_movimientos')
                ->leftJoin('almacens', 'inventario_movimientos.almacen_id', '=', 'almacens.id')
                ->whereNull('almacens.id')
                ->pluck('inventario_movimientos.id');

            if ($almacenesInexistentes->count() > 0) {
                DB::table('inventario_movimientos')
                    ->whereIn('id', $almacenesInexistentes)
                    ->delete();
                $movimientosEliminados += $almacenesInexistentes->count();
            }

            // Eliminar movimientos con usuarios inexistentes
            $usuariosInexistentes = DB::table('inventario_movimientos')
                ->leftJoin('users', 'inventario_movimientos.user_id', '=', 'users.id')
                ->whereNull('users.id')
                ->pluck('inventario_movimientos.id');

            if ($usuariosInexistentes->count() > 0) {
                DB::table('inventario_movimientos')
                    ->whereIn('id', $usuariosInexistentes)
                    ->delete();
                $movimientosEliminados += $usuariosInexistentes->count();
            }

            return response()->json([
                'success' => true,
                'message' => "Se eliminaron {$movimientosEliminados} movimientos huérfanos",
                'detalles' => [
                    'productos_inexistentes' => $productosInexistentes->count(),
                    'almacenes_inexistentes' => $almacenesInexistentes->count(),
                    'usuarios_inexistentes' => $usuariosInexistentes->count(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar movimientos huérfanos: ' . $e->getMessage()
            ], 500);
        }
    }
}

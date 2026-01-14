<?php

namespace App\Http\Controllers;

use App\Models\InventarioMovimiento;
use App\Models\Producto;
use App\Models\User;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReporteMovimientosController extends Controller
{
    public function __construct(private readonly InventarioService $inventarioService)
    {
    }

    /**
     * Muestra el reporte avanzado de movimientos de inventario.
     */
    public function index(Request $request)
    {
        $perPage = (int) ($request->integer('per_page') ?: 25);
        $page = max(1, (int) $request->get('page', 1));

        // Usar el servicio para aplicar filtros avanzados
        $query = $this->inventarioService->obtenerMovimientosConFiltros([
            'producto_id' => $request->producto_id,
            'tipo' => $request->tipo,
            'motivo' => $request->motivo,
            'fecha_desde' => $request->fecha_desde,
            'fecha_hasta' => $request->fecha_hasta,
            'user_id' => $request->user_id,
        ]);

        // Aplicar ordenamiento avanzado
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $allowedSorts = [
            'created_at', 'tipo', 'cantidad', 'motivo', 'stock_anterior',
            'stock_posterior', 'producto.nombre', 'user.name'
        ];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        // Ordenamiento inteligente según el campo
        if (in_array($sortBy, ['producto.nombre', 'user.name'])) {
            $query->join('productos', 'inventario_movimientos.producto_id', '=', 'productos.id')
                  ->join('users', 'inventario_movimientos.user_id', '=', 'users.id')
                  ->orderBy($sortBy === 'producto.nombre' ? 'productos.nombre' : 'users.name', $sortDirection)
                  ->select('inventario_movimientos.*');
        } else {
            $query->orderBy($sortBy, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        // Cargar relaciones después del ordenamiento
        $movimientos = $query->with([
            'producto' => function ($query) {
                $query->select('id', 'nombre', 'codigo', 'categoria_id', 'marca_id')
                      ->with(['categoria:id,nombre', 'marca:id,nombre']);
            },
            'user:id,name,email',
            'referencia' // Cargar relación polimórfica si existe
        ])->paginate($perPage)->appends($request->query());

        // Transformar datos avanzados para el frontend
        $movimientosData = collect($movimientos->items())->map(function ($movimiento) {
            // Determinar el nombre de la referencia polimórfica
            $referenciaNombre = null;
            if ($movimiento->referencia) {
                $referenciaNombre = match($movimiento->referencia_type) {
                    'App\Models\Compra' => 'Compra #' . $movimiento->referencia->id,
                    'App\Models\Venta' => 'Venta #' . $movimiento->referencia->id,
                    'App\Models\Pedido' => 'Pedido #' . $movimiento->referencia->id,
                    'App\Models\Cotizacion' => 'Cotización #' . $movimiento->referencia->id,
                    'App\Models\OrdenCompra' => 'Orden #' . $movimiento->referencia->id,
                    default => 'Referencia #' . $movimiento->referencia->id
                };
            }

            return [
                'id' => $movimiento->id,
                'producto' => $movimiento->producto ? [
                    'id' => $movimiento->producto->id,
                    'nombre' => $movimiento->producto->nombre,
                    'codigo' => $movimiento->producto->codigo,
                    'categoria' => $movimiento->producto->categoria?->nombre,
                    'marca' => $movimiento->producto->marca?->nombre,
                ] : null,
                'tipo' => $movimiento->tipo,
                'cantidad' => $movimiento->cantidad,
                'stock_anterior' => $movimiento->stock_anterior ?? 0, // Nuevo campo avanzado
                'stock_posterior' => $movimiento->stock_posterior ?? 0, // Nuevo campo avanzado
                'diferencia_stock' => ($movimiento->stock_posterior ?? 0) - ($movimiento->stock_anterior ?? 0),
                'motivo' => $movimiento->motivo,
                'referencia' => $referenciaNombre,
                'referencia_type' => $movimiento->referencia_type,
                'user' => $movimiento->user ? [
                    'id' => $movimiento->user->id,
                    'name' => $movimiento->user->name,
                    'email' => $movimiento->user->email,
                ] : null,
                'detalles' => $movimiento->detalles, // Nuevo campo avanzado
                'created_at' => $movimiento->created_at->format('Y-m-d H:i:s'),
                'fecha' => $movimiento->created_at->format('Y-m-d'),
                'hora' => $movimiento->created_at->format('H:i:s'),
            ];
        });

        // Crear nuevo paginador con datos transformados
        $movimientosPaginados = new \Illuminate\Pagination\LengthAwarePaginator(
            $movimientosData,
            $movimientos->total(),
            $movimientos->perPage(),
            $movimientos->currentPage(),
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        // Obtener opciones avanzadas para filtros
        $productos = Producto::select('id', 'nombre', 'codigo')
            ->orderBy('nombre')
            ->get()
            ->mapWithKeys(function ($producto) {
                return [$producto->id => $producto->nombre . ' (' . $producto->codigo . ')'];
            });

        $usuarios = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function ($user) {
                return [$user->id => $user->name . ' (' . $user->email . ')'];
            });

        // Obtener motivos únicos para el filtro
        $motivos = InventarioMovimiento::distinct()
            ->whereNotNull('motivo')
            ->pluck('motivo')
            ->filter()
            ->sort()
            ->values()
            ->mapWithKeys(function ($motivo) {
                return [$motivo => $motivo];
            });

        $filterOptions = [
            'productos' => $productos,
            'usuarios' => $usuarios,
            'tipos' => [
                'entrada' => 'Entrada',
                'salida' => 'Salida',
            ],
            'motivos' => $motivos, // Nuevo filtro avanzado
            'per_page_options' => [10, 25, 50, 100],
        ];

        // Estadísticas avanzadas usando el servicio
        $stats = $this->inventarioService->obtenerEstadisticasGenerales();

        // Agregar estadísticas adicionales avanzadas
        $statsAvanzadas = [
            'productos_mas_movidos_hoy' => $this->inventarioService->obtenerProductosMasMovidos(5),
            'usuarios_mas_activos' => $this->inventarioService->obtenerUsuariosMasActivos(5),
            'movimientos_ultima_semana' => InventarioMovimiento::whereDate('created_at', '>=', now()->subWeek())->count(),
            'eficiencia_movimientos' => $this->calcularEficienciaMovimientos(),
        ];

        return Inertia::render('Reportes/MovimientosInventario', [
            'movimientos' => $movimientosPaginados,
            'stats' => array_merge($stats, $statsAvanzadas),
            'filterOptions' => $filterOptions,
            'filters' => $request->only([
                'producto_id', 'tipo', 'motivo', 'fecha_desde', 'fecha_hasta', 'user_id'
            ]),
            'sorting' => [
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
                'allowed_sorts' => $allowedSorts,
            ],
            'pagination' => [
                'current_page' => $movimientos->currentPage(),
                'last_page' => $movimientos->lastPage(),
                'per_page' => $perPage,
                'total' => $movimientos->total(),
                'from' => $movimientos->firstItem(),
                'to' => $movimientos->lastItem(),
            ],
        ]);
    }

    /**
     * Calcula métricas de eficiencia del sistema de inventario.
     */
    private function calcularEficienciaMovimientos()
    {
        $totalMovimientos = InventarioMovimiento::count();
        if ($totalMovimientos === 0) {
            return [
                'porcentaje_automatizado' => 0,
                'movimientos_por_usuario' => 0,
                'tasa_errores' => 0,
                'nivel_automatizacion' => 'Bajo'
            ];
        }

        // Calcular porcentaje de movimientos automatizados (sin usuario manual)
        $movimientosAutomatizados = InventarioMovimiento::whereNull('user_id')->count();
        $porcentajeAutomatizado = round(($movimientosAutomatizados / $totalMovimientos) * 100, 2);

        // Movimientos promedio por usuario
        $usuariosActivos = InventarioMovimiento::distinct('user_id')->count('user_id');
        $movimientosPorUsuario = $usuariosActivos > 0 ? round($totalMovimientos / $usuariosActivos, 2) : 0;

        // Nivel de automatización basado en criterios
        $nivelAutomatizacion = match(true) {
            $porcentajeAutomatizado >= 80 => 'Muy Alto',
            $porcentajeAutomatizado >= 60 => 'Alto',
            $porcentajeAutomatizado >= 40 => 'Medio',
            $porcentajeAutomatizado >= 20 => 'Bajo',
            default => 'Muy Bajo'
        };

        return [
            'porcentaje_automatizado' => $porcentajeAutomatizado,
            'movimientos_por_usuario' => $movimientosPorUsuario,
            'tasa_errores' => 0, // Podría calcularse basado en movimientos revertidos
            'nivel_automatizacion' => $nivelAutomatizacion
        ];
    }

    /**
     * Muestra el detalle avanzado de un movimiento específico.
     */
    public function show($id)
    {
        $movimiento = InventarioMovimiento::with([
            'producto.categoria',
            'producto.marca',
            'user'
        ])->findOrFail($id);

        // Información adicional avanzada
        $infoAvanzada = [
            'impacto_stock' => $this->calcularImpactoMovimiento($movimiento),
            'movimientos_relacionados' => $this->obtenerMovimientosRelacionados($movimiento),
            'analisis_tendencia' => $this->analizarTendenciaProducto($movimiento),
        ];

        // Información detallada de la referencia polimórfica
        $referenciaDetalle = null;
        if ($movimiento->referencia) {
            $referenciaDetalle = [
                'tipo' => $movimiento->referencia_type,
                'id' => $movimiento->referencia_id,
                'nombre' => $this->obtenerNombreReferencia($movimiento->referencia_type, $movimiento->referencia),
                'url' => $this->obtenerUrlReferencia($movimiento->referencia_type, $movimiento->referencia_id),
            ];
        }

        return Inertia::render('Reportes/MovimientoDetalle', [
            'movimiento' => [
                'id' => $movimiento->id,
                'producto' => $movimiento->producto ? [
                    'id' => $movimiento->producto->id,
                    'nombre' => $movimiento->producto->nombre,
                    'codigo' => $movimiento->producto->codigo,
                    'categoria' => $movimiento->producto->categoria?->nombre,
                    'marca' => $movimiento->producto->marca?->nombre,
                    'stock_actual' => $movimiento->producto->stock,
                    'reservado' => $movimiento->producto->reservado,
                    'disponible' => $movimiento->producto->stock - $movimiento->producto->reservado,
                ] : null,
                'tipo' => $movimiento->tipo,
                'cantidad' => $movimiento->cantidad,
                'stock_anterior' => $movimiento->stock_anterior ?? 0,
                'stock_posterior' => $movimiento->stock_posterior ?? 0,
                'diferencia_stock' => ($movimiento->stock_posterior ?? 0) - ($movimiento->stock_anterior ?? 0),
                'motivo' => $movimiento->motivo,
                'referencia' => $referenciaDetalle,
                'user' => $movimiento->user ? [
                    'id' => $movimiento->user->id,
                    'name' => $movimiento->user->name,
                    'email' => $movimiento->user->email,
                ] : null,
                'detalles' => $movimiento->detalles,
                'created_at' => $movimiento->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $movimiento->updated_at->format('Y-m-d H:i:s'),
                'fecha' => $movimiento->created_at->format('Y-m-d'),
                'hora' => $movimiento->created_at->format('H:i:s'),
            ],
            'infoAvanzada' => $infoAvanzada,
        ]);
    }

    /**
     * Calcula el impacto de un movimiento específico en el stock.
     */
    private function calcularImpactoMovimiento($movimiento)
    {
        $producto = $movimiento->producto;
        if (!$producto) {
            return null;
        }

        $stockActual = $producto->stock;
        $reservadoActual = $producto->reservado;
        $disponibleActual = $stockActual - $reservadoActual;

        return [
            'porcentaje_impacto' => $stockActual > 0 ? round(($movimiento->cantidad / $stockActual) * 100, 2) : 0,
            'estado_stock' => $this->evaluarEstadoStock($disponibleActual, $producto),
            'nivel_criticidad' => $this->calcularNivelCriticidad($disponibleActual, $producto),
        ];
    }

    /**
     * Obtiene movimientos relacionados con el mismo producto.
     */
    private function obtenerMovimientosRelacionados($movimiento)
    {
        if (!$movimiento->producto) {
            return [];
        }

        return InventarioMovimiento::where('producto_id', $movimiento->producto_id)
            ->where('id', '!=', $movimiento->id)
            ->whereDate('created_at', $movimiento->created_at->format('Y-m-d'))
            ->with(['user:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($mov) {
                return [
                    'id' => $mov->id,
                    'tipo' => $mov->tipo,
                    'cantidad' => $mov->cantidad,
                    'motivo' => $mov->motivo,
                    'hora' => $mov->created_at->format('H:i:s'),
                    'usuario' => $mov->user?->name,
                ];
            });
    }

    /**
     * Analiza la tendencia del producto basado en movimientos recientes.
     */
    private function analizarTendenciaProducto($movimiento)
    {
        if (!$movimiento->producto) {
            return null;
        }

        $productoId = $movimiento->producto_id;
        $fechaActual = $movimiento->created_at;

        // Movimientos de los últimos 7 días
        $movimientosRecientes = InventarioMovimiento::where('producto_id', $productoId)
            ->whereDate('created_at', '>=', $fechaActual->copy()->subDays(7))
            ->whereDate('created_at', '<=', $fechaActual->format('Y-m-d'))
            ->get();

        $entradas = $movimientosRecientes->where('tipo', 'entrada')->sum('cantidad');
        $salidas = $movimientosRecientes->where('tipo', 'salida')->sum('cantidad');

        $tendencia = $salidas > $entradas ? 'descendente' : ($entradas > $salidas ? 'ascendente' : 'estable');

        return [
            'tendencia' => $tendencia,
            'entradas_7_dias' => $entradas,
            'salidas_7_dias' => $salidas,
            'neto_7_dias' => $entradas - $salidas,
            'movimientos_7_dias' => $movimientosRecientes->count(),
        ];
    }

    /**
     * Evalúa el estado actual del stock de un producto.
     */
    private function evaluarEstadoStock($disponible, $producto)
    {
        $stockTotal = $producto->stock;

        if ($disponible <= 0) {
            return ['estado' => 'agotado', 'color' => 'red', 'descripcion' => 'Stock agotado'];
        } elseif ($disponible <= 10) {
            return ['estado' => 'critico', 'color' => 'orange', 'descripcion' => 'Stock crítico'];
        } elseif ($disponible <= 50) {
            return ['estado' => 'bajo', 'color' => 'yellow', 'descripcion' => 'Stock bajo'];
        } else {
            return ['estado' => 'normal', 'color' => 'green', 'descripcion' => 'Stock normal'];
        }
    }

    /**
     * Calcula el nivel de criticidad basado en el stock disponible.
     */
    private function calcularNivelCriticidad($disponible, $producto)
    {
        if ($disponible <= 0) {
            return ['nivel' => 'crítico', 'prioridad' => 'alta', 'accion' => 'Reabastecer inmediatamente'];
        } elseif ($disponible <= 10) {
            return ['nivel' => 'alto', 'prioridad' => 'media', 'accion' => 'Planificar reabastecimiento'];
        } elseif ($disponible <= 50) {
            return ['nivel' => 'medio', 'prioridad' => 'baja', 'accion' => 'Monitorear niveles'];
        } else {
            return ['nivel' => 'bajo', 'prioridad' => 'ninguna', 'accion' => 'Mantener niveles actuales'];
        }
    }

    /**
     * Obtiene el nombre legible de una referencia polimórfica.
     */
    private function obtenerNombreReferencia($tipo, $referencia)
    {
        return match($tipo) {
            'App\Models\Compra' => 'Compra #' . $referencia->id,
            'App\Models\Venta' => 'Venta #' . $referencia->id,
            'App\Models\Pedido' => 'Pedido #' . $referencia->id,
            'App\Models\Cotizacion' => 'Cotización #' . $referencia->id,
            'App\Models\OrdenCompra' => 'Orden #' . $referencia->id,
            default => 'Referencia #' . $referencia->id
        };
    }

    /**
     * Obtiene la URL para ver los detalles de una referencia.
     */
    private function obtenerUrlReferencia($tipo, $id)
    {
        return match($tipo) {
            'App\Models\Compra' => route('compras.show', $id),
            'App\Models\Venta' => route('ventas.show', $id),
            'App\Models\Pedido' => route('pedidos.show', $id),
            'App\Models\Cotizacion' => route('cotizaciones.show', $id),
            'App\Models\OrdenCompra' => route('ordenescompra.show', $id),
            default => null
        };
    }

    /**
     * Exporta los movimientos de inventario a Excel o PDF.
     */
    public function export(Request $request)
    {
        // Por ahora solo retornamos JSON, pero aquí se podría implementar exportación
        $movimientos = InventarioMovimiento::with(['producto', 'user'])
            ->when($request->filled('producto_id'), fn($q) => $q->where('producto_id', $request->producto_id))
            ->when($request->filled('tipo'), fn($q) => $q->where('tipo', $request->tipo))
            ->when($request->filled('fecha_desde'), fn($q) => $q->whereDate('created_at', '>=', $request->fecha_desde))
            ->when($request->filled('fecha_hasta'), fn($q) => $q->whereDate('created_at', '<=', $request->fecha_hasta))
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $movimientos->map(function ($movimiento) {
                return [
                    'id' => $movimiento->id,
                    'producto' => $movimiento->producto?->nombre,
                    'tipo' => $movimiento->tipo,
                    'cantidad' => $movimiento->cantidad,
                    'motivo' => $movimiento->motivo,
                    'referencia' => $movimiento->referencia,
                    'usuario' => $movimiento->user?->name,
                    'fecha' => $movimiento->created_at->format('Y-m-d H:i:s'),
                ];
            })
        ]);
    }
}

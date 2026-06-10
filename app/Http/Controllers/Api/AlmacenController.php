<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Almacen;
use App\Http\Controllers\Traits\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\InventarioMovimiento;
use App\Services\InventarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AlmacenController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly InventarioService $inventarioService
    ) {
    }
    /**
     * Muestra una lista de todos los almacenes en formato JSON.
     */
    public function index()
    {
        try {
            $almacenes = Almacen::all();
            return $this->success($almacenes);
        } catch (\Exception $e) {
            return $this->serverError('Error al obtener los almacenes', $e);
        }
    }

    /**
     * Almacena un nuevo almacén en la base de datos.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('create almacenes')) {
            return $this->forbidden('No tienes permiso para crear almacenes');
        }

        // Validar los datos recibidos
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:almacenes,nombre',
            'descripcion' => 'nullable|string|max:1000',
            'ubicacion' => 'required|string|max:255',
        ]);

        try {
            $almacen = Almacen::create($validated);
            return $this->created($almacen, 'Almacén creado correctamente');
        } catch (\Exception $e) {
            return $this->serverError('Error al crear el almacén', $e);
        }
    }

    /**
     * Muestra un almacén específico en formato JSON.
     */
    public function show($id)
    {
        try {
            $almacen = Almacen::findOrFail($id);
            return $this->success($almacen);
        } catch (\Exception $e) {
            return $this->notFound('Almacén no encontrado');
        }
    }

    /**
     * Actualiza un almacén existente en la base de datos.
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('edit almacenes')) {
            return $this->forbidden('No tienes permiso para editar almacenes');
        }

        // Validar los datos recibidos
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:almacenes,nombre,' . $id,
            'descripcion' => 'nullable|string|max:1000',
            'ubicacion' => 'required|string|max:255',
        ]);

        try {
            $almacen = Almacen::findOrFail($id);
            $almacen->update($validated);

            return $this->success($almacen, 'Almacén actualizado correctamente');
        } catch (\Exception $e) {
            return $this->serverError('Error al actualizar el almacén', $e);
        }
    }

    /**
     * Elimina un almacén de la base de datos.
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('delete almacenes')) {
            return $this->forbidden('No tienes permiso para eliminar almacenes');
        }

        try {
            $almacen = Almacen::findOrFail($id);

            if ($almacen->productos()->exists()) {
                return $this->error('No se puede eliminar el almacén porque tiene productos asociados', 400);
            }

            $almacen->delete();

            return $this->success(null, 'Almacén eliminado correctamente');
        } catch (\Exception $e) {
            return $this->serverError('Error al eliminar el almacén', $e);
        }
    }

    /**
     * Obtiene el inventario del almacén asignado al usuario actual.
     */
    public function miInventario(Request $request)
    {
        try {
            $user = $request->user();
            \Illuminate\Support\Facades\Log::info('Obteniendo inventario para usuario: ' . $user->id);
            
            // Administradores, Super-Admins y Luis Carlos Perez (Auditor) ven todo el inventario consolidado
            $isAdmin = $user->hasRole(['super-admin', 'admin']) || $user->id === 16;
            
            $tecnicos = \App\Models\User::where('id', '!=', $user->id)
            ->where(function($q) {
                $q->whereHas('roles', function($r) {
                    $r->whereIn('name', ['tecnico', 'admin', 'super-admin']);
                })->orWhere('es_empleado', true);
            })
            ->get(['id', 'name']);

        if ($isAdmin) {
                $query = \App\Models\Inventario::with([
                    'producto' => function($q) {
                        $q->select('id', 'nombre', 'codigo', 'unidad_medida', 'precio_compra', 'precio_venta', 'requiere_serie')
                          ->withSum('ventaItems as vendidos', 'cantidad');
                    },
                    'almacen' => function($q) {
                        $q->select('id', 'nombre');
                    },
                    'series' => function($q) {
                        $q->where('estado', 'en_stock');
                    }
                ])->where('cantidad', '>', 0);

                // Filtrar por almacén si se solicita
                if ($request->has('almacen_id') && $request->almacen_id !== 'all') {
                    $query->where('almacen_id', $request->almacen_id);
                    $almacenActual = Almacen::find($request->almacen_id);
                } else {
                    $almacenActual = ['nombre' => 'Consolidado Global (Admin)'];
                }

                $inventario = $query->orderBy('almacen_id')->get();

                $herramientas = \App\Models\Herramienta::with('categoriaHerramienta:id,nombre', 'tecnico:id,name')
                    ->when($request->has('user_id') && $request->user_id !== 'all', function($q) use ($request) {
                        $q->where('user_id', $request->user_id);
                    })
                    ->get()
                    ->map(fn($h) => [
                        'id' => $h->id,
                        'nombre' => $h->nombre,
                        'codigo' => $h->codigo_inventario,
                        'numero_serie' => $h->numero_serie,
                        'foto' => $h->foto ? \App\Helpers\UrlHelper::storageUrl($h->foto) : null,
                        'categoria' => $h->categoriaHerramienta->nombre ?? 'Herramientas',
                        'estado' => $h->estado,
                        'estado_label' => $h->estado_label,
                        'estado_color' => $h->estado_color,
                        'marca' => $h->marca ?? 'N/A',
                        'tecnico' => $h->tecnico->name ?? 'Sin asignar',
                        'descripcion' => $h->descripcion,
                        'fecha_asignacion' => $h->fecha_asignacion ? $h->fecha_asignacion->format('d/m/Y') : 'N/A'
                    ]);

                // Calcular totales para el admin (basado en precio_compra)
                $totalArticulosGlobal = $inventario->sum('cantidad');
                $valorTotalGlobal = $inventario->reduce(function($carry, $item) {
                    return $carry + ($item->cantidad * ($item->producto->precio_compra ?? 0));
                }, 0);

                return $this->success([
                    'almacen' => $almacenActual,
                    'inventario' => $inventario,
                    'herramientas' => $herramientas,
                    'is_admin' => true,
                    'todos_los_almacenes' => Almacen::select('id', 'nombre')->get(),
                    'tecnicos' => \App\Models\User::tecnicosActivos()->select('id', 'name')->get(),
                    'stats' => [
                        'total_articulos' => $totalArticulosGlobal,
                        'valor_total' => $valorTotalGlobal
                    ],
                    'pin_auditoria' => \App\Models\EmpresaConfiguracion::getConfig()->pin_auditoria ?? '1234'
                ]);
            }

            // ... (Technician Logic)
            // Prioridad 1: Almacén de venta seleccionado por el usuario
            // Prioridad 2: Almacén donde el usuario es responsable
            $almacen = null;
            
            if ($user->almacen_venta_id) {
                $almacen = Almacen::find($user->almacen_venta_id);
            }
            
            if (!$almacen) {
                $almacen = Almacen::where('responsable', $user->id)->first();
            }
            
            if (!$almacen) {
                \Illuminate\Support\Facades\Log::warning('Usuario sin almacén asignado: ' . $user->id);
                return $this->error('No tienes un almacén asignado como responsable', 404);
            }

            \Illuminate\Support\Facades\Log::info('Almacén encontrado: ' . $almacen->nombre . ' (ID: ' . $almacen->id . ')');

            // Obtener inventario con productos (solo los que tengan stock)
            $inventario = \App\Models\Inventario::with(['producto' => function($q) use ($almacen) {
                $q->select('id', 'nombre', 'codigo', 'unidad_medida', 'precio_venta', 'precio_compra', 'requiere_serie')
                  ->withSum(['ventaItems as vendidos' => function($v) use ($almacen) {
                      $v->whereHas('venta', function($rv) use ($almacen) {
                          $rv->where('almacen_id', $almacen->id);
                      });
                  }], 'cantidad');
            }, 'series' => function($q) use ($almacen) {
                $q->where('almacen_id', $almacen->id)->where('estado', 'en_stock');
            }])
                ->where('almacen_id', $almacen->id)
                ->where('cantidad', '>', 0)
                ->get();

            // Calcular totales para el técnico (basado en precio_compra)
            $totalArticulos = $inventario->sum('cantidad');
            $valorTotal = $inventario->reduce(function($carry, $item) {
                return $carry + ($item->cantidad * ($item->producto->precio_compra ?? 0));
            }, 0);

            return $this->success([
                'almacen' => $almacen,
                'inventario' => $inventario,
                'herramientas' => \App\Models\Herramienta::where('user_id', $user->id)
                    ->with('categoriaHerramienta:id,nombre')
                    ->get()
                    ->map(fn($h) => [
                        'id' => $h->id,
                        'nombre' => $h->nombre,
                        'codigo' => $h->codigo_inventario,
                        'numero_serie' => $h->numero_serie,
                        'foto' => $h->foto ? \App\Helpers\UrlHelper::storageUrl($h->foto) : null,
                        'categoria' => $h->categoriaHerramienta->nombre ?? 'Herramientas',
                        'estado' => $h->estado,
                        'estado_label' => $h->estado_label,
                        'estado_color' => $h->estado_color,
                        'marca' => $h->marca ?? 'N/A',
                        'descripcion' => $h->descripcion,
                        'fecha_asignacion' => $h->fecha_asignacion ? $h->fecha_asignacion->format('d/m/Y') : 'N/A'
                    ]),
                'revisiones_pendientes' => collect()
                    ->concat(
                        \App\Models\HistorialHerramienta::where('tecnico_id', $user->id)
                            ->where('confirmado_por_tecnico', false)
                            ->whereNull('fecha_devolucion')
                            ->with(['herramienta:id,nombre,codigo_inventario,foto', 'asignadoPor:id,name'])
                            ->get()
                            ->map(fn($rev) => [
                                'id' => $rev->id,
                                'tipo' => 'asignacion',
                                'herramienta_id' => $rev->herramienta_id,
                                'nombre' => $rev->herramienta->nombre ?? 'Herramienta desconocida',
                                'codigo' => $rev->herramienta->codigo_inventario ?? 'S/N',
                                'foto' => ($rev->herramienta && $rev->herramienta->foto) ? \App\Helpers\UrlHelper::storageUrl($rev->herramienta->foto) : null,
                                'fecha' => $rev->fecha_asignacion ? $rev->fecha_asignacion->format('d/m/Y H:i') : 'N/A',
                                'emisor' => $rev->asignadoPor->name ?? 'Sistema'
                            ])
                    )
                    ->concat(
                        \App\Models\TransferenciaHerramienta::where('receptor_id', $user->id)
                            ->where('estado', 'pendiente')
                            ->with(['herramientas', 'emisor:id,name'])
                            ->get()
                            ->flatMap(function($trans) {
                                return $trans->herramientas->map(fn($h) => [
                                    'id' => $trans->id,
                                    'tipo' => 'traspaso',
                                    'herramienta_id' => $h->id,
                                    'nombre' => $h->nombre,
                                    'codigo' => $h->codigo_inventario,
                                    'foto' => $h->foto ? \App\Helpers\UrlHelper::storageUrl($h->foto) : null,
                                    'fecha' => $trans->created_at->format('d/m/Y H:i'),
                                    'emisor' => $trans->emisor->name ?? 'Compañero'
                                ]);
                            })
                    ),
                'is_admin' => false,
                'tecnicos' => $tecnicos,
                'stats' => [
                    'total_refacciones' => $totalArticulos,
                    'valor_total' => $valorTotal
                ],
                'pin_auditoria' => null
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error en miInventario: ' . $e->getMessage());
            return $this->serverError('Error al obtener tu inventario', $e);
        }
    }

    /**
     * Confirmar recepción de una herramienta por parte del técnico
     */
    public function confirmarRecepcion(Request $request, $id)
    {
        try {
            $user = auth()->user();
            $historial = \App\Models\HistorialHerramienta::where('id', $id)
                ->where('tecnico_id', $user->id)
                ->firstOrFail();

            if ($historial->confirmado_por_tecnico) {
                return $this->error('Esta asignación ya ha sido confirmada anteriormente.', 400);
            }

            \DB::beginTransaction();

            // 1. Marcar el historial como confirmado
            $historial->update([
                'confirmado_por_tecnico' => true,
                'fecha_confirmacion' => now()
            ]);

            // 2. Cambiar el estado de la herramienta a ASIGNADA oficialmente
            $herramienta = \App\Models\Herramienta::find($historial->herramienta_id);
            if ($herramienta) {
                $herramienta->update([
                    'estado' => \App\Models\Herramienta::ESTADO_ASIGNADA
                ]);
            }

            \DB::commit();

            return $this->success(null, 'Has confirmado la recepción de la herramienta correctamente.');
        } catch (\Exception $e) {
            \DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Error al confirmar recepción: ' . $e->getMessage());
            return $this->error('No se pudo confirmar la recepción: ' . $e->getMessage());
        }
    }

    /**
     * Finaliza la auditoría enviando los ajustes a revisión del Super Admin.
     */
    public function finalizarAuditoria(Request $request, $id)
    {
        $validated = $request->validate([
            'ajustes' => 'required|array|min:1',
            'ajustes.*.id' => 'required|exists:inventarios,id',
            'ajustes.*.nueva_cantidad' => 'required|integer|min:0',
            'observaciones' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            foreach ($validated['ajustes'] as $ajuste) {
                $inventario = \App\Models\Inventario::findOrFail($ajuste['id']);
                $cantidadAnterior = $inventario->cantidad;
                $nuevaCantidad = $ajuste['nueva_cantidad'];
                $diferencia = $nuevaCantidad - $cantidadAnterior;

                if ($diferencia == 0) continue;

                $esMerma = $diferencia < 0;
                $etiqueta = $esMerma ? 'MERMA' : 'EXCEDENTE';
                
                $motivo = "[PENDIENTE DE REVISIÓN] Auditoría App: {$etiqueta}. " . ($validated['observaciones'] ?? '');

                \App\Models\InventarioMovimiento::create([
                    'empresa_id' => $inventario->empresa_id,
                    'producto_id' => $inventario->producto_id,
                    'producto_nombre' => $inventario->producto->nombre ?? 'N/A',
                    'almacen_id' => $inventario->almacen_id,
                    'almacen_nombre' => $inventario->almacen->nombre ?? 'N/A',
                    'user_id' => Auth::id(),
                    'usuario_nombre' => Auth::user()->name ?? 'N/A',
                    'tipo' => $esMerma ? 'salida' : 'entrada',
                    'cantidad' => abs($diferencia),
                    'stock_anterior' => $cantidadAnterior,
                    'stock_posterior' => $nuevaCantidad,
                    'motivo' => $motivo,
                    'detalles' => [
                        'auditoria' => true,
                        'tipo_auditoria' => $etiqueta,
                        'cantidad_anterior' => $cantidadAnterior,
                        'cantidad_nueva' => $nuevaCantidad,
                        'estado' => 'revision',
                        'canal' => 'App Móvil'
                    ]
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true, 
                'mensaje' => 'Auditoría enviada correctamente a Jesus Lopez.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Error en API AlmacenController@finalizarAuditoria: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'No se pudo procesar la auditoría.'], 500);
        }
    }

    /**
     * Aprueba una auditoría pendiente y actualiza el stock real.
     */
    public function aprobarAuditoria(Request $request, $movimientoId)
    {
        try {
            $movimiento = \App\Models\InventarioMovimiento::findOrFail($movimientoId);
            
            if (($movimiento->detalles['estado'] ?? '') !== 'revision') {
                return response()->json(['success' => false, 'error' => 'Este movimiento no está en revisión'], 400);
            }

            DB::beginTransaction();

            $inventario = \App\Models\Inventario::where('producto_id', $movimiento->producto_id)
                ->where('almacen_id', $movimiento->almacen_id)
                ->firstOrFail();

            $nuevaCantidad = $movimiento->detalles['cantidad_nueva'];
            $inventario->update(['cantidad' => $nuevaCantidad]);

            $detalles = $movimiento->detalles;
            $detalles['estado'] = 'aprobado';
            $detalles['aprobado_por'] = Auth::id();
            $detalles['fecha_aprobacion'] = now();
            
            $movimiento->update([
                'motivo' => str_replace('[PENDIENTE DE REVISIÓN]', '[AUDITORÍA APROBADA]', $movimiento->motivo),
                'stock_anterior' => $movimiento->detalles['cantidad_anterior'] ?? $movimiento->stock_anterior,
                'stock_posterior' => $nuevaCantidad,
                'detalles' => $detalles
            ]);

            // Sincronizar el stock total del producto
            $producto = \App\Models\Producto::find($movimiento->producto_id);
            if ($producto) {
                $nuevoStockTotal = \App\Models\Inventario::where('producto_id', $producto->id)->sum('cantidad');
                $producto->update(['stock' => $nuevoStockTotal]);
            }

            DB::commit();

            return response()->json(['success' => true, 'mensaje' => 'Auditoría aprobada y stock actualizado.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => 'No se pudo aprobar la auditoría'], 500);
        }
    }

    /**
     * Obtener historial de movimientos de un producto
     */
    public function historial(Request $request, Producto $producto): JsonResponse
    {
        $query = \App\Models\InventarioMovimiento::where('producto_id', $producto->id)
            ->with('user')
            ->orderBy('created_at', 'desc');

        if ($request->has('almacen_id') && $request->almacen_id !== 'all') {
            $query->where('almacen_id', $request->almacen_id);
        }

        $movimientos = $query->limit(20)->get();

        return $this->success($movimientos);
    }

    /**
     * Realizar un ajuste manual de inventario
     */
    public function ajustar(Request $request): JsonResponse
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'almacen_id' => 'required|exists:almacenes,id',
            'cantidad' => 'required|integer|min:1',
            'tipo' => 'required|in:entrada,salida',
            'motivo' => 'required|string|max:255',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $producto = Producto::findOrFail($request->producto_id);
                $tipo = $request->tipo;
                $cantidad = $request->cantidad;
                $contexto = [
                    'almacen_id' => $request->almacen_id,
                    'motivo' => 'Ajuste manual: ' . $request->motivo,
                    'user_id' => Auth::id(),
                ];

                if ($tipo === 'entrada') {
                    $this->inventarioService->entrada($producto, $cantidad, $contexto);
                } else {
                    $this->inventarioService->salida($producto, $cantidad, $contexto);
                }

                return $this->success(null, 'Inventario ajustado correctamente');
            });
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Exportar inventario a PDF
     */
    public function exportarPdf(Request $request)
    {
        try {
            $user = $request->user();
            $almacenId = $request->get('almacen_id');
            
            $query = \App\Models\Inventario::with(['producto.categoria', 'almacen'])
                ->where('cantidad', '>', 0)
                ->whereHas('producto')
                ->join('almacenes', 'inventarios.almacen_id', '=', 'almacenes.id')
                ->join('productos', 'inventarios.producto_id', '=', 'productos.id')
                ->orderBy('almacenes.nombre')
                ->orderBy('productos.nombre')
                ->select('inventarios.*');

            if ($almacenId && $almacenId !== 'all') {
                $query->where('almacen_id', $almacenId);
                $almacen = Almacen::find($almacenId) ?? (object)['nombre' => 'Almacén Desconocido'];
            } else {
                $almacen = (object)['nombre' => 'Todos los Almacenes'];
            }

            $inventario = $query->get();
            \Illuminate\Support\Facades\Log::info('PDF: Query ejecutada. Items: ' . $inventario->count());
            
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.inventario', [
                'inventario' => $inventario,
                'almacen' => $almacen,
                'fecha' => now()->format('d/m/Y H:i'),
                'user' => $user,
                'empresa' => \App\Models\EmpresaConfiguracion::getInfoEmpresa()
            ]);
            \Illuminate\Support\Facades\Log::info('PDF: Vista cargada en DomPDF.');

            return $pdf->stream('inventario_' . now()->format('Ymd_His') . '.pdf');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error exportando PDF: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['error' => 'Error al generar el PDF: ' . $e->getMessage()], 500);
        }
    }
}

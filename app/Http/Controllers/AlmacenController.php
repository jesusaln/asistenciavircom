<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Almacen;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Support\EmpresaResolver;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MermasExport;
use App\Models\Inventario;
use App\Models\InventarioMovimiento;

class AlmacenController extends Controller
{
    /**
     * Muestra una lista de todos los almacenes con paginación y filtros.
     */
    public function index(Request $request)
    {
        try {
            $query = Almacen::with(['responsable:id,name'])
                ->withCount(['inventarios as total_articulos' => function($q) {
                    $q->select(DB::raw('COALESCE(SUM(cantidad), 0)'));
                }]);

            // Filtros
            if ($search = trim($request->input('search', ''))) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%")
                        ->orWhere('direccion', 'like', "%{$search}%")
                        ->orWhere('responsable', 'like', "%{$search}%");
                });
            }

            if ($estado = $request->input('estado')) {
                if ($estado === 'activo') {
                    $query->where('estado', 'activo');
                } elseif ($estado === 'inactivo') {
                    $query->where('estado', 'inactivo');
                }
            }

            // Ordenamiento
            $sortBy = $request->input('sort_by', 'nombre');
            $sortDirection = $request->input('sort_direction', 'asc');

            $validSortFields = ['nombre', 'descripcion', 'direccion', 'created_at'];
            if (!in_array($sortBy, $validSortFields)) {
                $sortBy = 'nombre';
            }

            $query->orderBy($sortBy, $sortDirection);

            // Paginación segura (Anti-DoS)
            $perPage = $this->getPerPage(10);
            $almacenes = $query->paginate($perPage)->appends($request->query());

            // Estadísticas
            $total = Almacen::count();
            $activos = Almacen::where('estado', 'activo')->count();
            $inactivos = Almacen::where('estado', 'inactivo')->count();

            $stats = [
                'total' => $total,
                'activos' => $activos,
                'inactivos' => $inactivos,
                'activos_porcentaje' => $total > 0 ? round(($activos / $total) * 100, 1) : 0,
                'inactivos_porcentaje' => $total > 0 ? round(($inactivos / $total) * 100, 1) : 0,
                'total_articulos_global' => \App\Models\Inventario::whereHas('almacen', function($q) {
                    $q->where('empresa_id', EmpresaResolver::resolveId());
                })->sum('cantidad'),
                'valor_total_global' => \App\Models\Inventario::join('productos', 'inventarios.producto_id', '=', 'productos.id')
                    ->whereHas('almacen', function($q) {
                        $q->where('almacenes.empresa_id', EmpresaResolver::resolveId());
                    })
                    ->select(DB::raw('SUM(inventarios.cantidad * COALESCE(productos.precio_compra, 0)) as total'))
                    ->value('total') ?? 0,
            ];

            // Agregar valor por almacén
            $almacenes->getCollection()->transform(function($almacen) {
                $almacen->valor_inventario = \App\Models\Inventario::join('productos', 'inventarios.producto_id', '=', 'productos.id')
                    ->where('inventarios.almacen_id', $almacen->id)
                    ->select(DB::raw('SUM(inventarios.cantidad * COALESCE(productos.precio_compra, 0)) as total'))
                    ->value('total') ?? 0;
                return $almacen;
            });

            return Inertia::render('Almacenes/Index', [
                'almacenes' => $almacenes,
                'stats' => $stats,
                'filters' => $request->only(['search', 'estado']),
                'sorting' => ['sort_by' => $sortBy, 'sort_direction' => $sortDirection],
            ]);
        } catch (\Exception $e) {
            Log::error('Error en AlmacenController@index: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar los almacenes.');
        }
    }

    /**
     * Muestra el formulario para crear un nuevo almacén.
     */
    public function create()
    {
        $usuarios = User::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Almacenes/Create', [
            'usuarios' => $usuarios,
        ]);
    }

    /**
     * Almacena un nuevo almacén en la base de datos.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('almacenes', 'nombre')->where('empresa_id', EmpresaResolver::resolveId())
            ],
            'descripcion' => 'nullable|string|max:1000',
            'ubicacion' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'responsable' => 'nullable|integer|exists:users,id',
            'estado' => 'required|in:activo,inactivo',
        ]);

        // Convertir responsable vacío a null
        if (isset($validated['responsable']) && $validated['responsable'] === '') {
            $validated['responsable'] = null;
        }

        Almacen::create($validated);

        return redirect()->route('almacenes.index')->with('success', 'Almacén creado correctamente.');
    }

    public function show(Almacen $almacene)
    {
        try {
            $almacene->load(['responsable:id,name']);
            $id = $almacene->id;

            // Obtener inventario detallado (Refacciones/Materiales)
            $inventario = \App\Models\Inventario::with(['producto' => function($q) {
                    $q->select('id', 'nombre', 'sku', 'categoria_id', 'precio_compra')
                      ->with('categoria:id,nombre');
                }])
                ->where('almacen_id', $id)
                ->where('cantidad', '>', 0)
                ->get()
                ->map(function($inv) {
                    $producto = $inv->producto;
                    if (!$producto) return null;
                    
                    return [
                        'id' => $inv->id,
                        'producto' => $producto->nombre ?? 'Producto no encontrado',
                        'sku' => $producto->codigo ?? 'N/A',
                        'categoria' => $producto->categoria->nombre ?? 'Sin categoría',
                        'cantidad' => $inv->cantidad,
                        'valor' => (float) ($inv->cantidad * ($producto->precio_compra ?? 0))
                    ];
                })
                ->filter(); // Eliminar nulos

            // Obtener herramientas asignadas al responsable (Activos)
            $herramientas = [];
            $responsableId = $almacene->getAttributes()['responsable'] ?? null;
            
            if ($responsableId) {
                $herramientas = \App\Models\Herramienta::where('user_id', $responsableId)
                    ->with('categoriaHerramienta:id,nombre')
                    ->get()
                    ->map(function($h) {
                        return [
                            'id' => $h->id,
                            'nombre' => $h->nombre,
                            'codigo' => $h->codigo_inventario ?? $h->numero_serie,
                            'categoria' => $h->categoriaHerramienta->nombre ?? $h->categoria ?? 'Sin categoría',
                            'estado' => $h->estado,
                            'marca' => $h->marca ?? 'N/A',
                            'modelo' => $h->modelo ?? 'N/A'
                        ];
                    });
            }

            return Inertia::render('Almacenes/Show', [
                'almacen' => $almacene,
                'inventario' => $inventario,
                'herramientas' => $herramientas,
                'stats' => [
                    'total_refacciones' => $inventario->sum('cantidad'),
                    'valor_inventario' => $inventario->sum('valor'),
                    'total_herramientas' => count($herramientas)
                ],
                'revisiones' => \App\Models\InventarioMovimiento::where('almacen_id', $almacene->id)
                    ->where('detalles->estado', 'revision')
                    ->with(['producto:id,nombre,codigo', 'user:id,name'])
                    ->latest()
                    ->get()
                    ->map(fn($m) => [
                        'id' => $m->id,
                        'producto' => $m->producto->nombre ?? 'N/A',
                        'sku' => $m->producto->codigo ?? 'N/A',
                        'cantidad' => $m->cantidad,
                        'tipo' => $m->detalles['tipo_auditoria'] ?? 'AJUSTE',
                        'anterior' => $m->detalles['cantidad_anterior'] ?? 0,
                        'nueva' => $m->detalles['cantidad_nueva'] ?? 0,
                        'fecha' => $m->created_at->format('d/m/Y H:i'),
                        'usuario' => $m->user->name ?? 'Sistema'
                    ])
            ]);
        } catch (\Exception $e) {
            Log::error('Error en AlmacenController@show: ' . $e->getMessage());
            return redirect()->route('almacenes.index')->with('error', 'Error al cargar el detalle del almacén.');
        }
    }

    /**
     * Muestra el formulario para editar un almacén existente.
     */
    public function edit($id)
    {
        $almacen = Almacen::with(['responsable:id,name'])->findOrFail($id);

        $usuarios = User::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Almacenes/Edit', [
            'almacen' => $almacen,
            'usuarios' => $usuarios,
        ]);
    }

    /**
     * Actualiza un almacén existente en la base de datos.
     */
    public function update(Request $request, $id)
    {
        $almacen = Almacen::findOrFail($id);

        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('almacenes', 'nombre')
                    ->where('empresa_id', EmpresaResolver::resolveId())
                    ->ignore($id)
            ],
            'descripcion' => 'nullable|string|max:1000',
            'ubicacion' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'responsable' => 'nullable|integer|exists:users,id',
            'estado' => 'required|in:activo,inactivo',
        ]);

        // Convertir responsable vacío a null
        if (isset($validated['responsable']) && $validated['responsable'] === '') {
            $validated['responsable'] = null;
        }

        $almacen->update($validated);

        return redirect()->route('almacenes.index')->with('success', 'Almacen actualizado correctamente.');
    }

    /**
     * Elimina un almacén de la base de datos.
     */
    public function destroy($id)
    {
        try {
            $almacen = Almacen::findOrFail($id);

            // 1. Verificar existencias físicas en inventario
            $hasStock = \App\Models\Inventario::where('almacen_id', $id)
                ->where('cantidad', '>', 0)
                ->exists();

            if ($hasStock) {
                return redirect()->route('almacenes.index')->withErrors(['error' => 'No se puede eliminar el almacén porque aún tiene productos con existencias físicas.']);
            }

            // 2. Verificar si tiene productos asignados (Legacy o configuraciones)
            if ($almacen->productos()->exists()) {
                return redirect()->route('almacenes.index')->withErrors(['error' => 'No se puede eliminar el almacén porque está asignado como almacén principal de algunos productos.']);
            }

            // 3. Verificar si hay movimientos de inventario asociados (Trazabilidad)
            $hasMovements = \App\Models\InventarioMovimiento::where('almacen_id', $id)->exists();
            if ($hasMovements) {
                return redirect()->route('almacenes.index')->withErrors(['error' => 'No se puede eliminar el almacén porque tiene un historial de movimientos de inventario. Considere desactivarlo en su lugar.']);
            }

            // 4. Verificar si está asignado a usuarios
            $assignedToUsers = \App\Models\User::where('almacen_venta_id', $id)
                ->orWhere('almacen_compra_id', $id)
                ->exists();
            
            if ($assignedToUsers) {
                return redirect()->route('almacenes.index')->withErrors(['error' => 'No se puede eliminar el almacén porque está asignado como predeterminado a uno o más usuarios.']);
            }

            $almacen->delete();

            return redirect()->route('almacenes.index')->with('success', 'Almacén eliminado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar almacen: ' . $e->getMessage());
            return redirect()->route('almacenes.index')->withErrors(['error' => 'Error al eliminar el almacen.']);
        }
    }

    /**
     * Alterna el estado de un almacén (activo/inactivo).
     */
    public function toggle($id)
    {
        try {
            $almacen = Almacen::findOrFail($id);
            $almacen->update(['estado' => $almacen->estado === 'activo' ? 'inactivo' : 'activo']);

            $mensaje = $almacen->estado === 'activo' ? 'Almacen activado correctamente' : 'Almacen desactivado correctamente';

            return redirect()->back()->with('success', $mensaje);
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado de almacen: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cambiar el estado del almacen.');
        }
    }

    /**
     * Exporta almacenes a CSV
     */
    public function export(Request $request)
    {
        try {
            $query = Almacen::with(['responsable:id,name']);

            // Aplicar los mismos filtros que en index
            if ($search = trim($request->input('search', ''))) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%")
                        ->orWhere('direccion', 'like', "%{$search}%")
                        ->orWhere('responsable', 'like', "%{$search}%");
                });
            }

            if ($estado = $request->input('estado')) {
                if ($estado === 'activo') {
                    $query->where('estado', 'activo');
                } elseif ($estado === 'inactivo') {
                    $query->where('estado', 'inactivo');
                }
            }

            $almacenes = $query->get();

            $filename = 'almacenes_' . date('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($almacenes) {
                $file = fopen('php://output', 'w');

                fputcsv($file, [
                    'ID',
                    'Nombre',
                    'Descripción',
                    'Dirección',
                    'Teléfono',
                    'Responsable',
                    'Estado',
                    'Fecha Creación'
                ]);

                foreach ($almacenes as $almacen) {
                    fputcsv($file, [
                        $almacen->id,
                        $almacen->nombre,
                        $almacen->descripcion ?? '',
                        $almacen->direccion ?? '',
                        $almacen->telefono ?? '',
                        $almacen->responsable ?? '',
                        $almacen->estado,
                        $almacen->created_at?->format('d/m/Y H:i:s')
                    ]);
                }
                fclose($file);
            };

            Log::info('Exportación de almacenes', ['total' => $almacenes->count()]);

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Error en exportación de almacenes: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al exportar los almacenes.');
        }
    }

    /**
     * Procesa una auditoría completa de almacén y la envía a revisión.
     * El stock NO se actualiza hasta que el Super Admin apruebe.
     */
    public function finalizarAuditoria(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'ajustes' => 'required|array',
                'ajustes.*.id' => 'required|exists:inventarios,id',
                'ajustes.*.nueva_cantidad' => 'required|integer|min:0',
                'observaciones' => 'nullable|string'
            ]);

            DB::beginTransaction();

            foreach ($validated['ajustes'] as $ajuste) {
                $inventario = Inventario::findOrFail($ajuste['id']);
                $cantidadAnterior = $inventario->cantidad;
                $nuevaCantidad = $ajuste['nueva_cantidad'];
                $diferencia = $nuevaCantidad - $cantidadAnterior;

                if ($diferencia == 0) continue;

                $esMerma = $diferencia < 0;
                $etiqueta = $esMerma ? 'MERMA' : 'EXCEDENTE';
                
                // Marcamos como REVISIÓN PENDIENTE (No actualizamos stock aún)
                $motivo = "[PENDIENTE DE REVISIÓN] Auditoría: {$etiqueta}. " . ($validated['observaciones'] ?? '');

                InventarioMovimiento::create([
                    'empresa_id' => $inventario->empresa_id,
                    'producto_id' => $inventario->producto_id,
                    'almacen_id' => $inventario->almacen_id,
                    'cantidad' => abs($diferencia),
                    'tipo' => $diferencia > 0 ? 'entrada' : 'salida',
                    'motivo' => $motivo,
                    'user_id' => auth()->id(),
                    'fecha' => now(),
                    'detalles' => [
                        'auditoria_pendiente' => true,
                        'tipo_auditoria' => $etiqueta,
                        'cantidad_anterior' => $cantidadAnterior,
                        'cantidad_nueva' => $nuevaCantidad,
                        'diferencia' => $diferencia,
                        'estado' => 'revision'
                    ]
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'mensaje' => 'La auditoría ha sido enviada a revisión por el Super Admin.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en AlmacenController@finalizarAuditoria: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'No se pudo enviar la auditoría'], 500);
        }
    }

    /**
     * Aprueba una auditoría pendiente y actualiza el stock real.
     */
    public function aprobarAuditoria(Request $request, $movimientoId)
    {
        try {
            $movimiento = InventarioMovimiento::findOrFail($movimientoId);
            
            if (($movimiento->detalles['estado'] ?? '') !== 'revision') {
                return response()->json(['success' => false, 'error' => 'Este movimiento no está en revisión'], 400);
            }

            DB::beginTransaction();

            $inventario = Inventario::where('producto_id', $movimiento->producto_id)
                ->where('almacen_id', $movimiento->almacen_id)
                ->firstOrFail();

            // Actualizar stock real
            $nuevaCantidad = $movimiento->detalles['cantidad_nueva'];
            $inventario->update(['cantidad' => $nuevaCantidad]);

            // Actualizar movimiento a aprobado
            $detalles = $movimiento->detalles;
            $detalles['estado'] = 'aprobado';
            $detalles['aprobado_por'] = auth()->id();
            $detalles['fecha_aprobacion'] = now();
            
            $movimiento->update([
                'motivo' => str_replace('[PENDIENTE DE REVISIÓN]', '[AUDITORÍA APROBADA]', $movimiento->motivo),
                'detalles' => $detalles
            ]);

            DB::commit();

            return response()->json(['success' => true, 'mensaje' => 'Auditoría aprobada y stock actualizado.']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Error en AlmacenController@aprobarAuditoria: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'No se pudo aprobar la auditoría'], 500);
        }
    }

    /**
     * Exporta el reporte de mermas y excedentes a Excel.
     */
    public function exportarMermas(Request $request, $id = null)
    {
        $nombreArchivo = $id ? "mermas_almacen_{$id}.xlsx" : "mermas_global.xlsx";
        return Excel::download(new MermasExport($id), $nombreArchivo);
    }
}

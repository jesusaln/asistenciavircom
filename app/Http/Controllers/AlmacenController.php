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

            // Paginación
            $perPage = min((int) $request->input('per_page', 10), 50);
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
}

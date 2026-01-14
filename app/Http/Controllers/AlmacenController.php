<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Almacen;
use App\Models\User;
use Illuminate\Http\Request;
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
            $query = Almacen::with(['responsable:id,name']);

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
            ];

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

            // Verificar si tiene productos relacionados antes de eliminar
            if ($almacen->productos()->exists()) {
                return redirect()->route('almacenes.index')->withErrors(['error' => 'No se puede eliminar el almacen porque tiene productos asociados.']);
            }

            $almacen->delete();

            return redirect()->route('almacenes.index')->with('success', 'Almacen eliminado correctamente.');
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

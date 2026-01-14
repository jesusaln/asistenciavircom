<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Illuminate\Validation\Rule;
use App\Support\EmpresaResolver;

class CategoriaController extends Controller
{
    /**
     * Muestra una lista de todas las categorías con paginación y filtros.
     */
    public function index(Request $request)
    {
        try {
            $query = Categoria::query();

            // Filtros
            if ($search = trim($request->input('search', ''))) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%");
                });
            }

            // Ordenamiento
            $sortBy = $request->input('sort_by', 'nombre');
            $sortDirection = $request->input('sort_direction', 'asc');

            $validSortFields = ['nombre', 'descripcion', 'created_at'];
            if (!in_array($sortBy, $validSortFields)) {
                $sortBy = 'nombre';
            }

            $query->orderBy($sortBy, $sortDirection);

            // Si es petición AJAX/JSON (pero NO Inertia), devolver lista simple
            if (($request->wantsJson() || $request->ajax()) && !$request->header('X-Inertia')) {
                return response()->json(Categoria::where('estado', 'activo')->orderBy('nombre')->get());
            }

            // Paginación
            $perPage = min((int) $request->input('per_page', 10), 50);
            $categorias = $query->paginate($perPage)->appends($request->query());

            // Estadísticas
            $total = Categoria::count();
            $activos = Categoria::where('estado', 'activo')->count();
            $inactivos = Categoria::where('estado', 'inactivo')->count();

            $stats = [
                'total' => $total,
                'activos' => $activos,
                'inactivos' => $inactivos,
                'activos_porcentaje' => $total > 0 ? round(($activos / $total) * 100, 1) : 0,
                'inactivos_porcentaje' => $total > 0 ? round(($inactivos / $total) * 100, 1) : 0,
            ];

            return Inertia::render('Categorias/Index', [
                'categorias' => $categorias,
                'stats' => $stats,
                'filters' => $request->only(['search']),
                'sorting' => ['sort_by' => $sortBy, 'sort_direction' => $sortDirection],
            ]);
        } catch (\Exception $e) {
            Log::error('Error en CategoriaController@index: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar las categorías.');
        }
    }

    /**
     * Muestra el formulario para crear una nueva categoría.
     */
    public function create()
    {
        return Inertia::render('Categorias/Create');
    }

    /**
     * Almacena una nueva categoría en la base de datos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categorias', 'nombre')->where('empresa_id', EmpresaResolver::resolveId())
            ],
            'descripcion' => 'nullable|string|max:1000',
            'estado' => 'required|in:activo,inactivo',
        ]);

        // Solo pasar los campos fillable para evitar conflictos con ID
        Categoria::create($request->only(['nombre', 'descripcion', 'estado']));

        return redirect()->route('categorias.index')->with('success', 'Categoría creada correctamente.');
    }

    /**
     * Muestra el formulario para editar una categoría existente.
     */
    public function edit(Categoria $categoria)
    {
        return Inertia::render('Categorias/Edit', [
            'categoria' => $categoria,
        ]);
    }

    /**
     * Actualiza una categoría existente en la base de datos.
     */
    public function update(Request $request, Categoria $categoria)
    {
        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categorias', 'nombre')
                    ->where('empresa_id', EmpresaResolver::resolveId())
                    ->ignore($categoria->id)
            ],
            'descripcion' => 'nullable|string|max:1000',
            'estado' => 'required|in:activo,inactivo',
        ]);

        $categoria->update($validated);

        return redirect()->route('categorias.index')->with('success', 'Categoría actualizada correctamente.');
    }

    /**
     * Elimina una categoría de la base de datos.
     */
    public function destroy($id)
    {
        try {
            $categoria = Categoria::findOrFail($id);

            // Verificar si tiene productos relacionados antes de eliminar
            if ($categoria->productos()->exists()) {
                return redirect()->route('categorias.index')->withErrors(['error' => 'No se puede eliminar la categoría porque tiene productos asociados.']);
            }

            $categoria->delete();

            return redirect()->route('categorias.index')->with('success', 'Categoría eliminada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar categoría: ' . $e->getMessage());
            return redirect()->route('categorias.index')->withErrors(['error' => 'Error al eliminar la categoría.']);
        }
    }

    /**
     * Alterna el estado de una categoría (activo/inactivo).
     */
    public function toggle(Categoria $categoria)
    {
        try {
            $categoria->update(['estado' => $categoria->estado === 'activo' ? 'inactivo' : 'activo']);

            $mensaje = $categoria->estado === 'activo' ? 'Categoría activada correctamente' : 'Categoría desactivada correctamente';

            return redirect()->back()->with('success', $mensaje);
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado del categoría: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cambiar el estado de la categoría.');
        }
    }

    /**
     * Exporta categorías a CSV
     */
    public function export(Request $request)
    {
        try {
            $query = Categoria::query();

            // Aplicar los mismos filtros que en index
            if ($search = trim($request->input('search', ''))) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%");
                });
            }

            $categorias = $query->get();

            $filename = 'categorias_' . date('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($categorias) {
                $file = fopen('php://output', 'w');

                fputcsv($file, [
                    'ID',
                    'Nombre',
                    'Descripción',
                    'Estado',
                    'Fecha Creación'
                ]);

                foreach ($categorias as $categoria) {
                    fputcsv($file, [
                        $categoria->id,
                        $categoria->nombre,
                        $categoria->descripcion ?? '',
                        $categoria->estado,
                        $categoria->created_at?->format('d/m/Y H:i:s')
                    ]);
                }
                fclose($file);
            };

            Log::info('Exportación de categorías', ['total' => $categorias->count()]);

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Error en exportación de categorías: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al exportar las categorías.');
        }
    }
}

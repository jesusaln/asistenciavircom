<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Marca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Support\EmpresaResolver;

class MarcaController extends Controller
{
    /**
     * Muestra una lista de todas las marcas con paginación y filtros.
     */
    public function index(Request $request)
    {
        try {
            $query = Marca::query();

            // Filtros
            if ($search = trim($request->input('search', ''))) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%");
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

            $validSortFields = ['nombre', 'descripcion', 'created_at'];
            if (!in_array($sortBy, $validSortFields)) {
                $sortBy = 'nombre';
            }

            $query->orderBy($sortBy, $sortDirection);

            // Si es petición AJAX/JSON (pero NO Inertia), devolver lista simple
            if (($request->wantsJson() || $request->ajax()) && !$request->header('X-Inertia')) {
                return response()->json(Marca::where('estado', 'activo')->orderBy('nombre')->get());
            }

            // Paginación
            $perPage = min((int) $request->input('per_page', 10), 50);
            $marcas = $query->paginate($perPage)->appends($request->query());

            // Estadísticas
            $total = Marca::count();
            $activos = Marca::where('estado', 'activo')->count();
            $inactivos = Marca::where('estado', 'inactivo')->count();

            $stats = [
                'total' => $total,
                'activos' => $activos,
                'inactivos' => $inactivos,
                'activos_porcentaje' => $total > 0 ? round(($activos / $total) * 100, 1) : 0,
                'inactivos_porcentaje' => $total > 0 ? round(($inactivos / $total) * 100, 1) : 0,
            ];

            return Inertia::render('Marcas/Index', [
                'marcas' => $marcas,
                'stats' => $stats,
                'filters' => $request->only(['search', 'estado']),
                'sorting' => ['sort_by' => $sortBy, 'sort_direction' => $sortDirection],
            ]);
        } catch (\Exception $e) {
            Log::error('Error en MarcaController@index: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar las marcas.');
        }
    }

    /**
     * Muestra el formulario para crear una nueva marca.
     */
    public function create()
    {
        return Inertia::render('Marcas/Create');
    }

    /**
     * Almacena una nueva marca en la base de datos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('marcas', 'nombre')->where('empresa_id', EmpresaResolver::resolveId())
            ],
            'descripcion' => 'nullable|string|max:1000',
            'estado' => 'required|in:activo,inactivo',
        ]);

        Marca::create($request->all());

        return redirect()->route('marcas.index')->with('success', 'Marca creada correctamente.');
    }

    /**
     * Muestra el formulario para editar una marca existente.
     */
    public function edit(Marca $marca)
    {
        return Inertia::render('Marcas/Edit', [
            'marca' => $marca,
        ]);
    }

    /**
     * Actualiza una marca existente en la base de datos.
     */
    public function update(Request $request, Marca $marca)
    {
        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('marcas', 'nombre')
                    ->where('empresa_id', EmpresaResolver::resolveId())
                    ->ignore($marca->id)
            ],
            'descripcion' => 'nullable|string|max:1000',
            'estado' => 'required|in:activo,inactivo',
        ]);

        $marca->update($validated);

        return redirect()->route('marcas.index')->with('success', 'Marca actualizada correctamente.');
    }

    /**
     * Elimina una marca de la base de datos.
     */
    public function destroy($id)
    {
        try {
            $marca = Marca::findOrFail($id);

            // Verificar si tiene productos relacionados antes de eliminar
            if ($marca->productos()->exists()) {
                return redirect()->route('marcas.index')->withErrors(['error' => 'No se puede eliminar la marca porque tiene productos asociados.']);
            }

            $marca->delete();

            return redirect()->route('marcas.index')->with('success', 'Marca eliminada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar marca: ' . $e->getMessage());
            return redirect()->route('marcas.index')->withErrors(['error' => 'Error al eliminar la marca.']);
        }
    }

    /**
     * Alterna el estado de una marca (activo/inactivo).
     */
    public function toggle(Marca $marca)
    {
        try {
            $marca->update(['estado' => $marca->estado === 'activo' ? 'inactivo' : 'activo']);

            $mensaje = $marca->estado === 'activo' ? 'Marca activada correctamente' : 'Marca desactivada correctamente';

            return redirect()->back()->with('success', $mensaje);
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado de marca: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cambiar el estado de la marca.');
        }
    }

    /**
     * Exporta marcas a CSV
     */
    public function export(Request $request)
    {
        try {
            $query = Marca::query();

            // Aplicar los mismos filtros que en index
            if ($search = trim($request->input('search', ''))) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%");
                });
            }

            if ($estado = $request->input('estado')) {
                if ($estado === 'activo') {
                    $query->where('estado', 'activo');
                } elseif ($estado === 'inactivo') {
                    $query->where('estado', 'inactivo');
                }
            }

            $marcas = $query->get();

            $filename = 'marcas_' . date('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($marcas) {
                $file = fopen('php://output', 'w');

                fputcsv($file, [
                    'ID',
                    'Nombre',
                    'Descripción',
                    'Estado',
                    'Fecha Creación'
                ]);

                foreach ($marcas as $marca) {
                    fputcsv($file, [
                        $marca->id,
                        $marca->nombre,
                        $marca->descripcion ?? '',
                        $marca->estado,
                        $marca->created_at?->format('d/m/Y H:i:s')
                    ]);
                }
                fclose($file);
            };

            Log::info('Exportación de marcas', ['total' => $marcas->count()]);

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Error en exportación de marcas: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al exportar las marcas.');
        }
    }
}

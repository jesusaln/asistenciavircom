<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Servicio;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\SatClaveUnidad;
use App\Models\SatObjetoImp;
use App\Models\SatClaveProdServ;

class ServicioController extends Controller
{
    /**
     * Muestra una lista de todos los servicios con paginación y filtros.
     */
    public function index(Request $request)
    {
        try {
            $query = Servicio::query()->with(['categoria']);

            // Filtros
            if ($search = trim($request->input('search', ''))) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('codigo', 'like', "%{$search}%")
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

            // Filtro por categoría
            if ($categoriaId = $request->input('categoria_id')) {
                $query->where('categoria_id', $categoriaId);
            }

            // Ordenamiento
            $sortBy = $request->input('sort_by', 'nombre');
            $sortDirection = $request->input('sort_direction', 'asc');

            $validSortFields = ['nombre', 'codigo', 'precio', 'duracion', 'created_at'];
            if (!in_array($sortBy, $validSortFields)) {
                $sortBy = 'nombre';
            }

            $query->orderBy($sortBy, $sortDirection);

            // Paginación
            $perPage = min((int) $request->input('per_page', 10), 50);
            $servicios = $query->paginate($perPage)->appends($request->query());

            // Agregar permisos a cada servicio
            foreach ($servicios->items() as $servicio) {
                $servicio->can_delete = $this->canDeleteServicio($servicio);
                $servicio->can_toggle_in_index = false; // No mostrar botón de cambiar estado en el índice
                $servicio->can_toggle_in_modal = true; // Sí mostrar en el modal
            }

            // Estadísticas basadas en estado del servicio
            $stats = [
                'total' => Servicio::count(),
                'activos' => Servicio::where('estado', 'activo')->count(),
                'inactivos' => Servicio::where('estado', 'inactivo')->count(),
            ];

            return Inertia::render('Servicios/Index', [
                'servicios' => $servicios,
                'stats' => $stats,
                'filters' => $request->only(['search', 'estado']),
                'sorting' => ['sort_by' => $sortBy, 'sort_direction' => $sortDirection],
            ]);
        } catch (\Exception $e) {
            Log::error('Error en ServicioController@index: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar los servicios.');
        }
    }

    /**
     * Muestra el formulario para crear un nuevo servicio.
     */
    public function create()
    {
        return Inertia::render('Servicios/Create', [
            'categorias' => Categoria::select('id', 'nombre')->get(),
            'satCatalogos' => [
                'unidades' => SatClaveUnidad::getOptions(),
                'objetosImp' => SatObjetoImp::getOptions(),
            ]
        ]);
    }

    /**
     * Almacena un nuevo servicio en la base de datos.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'codigo' => 'required|string|unique:servicios,codigo',
            'categoria_id' => 'required|exists:categorias,id',
            'precio' => 'required|numeric|min:0',
            'duracion' => 'required|integer|min:0',
            'estado' => 'required|in:activo,inactivo',
            'es_instalacion' => 'nullable|boolean',
            'comision_vendedor' => 'nullable|numeric|min:0',
            'sat_clave_prod_serv' => 'nullable|string|max:8',
            'sat_clave_unidad'    => 'nullable|string|max:3',
            'sat_objeto_imp'      => 'nullable|string|max:2',
        ]);

        // Asegurar que comision_vendedor tenga un valor por defecto
        $validated['comision_vendedor'] = $validated['comision_vendedor'] ?? 0;

        Servicio::create($validated);

        return redirect()->route('servicios.index')->with('success', 'Servicio creado correctamente.');
    }

    /**
     * Muestra el formulario para editar un servicio existente.
     */
    public function edit(Servicio $servicio)
    {
        return Inertia::render('Servicios/Edit', [
            'servicio' => $servicio,
            'categorias' => Categoria::all(),
            'satCatalogos' => [
                'unidades' => SatClaveUnidad::getOptions(),
                'objetosImp' => SatObjetoImp::getOptions(),
                'claveProdServActual' => $servicio->satClaveProdServ 
                    ? ['clave' => $servicio->sat_clave_prod_serv, 'descripcion' => $servicio->satClaveProdServ->descripcion]
                    : null,
            ]
        ]);
    }

    /**
     * Actualiza un servicio existente en la base de datos.
     */
    public function update(Request $request, Servicio $servicio)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'codigo' => 'nullable|string|unique:servicios,codigo,' . $servicio->id,
            'categoria_id' => 'required|exists:categorias,id',
            'precio' => 'required|numeric|min:0',
            'duracion' => 'required|integer|min:0',
            'estado' => 'required|in:activo,inactivo',
            'es_instalacion' => 'nullable|boolean',
            'comision_vendedor' => 'nullable|numeric|min:0',
            'sat_clave_prod_serv' => 'nullable|string|max:8',
            'sat_clave_unidad'    => 'nullable|string|max:3',
            'sat_objeto_imp'      => 'nullable|string|max:2',
        ]);

        // Asegurar que comision_vendedor tenga un valor por defecto
        $validated['comision_vendedor'] = $validated['comision_vendedor'] ?? 0;

        $servicio->update($validated);

        return redirect()->route('servicios.index')->with('success', 'Servicio actualizado correctamente.');
    }

    /**
     * Actualiza solo la clave SAT de producto/servicio.
     */
    public function updateSat(Request $request, Servicio $servicio)
    {
        $validated = $request->validate([
            'sat_clave_prod_serv' => 'required|string|max:8',
            'sat_clave_unidad' => 'nullable|string|max:3',
            'sat_objeto_imp' => 'nullable|string|max:2',
        ]);

        $servicio->update($validated);

        return response()->json([
            'message' => 'Clave SAT actualizada correctamente',
            'servicio' => $servicio,
        ]);
    }

    /**
     * Elimina un servicio de la base de datos.
     */
    public function destroy(Servicio $servicio)
    {
        // Validar que el servicio pueda ser eliminado
        if (!$this->canDeleteServicio($servicio)) {
            $mensaje = 'No se puede eliminar el servicio. ';
            
            if ($servicio->estado === 'activo') {
                $mensaje .= 'El servicio debe estar inactivo antes de eliminarlo.';
            } else {
                $mensaje .= 'El servicio tiene documentos asociados (cotizaciones, pedidos o ventas).';
            }
            
            return redirect()->route('servicios.index')->with('error', $mensaje);
        }

        $servicio->delete();

        return redirect()->route('servicios.index')->with('success', 'Servicio eliminado correctamente.');
    }

    /**
     * Alterna el estado de un servicio (activo/inactivo).
     */
    public function toggle(Servicio $servicio)
    {
        try {
            $servicio->update(['estado' => $servicio->estado === 'activo' ? 'inactivo' : 'activo']);

            $mensaje = $servicio->estado === 'activo' ? 'Servicio activado correctamente' : 'Servicio desactivado correctamente';

            return redirect()->back()->with('success', $mensaje);
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado del servicio: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cambiar el estado del servicio.');
        }
    }

    /**
     * Muestra los detalles de un servicio específico.
     */
    public function show($id)
    {
        $servicio = Servicio::with('categoria')->find($id);

        if (!$servicio) {
            return response()->json(['error' => 'Servicio no encontrado'], 404);
        }

        return response()->json($servicio);
    }

    /**
     * Exporta servicios a CSV
     */
    public function export(Request $request)
    {
        try {
            $query = Servicio::query()->with(['categoria']);

            // Aplicar los mismos filtros que en index
            if ($search = trim($request->input('search', ''))) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('codigo', 'like', "%{$search}%")
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

            $servicios = $query->get();

            $filename = 'servicios_' . date('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($servicios) {
                $file = fopen('php://output', 'w');

                fputcsv($file, [
                    'ID',
                    'Nombre',
                    'Código',
                    'Descripción',
                    'Categoría',
                    'Precio',
                    'Duración (min)',
                    'Estado',
                    'Fecha Creación'
                ]);

                foreach ($servicios as $servicio) {
                    fputcsv($file, [
                        $servicio->id,
                        $servicio->nombre,
                        $servicio->codigo,
                        $servicio->descripcion,
                        $servicio->categoria?->nombre ?? '',
                        $servicio->precio,
                        $servicio->duracion,
                        $servicio->estado,
                        $servicio->created_at?->format('d/m/Y H:i:s')
                    ]);
                }
                fclose($file);
            };

            Log::info('Exportación de servicios', ['total' => $servicios->count()]);

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Error en exportación de servicios: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al exportar los servicios.');
        }
    }

    /**
     * Verifica si un servicio puede ser eliminado.
     * Reglas:
     * - Solo servicios inactivos pueden ser eliminados
     * - No debe estar siendo usado en ningún documento de negocio
     */
    private function canDeleteServicio(Servicio $servicio): bool
    {
        // Solo servicios inactivos pueden ser eliminados
        if ($servicio->estado === 'activo') {
            return false;
        }

        // Verificar si está siendo usado en documentos de negocio
        if ($servicio->cotizaciones()->count() > 0) {
            return false; // Tiene cotizaciones
        }

        if ($servicio->pedidos()->count() > 0) {
            return false; // Tiene pedidos
        }

        if ($servicio->ventas()->count() > 0) {
            return false; // Tiene ventas
        }

        // ✅ FIX: Verificar citas
        if ($servicio->citas()->count() > 0) {
            return false; // Tiene citas
        }

        return true; // Puede ser eliminado
    }
}

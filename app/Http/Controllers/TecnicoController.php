<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\DB;

class TecnicoController extends Controller
{
    /**
     * Mostrar la lista de técnicos.
     */
    public function index(Request $request)
    {
        try {
            $query = User::tecnicos();

            // Filtros de búsqueda
            if ($s = trim((string) $request->input('search', ''))) {
                $query->where(function ($w) use ($s) {
                    $w->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%")
                        ->orWhere('telefono', 'like', "%{$s}%");
                });
            }

            // Filtrar por estado activo/inactivo
            if ($request->query->has('activo')) {
                $val = (string) $request->query('activo');
                if ($val === '1') {
                    $query->where(function ($query) {
                        $query->where('activo', true)->orWhereNull('activo');
                    });
                } elseif ($val === '0') {
                    $query->where('activo', false);
                }
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $validSort = ['nombre', 'apellido', 'email', 'telefono', 'created_at', 'activo'];

            if (!in_array($sortBy, $validSort))
                $sortBy = 'created_at';
            if (!in_array($sortDirection, ['asc', 'desc']))
                $sortDirection = 'desc';

            $query->orderBy($sortBy, $sortDirection);

            // Paginación
            $tecnicos = $query->paginate(10)->appends($request->query());

            // Estadísticas
            $tecnicosCount = User::tecnicos()->count();
            $tecnicosActivos = User::tecnicos()->where('activo', true)->count();

            return Inertia::render('Tecnicos/Index', [
                'tecnicos' => $tecnicos,
                'stats' => [
                    'total' => $tecnicosCount,
                    'activos' => $tecnicosActivos,
                    'inactivos' => $tecnicosCount - $tecnicosActivos,
                ],
                'filters' => $request->only(['search', 'activo']),
                'sorting' => ['sort_by' => $sortBy, 'sort_direction' => $sortDirection],
            ]);
        } catch (Exception $e) {
            Log::error('Error en TecnicoController@index: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar la lista de técnicos.');
        }
    }

    /**
     * Mostrar el formulario para crear un nuevo técnico.
     */
    public function create()
    {
        return Inertia::render('Tecnicos/Create', [
            'usuarios' => User::select('id', 'name', 'email')->get(),
        ]);
    }

    /**
     * Almacenar un nuevo técnico en la base de datos.
     */
    public function store(Request $request)
    {
        // Validar los datos del formulario
        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8',
            'margen_venta_productos' => 'nullable|numeric|min:0|max:100',
            'margen_venta_servicios' => 'nullable|numeric|min:0|max:100',
            'comision_instalacion' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Crear el usuario
            $user = User::create([
                'name' => trim($request->nombre . ' ' . $request->apellido),
                'email' => $request->email,
                'password' => bcrypt($request->password ?? 'temppassword'), // Ojo: gestión de contraseña
                'telefono' => $request->telefono,
                'direccion' => $request->direccion,
                'es_tecnico' => true,
                'activo' => true,
                'margen_venta_productos' => $request->margen_venta_productos ?? 0,
                'margen_venta_servicios' => $request->margen_venta_servicios ?? 0,
                'comision_instalacion' => $request->comision_instalacion ?? 0,
            ]);

            DB::commit();
            return redirect()->route('tecnicos.index')->with('success', 'Técnico creado correctamente.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creando técnico: ' . $e->getMessage());
            return back()->with('error', 'Error al crear el técnico: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar el formulario para editar un técnico existente.
     */
    public function edit(User $tecnico)
    {
        // Adaptar User a campos de tecnico legado si el frontend lo requiere (nombre, apellido separados)
        // El frontend probablemente espere 'nombre' y 'apellido'.
        // Podemos splitear el name.
        $parts = explode(' ', $tecnico->name, 2);
        $tecnico->setAttribute('nombre', $parts[0] ?? '');
        $tecnico->setAttribute('apellido', $parts[1] ?? '');

        return Inertia::render('Tecnicos/Edit', [
            'tecnico' => $tecnico,
            // 'usuarios' legacy removed, now managing User directly
        ]);
    }

    /**
     * Actualizar un técnico existente en la base de datos.
     */
    public function update(Request $request, User $tecnico)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $tecnico->id,
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'margen_venta_productos' => 'nullable|numeric|min:0|max:100',
            'margen_venta_servicios' => 'nullable|numeric|min:0|max:100',
            'comision_instalacion' => 'nullable|numeric|min:0',
        ]);

        $tecnico->update([
            'name' => trim($validated['nombre'] . ' ' . $validated['apellido']),
            'email' => $validated['email'],
            'telefono' => $validated['telefono'],
            'direccion' => $validated['direccion'],
            'margen_venta_productos' => $validated['margen_venta_productos'] ?? 0,
            'margen_venta_servicios' => $validated['margen_venta_servicios'] ?? 0,
            'comision_instalacion' => $validated['comision_instalacion'] ?? 0,
        ]);

        return redirect()->route('tecnicos.index')->with('success', 'Técnico actualizado correctamente.');
    }

    /**
     * Eliminar un técnico de la base de datos.
     */
    public function destroy(User $tecnico)
    {
        // Soft delete para usuarios? O solo quitar flag?
        // Si el usuario solo es técnico, podríamos borrarlo.
        // Pero mejor simplemente quitar el flag es_tecnico para mantener historial si es necesario,
        // o usar método delete de User si queremos borrar.
        // Asumimos comportamiento de borrar técnico = borrar user (soft delete).
        $tecnico->delete();

        return redirect()->route('tecnicos.index')->with('success', 'Técnico dado de baja correctamente.');
    }

    /**
     * Verificar si un email ya existe (opcional para validaciones en tiempo real).
     */
    public function checkEmail(Request $request)
    {
        $exists = User::where('email', $request->query('email'))->exists();
        return response()->json(['exists' => $exists]);
    }

    public function toggle(User $tecnico)
    {
        try {
            $tecnico->update(['activo' => !$tecnico->activo]);
            return redirect()->back()->with('success', $tecnico->activo ? 'Técnico activado correctamente' : 'Técnico desactivado correctamente');
        } catch (Exception $e) {
            Log::error('Error cambiando estado del técnico: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Hubo un problema al cambiar el estado del técnico.');
        }
    }

    public function export(Request $request)
    {
        try {
            $query = User::tecnicos();

            // Aplicar los mismos filtros que en index
            if ($s = trim((string) $request->input('search', ''))) {
                $query->where(function ($w) use ($s) {
                    $w->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%")
                        ->orWhere('telefono', 'like', "%{$s}%");
                });
            }

            if ($request->query->has('activo')) {
                $val = (string) $request->query('activo');
                if ($val === '1') {
                    $query->where(function ($query) {
                        $query->where('activo', true)->orWhereNull('activo');
                    });
                } elseif ($val === '0') {
                    $query->where('activo', false);
                }
            }

            $tecnicos = $query->get();

            $filename = 'tecnicos_' . date('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($tecnicos) {
                $file = fopen('php://output', 'w');

                fputcsv($file, [
                    'ID',
                    'Nombre',
                    'Apellido',
                    'Email',
                    'Teléfono',
                    'Dirección',
                    'Activo',
                    'Fecha Creación'
                ]);

                foreach ($tecnicos as $tecnico) {
                    fputcsv($file, [
                        $tecnico->id,
                        $tecnico->name, // Nombre completo
                        '', // Apellido (vacío en csv unificado o duplicado)
                        $tecnico->email,
                        $tecnico->telefono,
                        $tecnico->direccion,
                        $tecnico->activo ? 'Sí' : 'No',
                        $tecnico->created_at?->format('d/m/Y H:i:s')
                    ]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (Exception $e) {
            Log::error('Error en exportación de técnicos: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al exportar los técnicos.');
        }
    }
}

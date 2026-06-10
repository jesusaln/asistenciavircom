<?php

namespace App\Http\Controllers;

use App\Models\ProyectoTarea;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ProyectoTareaController extends Controller
{
    /**
     * Muestra el tablero de planeación.
     * DEPRECADO: Usar ProyectoController@show
     */
    public function index()
    {
        return redirect()->route('proyectos.index');
    }

    /**
     * Almacena una nueva tarea vinculada a un proyecto.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'estado' => 'required|in:sugerencias,pendiente,en_progreso,completado',
            'prioridad' => 'required|in:baja,media,alta,urgente',
            'proyecto_id' => 'required|exists:proyectos,id',
        ]);

        $this->checkEditAccess($validated['proyecto_id']);

        // Calcular el orden (al final de la columna dentro del proyecto)
        $maxOrden = ProyectoTarea::where('estado', $validated['estado'])
            ->where('proyecto_id', $validated['proyecto_id'])
            ->max('orden') ?? -1;

        $validated['orden'] = $maxOrden + 1;

        ProyectoTarea::create($validated);

        return redirect()->back()->with('success', 'Tarea añadida al tablero.');
    }

    /**
     * Actualiza el estado o el orden de una tarea.
     */
    public function update(Request $request, ProyectoTarea $tarea)
    {
        $this->checkEditAccess($tarea->proyecto_id);

        $validated = $request->validate([
            'titulo' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'estado' => 'sometimes|required|in:sugerencias,pendiente,en_progreso,completado',
            'prioridad' => 'sometimes|required|in:baja,media,alta,urgente',
            'orden' => 'sometimes|required|integer',
        ]);

        $tarea->update($validated);

        return redirect()->back()->with('success', 'Tarea actualizada.');
    }

    /**
     * Elimina una tarea.
     */
    public function destroy(ProyectoTarea $tarea)
    {
        $this->checkEditAccess($tarea->proyecto_id);

        $tarea->delete();
        return redirect()->back()->with('success', 'Tarea eliminada.');
    }

    /**
     * Reordenar múltiples tareas.
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'tareas' => 'required|array',
            'tareas.*.id' => 'required|exists:proyecto_tareas,id',
            'tareas.*.orden' => 'required|integer',
            'tareas.*.estado' => 'required|in:sugerencias,pendiente,en_progreso,completado',
        ]);

        if (empty($request->tareas)) {
            return response()->json(['success' => true]);
        }

        // Obtener el proyecto de la primera tarea y verificar acceso
        $firstTask = ProyectoTarea::findOrFail($request->tareas[0]['id']);
        $this->checkEditAccess($firstTask->proyecto_id);

        DB::transaction(function () use ($request) {
            foreach ($request->tareas as $item) {
                // Opción: Validar que pertenezcan al mismo proyecto
                ProyectoTarea::where('id', $item['id'])->update([
                    'orden' => $item['orden'],
                    'estado' => $item['estado']
                ]);
            }
        });

        return response()->json(['success' => true]);
    }

    /**
     * Verificar permisos de edición sobre el proyecto.
     */
    private function checkEditAccess(int $proyectoId): void
    {
        $user = auth()->user();
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            return;
        }

        $proyecto = \App\Models\Proyecto::findOrFail($proyectoId);
        if ($proyecto->owner_id === $user->id) {
            return;
        }

        $member = $proyecto->members()->where('user_id', $user->id)->first();
        if ($member && $member->pivot->role === 'editor') {
            return;
        }

        abort(403, 'No tienes permiso para modificar este proyecto.');
    }
}

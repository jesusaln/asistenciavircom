<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PolizaMantenimientoEjecucion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Carbon\Carbon;

class PolizaMantenimientoTecnicoController extends Controller
{
    /**
     * Dashboard de mantenimientos para el técnico.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Tareas asignadas al técnico pendiente o en proceso
        $misTareas = PolizaMantenimientoEjecucion::with(['mantenimiento.poliza.cliente', 'mantenimiento.poliza.direccion'])
            ->where('tecnico_id', $user->id)
            ->whereIn('estado', ['pendiente', 'en_proceso', 'reprogramado'])
            ->orderBy('fecha_programada', 'asc')
            ->get();

        // Tareas sin asignar (bolsa de trabajo)
        $tareasDisponibles = PolizaMantenimientoEjecucion::with(['mantenimiento.poliza.cliente', 'mantenimiento.poliza.direccion'])
            ->whereNull('tecnico_id')
            ->whereIn('estado', ['pendiente', 'reprogramado'])
            ->orderBy('fecha_programada', 'asc')
            ->limit(20)
            ->get();

        // Tareas completadas hoy por este técnico
        $completadasHoy = PolizaMantenimientoEjecucion::where('tecnico_id', $user->id)
            ->where('estado', 'completado')
            ->whereDate('fecha_ejecucion', Carbon::today())
            ->count();

        return Inertia::render('Mantenimientos/Tecnico/Index', [
            'misTareas' => $misTareas,
            'tareasDisponibles' => $tareasDisponibles,
            'stats' => [
                'pendientes' => $misTareas->count(),
                'completadas_hoy' => $completadasHoy,
            ]
        ]);
    }

    /**
     * Auto-asignarse una tarea disponible.
     */
    public function tomarTarea(Request $request, $id)
    {
        try {
            $tarea = PolizaMantenimientoEjecucion::findOrFail($id);

            if ($tarea->tecnico_id) {
                return back()->with('error', 'Esta tarea ya fue tomada por otro técnico.');
            }

            $tarea->update([
                'tecnico_id' => Auth::id(),
                'estado' => 'en_proceso', // Marcar como iniciada/aceptada
            ]);

            return back()->with('success', 'Tarea asignada correctamente.');

        } catch (\Exception $e) {
            Log::error("Error tomando tarea {$id}: " . $e->getMessage());
            return back()->with('error', 'Error al asignar la tarea.');
        }
    }

    /**
     * Marcar una tarea como completada.
     */
    public function completar(Request $request, $id)
    {
        $validated = $request->validate([
            'resultado' => 'required|in:exitoso,con_observaciones,fallido',
            'notas_tecnico' => 'nullable|string',
            'checklist' => 'nullable|array', // Validación de checklist
            'evidencia' => 'nullable|array', // Array de archivos si se suben
        ]);

        try {
            $tarea = PolizaMantenimientoEjecucion::findOrFail($id);

            // Lógica de auto-asignación al completar
            // Si el usuario es el mismo asignado OR si no tiene nadie asignado (se la asigna "on the fly")
            // OR si es admin/super-admin (que pueda cerrar tareas de otros, opcional)

            if ($tarea->tecnico_id && $tarea->tecnico_id !== Auth::id()) {
                // Aquí podríamos permitir a admins sobreescribir, pero por seguridad básica:
                // Si ya tiene dueño y no soy yo, error. (A menos que queramos "robar" la tarea)
                // El requerimiento dice: "detecte el que lo cierre ese seria tambien el reponsable asignado"
                // Asumiremos que si otro técnico ya la tiene, no deberíamos cerrarla nosotros sin "tomarla" primero.
                // PERO, si el usuario insiste... dejaremos la restricción por ahora, y nos enfocamos en el caso NULL.
                return back()->with('error', 'Esta tarea pertenece a otro técnico.');
            }

            // Si no tenía técnico asignado, o soy yo:
            $updateData = [
                'estado' => 'completado',
                'fecha_ejecucion' => now(),
                'resultado' => $validated['resultado'],
                'notas_tecnico' => $validated['notas_tecnico'],
                'checklist' => $validated['checklist'] ?? $tarea->checklist,
            ];

            // Si estaba huérfana, me la asigno
            if (!$tarea->tecnico_id) {
                $updateData['tecnico_id'] = Auth::id();
            }

            $tarea->update($updateData);

            return back()->with('success', 'Mantenimiento completado.');

        } catch (\Exception $e) {
            Log::error("Error completando tarea {$id}: " . $e->getMessage());
            return back()->with('error', 'Error al completar la tarea.');
        }
    }
}

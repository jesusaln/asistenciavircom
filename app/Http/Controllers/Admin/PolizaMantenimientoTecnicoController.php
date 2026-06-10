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
        $misTareas = PolizaMantenimientoEjecucion::with(['mantenimiento.poliza.cliente'])
            ->where('tecnico_id', $user->id)
            ->whereIn('estado', ['pendiente', 'en_proceso', 'reprogramado'])
            ->orderBy('fecha_programada', 'asc')
            ->get();

        // Tareas sin asignar (bolsa de trabajo)
        $tareasDisponibles = PolizaMantenimientoEjecucion::with(['mantenimiento.poliza.cliente'])
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
            $afectadas = PolizaMantenimientoEjecucion::where('id', $id)
                ->whereNull('tecnico_id')
                ->update([
                    'tecnico_id' => Auth::id(),
                ]);

            if ($afectadas === 0) {
                return back()->with('error', 'Esta tarea ya fue tomada por otro técnico.');
            }

            return back()->with('success', 'Tarea asignada correctamente.');

        } catch (\Exception $e) {
            Log::error("Error tomando tarea {$id}: " . $e->getMessage());
            return back()->with('error', 'Error al asignar la tarea.');
        }
    }

    /**
     * Iniciar una tarea asignada (Antes).
     */
    public function iniciar(Request $request, $id)
    {
        $validated = $request->validate([
            'notas_iniciales' => 'nullable|string',
            'fotos_antes' => 'nullable|array',
            'fotos_antes.*' => 'image|max:10240', // 10MB max
        ]);

        try {
            $tarea = PolizaMantenimientoEjecucion::findOrFail($id);

            if ($tarea->tecnico_id !== Auth::id()) {
                return back()->with('error', 'No tienes permiso para iniciar esta tarea.');
            }

            $fotosAntesPaths = [];
            if ($request->hasFile('fotos_antes')) {
                foreach ($request->file('fotos_antes') as $file) {
                    $path = $file->store("evidencias_mantenimiento/{$tarea->id}/antes", 'public');
                    $fotosAntesPaths[] = $path;
                }
            }

            $tarea->update([
                'estado' => 'en_proceso',
                'notas_iniciales' => $validated['notas_iniciales'] ?? null,
                'fotos_antes' => $fotosAntesPaths,
            ]);

            return back()->with('success', 'Servicio iniciado correctamente.');

        } catch (\Exception $e) {
            Log::error("Error iniciando tarea {$id}: " . $e->getMessage());
            return back()->with('error', 'Error al iniciar la tarea.');
        }
    }

    /**
     * Marcar una tarea como completada (Después).
     */
    public function completar(Request $request, $id)
    {
        $validated = $request->validate([
            'resultado' => 'required|in:exitoso,con_observaciones,fallido',
            'notas_tecnico' => 'nullable|string',
            'numero_serie' => 'nullable|string|max:100',
            'fotos_despues' => 'nullable|array',
            'fotos_despues.*' => 'image|max:10240', // 10MB max, solo imágenes
        ]);

        try {
            $tarea = PolizaMantenimientoEjecucion::findOrFail($id);

            if ($tarea->tecnico_id !== Auth::id()) {
                return back()->with('error', 'No tienes permiso para completar esta tarea.');
            }

            // Manejo de subida de evidencia (imágenes)
            $fotosDespuesPaths = [];
            if ($request->hasFile('fotos_despues')) {
                foreach ($request->file('fotos_despues') as $file) {
                    $path = $file->store("evidencias_mantenimiento/{$tarea->id}/despues", 'public');
                    $fotosDespuesPaths[] = $path;
                }
            }

            $tarea->update([
                'estado' => 'completado',
                'fecha_ejecucion' => now(),
                'resultado' => $validated['resultado'],
                'notas_tecnico' => $validated['notas_tecnico'],
                'numero_serie' => $validated['numero_serie'] ?? null,
                'fotos_despues' => $fotosDespuesPaths,
                'evidencia' => $fotosDespuesPaths, // fallback para compatibilidad
            ]);

            return back()->with('success', 'Mantenimiento completado.');

        } catch (\Exception $e) {
            Log::error("Error completando tarea {$id}: " . $e->getMessage());
            return back()->with('error', 'Error al completar la tarea.');
        }
    }
}

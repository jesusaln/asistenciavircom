<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PolizaMantenimientoEjecucion;
use App\Traits\ImageOptimizerTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Cita;
use App\Models\Cotizacion;
use App\Models\PolizaServicio;
use Carbon\Carbon;

class PolizaMantenimientoTecnicoApiController extends Controller
{
    use ImageOptimizerTrait;

    private array $equiposCache = [];

    /**
     * Dashboard de mantenimientos para el técnico (App móvil).
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            $perPage = min((int) $request->query('per_page', 50), 100);

            // Tareas asignadas al técnico pendiente o en proceso
            $misTareas = PolizaMantenimientoEjecucion::with(['mantenimiento.poliza.cliente', 'mantenimiento.poliza.equipos'])
                ->where('tecnico_id', $user->id)
                ->whereIn('estado', ['pendiente', 'en_proceso', 'reprogramado'])
                ->orderBy('fecha_programada', 'asc')
                ->paginate($perPage);

            // Tareas sin asignar (bolsa de trabajo) — primeras 50
            $tareasDisponibles = PolizaMantenimientoEjecucion::with(['mantenimiento.poliza.cliente', 'mantenimiento.poliza.equipos'])
                ->whereNull('tecnico_id')
                ->whereIn('estado', ['pendiente', 'en_proceso', 'reprogramado'])
                ->orderBy('fecha_programada', 'asc')
                ->take(50)
                ->get();

            // Procesar equipos unificados (con cache por poliza)
            foreach ($misTareas as $tarea) {
                if ($tarea->mantenimiento && $tarea->mantenimiento->poliza) {
                    $tarea->mantenimiento->poliza->equipos_list = $this->obtenerEquiposUnificados($tarea);
                }
            }
            foreach ($tareasDisponibles as $tarea) {
                if ($tarea->mantenimiento && $tarea->mantenimiento->poliza) {
                    $tarea->mantenimiento->poliza->equipos_list = $this->obtenerEquiposUnificados($tarea);
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'mis_tareas' => $misTareas->items(),
                    'tareas_disponibles' => $tareasDisponibles->values(),
                    'pagination' => [
                        'current_page' => $misTareas->currentPage(),
                        'last_page' => $misTareas->lastPage(),
                        'per_page' => $misTareas->perPage(),
                        'total' => $misTareas->total(),
                    ],
                    'stats' => [
                        'pendientes' => $misTareas->total(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en PolizaMantenimientoTecnicoApiController@index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las tareas de mantenimiento de pólizas.'
            ], 500);
        }
    }

    /**
     * Auto-asignarse una tarea disponible.
     */
    public function tomarTarea(Request $request, $id)
    {
        try {
            $userId = Auth::id();

            $afectadas = \Illuminate\Support\Facades\DB::transaction(function () use ($id, $userId) {
                $tarea = PolizaMantenimientoEjecucion::where('id', $id)
                    ->whereNull('tecnico_id')
                    ->lockForUpdate()
                    ->first();

                if (!$tarea) return 0;

                return $tarea->update(['tecnico_id' => $userId]) ? 1 : 0;
            });

            if ($afectadas === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta tarea ya fue tomada por otro técnico o no está disponible.'
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Tarea asignada correctamente.'
            ]);

        } catch (\Exception $e) {
            Log::error("Error tomando tarea {$id} (API): " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar la tarea.'
            ], 500);
        }
    }

    /**
     * Iniciar una tarea asignada (Antes).
     */
    public function iniciar(Request $request, $id)
    {
        $validated = $request->validate([
            'equipo_id' => 'nullable|string',
            'notas_iniciales' => 'nullable|string',
            'fotos_antes' => 'nullable|array',
            'fotos_antes.*' => 'image|max:10240', // 10MB max
        ]);

        try {
            $tarea = PolizaMantenimientoEjecucion::findOrFail($id);

            if ($tarea->tecnico_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para iniciar esta tarea.'
                ], 403);
            }

            $equipoId = $validated['equipo_id'] ?? null;
            $fotosAntesPaths = [];

            if ($request->hasFile('fotos_antes')) {
                foreach ($request->file('fotos_antes') as $file) {
                    // Optimizar y guardar como WebP
                    $path = $this->saveImageAsWebP($file, "evidencias_mantenimiento/{$tarea->id}/antes", 'public', 72, 1600);
                    if ($path) {
                        $fotosAntesPaths[] = $path;
                    }
                }
            }

            $equiposDetalles = $tarea->equipos_detalles ?? [];
            $tarea->load(['mantenimiento.poliza.cliente', 'mantenimiento.poliza.equipos']);
            $equipos = $this->obtenerEquiposUnificados($tarea);

            // Asegurar que todos los equipos existan en equipos_detalles
            if (count($equipos) > 0) {
                foreach ($equipos as $eq) {
                    $key = "equipo_{$eq['id']}";
                    if (isset($equiposDetalles[$key])) continue;
                    $equiposDetalles[$key] = [
                        'id' => $eq['id'],
                        'tipo' => $eq['tipo'],
                        'tipo_equipo' => $eq['tipo_equipo'] ?? '',
                        'nombre' => $eq['nombre'],
                        'marca' => $eq['marca'],
                        'modelo' => $eq['modelo'],
                        'numero_serie' => $eq['numero_serie'],
                        'serie_evaporador' => $eq['serie_evaporador'] ?? null,
                        'serie_condensadora' => $eq['serie_condensadora'] ?? null,
                        'estado' => 'pendiente',
                        'notas_iniciales' => '',
                        'fotos_antes' => [],
                        'resultado' => '',
                        'notas_tecnico' => '',
                        'fotos_despues' => [],
                        'fecha_inicio' => null,
                        'fecha_fin' => null
                    ];
                }
            }

            if ($equipoId) {
                // Iniciar servicio solo para este equipo
                $equipoKey = "equipo_{$equipoId}";
                if (isset($equiposDetalles[$equipoKey])) {
                    $equiposDetalles[$equipoKey]['estado'] = 'en_proceso';
                    $equiposDetalles[$equipoKey]['fecha_inicio'] = now()->toDateTimeString();
                    $equiposDetalles[$equipoKey]['notas_iniciales'] = $validated['notas_iniciales'] ?? '';
                    
                    // Procesar fotos para este equipo
                    $fotosEquipo = [];
                    $fileInputName = "fotos_antes_equipo_{$equipoId}";
                    if ($request->hasFile($fileInputName)) {
                        foreach ($request->file($fileInputName) as $file) {
                            $path = $this->saveImageAsWebP($file, "evidencias_mantenimiento/{$tarea->id}/equipos/{$equipoId}/antes", 'public', 72, 1600);
                            if ($path) {
                                $fotosEquipo[] = $path;
                            }
                        }
                    }
                    $equiposDetalles[$equipoKey]['fotos_antes'] = $fotosEquipo;
                }
            } else {
                // Iniciar servicio general o para todos los equipos pendientes
                foreach ($equipos as $eq) {
                    $equipoKey = "equipo_{$eq['id']}";
                    if (isset($equiposDetalles[$equipoKey]) && $equiposDetalles[$equipoKey]['estado'] === 'pendiente') {
                        $equiposDetalles[$equipoKey]['estado'] = 'en_proceso';
                        $equiposDetalles[$equipoKey]['fecha_inicio'] = now()->toDateTimeString();
                        $equiposDetalles[$equipoKey]['notas_iniciales'] = $validated['notas_iniciales'] ?? '';
                    }
                }
            }

            // Unificar fotos del antes para la galería general
            $allAntesPhotos = $fotosAntesPaths;
            foreach ($equiposDetalles as $eqData) {
                if (!empty($eqData['fotos_antes'])) {
                    $allAntesPhotos = array_merge($allAntesPhotos, $eqData['fotos_antes']);
                }
            }

            $tarea->update([
                'estado' => 'en_proceso',
                'notas_iniciales' => $validated['notas_iniciales'] ?? $tarea->notas_iniciales,
                'fotos_antes' => array_values(array_unique($allAntesPhotos)),
                'equipos_detalles' => $equiposDetalles,
            ]);

            // Forzar carga de equipos unificados en la respuesta
            $tarea = $tarea->fresh(['mantenimiento.poliza.cliente', 'mantenimiento.poliza.equipos']);
            if ($tarea->mantenimiento && $tarea->mantenimiento->poliza) {
                $tarea->mantenimiento->poliza->equipos_list = $this->obtenerEquiposUnificados($tarea);
            }

            return response()->json([
                'success' => true,
                'message' => $equipoId ? 'Servicio iniciado para el equipo correctamente.' : 'Servicio iniciado correctamente.',
                'data' => $tarea
            ]);

        } catch (\Exception $e) {
            Log::error("Error iniciando tarea {$id} (API): " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar la tarea.'
            ], 500);
        }
    }

    /**
     * Marcar una tarea como completada (Después)
     */
    public function completar(Request $request, $id)
    {
        $validated = $request->validate([
            'equipo_id' => 'nullable|string',
            'resultado' => 'required|in:exitoso,con_observaciones,fallido',
            'notas_tecnico' => 'nullable|string',
            'numero_serie' => 'nullable|string|max:100',
            'serie_evaporador' => 'nullable|string|max:100',
            'serie_condensadora' => 'nullable|string|max:100',
            'tipo_equipo' => 'nullable|string|max:50',
            'fotos_despues' => 'nullable|array',
            'fotos_despues.*' => 'image|max:10240', // 10MB max
            'presion_gas' => 'nullable|string|max:100',
            'amperaje' => 'nullable|string|max:100',
            'voltaje' => 'nullable|string|max:100',
            'temperatura_inyeccion' => 'nullable|string|max:100',
            'temperatura_retorno' => 'nullable|string|max:100',
            'checklist_rutina' => 'nullable|string',
        ]);

        try {
            $tarea = PolizaMantenimientoEjecucion::findOrFail($id);

            if ($tarea->estado === 'completado') {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta tarea ya está completada. No se puede modificar.'
                ], 422);
            }

            if ($tarea->tecnico_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para completar esta tarea.'
                ], 403);
            }

            $equipoId = $validated['equipo_id'] ?? null;
            $fotosDespuesPaths = [];

            if ($request->hasFile('fotos_despues')) {
                foreach ($request->file('fotos_despues') as $file) {
                    // Optimizar y guardar como WebP
                    $path = $this->saveImageAsWebP($file, "evidencias_mantenimiento/{$tarea->id}/despues", 'public', 72, 1600);
                    if ($path) {
                        $fotosDespuesPaths[] = $path;
                    }
                }
            }

            $equiposDetalles = $tarea->equipos_detalles ?? [];
            $tarea->load(['mantenimiento.poliza.cliente', 'mantenimiento.poliza.equipos']);
            $equipos = $this->obtenerEquiposUnificados($tarea);

            // Asegurar que todos los equipos existan en equipos_detalles
            if (count($equipos) > 0) {
                foreach ($equipos as $eq) {
                    $key = "equipo_{$eq['id']}";
                    if (isset($equiposDetalles[$key])) continue;
                    $equiposDetalles[$key] = [
                        'id' => $eq['id'],
                        'tipo' => $eq['tipo'],
                        'tipo_equipo' => $eq['tipo_equipo'] ?? '',
                        'nombre' => $eq['nombre'],
                        'marca' => $eq['marca'],
                        'modelo' => $eq['modelo'],
                        'numero_serie' => $eq['numero_serie'],
                        'serie_evaporador' => $eq['serie_evaporador'] ?? null,
                        'serie_condensadora' => $eq['serie_condensadora'] ?? null,
                        'estado' => 'pendiente',
                        'notas_iniciales' => '',
                        'fotos_antes' => [],
                        'resultado' => '',
                        'notas_tecnico' => '',
                        'fotos_despues' => [],
                        'fecha_inicio' => null,
                        'fecha_fin' => null
                    ];
                }
            }

            if ($equipoId) {
                // Completar servicio solo para este equipo
                $equipoKey = "equipo_{$equipoId}";
                if (isset($equiposDetalles[$equipoKey])) {
                    $this->completarDatosEquipo($equiposDetalles[$equipoKey], $validated, $equiposDetalles[$equipoKey]);

                    // Actualizar el número de serie en la tabla principal de equipos si cambió y es de catálogo
                    if (($equiposDetalles[$equipoKey]['tipo'] ?? '') === 'catalogo') {
                        $eqModel = \App\Models\Equipo::find($equipoId);
                        if ($eqModel && !empty($validated['numero_serie']) && $validated['numero_serie'] !== 'N/A' && $validated['numero_serie'] !== $eqModel->numero_serie) {
                            $eqModel->update(['numero_serie' => $validated['numero_serie']]);
                        }
                    }

                    $this->persistirSerialesEquipoExtra($tarea, $equipoId, $validated);

                    // Procesar fotos para este equipo
                    $fotosEquipo = [];
                    $fileInputName = "fotos_despues_equipo_{$equipoId}";
                    if ($request->hasFile($fileInputName)) {
                        foreach ($request->file($fileInputName) as $file) {
                            $path = $this->saveImageAsWebP($file, "evidencias_mantenimiento/{$tarea->id}/equipos/{$equipoId}/despues", 'public', 72, 1600);
                            if ($path) {
                                $fotosEquipo[] = $path;
                            }
                        }
                    }
                    $equiposDetalles[$equipoKey]['fotos_despues'] = $fotosEquipo;
                    $equiposDetalles[$equipoKey]['fecha_fin'] = now()->toDateTimeString();
                }
            } else {
                // Completar todos los equipos pendientes
                foreach ($equipos as $eq) {
                    $equipoKey = "equipo_{$eq['id']}";
                    if (isset($equiposDetalles[$equipoKey]) && $equiposDetalles[$equipoKey]['estado'] !== 'completado') {
                        $this->completarDatosEquipo($equiposDetalles[$equipoKey], $validated, $eq);
                        $equiposDetalles[$equipoKey]['fecha_fin'] = now()->toDateTimeString();

                        if ($eq['tipo'] === 'catalogo') {
                            $eqModel = \App\Models\Equipo::find($eq['id']);
                            if ($eqModel && !empty($validated['numero_serie']) && $validated['numero_serie'] !== 'N/A' && $validated['numero_serie'] !== $eqModel->numero_serie) {
                                $eqModel->update(['numero_serie' => $validated['numero_serie']]);
                            }
                        }

                        $this->persistirSerialesEquipoExtra($tarea, $eq['id'], $validated);
                    }
                }
            }

            // Unificar fotos del después para la galería general
            $allDespuesPhotos = $fotosDespuesPaths;
            foreach ($equiposDetalles as $eqData) {
                if (!empty($eqData['fotos_despues'])) {
                    $allDespuesPhotos = array_merge($allDespuesPhotos, $eqData['fotos_despues']);
                }
            }

            // Validar si todos los equipos han sido completados
            $todosCompletados = true;
            if (count($equipos) > 0) {
                foreach ($equipos as $eq) {
                    $eqKey = "equipo_{$eq['id']}";
                    if (!isset($equiposDetalles[$eqKey]) || $equiposDetalles[$eqKey]['estado'] !== 'completado') {
                        $todosCompletados = false;
                        break;
                    }
                }
            }

            $nuevoEstado = $todosCompletados ? 'completado' : 'en_proceso';

            $tarea->update([
                'estado' => $nuevoEstado,
                'fecha_ejecucion' => $todosCompletados ? now() : null,
                'resultado' => $todosCompletados ? $validated['resultado'] : $tarea->resultado,
                'notas_tecnico' => $todosCompletados ? $validated['notas_tecnico'] : $tarea->notas_tecnico,
                'numero_serie' => $todosCompletados ? $validated['numero_serie'] : $tarea->numero_serie,
                'fotos_despues' => array_values(array_unique($allDespuesPhotos)),
                'evidencia' => array_values(array_unique($allDespuesPhotos)),
                'equipos_detalles' => $equiposDetalles,
            ]);

            // Forzar carga de equipos unificados en la respuesta
            $tarea = $tarea->fresh(['mantenimiento.poliza.cliente', 'mantenimiento.poliza.equipos']);
            if ($tarea->mantenimiento && $tarea->mantenimiento->poliza) {
                $tarea->mantenimiento->poliza->equipos_list = $this->obtenerEquiposUnificados($tarea);
            }

            return response()->json([
                'success' => true,
                'message' => $todosCompletados ? 'Mantenimiento de la póliza completado.' : 'Mantenimiento del equipo completado.',
                'data' => $tarea
            ]);

        } catch (\Exception $e) {
            Log::error("Error completando tarea {$id} (API): " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al completar la tarea.'
            ], 500);
        }
    }

    /**
     * Liberar/pausar una tarea para que pueda ser tomada por otro técnico.
     */
    public function liberarTarea(Request $request, $id)
    {
        try {
            $tarea = PolizaMantenimientoEjecucion::findOrFail($id);

            // No permitir liberar tareas ya completadas o canceladas
            if (in_array($tarea->estado, ['completado', 'cancelado'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede liberar una tarea que ya está completada o cancelada.'
                ], 422);
            }

            // Permitir liberar si es el técnico asignado
            if ($tarea->tecnico_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para liberar esta tarea.'
                ], 403);
            }

            // Liberar la tarea
            $tarea->update([
                'tecnico_id' => null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tarea liberada correctamente. Ahora está disponible en la bolsa de trabajo.'
            ]);

        } catch (\Exception $e) {
            Log::error("Error liberando tarea {$id} (API): " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al liberar la tarea.'
            ], 500);
        }
    }

    public function historialEquipo(Request $request, $id)
    {
        try {
            $tarea = PolizaMantenimientoEjecucion::with(['mantenimiento.poliza'])->findOrFail($id);
            $poliza = $tarea->mantenimiento?->poliza;
            if (!$poliza) {
                return response()->json(['success' => false, 'message' => 'Póliza no encontrada'], 404);
            }

            $equipoNombre = $request->query('equipo');
            if (!$equipoNombre) {
                return response()->json(['success' => false, 'message' => 'Falta el nombre del equipo'], 422);
            }

            // Citas vinculadas
            $citas = Cita::where('poliza_id', $poliza->id)
                ->where(function ($q) use ($equipoNombre) {
                    $q->whereJsonContains('equipos_servicio', $equipoNombre)
                      ->orWhere('trabajo_realizado', 'ilike', "%{$equipoNombre}%");
                })
                ->with('tecnico:id,name')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn($c) => [
                    'id' => $c->id,
                    'folio' => $c->folio,
                    'fecha' => $c->created_at->format('d/m/Y H:i'),
                    'tipo' => $c->tipo_servicio,
                    'trabajo_realizado' => $c->trabajo_realizado,
                    'fotos' => $c->fotos_finales ?? [],
                    'tecnico' => $c->tecnico?->name ?? 'N/A',
                ]);

            // Mantenimientos previos (ejecuciones)
            $mantenimientos = PolizaMantenimientoEjecucion::whereHas('mantenimiento', function ($q) use ($poliza) {
                    $q->where('poliza_id', $poliza->id);
                })
                ->whereNotNull('fecha_ejecucion')
                ->where('id', '!=', $id)
                ->orderByDesc('fecha_ejecucion')
                ->get()
                ->filter(function ($maint) use ($equipoNombre) {
                    if (empty($maint->equipos_detalles)) return false;
                    foreach ($maint->equipos_detalles as $eqKey => $eqData) {
                        if (isset($eqData['nombre']) && strtolower($eqData['nombre']) === strtolower($equipoNombre)) {
                            return true;
                        }
                    }
                    return false;
                })
                ->map(function ($maint) use ($equipoNombre) {
                    $detalleEquipo = null;
                    foreach ($maint->equipos_detalles as $eqKey => $eqData) {
                        if (isset($eqData['nombre']) && strtolower($eqData['nombre']) === strtolower($equipoNombre)) {
                            $detalleEquipo = $eqData;
                            break;
                        }
                    }
                    return [
                        'id' => $maint->id,
                        'nombre' => $maint->mantenimiento?->nombre ?? 'Mantenimiento Preventivo',
                        'fecha' => $maint->fecha_ejecucion ? \Carbon\Carbon::parse($maint->fecha_ejecucion)->format('d/m/Y H:i') : '',
                        'estado' => $detalleEquipo['estado'] ?? $maint->estado,
                        'resultado' => $detalleEquipo['resultado'] ?? $maint->resultado,
                        'notas_iniciales' => $detalleEquipo['notas_iniciales'] ?? $maint->notas_iniciales,
                        'notas_tecnico' => $detalleEquipo['notas_tecnico'] ?? $maint->notas_tecnico,
                    'numero_serie' => $detalleEquipo['numero_serie'] ?? $maint->numero_serie,
                    'serie_evaporador' => $detalleEquipo['serie_evaporador'] ?? null,
                    'serie_condensadora' => $detalleEquipo['serie_condensadora'] ?? null,
                    'fotos_antes' => $detalleEquipo['fotos_antes'] ?? [],
                    'fotos_despues' => $detalleEquipo['fotos_despues'] ?? [],
                    'presion_gas' => $detalleEquipo['presion_gas'] ?? null,
                    'amperaje' => $detalleEquipo['amperaje'] ?? null,
                    'voltaje' => $detalleEquipo['voltaje'] ?? null,
                    'temperatura_inyeccion' => $detalleEquipo['temperatura_inyeccion'] ?? null,
                    'temperatura_retorno' => $detalleEquipo['temperatura_retorno'] ?? null,
                    'checklist_rutina' => $detalleEquipo['checklist_rutina'] ?? [],
                ];
                })->values();

            // Cotizaciones vinculadas
            $cotizaciones = Cotizacion::where('poliza_id', $poliza->id)
                ->where('equipo_nombre', $equipoNombre)
                ->orderByDesc('created_at')
                ->get()
                ->map(fn($c) => [
                    'id' => $c->id,
                    'numero' => $c->numero_cotizacion,
                    'fecha' => $c->created_at->format('d/m/Y'),
                    'total' => $c->total,
                    'estado' => $c->estado,
                ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'citas' => $citas,
                    'mantenimientos' => $mantenimientos,
                    'cotizaciones' => $cotizaciones,
                    'equipo_nombre' => $equipoNombre,
                ]
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error en historialEquipo: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial del equipo.'
            ], 500);
        }
    }

    /**
     * Completa los datos de un equipo individual (usado tanto en individual como bulk).
     */
    private function completarDatosEquipo(array &$detalle, array $validated, array $eq): void
    {
        $detalle['estado'] = 'completado';
        $detalle['fecha_fin'] = now()->toDateTimeString();
        $detalle['resultado'] = $validated['resultado'];
        $detalle['notas_tecnico'] = $validated['notas_tecnico'] ?? '';

        $eqTipoEquipo = $eq['tipo_equipo'] ?? $validated['tipo_equipo'] ?? '';
        if ($eqTipoEquipo === 'minisplit') {
            $serieEva = $validated['serie_evaporador'] ?? $detalle['serie_evaporador'] ?? null;
            $serieCond = $validated['serie_condensadora'] ?? $detalle['serie_condensadora'] ?? null;
            $detalle['numero_serie'] = ($serieEva || $serieCond)
                ? "Eva: {$serieEva} | Cond: {$serieCond}"
                : ($validated['numero_serie'] ?? $eq['numero_serie'] ?? 'N/A');
            $detalle['serie_evaporador'] = $serieEva;
            $detalle['serie_condensadora'] = $serieCond;
        } else {
            $detalle['numero_serie'] = $validated['numero_serie'] ?? $eq['numero_serie'] ?? 'N/A';
        }

        $detalle['presion_gas'] = $validated['presion_gas'] ?? null;
        $detalle['amperaje'] = $validated['amperaje'] ?? null;
        $detalle['voltaje'] = $validated['voltaje'] ?? null;
        $detalle['temperatura_inyeccion'] = $validated['temperatura_inyeccion'] ?? null;
        $detalle['temperatura_retorno'] = $validated['temperatura_retorno'] ?? null;
        $detalle['checklist_rutina'] = json_decode($validated['checklist_rutina'] ?? '', true) ?: [];
    }

    /**
     * Persiste los seriales del equipo extra a condiciones_especiales de la póliza.
     */
    private function persistirSerialesEquipoExtra($tarea, $equipoId, array $validated): void
    {
        $poliza = $tarea->mantenimiento->poliza;
        if (!$poliza || !isset($poliza->condiciones_especiales['equipos_cliente'])) return;

        $equiposCliente = $poliza->condiciones_especiales['equipos_cliente'];
        if (!str_starts_with((string) $equipoId, 'extra_')) return;

        $idx = (int) substr((string) $equipoId, 6);
        if (!isset($equiposCliente[$idx])) return;

        $equiposCliente[$idx]['serie_evaporador'] = $validated['serie_evaporador'] ?? $equiposCliente[$idx]['serie_evaporador'] ?? '';
        $equiposCliente[$idx]['serie_condensadora'] = $validated['serie_condensadora'] ?? $equiposCliente[$idx]['serie_condensadora'] ?? '';
        $poliza->update(['condiciones_especiales' => $poliza->condiciones_especiales]);
    }

    /**
     * Obtiene la lista unificada de equipos (tanto del catálogo como de condiciones especiales).
     */
    private function obtenerEquiposUnificados($tarea)
    {
        $poliza = $tarea->mantenimiento->poliza ?? null;
        if (!$poliza) return [];

        $polizaId = $poliza->id;
        if (isset($this->equiposCache[$polizaId])) {
            return $this->equiposCache[$polizaId];
        }

        $equipos = [];

        // 1. Equipos del catálogo
        if ($poliza->equipos) {
            foreach ($poliza->equipos as $eq) {
                $equipos[] = [
                    'id' => (string) $eq->id,
                    'tipo' => 'catalogo',
                    'tipo_equipo' => $eq->tipo_equipo ?? 'ventana',
                    'nombre' => $eq->nombre,
                    'marca' => $eq->marca ?? '',
                    'modelo' => $eq->modelo ?? '',
                    'numero_serie' => $eq->numero_serie ?? $eq->serie ?? 'N/A',
                    'serie_evaporador' => null,
                    'serie_condensadora' => null,
                ];
            }
        }

        // 2. Equipos de condiciones especiales (JSON)
        if (isset($poliza->condiciones_especiales['equipos_cliente']) && is_array($poliza->condiciones_especiales['equipos_cliente'])) {
            foreach ($poliza->condiciones_especiales['equipos_cliente'] as $index => $eq) {
                $tipoEquipo = $eq['tipo_equipo'] ?? 'ventana';
                $serial = 'N/A';
                $serieEva = $eq['serie_evaporador'] ?? null;
                $serieCond = $eq['serie_condensadora'] ?? null;

                if ($tipoEquipo === 'minisplit') {
                    if (!empty($serieEva) && !empty($serieCond)) {
                        $serial = "Eva: {$serieEva} | Cond: {$serieCond}";
                    } elseif (!empty($serieEva)) {
                        $serial = "Eva: {$serieEva}";
                    } elseif (!empty($serieCond)) {
                        $serial = "Cond: {$serieCond}";
                    }
                } else {
                    $serial = $eq['numero_serie'] ?? $eq['serie'] ?? $serieEva ?? $serieCond ?? 'N/A';
                }

                $equipos[] = [
                    'id' => "extra_" . $index,
                    'tipo' => 'extra',
                    'tipo_equipo' => $tipoEquipo,
                    'nombre' => $eq['nombre'] ?? ("Equipo " . ($index + 1)),
                    'marca' => $eq['marca'] ?? '',
                    'modelo' => $eq['modelo'] ?? '',
                    'numero_serie' => $serial,
                    'serie_evaporador' => $serieEva,
                    'serie_condensadora' => $serieCond,
                ];
            }
        }

        $this->equiposCache[$polizaId] = $equipos;
        return $equipos;
    }
}

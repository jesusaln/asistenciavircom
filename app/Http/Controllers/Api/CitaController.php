<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
// use App\Models\Tecnico;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Exception;
use App\Support\EmpresaResolver;
use App\Traits\ImageOptimizerTrait;

class CitaController extends Controller
{
    use ImageOptimizerTrait;
    /**
     * Obtener todas las citas en formato JSON con paginación, filtros y estadísticas.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $query = Cita::with('tecnico', 'cliente');

            // Filtrar por técnico autenticado (excepto si es admin)
            $isAdmin = $user->hasAnyRole(['admin', 'super-admin']);
            $tecnico = \App\Models\User::tecnicos()->where('id', $user->id)->first();

            if ($tecnico && !$isAdmin) {
                // Es técnico (y NO admin): solo ve sus propias citas
                $query->where('tecnico_id', $tecnico->id);
            }
            // Si es admin (aunque también sea técnico), ve todas las citas

            // Filtros de búsqueda (igual que en Web)
            if ($s = trim((string) $request->input('search', ''))) {
                $query->where(function ($w) use ($s) {
                    $w->where('tipo_servicio', 'like', "%{$s}%")
                        ->orWhere('descripcion', 'like', "%{$s}%")
                        ->orWhere('problema_reportado', 'like', "%{$s}%")
                        ->orWhereHas('cliente', function ($clienteQuery) use ($s) {
                            $clienteQuery->where('nombre_razon_social', 'like', "%{$s}%");
                        })
                        ->orWhereHas('tecnico', function ($tecnicoQuery) use ($s) {
                            $tecnicoQuery->where('nombre', 'like', "%{$s}%");
                        });
                });
            }

            // Filtros adicionales
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->filled('tecnico_id')) {
                $query->where('tecnico_id', $request->tecnico_id);
            }

            if ($request->filled('cliente_id')) {
                $query->where('cliente_id', $request->cliente_id);
            }

            if ($request->filled('fecha_desde')) {
                $query->whereDate('fecha_hora', '>=', $request->fecha_desde);
            }

            if ($request->filled('fecha_hasta')) {
                $query->whereDate('fecha_hora', '<=', $request->fecha_hasta);
            }

            // Filtro para excluir completadas y canceladas (para Home)
            if ($request->filled('active_only') && $request->active_only) {
                $query->whereNotIn('estado', [Cita::ESTADO_COMPLETADO, Cita::ESTADO_CANCELADO]);
            }

            // Ordenamiento dinámico (igual que en Web)
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');

            if ($sortBy === 'created_at') {
                $query->orderByRaw("
                    CASE
                        WHEN estado = 'en_proceso' THEN 1
                        WHEN estado = 'programado' THEN 2
                        WHEN estado = 'pendiente' THEN 3
                        WHEN estado = 'reprogramado' THEN 4
                        WHEN estado = 'completado' THEN 5
                        WHEN estado = 'cancelado' THEN 6
                        ELSE 999
                    END ASC
                ")->orderBy('fecha_hora', 'asc');
            } else {
                $query->orderBy($sortBy, $sortDirection);
            }

            // Paginación
            $perPage = $request->get('per_page', 15); // Default de API a 15
            $citas = $query->paginate((int) $perPage);

            // Estadísticas por estado (filtradas por técnico si aplica)
            $statsQuery = Cita::query();
            if ($tecnico && !$isAdmin) {
                $statsQuery->where('tecnico_id', $tecnico->id);
            }

            $stats = [
                'total' => (clone $statsQuery)->count(),
                'pendientes' => (clone $statsQuery)->where('estado', Cita::ESTADO_PENDIENTE)->count(),
                'en_proceso' => (clone $statsQuery)->where('estado', Cita::ESTADO_EN_PROCESO)->count(),
                'completadas' => (clone $statsQuery)->where('estado', Cita::ESTADO_COMPLETADO)->count(),
                'canceladas' => (clone $statsQuery)->where('estado', Cita::ESTADO_CANCELADO)->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $citas,
                'stats' => $stats,
                'meta' => [
                    'current_page' => $citas->currentPage(),
                    'last_page' => $citas->lastPage(),
                    'per_page' => $citas->perPage(),
                    'total' => $citas->total(),
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error en CitaController@index API: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar la lista de citas.'
            ], 500);
        }
    }

    /**
     * Almacenar una nueva cita.
     * Permitimos agendar, pero NO vender (sin items).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tecnico_id' => 'required|exists:users,id',
            'cliente_id' => 'required|exists:clientes,id',
            'tipo_servicio' => 'required|string|max:255',
            'fecha_hora' => [
                'required',
                'date',
                'after_or_equal:now',
                function ($attribute, $value, $fail) {
                    $fecha = Carbon::parse($value);
                    if ($fecha->isSunday()) {
                        $fail('No se pueden programar citas los domingos.');
                    }
                    if ($fecha->hour < 8 || $fecha->hour > 18) {
                        $fail('Las citas deben programarse entre las 8:00 AM y 6:00 PM.');
                    }
                }
            ],
            'fecha_hora_fin' => 'nullable|date|after:fecha_hora',
            'descripcion' => 'nullable|string|max:1000',
            'problema_reportado' => 'nullable|string|max:1000',
            'prioridad' => 'nullable|string|in:baja,media,alta,urgente',
            'estado' => 'required|string|in:pendiente,programado,en_proceso,completado,cancelado,reprogramado',
            'evidencias' => 'nullable|string|max:2000',
            'tipo_equipo' => 'nullable|string|max:100',
            'marca_equipo' => 'nullable|string|max:100',
            'modelo_equipo' => 'nullable|string|max:100',
            'foto_equipo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'foto_hoja_servicio' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'foto_identificacion' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'latitud' => 'nullable|numeric',
            'longitud' => 'nullable|numeric',
        ], [
            'tecnico_id.required' => 'Debe seleccionar un técnico.',
            'cliente_id.required' => 'Debe seleccionar un cliente.',
            'fecha_hora.required' => 'La fecha y hora son obligatorias.',
            'fecha_hora.after_or_equal' => 'La fecha debe ser igual o posterior a la actual.',
            '*.max' => 'La imagen no debe superar los 5MB.',
        ]);

        try {
            DB::beginTransaction();

            $duracion = 60;
            if ($request->filled('fecha_hora_fin')) {
                $inicio = Carbon::parse($validated['fecha_hora']);
                $fin = Carbon::parse($request->fecha_hora_fin);
                $duracion = $inicio->diffInMinutes($fin);
            }

            $this->verificarDisponibilidadTecnico($validated['tecnico_id'], $validated['fecha_hora'], null, $duracion);
            $this->verificarLimiteCitasPorDia($validated['tecnico_id'], $validated['fecha_hora']);
            $this->verificarCitasClienteActivas($validated['cliente_id'], $validated['fecha_hora']);

            $filePaths = $this->saveFiles($request, ['foto_equipo', 'foto_hoja_servicio', 'foto_identificacion']);

            $citaData = array_merge($validated, $filePaths);
            if ($request->filled('fecha_hora_fin')) {
                $citaData['fecha_fin'] = $request->fecha_hora_fin;
            } else {
                $citaData['fecha_fin'] = Carbon::parse($validated['fecha_hora'])->addMinutes(60)->toDateTimeString();
            }

            $cita = Cita::create($citaData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cita creada exitosamente.',
                'data' => $cita->load('cliente', 'tecnico')
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear cita API: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la cita.'
            ], 500);
        }
    }

    /**
     * Mostrar detalles de una cita.
     * Incluye items en modo lectura para que el móvil los vea si existen.
     */
    public function show($id)
    {
        try {
            $cita = Cita::with(['cliente', 'tecnico', 'items.citable'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $cita
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Cita no encontrada'], 404);
        } catch (Exception $e) {
            Log::error('Error al obtener cita API: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener la cita'], 500);
        }
    }

    /**
     * Actualizar cita.
     * Permite cambiar estado (incluyendo completado) y subir evidencias.
     * NO permite editar items (Productos/Servicios).
     */
    public function update(Request $request, $id)
    {
        try {
            $cita = Cita::findOrFail($id);

            $validated = $request->validate([
                'tecnico_id' => 'sometimes|required|exists:users,id',
                'cliente_id' => 'sometimes|required|exists:clientes,id',
                'tipo_servicio' => 'sometimes|required|string|max:255',
                'fecha_hora' => [
                    'sometimes',
                    'required',
                    'date',
                    function ($attribute, $value, $fail) use ($cita) {
                        $fecha = Carbon::parse($value);
                        if ($fecha->isPast() && $cita->estado === Cita::ESTADO_PENDIENTE) {
                            $fail('No se puede programar una cita pendiente en el pasado.');
                        }
                        if ($fecha->isSunday()) {
                            $fail('No se pueden programar citas los domingos.');
                        }
                        if ($fecha->hour < 8 || $fecha->hour > 18) {
                            $fail('Las citas deben programarse entre las 8:00 AM y 6:00 PM.');
                        }
                    }
                ],
                'fecha_hora_fin' => 'nullable|date|after:fecha_hora',
                'descripcion' => 'nullable|string|max:1000',
                'problema_reportado' => 'nullable|string|max:1000',
                'prioridad' => 'nullable|string|in:baja,media,alta,urgente',
                'estado' => 'sometimes|required|string|in:pendiente,programado,en_proceso,completado,cancelado,reprogramado',
                'evidencias' => 'nullable|string|max:2000',
                'tipo_equipo' => 'nullable|string|max:100',
                'marca_equipo' => 'nullable|string|max:100',
                'modelo_equipo' => 'nullable|string|max:100',
                'foto_equipo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'foto_hoja_servicio' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'foto_identificacion' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'latitud' => 'nullable|numeric',
                'longitud' => 'nullable|numeric',
            ]);

            DB::beginTransaction();

            // Verificaciones de disponibilidad si cambian datos críticos
            if (
                isset($validated['tecnico_id']) &&
                ($validated['tecnico_id'] != $cita->tecnico_id ||
                    isset($validated['fecha_hora']) && $validated['fecha_hora'] != $cita->fecha_hora)
            ) {
                $duracion = 60;
                if ($request->filled('fecha_hora_fin')) {
                    $inicio = Carbon::parse($validated['fecha_hora'] ?? $cita->fecha_hora);
                    $fin = Carbon::parse($request->fecha_hora_fin);
                    $duracion = $inicio->diffInMinutes($fin);
                } elseif ($cita->fecha_fin && !isset($validated['fecha_hora'])) {
                    // Si no cambia inicio pero ya tenía fin, mantener duración
                    $duracion = Carbon::parse($cita->fecha_hora)->diffInMinutes(Carbon::parse($cita->fecha_fin));
                }

                $this->verificarDisponibilidadTecnico(
                    $validated['tecnico_id'] ?? $cita->tecnico_id,
                    $validated['fecha_hora'] ?? $cita->fecha_hora,
                    $cita->id,
                    $duracion
                );

                if (isset($validated['fecha_hora'])) {
                    $this->verificarLimiteCitasPorDia(
                        $validated['tecnico_id'],
                        $validated['fecha_hora']
                    );
                }
            }

            if (isset($validated['cliente_id']) && $validated['cliente_id'] != $cita->cliente_id) {
                $this->verificarCitasClienteActivas(
                    $validated['cliente_id'],
                    $validated['fecha_hora'] ?? $cita->fecha_hora
                );
            }

            // Validar transición de estado
            if (isset($validated['estado']) && $validated['estado'] !== $cita->estado) {
                $estadosValidos = $cita->getSiguientesEstadosValidos();
                // Permitir ir a Completado explícitamente si la lógica de negocio lo requiere, 
                // aunque getSiguientesEstadosValidos debería manejarlo.
                if (!in_array($validated['estado'], $estadosValidos)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede cambiar al estado solicitado desde el estado actual.',
                        'data' => [
                            'estado_actual' => $cita->estado,
                            'estados_permitidos' => $estadosValidos
                        ]
                    ], 422);
                }
            }

            // Lógica para registrar tiempos
            if (isset($validated['estado']) && $validated['estado'] !== $cita->estado) {
                if ($validated['estado'] === Cita::ESTADO_EN_PROCESO) {
                    // Solo setear inicio si no existe, o si queremos reescribirlo (depende de regla de negocio)
                    // Aquí asumimos: primer 'En Proceso' marca el inicio.
                    if (!$cita->inicio_servicio) {
                        $validated['inicio_servicio'] = now();
                    }
                } elseif ($validated['estado'] === Cita::ESTADO_COMPLETADO) {
                    $validated['fin_servicio'] = now();

                    // Calcular duración
                    // Usar inicio existente o el que se acaba de generar (si pasó de nada a completado muy rápido, raro pero posible)
                    $inicio = $cita->inicio_servicio ?? $validated['inicio_servicio'] ?? null;

                    if ($inicio) {
                        $inicioC = Carbon::parse($inicio);
                        $finC = Carbon::parse($validated['fin_servicio']);
                        // Diferencia en minutos absolutos (entero)
                        $validated['tiempo_servicio'] = (int) $inicioC->diffInMinutes($finC);
                    }
                }
            }

            $filePaths = $this->saveFiles($request, ['foto_equipo', 'foto_hoja_servicio', 'foto_identificacion'], [
                'foto_equipo' => $cita->foto_equipo,
                'foto_hoja_servicio' => $cita->foto_hoja_servicio,
                'foto_identificacion' => $cita->foto_identificacion,
            ]);

            $dataToUpdate = array_merge($validated, $filePaths);
            
            // Si cambia la fecha_hora pero NO se envía fecha_hora_fin, ajustar fecha_fin para mantener duración
            if (isset($validated['fecha_hora']) && !isset($validated['fecha_hora_fin'])) {
                $duracionAnterior = Carbon::parse($cita->fecha_hora)->diffInMinutes(Carbon::parse($cita->fecha_fin ?? $cita->fecha_hora->copy()->addMinutes(60)));
                $dataToUpdate['fecha_fin'] = Carbon::parse($validated['fecha_hora'])->addMinutes($duracionAnterior)->toDateTimeString();
            }

            // Actualizar solo datos básicos y estado, NO items ni totales
            $cita->update($dataToUpdate);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cita actualizada exitosamente.',
                'data' => $cita->load('cliente', 'tecnico')
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar cita API: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la cita.'
            ], 500);
        }
    }

    /**
     * Eliminar una cita.
     */
    public function destroy($id)
    {
        try {
            $cita = Cita::findOrFail($id);

            // Verificar si se puede eliminar (reglas de negocio del modelo)
            if (!$cita->puedeSerEliminada()) {
                $mensaje = $cita->estado === Cita::ESTADO_EN_PROCESO
                    ? 'No se puede eliminar una cita en proceso. Solo se puede cancelar.'
                    : 'No se pueden eliminar citas completadas con menos de 30 días de antigüedad.';

                return response()->json([
                    'success' => false,
                    'message' => $mensaje
                ], 422);
            }

            DB::beginTransaction();

            $archivos = [
                $cita->foto_equipo,
                $cita->foto_hoja_servicio,
                $cita->foto_identificacion
            ];

            foreach ($archivos as $archivo) {
                if ($archivo && Storage::disk('public')->exists($archivo)) {
                    Storage::disk('public')->delete($archivo);
                }
            }

            $cita->delete();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Cita eliminada exitosamente.']);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Cita no encontrada'], 404);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar cita API: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la cita.'
            ], 500);
        }
    }

    /**
     * Reasignar cita a otro técnico (solo admin).
     */
    public function reasignar(Request $request, $id)
    {
        try {
            $user = $request->user();

            // Verificar que el usuario sea admin
            if (!$user->hasAnyRole(['admin', 'super-admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los administradores pueden reasignar citas.'
                ], 403);
            }

            $validated = $request->validate([
                'tecnico_id' => 'required|exists:users,id'
            ]);

            $cita = Cita::findOrFail($id);
            $tecnicoAnterior = $cita->tecnico?->nombre ?? 'N/A';

            $cita->tecnico_id = $validated['tecnico_id'];
            $cita->save();

            $tecnicoNuevo = $cita->fresh()->tecnico?->nombre ?? 'N/A';

            Log::info("Cita #{$id} reasignada", [
                'user_id' => $user->id,
                'tecnico_anterior' => $tecnicoAnterior,
                'tecnico_nuevo' => $tecnicoNuevo
            ]);

            return response()->json([
                'success' => true,
                'message' => "Cita reasignada de {$tecnicoAnterior} a {$tecnicoNuevo}",
                'data' => $cita->load('tecnico', 'cliente')
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Cita no encontrada'], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Error al reasignar cita: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al reasignar la cita.'
            ], 500);
        }
    }

    // --- Helpers Privados ---

    private function verificarDisponibilidadTecnico(int $tecnicoId, string $fechaHora, ?int $excludeId = null, int $duracionMin = 60): void
    {
        $citaExistente = Cita::hayConflictoHorario($tecnicoId, $fechaHora, $excludeId, $duracionMin);

        if ($citaExistente) {
            $inicio = $citaExistente->fecha_hora->format('h:i A');
            $finTime = $citaExistente->fecha_fin ?? $citaExistente->fecha_hora->copy()->addMinutes(60);
            $fin = $finTime->format('h:i A');

            throw ValidationException::withMessages([
                'fecha_hora' => "⚠️ Conflicto detectado: El técnico tiene una cita de {$inicio} a {$fin}. Selecciona un horario que no se traslape."
            ]);
        }
    }

    private function verificarLimiteCitasPorDia(int $tecnicoId, string $fechaHora): void
    {
        $fecha = Carbon::parse($fechaHora)->toDateString();
        $inicioDia = Carbon::parse($fecha)->startOfDay();
        $finDia = Carbon::parse($fecha)->endOfDay();

        $citasEnDia = Cita::where('tecnico_id', $tecnicoId)
            ->whereBetween('fecha_hora', [$inicioDia, $finDia])
            ->where('estado', '!=', Cita::ESTADO_CANCELADO)
            ->count();

        if ($citasEnDia >= 8) {
            throw ValidationException::withMessages([
                'fecha_hora' => 'El técnico ya tiene el máximo de 8 citas programadas para este día.'
            ]);
        }
    }

    private function verificarCitasClienteActivas(int $clienteId, string $fechaHora): void
    {
        $fecha = Carbon::parse($fechaHora);

        $citasActivas = Cita::where('cliente_id', $clienteId)
            ->whereIn('estado', ['pendiente', 'en_proceso'])
            ->where('fecha_hora', '>=', now())
            ->where('fecha_hora', '<=', now()->addDays(7))
            ->count();

        if ($citasActivas >= 5) {
            throw ValidationException::withMessages([
                'cliente_id' => 'El cliente ya tiene múltiples citas activas. Complete las citas existentes antes de programar nuevas.'
            ]);
        }
    }

    private function saveFiles(Request $request, array $fileFields, $existingFiles = [])
    {
        $filePaths = [];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                try {
                    $file = $request->file($field);
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $filename = $originalName . '_' . now()->format('YmdHis') . '_' . substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 6) . '.' . $extension;

                    // Aislamiento por Empresa
                    $empresaId = EmpresaResolver::resolveId();
                    $folder = $empresaId ? "empresas/{$empresaId}/citas" : 'citas';

                    $path = $this->saveImageAsWebP($file, $folder);
                    $filePaths[$field] = $path;

                    if (!empty($existingFiles[$field])) {
                        Storage::disk('public')->delete($existingFiles[$field]);
                    }
                } catch (Exception $e) {
                    Log::error("Error al guardar el archivo {$field}: " . $e->getMessage());
                    $filePaths[$field] = $existingFiles[$field] ?? null;
                }
            } else {
                $filePaths[$field] = $existingFiles[$field] ?? null;
            }
        }
        return $filePaths;
    }

    /**
     * Obtener agenda del técnico para un día específico (Citas y slots ocupados)
     * Utilizado para el mapa de logística en la creación de citas.
     */
    public function agendaTecnico(Request $request)
    {
        $tecnicoId = $request->input('tecnico_id');
        $fecha = $request->input('fecha', now()->toDateString());
        $excludeId = $request->input('exclude_id');

        if (!$tecnicoId) {
            return response()->json(['error' => 'ID de técnico requerido'], 400);
        }

        $inicioDia = Carbon::parse($fecha)->startOfDay();
        $finDia = Carbon::parse($fecha)->endOfDay();

        $query = Cita::where('tecnico_id', $tecnicoId)
            ->whereNotIn('estado', [Cita::ESTADO_CANCELADO, Cita::ESTADO_COMPLETADO])
            ->where(function ($q) use ($inicioDia, $finDia) {
                $q->whereBetween('fecha_hora', [$inicioDia, $finDia]);
            })
            ->orderBy('fecha_hora');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $citas = $query
            ->get(['id', 'folio', 'fecha_hora', 'fecha_fin', 'fecha_inicio', 'tipo_servicio', 'estado', 'cliente_id', 'latitud', 'longitud', 'direccion_calle', 'direccion_colonia'])
            ->map(function ($cita) {
                $inicio = $cita->fecha_hora;
                $fin = $cita->fecha_fin ?? $cita->fecha_hora->copy()->addMinutes(60);
                return [
                    'id' => $cita->id,
                    'folio' => $cita->folio,
                    'inicio' => $inicio->format('H:i'),
                    'fin' => $fin->format('H:i'),
                    'inicio_raw' => $inicio->toDateTimeString(),
                    'fin_raw' => $fin instanceof Carbon ? $fin->toDateTimeString() : Carbon::parse($fin)->toDateTimeString(),
                    'tipo_servicio' => $cita->tipo_servicio,
                    'estado' => $cita->estado,
                    'duracion_min' => $inicio->diffInMinutes($fin),
                    'latitud' => $cita->latitud,
                    'longitud' => $cita->longitud,
                    'direccion' => $cita->direccion_completa ?: "{$cita->direccion_calle}, {$cita->direccion_colonia}",
                ];
            });

        return response()->json([
            'success' => true,
            'tecnico_id' => (int) $tecnicoId,
            'fecha' => $fecha,
            'citas' => $citas->values()
        ]);
    }

    /**
     * Endpoint para verificar disponibilidad en tiempo real (Ionic)
     */
    public function verificarDisponibilidadApi(Request $request)
    {
        $tecnicoId = $request->input('tecnico_id');
        $fechaHora = $request->input('fecha_hora');
        $fechaHoraFin = $request->input('fecha_hora_fin');
        $duracion = $request->input('duracion', 60);
        $excludeId = $request->input('exclude_id');

        if (!$tecnicoId || !$fechaHora) {
            return response()->json([
                'available' => true,
                'message' => ''
            ]);
        }

        // Si se proporciona fecha_hora_fin, calcular la duración en minutos
        if ($fechaHoraFin) {
            $inicio = Carbon::parse($fechaHora);
            $fin = Carbon::parse($fechaHoraFin);
            $duracion = $inicio->diffInMinutes($fin);
        }

        $citaConflicto = Cita::hayConflictoHorario((int)$tecnicoId, $fechaHora, $excludeId, (int)$duracion);

        if ($citaConflicto) {
            $tecnicoNombre = $citaConflicto->tecnico?->nombre ?? 'el técnico';
            $inicio = $citaConflicto->fecha_hora->format('h:i A');
            $finTime = $citaConflicto->fecha_fin ?? $citaConflicto->fecha_hora->copy()->addMinutes(60);
            $fin = $finTime->format('h:i A');

            return response()->json([
                'available' => false,
                'message' => "⚠️ Horario ocupado: {$tecnicoNombre} tiene una cita de {$inicio} a {$fin}. Por favor elige otro horario o técnico."
            ]);
        }

        return response()->json([
            'available' => true,
            'message' => 'Horario disponible'
        ]);
    }
}

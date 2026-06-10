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
use App\Services\VentaFromCitaService;
use App\Models\Ticket;
use App\Models\PolizaServicio;

class CitaController extends Controller
{
    use ImageOptimizerTrait;
    /**
     * Verificar disponibilidad de un técnico para una fecha y hora específica.
     */
    public function checkAvailability(Request $request)
    {
        try {
            $tecnicoId = $request->input('tecnico_id');
            $fechaHora = $request->input('fecha_hora');
            $fechaHoraFin = $request->input('fecha_hora_fin');
            $duracion = $request->input('duracion', 60);
            $excludeId = $request->input('cita_id') ?? $request->input('exclude_id');

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

            $tecnico = \App\Models\User::find($tecnicoId);
            if ($tecnico && $tecnico->estaDeVacaciones($fechaHora)) {
                return response()->json([
                    'available' => false,
                    'message' => "⚠️ El técnico {$tecnico->name} se encuentra de vacaciones o en día de descanso en la fecha seleccionada."
                ]);
            }

            $citaConflicto = Cita::hayConflictoHorario((int)$tecnicoId, $fechaHora, $excludeId ? (int)$excludeId : null, (int)$duracion);

            if ($citaConflicto) {
                $tecnicoNombre = $citaConflicto->tecnico?->nombre ?? $citaConflicto->tecnico?->name ?? 'el técnico';
                $inicioStr = $citaConflicto->fecha_hora->format('h:i A');
                $finTime = $citaConflicto->fecha_hora_fin ?? $citaConflicto->fecha_hora->copy()->addMinutes(60);
                $finStr = $finTime->format('h:i A');

                return response()->json([
                    'available' => false,
                    'message' => "⚠️ Horario ocupado: {$tecnicoNombre} tiene una cita de {$inicioStr} a {$finStr}. Por favor elige otro horario o técnico."
                ]);
            }

            return response()->json([
                'available' => true,
                'message' => 'Horario disponible'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en API checkAvailability: ' . $e->getMessage());
            return response()->json([
                'available' => false,
                'message' => 'Error al validar disponibilidad: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener bloques de tiempo ocupados para un técnico en una fecha específica.
     */
    public function getBusySlots(Request $request)
    {
        try {
            $request->validate([
                'tecnico_id' => 'required|exists:users,id',
                'fecha' => 'required|date_format:Y-m-d',
                'exclude_id' => 'nullable|exists:citas,id'
            ]);

            $tecnicoId = (int) $request->tecnico_id;
            $fecha = $request->fecha;
            $excludeId = $request->filled('exclude_id') ? (int) $request->exclude_id : null;

            $citas = Cita::where('tecnico_id', $tecnicoId)
                ->whereDate('fecha_hora', $fecha)
                ->where('estado', '!=', Cita::ESTADO_CANCELADO)
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->orderBy('fecha_hora')
                ->get(['id', 'fecha_hora', 'fecha_hora_fin']);

            $slots = $citas->map(function ($cita) {
                return [
                    'id' => $cita->id,
                    'start' => Carbon::parse($cita->fecha_hora)->format('H:i'),
                    'end' => Carbon::parse($cita->fecha_hora_fin ?? $cita->fecha_hora)->format('H:i'),
                ];
            });

            $tecnico = \App\Models\User::find($tecnicoId);
            $onVacation = $tecnico ? $tecnico->estaDeVacaciones($fecha) : false;

            return response()->json([
                'success' => true,
                'slots' => $slots,
                'on_vacation' => $onVacation,
                'message' => $onVacation ? "El técnico se encuentra de vacaciones o en descanso este día." : null
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener bloques ocupados: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener la próxima cita pendiente del técnico autenticado.
     */
    public function proxima(Request $request)
    {
        try {
            $user = $request->user();
            
            $proxima = Cita::with('cliente', 'tecnico')
                ->where('tecnico_id', $user->id)
                ->whereIn('estado', [Cita::ESTADO_PENDIENTE, Cita::ESTADO_PROGRAMADO])
                ->where('fecha_hora', '>=', now()->startOfDay()) // Solo de hoy en adelante
                ->orderBy('fecha_hora', 'asc')
                ->first();
            
            return response()->json([
                'success' => true,
                'data' => $proxima
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener próxima cita: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $query = Cita::with('tecnico', 'cliente');

            // Filtrar por técnico autenticado (excepto si es admin, compras o tiene permiso manage-all-citas)
            $puedeVerTodas = $user->hasAnyRole(['admin', 'super-admin', 'compras'])
                || $user->can('manage-all-citas');
            $esTecnicoEnAgenda = ! $puedeVerTodas && (
                \App\Models\User::tecnicos()->where('id', $user->id)->exists()
                || $user->hasRole('tecnico')
            );

            if ($esTecnicoEnAgenda) {
                $query->where('tecnico_id', $user->id);
            }

            if ($s = trim((string) $request->input('search', ''))) {
                $query->where(function ($w) use ($s) {
                    $searchPattern = "%{$s}%";
                    $w->where('tipo_servicio', 'ILIKE', $searchPattern)
                        ->orWhere('descripcion', 'ILIKE', $searchPattern)
                        ->orWhere('problema_reportado', 'ILIKE', $searchPattern)
                        ->orWhere('folio', 'ILIKE', $searchPattern)
                        ->orWhereHas('cliente', function ($clienteQuery) use ($searchPattern) {
                            $clienteQuery->whereRaw("unaccent(nombre_razon_social) ILIKE unaccent(?)", [$searchPattern]);
                        })
                        ->orWhereHas('tecnico', function ($tecnicoQuery) use ($searchPattern) {
                            $tecnicoQuery->where('name', 'ILIKE', $searchPattern);
                        });
                });
            }

            if ($request->filled('estado')) {
                $allowedEstados = [
                    Cita::ESTADO_PENDIENTE,
                    Cita::ESTADO_PENDIENTE_ASIGNACION,
                    Cita::ESTADO_PROGRAMADO,
                    Cita::ESTADO_EN_PROCESO,
                    Cita::ESTADO_COMPLETADO,
                    Cita::ESTADO_CANCELADO,
                    Cita::ESTADO_REPROGRAMADO,
                ];
                $raw = $request->input('estado');
                if (is_string($raw) && str_contains($raw, ',')) {
                    $states = array_values(array_intersect($allowedEstados, array_map('trim', explode(',', $raw))));
                    if ($states !== []) {
                        $query->whereIn('estado', $states);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                } elseif (in_array($raw, $allowedEstados, true)) {
                    $query->where('estado', $raw);
                } else {
                    $query->whereRaw('1 = 0');
                }
            } else {
                $query->where('estado', '!=', Cita::ESTADO_CANCELADO);
            }

            if ($request->filled('tecnico_id')) {
                $query->where('tecnico_id', $request->tecnico_id);
            }

            if ($request->filled('cliente_id')) {
                $query->where('cliente_id', $request->cliente_id);
            }

            // REGLA DE ORO: Si hay citas en proceso del técnico, se muestran siempre
            if ($request->filled('fecha_desde') || $request->filled('fecha_hasta')) {
                $query->where(function ($q) use ($request) {
                    $q->where(function ($dateQuery) use ($request) {
                        if ($request->filled('fecha_desde')) {
                            $dateQuery->whereDate('fecha_hora', '>=', $request->fecha_desde);
                        }
                        if ($request->filled('fecha_hasta')) {
                            $dateQuery->whereDate('fecha_hora', '<=', $request->fecha_hasta);
                        }
                    });
                    
                    // Prioridad absoluta a lo que está "En Proceso"
                    $q->orWhere('estado', Cita::ESTADO_EN_PROCESO);
                });
            }

            if ($request->get('atrasadas') == 1) {
                $query->where('fecha_hora', '<', now())
                      ->whereNotIn('estado', [Cita::ESTADO_COMPLETADO, Cita::ESTADO_CANCELADO]);
            }

            if ($request->filled('active_only') && $request->active_only) {
                $query->whereNotIn('estado', [Cita::ESTADO_COMPLETADO, Cita::ESTADO_CANCELADO]);
            }

            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            if ($sortBy === 'created_at') {
                $query->orderDefaultAgenda();
            } else {
                $query->orderBy($sortBy, $sortDirection);
            }

            $perPage = $request->get('per_page', 15);
            $citas = $query->paginate((int) $perPage);

            $stats = [];
            if ($request->get('page', 1) == 1) {
                $statsQuery = Cita::query();
                if ($esTecnicoEnAgenda) {
                    $statsQuery->where('tecnico_id', $user->id);
                }
                $porAtender = (clone $statsQuery)->whereIn('estado', [
                    Cita::ESTADO_PENDIENTE,
                    Cita::ESTADO_PENDIENTE_ASIGNACION,
                ])->count();
                $stats = [
                    'total' => (clone $statsQuery)->count(),
                    'programadas' => (clone $statsQuery)->where('estado', Cita::ESTADO_PROGRAMADO)->count(),
                    'por_atender' => $porAtender,
                    'en_proceso' => (clone $statsQuery)->where('estado', Cita::ESTADO_EN_PROCESO)->count(),
                    'completadas' => (clone $statsQuery)->where('estado', Cita::ESTADO_COMPLETADO)->count(),
                    'canceladas' => (clone $statsQuery)->where('estado', Cita::ESTADO_CANCELADO)->count(),
                    'reprogramadas' => (clone $statsQuery)->where('estado', Cita::ESTADO_REPROGRAMADO)->count(),
                    'atrasadas' => (clone $statsQuery)->where('fecha_hora', '<', now())
                        ->whereNotIn('estado', [Cita::ESTADO_COMPLETADO, Cita::ESTADO_CANCELADO])
                        ->count(),
                    'pendientes' => $porAtender,
                ];
            }

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
        } catch (\Exception $e) {
            \Log::error('Error en CitaController@index API: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al cargar la lista de citas.'], 500);
        }
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tecnico_id' => 'required|exists:users,id', // Idealmente validar que sea técnico
            'ayudante_id' => 'nullable|exists:users,id|different:tecnico_id',
            'cliente_id' => 'required|exists:clientes,id',
            'tipo_servicio' => 'required|string|max:255',
            'fecha_hora' => [
                'required',
                'date',
                'after_or_equal:today',
                function ($attribute, $value, $fail) {
                    $fecha = Carbon::parse($value);
                    if ($fecha->isSunday()) {
                        $fail('No se pueden programar citas los domingos.');
                    }
                    if ($fecha->hour < 8 || $fecha->hour >= 20) {
                        $fail('Las citas deben programarse entre las 8:00 AM y 8:00 PM.');
                    }
                }
            ],
            'fecha_hora_fin' => 'required|date|after:fecha_hora',
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
            'folio' => 'nullable|string|max:120',
            'latitud' => 'nullable|numeric',
            'longitud' => 'nullable|numeric',
        ]);

        try {
            DB::beginTransaction();

            if (array_key_exists('folio', $validated)) {
                $validated['folio'] = trim((string) $validated['folio']);
                if ($validated['folio'] === '') {
                    unset($validated['folio']);
                }
            }

            $duracion = 60;
            if ($request->filled('fecha_hora_fin')) {
                $inicio = Carbon::parse($validated['fecha_hora']);
                $fin = Carbon::parse($validated['fecha_hora_fin']);
                $duracion = $inicio->diffInMinutes($fin);
            }

            $this->verificarDisponibilidadTecnico((int)$validated['tecnico_id'], $validated['fecha_hora'], null, $duracion);
            if (!empty($validated['ayudante_id'])) {
                $this->verificarDisponibilidadTecnico((int)$validated['ayudante_id'], $validated['fecha_hora'], null, $duracion);
            }
            $this->verificarLimiteCitasPorDia((int)$validated['tecnico_id'], $validated['fecha_hora']);
            $this->verificarCitasClienteActivas((int)$validated['cliente_id'], $validated['fecha_hora']);

            $filePaths = $this->saveFiles($request, ['foto_equipo', 'foto_hoja_servicio', 'foto_identificacion']);
            
            $data = array_merge($validated, $filePaths);
            if (!isset($data['estado'])) {
                $data['estado'] = \App\Models\Cita::ESTADO_PROGRAMADO;
            }

            $cita = Cita::create($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cita creada exitosamente.',
                'data' => $cita->load('cliente', 'tecnico')
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            if (isset($filePaths)) {
                $this->deleteFiles($filePaths);
            }
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            if (isset($filePaths)) {
                $this->deleteFiles($filePaths);
            }
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
                'ayudante_id' => 'nullable|exists:users,id|different:tecnico_id',
                'cliente_id' => 'sometimes|required|exists:clientes,id',
                'tipo_servicio' => 'sometimes|required|string|max:255',
                'fecha_hora' => [
                    'sometimes',
                    'required',
                    'date',
                    function ($attribute, $value, $fail) use ($request, $cita) {
                        $fecha = Carbon::parse($value);
                        $nuevoEstado = $request->input('estado');
                        if ($fecha->isPast() && $cita->estado === Cita::ESTADO_PENDIENTE && !in_array($nuevoEstado, ['cancelado', 'completado'])) {
                            $fail('No se puede programar una cita pendiente en el pasado.');
                        }
                        if ($fecha->isSunday()) {
                            $fail('No se pueden programar citas los domingos.');
                        }
                        if ($fecha->hour < 8 || $fecha->hour >= 20) {
                            $fail('Las citas deben programarse entre las 8:00 AM y 8:00 PM.');
                        }
                    }
                ],
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
                'nuevas_fotos' => 'nullable|array',
                'nuevas_fotos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'trabajo_realizado' => 'nullable|string|max:2000',
                'folio' => 'nullable|string|max:120',
                'latitud' => 'nullable|numeric',
                'longitud' => 'nullable|numeric',
            ]);

            if (array_key_exists('folio', $validated)) {
                $validated['folio'] = trim((string) $validated['folio']);
                if ($validated['folio'] === '') {
                    unset($validated['folio']);
                }
            }

            DB::beginTransaction();

            $nuevoTecnicoId = array_key_exists('tecnico_id', $validated) ? $validated['tecnico_id'] : $cita->tecnico_id;
            $nuevoAyudanteId = array_key_exists('ayudante_id', $validated) ? $validated['ayudante_id'] : $cita->ayudante_id;
            $horarioCambio = isset($validated['fecha_hora']) && $validated['fecha_hora'] != $cita->fecha_hora;

            if ($nuevoTecnicoId && ($horarioCambio || (array_key_exists('tecnico_id', $validated) && $validated['tecnico_id'] != $cita->tecnico_id))) {
                $duracion = 60;
                if ($request->filled('fecha_hora_fin')) {
                    $inicio = Carbon::parse($validated['fecha_hora'] ?? $cita->fecha_hora);
                    $fin = Carbon::parse($validated['fecha_hora_fin']);
                    $duracion = $inicio->diffInMinutes($fin);
                } elseif ($cita->fecha_hora_fin && !isset($validated['fecha_hora'])) {
                    $duracion = Carbon::parse($cita->fecha_hora)->diffInMinutes(Carbon::parse($cita->fecha_hora_fin));
                }

                $this->verificarDisponibilidadTecnico(
                    (int)$nuevoTecnicoId,
                    $validated['fecha_hora'] ?? $cita->fecha_hora->toDateTimeString(),
                    $cita->id,
                    $duracion
                );

                if (isset($validated['fecha_hora'])) {
                    $this->verificarLimiteCitasPorDia(
                        (int)$nuevoTecnicoId,
                        $validated['fecha_hora']
                    );
                }
            }

            if ($nuevoAyudanteId && ($horarioCambio || (array_key_exists('ayudante_id', $validated) && $validated['ayudante_id'] != $cita->ayudante_id))) {
                $duracion = 60;
                if ($request->filled('fecha_hora_fin')) {
                    $inicio = Carbon::parse($validated['fecha_hora'] ?? $cita->fecha_hora);
                    $fin = Carbon::parse($validated['fecha_hora_fin']);
                    $duracion = $inicio->diffInMinutes($fin);
                } elseif ($cita->fecha_hora_fin && !isset($validated['fecha_hora'])) {
                    $duracion = Carbon::parse($cita->fecha_hora)->diffInMinutes(Carbon::parse($cita->fecha_hora_fin));
                }

                $this->verificarDisponibilidadTecnico(
                    (int)$nuevoAyudanteId,
                    $validated['fecha_hora'] ?? $cita->fecha_hora->toDateTimeString(),
                    $cita->id,
                    $duracion
                );
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

            // Lógica para registrar tiempos y ajustar fecha si es adelanto/atraso
            if (isset($validated['estado']) && $validated['estado'] !== $cita->estado) {
                // ✅ REGLA DE NEGOCIO: Si se inicia o completa un servicio que no era para hoy,
                // actualizamos la fecha programada a HOY para que los reportes y el corte sean exactos.
                if (in_array($validated['estado'], [Cita::ESTADO_EN_PROCESO, Cita::ESTADO_COMPLETADO])) {
                    if (!$cita->fecha_hora->isToday()) {
                        $validated['fecha_hora'] = now();
                        // Ajustar fin proporcionalmente para mantener la duración estimada
                        if ($cita->fecha_hora_fin) {
                            $duracion = $cita->fecha_hora->diffInMinutes($cita->fecha_hora_fin);
                            $validated['fecha_hora_fin'] = now()->addMinutes($duracion);
                        } else {
                            $validated['fecha_hora_fin'] = now()->addMinutes(60);
                        }
                    }
                }

                if ($validated['estado'] === Cita::ESTADO_EN_PROCESO) {
                    // Solo setear inicio si no existe, o si queremos reescribirlo (depende de regla de negocio)
                    // Aquí asumimos: primer 'En Proceso' marca el inicio.
                    if (!$cita->inicio_servicio) {
                        $validated['inicio_servicio'] = now();
                    }
                } elseif ($validated['estado'] === Cita::ESTADO_COMPLETADO) {
                    if ($msg = $cita->bloqueoMensajePorTiempoMinimoCompletar($request->user())) {
                        DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' => $msg,
                        ], 422);
                    }
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

            // Procesar fotos múltiples si se envían
            if ($request->hasFile('nuevas_fotos')) {
                $nuevas = [];
                $empresaId = EmpresaResolver::resolveId();
                $folder = $empresaId ? "empresas/{$empresaId}/citas/finales" : 'citas/finales';

                foreach ($request->file('nuevas_fotos') as $fotoFile) {
                    try {
                        $path = $this->saveImageAsWebP($fotoFile, $folder, 'public', 72, 1600);
                        if (is_string($path) && $path !== '') {
                            $nuevas[] = $path;
                        }
                    } catch (Exception $e) {
                         Log::error("Error al guardar una de las nuevas fotos: " . $e->getMessage());
                    }
                }
                
                $actuales = is_array($cita->fotos_finales) ? $cita->fotos_finales : [];
                $validated['fotos_finales'] = array_merge($actuales, $nuevas);
            }

            // Actualizar la cita con los datos validados y las rutas de los archivos
            $cita->update(array_merge($validated, $filePaths));

            // PROCESAR ITEMS SI SE ENVIAN
            if ($request->has('items')) {
                $this->syncCitaItems($cita, $request->input('items'));
            }

            // GENERAR VENTA SI SE COMPLETA Y HAY ITEMS
            if ($cita->estado === Cita::ESTADO_COMPLETADO && $cita->items()->count() > 0) {
                app(VentaFromCitaService::class)->createFromCita($cita);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cita actualizada exitosamente.',
                'data' => $cita->load('cliente', 'tecnico')
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            if (isset($filePaths)) {
                $this->deleteFiles($filePaths);
            }
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            if (isset($filePaths)) {
                $this->deleteFiles($filePaths);
            }
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
            $user = auth()->user();

            // RESTRICCIÓN DE NEGOCIO: Solo Super Admin puede borrar citas completadas o canceladas
            if (in_array($cita->estado, [Cita::ESTADO_COMPLETADO, Cita::ESTADO_CANCELADO])) {
                if (!$user || !$user->hasRole('super-admin')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Solo un Super Administrador puede eliminar citas en estado ' . $cita->estado . '.'
                    ], 403);
                }
            }

            // No permitir eliminar citas en proceso
            if ($cita->estado === Cita::ESTADO_EN_PROCESO) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar una cita en proceso.'
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
            if (!$user->hasAnyRole(['admin', 'super-admin', 'compras'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los administradores pueden reasignar citas.'
                ], 403);
            }

            $validated = $request->validate([
                'tecnico_id' => 'required|exists:users,id'
            ]);

            $cita = Cita::findOrFail($id);

            // Seguridad: No permitir reasignar si ya está completada
            if ($cita->estado === 'completado') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede reasignar una cita que ya ha sido completada por seguridad.'
                ], 422);
            }

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

    /**
     * Iniciar servicio (app móvil): equivalente a Mi Agenda / CitaController::iniciar web.
     */
    public function iniciar(Request $request, Cita $cita)
    {
        try {
            DB::beginTransaction();

            $cita = Cita::where('id', $cita->id)->lockForUpdate()->firstOrFail();

            $user = $request->user();
            if ($cita->tecnico_id !== $user->id && !$user->hasAnyRole(['admin', 'super-admin', 'compras'])) {
                DB::rollBack();

                $tecnicoNombre = $cita->tecnico ? $cita->tecnico->name : 'un técnico asignado';
                return response()->json([
                    'success' => false,
                    'message' => "No puedes modificar este servicio. Debe ser gestionado por el técnico asignado: {$tecnicoNombre}.",
                ], 403);
            }

            if (!in_array($cita->estado, [
                Cita::ESTADO_PENDIENTE,
                Cita::ESTADO_PROGRAMADO,
                Cita::ESTADO_REPROGRAMADO,
            ], true)) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'La cita no puede ser iniciada desde su estado actual (' . $cita->estado . ').',
                ], 422);
            }

            if ($cita->tipo_servicio === 'soporte_sitio') {
                $poliza = PolizaServicio::where('cliente_id', $cita->cliente_id)->activa()->first();
                if ($poliza && $poliza->excede_limite_visitas) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'El cliente ha excedido el límite de visitas de su póliza. Requiere autorización o cargo extra para proceder.',
                    ], 422);
                }
            }

            if (!$cita->cambiarEstado(Cita::ESTADO_EN_PROCESO, 'Servicio iniciado desde la app móvil.')) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'No se puede iniciar el servicio. Verifica que no tengas otra cita "En Proceso" o que el estado actual permita el inicio.',
                ], 422);
            }

            if (!$cita->inicio_servicio) {
                $cita->update(['inicio_servicio' => now()]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Servicio iniciado.',
                'data' => $cita->fresh()->load('cliente', 'tecnico'),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Cita no encontrada'], 404);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al iniciar cita API: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar el servicio.',
            ], 500);
        }
    }

    /**
     * Cancelar cita (app móvil), alineado al panel Mi Agenda.
     */
    public function cancelar(Request $request, Cita $cita)
    {
        try {
            $user = $request->user();
            if ($cita->tecnico_id !== $user->id && !$user->hasAnyRole(['admin', 'super-admin', 'compras'])) {
                $tecnicoNombre = $cita->tecnico ? $cita->tecnico->name : 'un técnico asignado';
                return response()->json([
                    'success' => false,
                    'message' => "No puedes cancelar este servicio. Debe ser cancelado por el técnico asignado: {$tecnicoNombre}.",
                ], 403);
            }

            if ($cita->estado === Cita::ESTADO_COMPLETADO) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cancelar una cita completada.',
                ], 422);
            }

            $validated = $request->validate([
                'motivo' => 'nullable|string|max:1000',
            ]);

            $motivo = $validated['motivo'] ?? 'Cancelado desde la app móvil.';
            
            if (!$cita->cambiarEstado(Cita::ESTADO_CANCELADO, $motivo)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo cancelar la cita. Verifica que el estado actual permita la cancelación.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cita cancelada.',
                'data' => $cita->fresh()->load('cliente', 'tecnico'),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error al cancelar cita API: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar la cita.',
            ], 500);
        }
    }

    /**
     * Completar servicio desde la app móvil (equivalente a Mi Agenda en panel web).
     */
    public function completar(Request $request, Cita $cita)
    {
        try {
            DB::beginTransaction();

            $cita = Cita::where('id', $cita->id)->lockForUpdate()->firstOrFail();

            $user = $request->user();
            if ($cita->tecnico_id !== $user->id && !$user->hasAnyRole(['admin', 'super-admin', 'compras'])) {
                DB::rollBack();

                $tecnicoNombre = $cita->tecnico ? $cita->tecnico->name : 'un técnico asignado';
                return response()->json([
                    'success' => false,
                    'message' => "No puedes modificar este servicio. Debe ser gestionado por el técnico asignado: {$tecnicoNombre}.",
                ], 403);
            }

            if (!in_array($cita->estado, [Cita::ESTADO_EN_PROCESO, Cita::ESTADO_PENDIENTE, Cita::ESTADO_PROGRAMADO], true)) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden completar citas que están en proceso, pendientes o programadas.'
                ], 422);
            }

            if ($msg = $cita->bloqueoMensajePorTiempoMinimoCompletar($user)) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 422);
            }

            $request->validate([
                'trabajo_realizado' => 'nullable|string',
                'fotos_finales' => 'nullable|array',
                'fotos_finales.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:8192',
                'cerrar_ticket' => 'nullable|boolean',
                'firma_cliente' => 'nullable|string',
            ]);

            $firmaData = $this->saveClienteSignatureForCompletar($request->input('firma_cliente'), $cita->id);

            $filePaths = []; // Empezamos con un array vacío para las nuevas fotos de esta transacción
            if ($request->hasFile('fotos_finales')) {
                // Usar el ID de empresa de la cita directamente para asegurar que el path sea correcto
                $empresaId = $cita->empresa_id;
                $folder = "empresas/{$empresaId}/citas/finales";

                foreach ($request->file('fotos_finales') as $file) {
                    if (!$file) continue;
                    
                    try {
                        $savedPath = $this->saveImageAsWebP($file, $folder);
                        if ($savedPath) {
                            $filePaths[] = $savedPath;
                        }
                    } catch (Exception $e) {
                        Log::error('Error al guardar foto final de cita API: ' . $e->getMessage());
                    }
                }
            }

            // Combinar con fotos existentes si las hay y no se están reemplazando (ajustar según lógica deseada)
            $existingPhotos = is_array($cita->fotos_finales) ? $cita->fotos_finales : [];
            $allPhotos = array_merge($existingPhotos, $filePaths);

            $updateData = [
                'trabajo_realizado' => $request->input('trabajo_realizado'),
                'fotos_finales' => $allPhotos,
            ];

            if ($firmaData) {
                $updateData['firma_cliente'] = $firmaData['path'];
                $updateData['firma_cliente_hash'] = $firmaData['hash'];
                $updateData['fecha_firma'] = now();
            }

            $cita->update($updateData);

            // Sincronizar ítems si se envían desde el móvil
            if ($request->has('items')) {
                $this->syncCitaItems($cita, $request->input('items'));
            }

            if (!$cita->cambiarEstado(Cita::ESTADO_COMPLETADO, 'Servicio completado desde la app móvil.')) {
                DB::rollBack();
                $this->deleteFiles($filePaths); // Limpiar archivos si falla el cambio de estado

                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo finalizar el cambio de estado de la cita.',
                ], 422);
            }

            if ($request->boolean('cerrar_ticket') && $cita->ticket_id) {
                $ticket = Ticket::find($cita->ticket_id);
                if ($ticket && !in_array($ticket->estado, ['resuelto', 'cerrado'], true)) {
                    $horas = $cita->tiempo_servicio ? round($cita->tiempo_servicio / 60, 2) : null;
                    $ticket->marcarComoResuelto($horas, null, null, true);

                    $ticket->comentarios()->create([
                        'user_id' => $user->id,
                        'contenido' => "✅ Ticket resuelto automáticamente al completar la cita #{$cita->id} (app móvil).",
                        'tipo' => 'estado',
                        'es_interno' => false,
                    ]);
                }
            }

            $cita->refresh();
            if ($cita->estado === Cita::ESTADO_COMPLETADO && $cita->items()->count() > 0) {
                $venta = app(VentaFromCitaService::class)->createFromCita($cita, [
                    "pago_recibido" => $request->input("pago_recibido", "no"), 
                    "metodo_pago" => $request->input("metodo_pago"), 
                    "cuenta_id" => $request->input("cuenta_id")
                ]);

                if (!$venta) {
                    DB::rollBack();
                    $this->deleteFiles($filePaths); // Limpiar archivos si falla la venta

                    return response()->json([
                        'success' => false,
                        'message' => 'No se pudo generar la venta/consumo de inventario. Verifica que tengas stock suficiente o números de serie válidos en tu almacén asignado.',
                    ], 422);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Servicio completado exitosamente.',
                'data' => $cita->load('cliente', 'tecnico', 'items.citable', 'venta'),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            if (isset($filePaths)) $this->deleteFiles($filePaths);

            return response()->json(['success' => false, 'message' => 'Cita no encontrada'], 404);
        } catch (ValidationException $e) {
            DB::rollBack();
            if (isset($filePaths)) $this->deleteFiles($filePaths);

            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            if (isset($filePaths)) $this->deleteFiles($filePaths);
            Log::error('Error al completar cita API: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al completar el servicio.',
            ], 500);
        }
    }

    /**
     * Sincronizar ítems de la cita (Productos/Servicios).
     */
    private function syncCitaItems(Cita $cita, $itemsData)
    {
        if (is_string($itemsData)) {
            $itemsData = json_decode($itemsData, true);
        }

        if (!is_array($itemsData)) {
            return;
        }

        // Limpiar items anteriores
        $cita->items()->delete();

        $subtotalCita = 0;
        $empresaConfig = \App\Models\EmpresaConfiguracion::getConfig($cita->empresa_id);
        $ivaPorcentaje = $empresaConfig ? $empresaConfig->iva_porcentaje : 16.00;

        foreach ($itemsData as $item) {
            $cantidad = (int) ($item['cantidad'] ?? 1);
            $precioOriginal = $item['precio'] ?? 0;
            $descuentoInfo = $item['descuento'] ?? 0;
            $notasItem = $item['notas'] ?? null;

            $esServicio = ($item['tipo'] === 'servicio');
            $precioAplicado = $precioOriginal;

            $subtotalItem = $cantidad * $precioAplicado;
            $descuentoMonto = $subtotalItem * ($descuentoInfo / 100);
            $subtotalItemConDescuento = $subtotalItem - $descuentoMonto;

            $citaItem = new \App\Models\CitaItem([
                'empresa_id' => $cita->empresa_id,
                'cita_id' => $cita->id,
                'citable_type' => $esServicio ? \App\Models\Servicio::class : \App\Models\Producto::class,
                'citable_id' => $item['id'],
                'cantidad' => $cantidad,
                'precio' => $precioAplicado,
                'descuento' => $descuentoInfo,
                'descuento_monto' => $descuentoMonto,
                'subtotal' => $subtotalItemConDescuento,
                'notas' => $notasItem,
                'series' => $item['series'] ?? [],
            ]);
            $citaItem->save();

            $subtotalCita += $subtotalItemConDescuento;
        }

        $ivaCita = $subtotalCita * ($ivaPorcentaje / 100);
        $totalCita = $subtotalCita + $ivaCita;

        Log::info("Sincronizando ítems para Cita #{$cita->id}. Subtotal: {$subtotalCita}, IVA: {$ivaCita}, Total: {$totalCita}");

        $cita->update([
            'subtotal' => $subtotalCita,
            'iva' => $ivaCita,
            'total' => $totalCita,
        ]);
        
        $cita->refresh();
    }

    private function saveClienteSignatureForCompletar(?string $base64Data, int $citaId)
    {
        if (empty($base64Data) || !str_contains($base64Data, 'base64')) {
            return null;
        }

        try {
            $empresaId = auth()->user()->empresa_id;
            $data = explode(',', $base64Data);
            if (count($data) < 2) {
                return null;
            }

            $decoded = base64_decode($data[1]);
            $filename = "cliente_final_cita_{$citaId}_" . time() . '.png';
            $path = "empresa_{$empresaId}/citas/firmas/{$filename}";

            Storage::disk('public')->put($path, $decoded);

            return [
                'path' => $path,
                'hash' => hash('sha256', $decoded),
                'timestamp' => now()->toIso8601String(),
            ];
        } catch (Exception $e) {
            Log::error("Error al guardar firma de cita #{$citaId} (API): " . $e->getMessage());

            return null;
        }
    }

    // --- Helpers Privados ---

    private function verificarDisponibilidadTecnico(int $tecnicoId, string $fechaHora, ?int $excludeId = null, int $duracionMin = 60): void
    {
        $citaConflicto = Cita::hayConflictoHorario($tecnicoId, $fechaHora, $excludeId, $duracionMin);

        if ($citaConflicto) {
            $tecnicoNombre = $citaConflicto->tecnico?->nombre ?? $citaConflicto->tecnico?->name ?? 'el técnico';
            $inicio = $citaConflicto->fecha_hora->format('h:i A');
            $finTime = $citaConflicto->fecha_hora_fin ?? $citaConflicto->fecha_hora->copy()->addMinutes(60);
            $fin = $finTime->format('h:i A');

            throw ValidationException::withMessages([
                'fecha_hora' => "⚠️ Horario ocupado: {$tecnicoNombre} tiene una cita de {$inicio} a {$fin}. Selecciona un horario que no se traslape."
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

        if ($citasActivas >= 2) {
            throw ValidationException::withMessages([
                'cliente_id' => 'El cliente ya tiene múltiples citas activas. Complete las citas existentes antes de programar nuevas.'
            ]);
        }

        $citasMismoDia = Cita::where('cliente_id', $clienteId)
            ->whereDate('fecha_hora', $fecha->toDateString())
            ->where('estado', '!=', Cita::ESTADO_CANCELADO)
            ->where('fecha_hora', '!=', $fechaHora)
            ->count();

        if ($citasMismoDia > 0) {
            throw ValidationException::withMessages([
                'fecha_hora' => 'El cliente ya tiene una cita programada para este día.'
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
    public function regresar(Request $request, Cita $cita)
    {
        try {
            DB::beginTransaction();

            $cita = Cita::where('id', $cita->id)->lockForUpdate()->firstOrFail();

            // Validar propiedad: Solo el técnico asignado o un admin/super-admin pueden regresar
            $user = $request->user();
            if ($cita->tecnico_id !== $user->id && !$user->hasAnyRole(['admin', 'super-admin'])) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para modificar esta cita.',
                ], 403);
            }


            if (!in_array($cita->estado, [Cita::ESTADO_EN_PROCESO, Cita::ESTADO_PROGRAMADO, Cita::ESTADO_PENDIENTE])) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden regresar citas que estén actualmente en proceso, programadas o pendientes.',
                ], 422);
            }

            if (!$cita->cambiarEstado(Cita::ESTADO_PROGRAMADO, 'Servicio regresado a programado por el técnico.')) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo cambiar el estado de la cita.',
                ], 422);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Servicio regresado a estado programado con éxito.',
                'data' => $cita
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error en CitaController@regresar: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar la solicitud.',
            ], 500);
        }
    }

    public function setPendiente(Request $request, Cita $cita)
    {
        try {
            $cita->update([
                'estado' => Cita::ESTADO_PENDIENTE,
                'notas' => ($cita->notas ? $cita->notas . "\n" : "") . "-- Cita movida a pendiente por el usuario: " . $request->user()->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cita movida a pendiente correctamente.',
                'cita' => $cita
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mover cita a pendiente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activa una cita pendiente para el día de hoy.
     */
    public function programarHoy(Request $request, $id)
    {
        try {
            $cita = Cita::findOrFail($id);
            $user = $request->user();

            $validated = $request->validate([
                'tecnico_id' => 'required|exists:users,id'
            ]);

            $tecnicoId = $validated['tecnico_id'];

            // Actualizar cita
            $cita->update([
                'estado' => Cita::ESTADO_PROGRAMADO,
                'tecnico_id' => $tecnicoId,
                'fecha_hora' => now(),
                'fecha_hora_fin' => now()->addHour(),
                'notas' => ($cita->notas ? $cita->notas . "\n" : "") . "-- Cita activada para hoy por el usuario: " . $user->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cita activada para hoy exitosamente.',
                'data' => $cita->load('tecnico', 'cliente')
            ]);
        } catch (Exception $e) {
            Log::error('Error en programarHoy: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al activar la cita: ' . $e->getMessage()
            ], 500);
        }
    }

    private function deleteFiles(array $filePaths): void
    {
        foreach ($filePaths as $path) {
            if ($path && is_string($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }
}

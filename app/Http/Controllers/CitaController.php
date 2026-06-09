<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\User;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use App\Helpers\ActivityLogger;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use ZipArchive;
use App\Traits\ImageOptimizerTrait;

class CitaController extends Controller
{
    use ImageOptimizerTrait;

    /**
     * Query base del listado de citas (mismos filtros que el índice).
     */
    protected function buildCitasIndexQuery(Request $request): Builder
    {
        $query = Cita::with('tecnico', 'cliente');

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
                $states = array_values(array_intersect(
                    $allowedEstados,
                    array_map('trim', explode(',', $raw))
                ));
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
        }

        if ($request->filled('activo') || $request->filled('active_only')) {
            $query->where('activo', true);
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

        return $query;
    }

    /**
     * Descarga un ZIP con las evidencias fotográficas (fotos_finales) de una sola cita.
     */
    public function downloadEvidenciasCita(Cita $cita)
    {
        $disk = Storage::disk('public');
        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipPath = $tempDir.'/cita-evidencias-'.uniqid('', true).'.zip';
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return redirect()->route('citas.index')->with('error', 'No se pudo crear el archivo ZIP.');
        }

        $prefix = 'cita-'.$cita->id;
        $fotos = is_array($cita->fotos_finales) ? $cita->fotos_finales : [];
        $added = 0;
        foreach ($fotos as $i => $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }
            $path = ltrim($path, '/');
            if (! $disk->exists($path)) {
                continue;
            }
            $ext = pathinfo($path, PATHINFO_EXTENSION) ?: 'webp';
            $nameInZip = $prefix.'_evidencia_'.($i + 1).'.'.$ext;
            $zip->addFromString($nameInZip, $disk->get($path));
            $added++;
        }

        $zip->close();

        if ($added === 0) {
            if (is_file($zipPath)) {
                @unlink($zipPath);
            }

            return redirect()->back()->with('error', 'Esta cita no tiene evidencias fotográficas para descargar.');
        }

        $fileName = 'cita-'.$cita->id.'-evidencias-'.now()->format('Y-m-d-His').'.zip';

        return response()->download($zipPath, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Mostrar todas las citas con paginación y filtros.
     */
    public function index(Request $request)
    {
        try {
            $query = $this->buildCitasIndexQuery($request);

            // Ordenamiento dinámico
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');

            // Por defecto: agenda por fecha de programación (activas próximas primero; completadas después; canceladas al final)
            if ($sortBy === 'created_at') {
                $query->orderDefaultAgenda();
            } else {
                $query->orderBy($sortBy, $sortDirection);
            }

            // Paginación - obtener per_page del request o usar default
            $perPage = $request->get('per_page', 10);
            $validPerPage = [1, 5, 10, 15, 25, 50]; // Solo estas opciones válidas
            if (!in_array((int) $perPage, $validPerPage)) {
                $perPage = 50;
            }

            // Paginar con el per_page dinámico
            $citas = $query->paginate((int) $perPage);

            // =====================================================
            // RESPUESTA API (Para Ionic/Mobile/AJAX)
            // =====================================================
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'data' => $citas->items(),
                    'total' => $citas->total(),
                    'current_page' => $citas->currentPage(),
                    'last_page' => $citas->lastPage(),
                    'per_page' => $citas->perPage(),
                ]);
            }

            // Estadísticas por estado de cita (alineadas con flujo real: programado vs cola de entrada)
            $citasCount = Cita::count();
            $citasProgramadas = Cita::where('estado', Cita::ESTADO_PROGRAMADO)->count();
            $citasPorAtender = Cita::whereIn('estado', [
                Cita::ESTADO_PENDIENTE,
                Cita::ESTADO_PENDIENTE_ASIGNACION,
            ])->count();
            $citasEnProceso = Cita::where('estado', Cita::ESTADO_EN_PROCESO)->count();
            $citasCompletadas = Cita::where('estado', Cita::ESTADO_COMPLETADO)->count();
            $citasCanceladas = Cita::where('estado', Cita::ESTADO_CANCELADO)->count();

            // Datos adicionales para filtros
            $tecnicos = User::tecnicos()->select('id', 'name as nombre')->get();
            $clientes = Cliente::select('id', 'nombre_razon_social')->get();
            $estados = [
                Cita::ESTADO_PENDIENTE => 'Pendiente',
                Cita::ESTADO_PENDIENTE_ASIGNACION => 'Pendiente asignación',
                Cita::ESTADO_PROGRAMADO => 'Programado',
                Cita::ESTADO_EN_PROCESO => 'En Proceso',
                Cita::ESTADO_COMPLETADO => 'Completado',
                Cita::ESTADO_CANCELADO => 'Cancelado',
                Cita::ESTADO_REPROGRAMADO => 'Reprogramado',
            ];

            return Inertia::render('Citas/Index', [
                'citas' => $citas,
                'stats' => [
                    'total' => $citasCount,
                    'programadas' => $citasProgramadas,
                    'por_atender' => $citasPorAtender,
                    'en_proceso' => $citasEnProceso,
                    'completadas' => $citasCompletadas,
                    'canceladas' => $citasCanceladas,
                ],
                'tecnicos' => $tecnicos,
                'clientes' => $clientes,
                'estados' => $estados,
                'filters' => $request->only(['search', 'estado', 'tecnico_id', 'cliente_id', 'fecha_desde', 'fecha_hasta']),
                'sorting' => [
                    'sort_by' => $sortBy,
                    'sort_direction' => $sortDirection
                ],
                'pagination' => [
                    'per_page' => (int) $perPage,
                    'current_page' => $citas->currentPage(),
                    'last_page' => $citas->lastPage(),
                    'total' => $citas->total(),
                    'from' => $citas->firstItem(),
                    'to' => $citas->lastItem(),
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Error en CitaController@index: ' . $e->getMessage());

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Error al cargar citas'], 500);
            }

            return redirect()->back()->with('error', 'Error al cargar la lista de citas.');
        }
    }

    /**
     * Mostrar formulario para crear una nueva cita.
     */
    public function create(Request $request)
    {
        $tecnicos = User::tecnicos()->select('id', 'name as nombre')->get();
        try {
            // Optimización: No cargar TODOS los clientes. Cargar solo los últimos 50
            // o permitir búsqueda dinámica desde el frontend.
            $clientes = Cliente::active()->latest()->limit(50)->get(['id', 'nombre_razon_social']);
        } catch (Exception $e) {
            Log::error('Error loading clientes in CitaController@create: ' . $e->getMessage());
            $clientes = [];
        }
        // Productos y servicios eliminados para no vender desde citas

        $prefill = array_filter([
            'cliente_id' => $request->input('cliente_id'),
            'numero_serie' => $request->input('numero_serie'),
            'descripcion' => $request->input('descripcion'),
            'direccion_servicio' => $request->input('direccion') ?? $request->input('direccion_servicio'),
            'tipo_servicio' => $request->input('tipo_servicio'),
            'ticket_id' => $request->input('ticket_id'),
        ], fn($v) => $v !== null && $v !== '');

        // Si viene de un ticket, cargar datos del ticket
        if ($request->has('ticket_id')) {
            $ticket = \App\Models\Ticket::find($request->ticket_id);
            if ($ticket) {
                $prefill['ticket_id'] = $ticket->id;
                $prefill['cliente_id'] = $ticket->cliente_id ?? $prefill['cliente_id'] ?? null;
                $prefill['descripcion'] = $ticket->titulo . "\n" . $ticket->descripcion;
                $prefill['tipo_servicio'] = $ticket->tipo_servicio ?? $prefill['tipo_servicio'] ?? null;
            }
        }

        if (($prefill['tipo_servicio'] ?? null) === 'garantia') {
            $prefill['garantia'] = 'si';
        }

        // Cargar catálogos de marcas y categorías (tipos de equipo)
        $marcas = \App\Models\Marca::orderBy('nombre')->get(['id', 'nombre']);
        $categorias = \App\Models\Categoria::orderBy('nombre')->get(['id', 'nombre']);

        return Inertia::render('Citas/Create', [
            'tecnicos' => $tecnicos,
            'clientes' => $clientes,
            'prefill' => $prefill,
            'marcas' => $marcas,
            'categorias' => $categorias,
        ]);
    }

    /**
     * Almacenar una nueva cita en la base de datos.
     */
    public function store(Request $request)
    {
        // Validar los datos recibidos con mejoras
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
                    if ($fecha->hour < 8 || $fecha->hour >= 20) {
                        $fail('Las citas deben programarse entre las 8:00 AM y 8:00 PM.');
                    }
                }
            ],
            'fecha_hora_fin' => 'required|date|after:fecha_hora',
            'prioridad' => 'nullable|string|in:baja,media,alta,urgente',
            'descripcion' => 'nullable|string|max:1000',
            'estado' => 'required|string|in:pendiente,programado,en_proceso,completado,cancelado,reprogramado',
            'evidencias' => 'nullable|string|max:2000',
            'foto_equipo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'foto_hoja_servicio' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'foto_identificacion' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'notas' => 'nullable|string|max:1000',
            'producto_serie_id' => 'nullable|integer|exists:producto_series,id',
            'tipo_equipo' => 'required|string|max:255',
            'marca_equipo' => 'nullable|string|max:255',
            'modelo_equipo' => 'nullable|string|max:255',
            'ticket_id' => 'nullable|integer|exists:tickets,id',
            'poliza_id' => 'nullable|integer|exists:polizas_servicio,id',
            'problema_reportado' => 'nullable|string|max:1000',
            'direccion_servicio' => 'nullable|string|max:1000',
            'folio' => 'nullable|string|max:120',
        ], [
            'tecnico_id.required' => 'Debe seleccionar un técnico.',
            'cliente_id.required' => 'Debe seleccionar un cliente.',
            'fecha_hora.after' => 'La fecha debe ser posterior a la actual.',
            '*.max:2048' => 'La imagen no debe superar los 2MB.',
            'tipo_equipo.required' => 'El tipo de equipo es obligatorio.',
        ]);

        try {
            DB::beginTransaction();

            // Bloqueo pesimista para evitar race conditions al agendar para el mismo técnico
            User::where('id', $validated['tecnico_id'])->lockForUpdate()->firstOrFail();
            
            // Bloqueo pesimista para el cliente para asegurar consistencia en sus validaciones
            Cliente::where('id', $validated['cliente_id'])->lockForUpdate()->firstOrFail();

            // Verificar disponibilidad del técnico
            $this->verificarDisponibilidadTecnico(
                $validated['tecnico_id'],
                $validated['fecha_hora'],
                $validated['fecha_hora_fin']
            );

            // Verificar límite de citas por día para el técnico (Límite dinámico si es posible en el futuro)
            $this->verificarLimiteCitasPorDia(
                $validated['tecnico_id'],
                $validated['fecha_hora']
            );

            // Verificar que el cliente no tenga múltiples citas activas
            $this->verificarCitasClienteActivas(
                $validated['cliente_id'],
                $validated['fecha_hora']
            );

            // Manejo de póliza
            if (!empty($validated['poliza_id'])) {
                $poliza = \App\Models\PolizaServicio::find($validated['poliza_id']);
            } elseif ($validated['tipo_servicio'] === 'soporte_sitio' || $validated['tipo_servicio'] === 'diagnostico') {
                $poliza = \App\Models\PolizaServicio::where('cliente_id', $validated['cliente_id'])
                    ->activa()
                    ->first();
                if ($poliza) {
                    $validated['poliza_id'] = $poliza->id;
                }
            }

            // Guardar archivos y obtener sus rutas
            $filePaths = $this->saveFiles($request, ['foto_equipo', 'foto_hoja_servicio', 'foto_identificacion']);

            // Mapear direccion_servicio a direccion_calle y limpiar Google Maps si existe
            if (!empty($validated['direccion_servicio'])) {
                [$cleanAddress, $lat, $lng] = $this->cleanAddressAndExtractGmaps($validated['direccion_servicio']);
                $validated['direccion_calle'] = $cleanAddress;
                // Si encontramos coordenadas, las guardamos
                if ($lat && $lng) {
                    $validated['latitud'] = $lat;
                    $validated['longitud'] = $lng;
                }
            }

            if (array_key_exists('folio', $validated)) {
                $validated['folio'] = trim((string) $validated['folio']);
                if ($validated['folio'] === '') {
                    unset($validated['folio']);
                }
            }

            $cita = Cita::create(array_merge($validated, $filePaths, [
                'subtotal' => 0,
                'descuento_general' => 0,
                'descuento_items' => 0,
                'iva' => 0,
                'total' => 0,
            ]));

            // Si viene de una garantía, asociar la serie con la cita
            if ($request->filled('producto_serie_id')) {
                $productoSerieId = $request->input('producto_serie_id');

                $serieExistente = DB::table('producto_series')
                    ->where('id', $productoSerieId)
                    ->whereNotNull('cita_id')
                    ->first();

                if ($serieExistente) {
                    DB::rollBack();
                    return redirect()
                        ->back()
                        ->withInput()
                        ->with('error', 'Esta serie de garantía ya tiene una cita asociada (Cita #' . $serieExistente->cita_id . ').');
                }

                DB::table('producto_series')
                    ->where('id', $productoSerieId)
                    ->update(['cita_id' => $cita->id]);
            }

            ActivityLogger::log("Creó una nueva cita (#{$cita->id}) para el cliente " . $cita->cliente->nombre_razon_social);
            DB::commit();

            return redirect()->route('citas.index')->with('success', 'Cita creada exitosamente.');
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear cita: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear la cita.');
        }
    }

    /**
     * Mostrar formulario para editar una cita existente.
     */
    public function edit(Cita $cita)
    {
        $tecnicos = User::tecnicos()->select('id', 'name as nombre')->get();

        try {
            $clientes = Cliente::active()->get(['id', 'nombre_razon_social', 'telefono', 'email']);
        } catch (Exception $e) {
            Log::error('Error loading clientes in CitaController@edit: ' . $e->getMessage());
            $clientes = [];
        }

        return Inertia::render('Citas/Edit', [
            'cita' => $cita,
            'tecnicos' => $tecnicos,
            'clientes' => $clientes,
        ]);
    }

    /**
     * Actualizar una cita existente en la base de datos.
     */
    public function update(Request $request, Cita $cita)
    {
        // Validar los datos recibidos
        $validated = $request->validate([
            'tecnico_id' => 'sometimes|required|exists:users,id',
            'cliente_id' => 'sometimes|required|exists:clientes,id',
            'tipo_servicio' => 'sometimes|required|string|max:255',
            'fecha_hora' => [
                'sometimes',
                'required',
                'date',
                function ($attribute, $value, $fail) use ($request, $cita) {
                    $fecha = Carbon::parse($value);
                    $nuevoEstado = $request->input('estado');

                    // Solo bloquear fechas pasadas si NO se está cancelando la cita
                    if ($fecha->isPast() && $cita->estado === Cita::ESTADO_PENDIENTE && $nuevoEstado !== 'cancelado') {
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
            'fecha_hora_fin' => 'sometimes|required|date|after:fecha_hora',
            'prioridad' => 'nullable|string|in:baja,media,alta,urgente',
            'descripcion' => 'nullable|string|max:1000',
            'estado' => 'sometimes|required|string|in:pendiente,programado,en_proceso,completado,cancelado,reprogramado',
            'evidencias' => 'nullable|string|max:2000',
            'foto_equipo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'foto_hoja_servicio' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'foto_identificacion' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'tipo_equipo' => 'nullable|string|max:255',
            'marca_equipo' => 'nullable|string|max:255',
            'modelo_equipo' => 'nullable|string|max:255',
            'problema_reportado' => 'nullable|string|max:1000',
            'direccion_servicio' => 'nullable|string|max:1000',
            'folio' => 'nullable|string|max:120',
            'firma_cliente' => 'nullable|string',
            'nombre_firmante' => 'nullable|string|max:255',
            'firma_tecnico' => 'nullable|string',
            'cerrar_ticket' => 'nullable|boolean',
            'tiempo_servicio' => 'nullable|integer|min:0',
            'trabajo_realizado' => 'nullable|string',
            'latitud' => 'nullable|numeric',
            'longitud' => 'nullable|numeric',
            'fecha_gps' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            if (array_key_exists('folio', $validated)) {
                $validated['folio'] = trim((string) $validated['folio']);
                if ($validated['folio'] === '') {
                    unset($validated['folio']);
                }
            }

            // Verificar disponibilidad del técnico si cambió
            if (
                isset($validated['tecnico_id']) &&
                ($validated['tecnico_id'] != $cita->tecnico_id ||
                    isset($validated['fecha_hora']) && $validated['fecha_hora'] != $cita->fecha_hora ||
                    isset($validated['fecha_hora_fin']) && $validated['fecha_hora_fin'] != $cita->fecha_hora_fin)
            ) {
                $this->verificarDisponibilidadTecnico(
                    $validated['tecnico_id'],
                    $validated['fecha_hora'] ?? $cita->fecha_hora,
                    $validated['fecha_hora_fin'] ?? $cita->fecha_hora_fin,
                    $cita->id
                );
            }

            // Guardar archivos
            $filePaths = $this->saveFiles($request, ['foto_equipo', 'foto_hoja_servicio', 'foto_identificacion'], [
                'foto_equipo' => $cita->foto_equipo,
                'foto_hoja_servicio' => $cita->foto_hoja_servicio,
                'foto_identificacion' => $cita->foto_identificacion,
            ]);

            $dataToUpdate = array_merge($validated, $filePaths);

            // Mapear direccion_servicio a direccion_calle y limpiar Google Maps
            if (isset($validated['direccion_servicio'])) {
                [$cleanAddress, $lat, $lng] = $this->cleanAddressAndExtractGmaps($validated['direccion_servicio']);
                $dataToUpdate['direccion_calle'] = $cleanAddress;
                if ($lat && $lng) {
                    $dataToUpdate['latitud'] = $lat;
                    $dataToUpdate['longitud'] = $lng;
                }
            }

            // Manejo de firmas (Convertir Base64 a archivo físico con integridad)
            if ($request->filled('firma_cliente')) {
                $signatureData = $this->saveSignatureToFile($request->input('firma_cliente'), 'cliente', $cita->id);
                if (is_array($signatureData)) {
                    $dataToUpdate['firma_cliente'] = $signatureData['path'];
                    $dataToUpdate['fecha_firma'] = now();
                    // El hash se registrará automáticamente en el historial a través de cambiarEstado
                }
            }

            if ($request->filled('firma_tecnico')) {
                $signatureData = $this->saveSignatureToFile($request->input('firma_tecnico'), 'tecnico', $cita->id);
                if (is_array($signatureData)) {
                    $dataToUpdate['firma_tecnico'] = $signatureData['path'];
                }
            }

            // Manejo de 'nuevas_fotos' (WebP, tamaño acotado para carga rápida en listados y detalle)
            if ($request->hasFile('nuevas_fotos')) {
                $currentFotos = $cita->fotos_finales ?? [];
                $newFotos = [];
                foreach ($request->file('nuevas_fotos') as $foto) {
                    try {
                        $path = $this->saveImageAsWebP($foto, 'citas/evidencias_finales', 'public', 72, 1600);
                        if (is_string($path) && $path !== '') {
                            $newFotos[] = $path;
                        }
                    } catch (Exception $e) {
                        Log::error('Error al guardar evidencia (cita web): '.$e->getMessage());
                    }
                }
                $dataToUpdate['fotos_finales'] = array_merge($currentFotos, $newFotos);
            }

            // Asegurar consistencia de duración si cambió la fecha_hora pero no la fecha_hora_fin
            if (isset($validated['fecha_hora']) && !isset($validated['fecha_hora_fin'])) {
                $inicioOriginal = $cita->fecha_hora;
                $finOriginal = $cita->fecha_hora_fin ?? (clone $inicioOriginal)->addHour();
                $duracionMinutos = $inicioOriginal->diffInMinutes($finOriginal);
                
                $dataToUpdate['fecha_hora_fin'] = Carbon::parse($validated['fecha_hora'])->addMinutes($duracionMinutos);
            }

            $cita->update($dataToUpdate);

            // Si se marcó para cerrar el ticket
            if ($request->boolean('cerrar_ticket') && $cita->estado === Cita::ESTADO_COMPLETADO && $cita->ticket_id) {
                $ticket = \App\Models\Ticket::find($cita->ticket_id);
                if ($ticket && !in_array($ticket->estado, ['resuelto', 'cerrado'])) {
                    $horas = $request->filled('tiempo_servicio') ? round($request->input('tiempo_servicio') / 60, 2) : null;
                    $ticket->marcarComoResuelto($horas, null, null, true);
                    
                    $ticket->comentarios()->create([
                        'user_id' => auth()->id(),
                        'contenido' => "✅ Ticket resuelto automáticamente al completar la cita #{$cita->id}.",
                        'tipo' => 'estado',
                        'es_interno' => false
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('citas.index')->with('success', 'Cita actualizada exitosamente.');
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar cita: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar la cita.');
        }
    }

    /**
     * Convertir firma Base64 a archivo físico con aislamiento multi-empresa
     */
    private function saveSignatureToFile(?string $base64Data, string $prefix, int $citaId): string|array|null
    {
        if (empty($base64Data) || !str_contains($base64Data, 'base64')) {
            return $base64Data; // Ya es una ruta o está vacío
        }

        try {
            $empresaId = auth()->user()->empresa_id;
            $data = explode(',', $base64Data);
            if (count($data) < 2) return null;

            $decoded = base64_decode($data[1]);
            $filename = "{$prefix}_cita_{$citaId}_" . time() . ".png";
            $path = "empresa_{$empresaId}/citas/firmas/{$filename}";

            Storage::disk('public')->put($path, $decoded);

            // Generar hash de integridad (Validez Legal #902)
            $hash = hash('sha256', $decoded);
            
            return [
                'path' => $path,
                'hash' => $hash,
                'timestamp' => now()->toIso8601String()
            ];
        } catch (Exception $e) {
            Log::error("Error al guardar firma de cita #{$citaId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Método mejorado para guardar archivos con aislamiento multi-empresa
     */
    private function saveFiles(Request $request, array $fileFields, $existingFiles = [])
    {
        $filePaths = [];
        $empresaId = auth()->user()->empresa_id;

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                try {
                    $file = $request->file($field);

                    // Generar nombre único para evitar conflictos
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $filename = $originalName . '_' . now()->format('YmdHis') . '_' . substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 6) . '.' . $extension;

                    $path = $file->storeAs("empresa_{$empresaId}/citas", $filename, 'public');
                    $filePaths[$field] = $path;

                    // Eliminar el archivo anterior si existe
                    if (!empty($existingFiles[$field])) {
                        Storage::disk('public')->delete($existingFiles[$field]);
                    }
                } catch (Exception $e) {
                    Log::error("Error al guardar el archivo {$field}: " . $e->getMessage());
                    $filePaths[$field] = $existingFiles[$field] ?? null;
                }
            } else {
                $filePaths[$field] = $existingFiles[$field] ?? null; // Conservar el archivo existente
            }
        }
        return $filePaths;
    }

    /**
     * Verificar disponibilidad del técnico
     */
    private function verificarDisponibilidadTecnico(int $tecnicoId, $fechaHora, $fechaHoraFin = null, ?int $excludeId = null): void
    {
        if ($fechaHora instanceof \Carbon\Carbon) $fechaHora = $fechaHora->toDateTimeString();
        if ($fechaHoraFin instanceof \Carbon\Carbon) $fechaHoraFin = $fechaHoraFin->toDateTimeString();

        if (empty($fechaHora)) return;
        
        // Si no viene fin, asumimos 1 hora por defecto para la validación
        if (!$fechaHoraFin) {
            $fechaHoraFin = Carbon::parse($fechaHora)->addHour()->toDateTimeString();
        }

        $duracion = Carbon::parse($fechaHora)->diffInMinutes(Carbon::parse($fechaHoraFin));
        if (Cita::hayConflictoHorario($tecnicoId, $fechaHora, $excludeId, (int)$duracion)) {
            $conflicto = Cita::where('tecnico_id', $tecnicoId)
                ->where('estado', '!=', 'cancelado')
                ->where(function($q) use ($fechaHora, $fechaHoraFin) {
                    $q->where('fecha_hora', '<', $fechaHoraFin)
                      ->where('fecha_hora_fin', '>', $fechaHora);
                })
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->first();

            $hIn = Carbon::parse($conflicto->fecha_hora)->format('H:i');
            $hOut = Carbon::parse($conflicto->fecha_hora_fin)->format('H:i');
            throw ValidationException::withMessages([
                'fecha_hora' => "El técnico ya tiene una cita de {$hIn} a {$hOut}. Selecciona otro horario."
            ]);
        }
    }

    /**
     * Eliminar una cita existente.
     */
    public function destroy(Cita $cita)
    {
        try {
            DB::beginTransaction();

            // Programar eliminación de archivos SOLO tras un commit exitoso
            $archivos = array_filter([
                $cita->foto_equipo,
                $cita->foto_hoja_servicio,
                $cita->foto_identificacion
            ]);

            DB::afterCommit(function () use ($archivos) {
                foreach ($archivos as $archivo) {
                    if ($archivo && Storage::disk('public')->exists($archivo)) {
                        Storage::disk('public')->delete($archivo);
                    }
                }
            });

            $cita->delete();

            DB::commit();

            return redirect()->route('citas.index')->with('success', 'Cita eliminada exitosamente.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar cita: ' . $e->getMessage());
            return redirect()
                ->back()
                ->with('error', 'Error al eliminar la cita.');
        }
    }

    /**
     * Mostrar detalles de una cita.
     */
    public function show(Cita $cita)
    {
        $cita->load(['cliente', 'tecnico', 'items.citable', 'venta']);

        return Inertia::render('Citas/Show', [
            'cita' => $cita,
            'tecnicos' => User::tecnicos()->get(['id', 'name']),
            'clientes' => [], // No cargar todos los clientes en la vista de detalle
        ]);
    }


    public function export(Request $request)
    {
        try {
            $query = Cita::with('tecnico', 'cliente');

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


            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="citas_export_' . now()->format('Y-m-d_His') . '.csv"',
            ];

            $callback = function () use ($query) {
                $file = fopen('php://output', 'w');

                fputcsv($file, [
                    'ID',
                    'Folio',
                    'Cliente',
                    'Técnico',
                    'Tipo Servicio',
                    'Fecha y Hora',
                    'Estado',
                    'Prioridad',
                    'Fecha Creación'
                ]);

                // Usar cursor para no saturar la memoria RAM (Bomba de Memoria #402)
                foreach ($query->cursor() as $cita) {
                    fputcsv($file, [
                        $cita->id,
                        $cita->folio,
                        $cita->cliente?->nombre_razon_social ?? 'N/A',
                        $cita->tecnico?->name ?? 'N/A',
                        $cita->tipo_servicio,
                        $cita->fecha_hora?->format('d/m/Y H:i:s'),
                        $cita->estado,
                        $cita->prioridad ?? 'N/A',
                        $cita->created_at?->format('d/m/Y H:i:s')
                    ]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (Exception $e) {
            Log::error('Error en exportación de citas: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al exportar las citas.');
        }
    }

    /**
     * Verificar límite de citas por día para un técnico
     */
    private function verificarLimiteCitasPorDia(int $tecnicoId, string $fechaHora): void
    {
        $fecha = Carbon::parse($fechaHora)->toDateString();
        $inicioDia = Carbon::parse($fecha)->startOfDay();
        $finDia = Carbon::parse($fecha)->endOfDay();

        $citasEnDia = Cita::where('tecnico_id', $tecnicoId)
            ->whereBetween('fecha_hora', [$inicioDia, $finDia])
            ->where('estado', '!=', 'cancelado')
            ->count();

        // Límite dinámico basado en constante de modelo
        if ($citasEnDia >= Cita::MAX_CITAS_POR_DIA) {
            throw ValidationException::withMessages([
                'fecha_hora' => "El técnico ya tiene el máximo de " . Cita::MAX_CITAS_POR_DIA . " citas programadas para este día."
            ]);
        }
    }

    /**
     * Verificar que el cliente no tenga múltiples citas activas
     */
    private function verificarCitasClienteActivas(int $clienteId, string $fechaHora): void
    {
        $fecha = Carbon::parse($fechaHora);

        // Verificar si el cliente tiene más de 2 citas activas (sin importar la fecha futura)
        $citasActivas = Cita::where('cliente_id', $clienteId)
            ->whereIn('estado', ['pendiente', 'en_proceso', 'programado', 'reprogramado'])
            ->count();

        if ($citasActivas >= 3) { // Permitimos un margen de 2, bloqueamos en la 3era
            throw ValidationException::withMessages([
                'cliente_id' => 'El cliente ya tiene 2 o más citas activas o pendientes. Debe completar sus citas actuales antes de programar nuevas.'
            ]);
        }

        // Verificar si hay conflicto de horario el mismo día
        $citasMismoDia = Cita::where('cliente_id', $clienteId)
            ->whereDate('fecha_hora', $fecha->toDateString())
            ->where('estado', '!=', 'cancelado')
            ->where('fecha_hora', '!=', $fechaHora)
            ->count();

        if ($citasMismoDia > 0) {
            throw ValidationException::withMessages([
                'fecha_hora' => 'El cliente ya tiene una cita programada para este día.'
            ]);
        }
    }

    /**
     * Verificar si se puede eliminar la cita (restringido por rol y estado)
     */
    private function verificarPuedeEliminar(Cita $cita): void
    {
        $user = auth()->user();
        $isSuperAdmin = $user && $user->hasRole('super-admin');

        // Si la cita está completada o cancelada, SOLO el super_admin puede borrarla
        if (in_array($cita->estado, [Cita::ESTADO_COMPLETADO, Cita::ESTADO_CANCELADO])) {
            if (!$isSuperAdmin) {
                throw new Exception('Solo un Super Administrador puede eliminar citas en estado ' . $cita->estado . '.');
            }
        }

        // Si la cita está en proceso, nadie puede borrarla (primero debe cancelarse o completarse)
        if ($cita->estado === Cita::ESTADO_EN_PROCESO) {
            throw new Exception('No se puede eliminar una cita que está actualmente "En Proceso".');
        }
    }



    /**
     * Cambiar el estado de una cita (AJAX endpoint)
     */
    public function changeStatus(Request $request, Cita $cita)
    {
        try {
            // Verificar autorización (Escalada de privilegios #142)
            $user = auth()->user();
            if ($cita->tecnico_id !== $user->id && !$user->can('manage-all-citas')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permiso para cambiar el estado de esta cita.',
                ], 403);
            }

            $validated = $request->validate([
                'estado' => 'required|in:pendiente,programado,en_proceso,completado,cancelado,reprogramado',
                'trabajo_realizado' => 'nullable|string|max:5000',
            ]);

            $nuevoEstado = $validated['estado'];

            // Sanitización básica del texto
            if ($request->filled('trabajo_realizado')) {
                $cita->trabajo_realizado = strip_tags($validated['trabajo_realizado']);
            }

            // Verificar si el cambio de estado es válido
            if (!$cita->cambiarEstado($nuevoEstado)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cambiar al estado solicitado desde el estado actual.',
                ], 400);
            }


            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente.',
                'cita' => $cita->fresh(['cliente', 'tecnico']),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error al cambiar estado de cita: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado de la cita.',
            ], 500);
        }
    }

    // ==================== MÉTODOS PARA CITAS PÚBLICAS ====================

    /**
     * Vista de calendario con todas las citas de técnicos
     */
    public function calendario(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        // Obtener técnicos
        $tecnicos = User::where('empresa_id', $empresaId)
            ->where(function ($q) {
                $q->whereHas('roles', function ($r) {
                    $r->where('name', 'like', '%tecnico%')
                        ->orWhere('name', 'like', '%técnico%');
                })->orWhere('es_tecnico', true);
            })
            ->get(['id', 'name', 'email', 'telefono']);

        // Obtener mes y año del request
        $mes = $request->input('mes', Carbon::now()->month);
        $año = $request->input('año', Carbon::now()->year);

        // OPTIMIZACIÓN: Eager loading para evitar N+1 queries (Fuga de Datos #503)
        $citas = Cita::where('empresa_id', $empresaId)
            ->where(function ($q) use ($mes, $año) {
                $q->whereMonth('fecha_hora', $mes)->whereYear('fecha_hora', $año)
                    ->orWhere(function ($q2) use ($mes, $año) {
                        $q2->whereMonth('fecha_confirmada', $mes)->whereYear('fecha_confirmada', $año);
                    });
            })
            ->with(['cliente:id,nombre_razon_social,telefono', 'tecnico:id,name'])
            ->orderBy('fecha_hora')
            ->get();

        // Citas pendientes de asignación
        $citasPendientes = Cita::where('empresa_id', $empresaId)
            ->where('estado', Cita::ESTADO_PENDIENTE_ASIGNACION)
            ->with(['cliente:id,nombre_razon_social,telefono'])
            ->orderBy('created_at')
            ->get();

        // Colores por técnico
        $colores = ['#FF6B35', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA0DD', '#98D8C8', '#F7DC6F'];
        $tecnicosConColor = $tecnicos->map(function ($t, $index) use ($colores) {
            $t->color = $colores[$index % count($colores)];
            return $t;
        });

        return Inertia::render('Citas/Calendario', [
            'tecnicos' => $tecnicosConColor,
            'citas' => $citas,
            'citasPendientes' => $citasPendientes,
            'mes' => (int) $mes,
            'año' => (int) $año,
            'horarios' => Cita::HORARIOS_PREFERIDOS,
            'tiendas' => Cita::TIENDAS_ORIGEN,
        ]);
    }

    /**
     * Asignar un técnico a una cita pública
     */
    public function asignarTecnico(Request $request, Cita $cita)
    {
        $validated = $request->validate([
            'tecnico_id' => 'required|exists:users,id',
            'fecha_confirmada' => [
                'required', 
                'date', 
                'after_or_equal:today',
                function($attribute, $value, $fail) {
                    if (Carbon::parse($value)->isSunday()) {
                        $fail('No se pueden programar citas los domingos.');
                    }
                }
            ],
            'hora_confirmada' => 'required|date_format:H:i',
        ]);

        try {
            DB::beginTransaction();

            // Bloqueo pesimista para evitar colisiones de asignación (Bug Concurrencia #604)
            User::where('id', $validated['tecnico_id'])->lockForUpdate()->firstOrFail();

            // Verificar disponibilidad del técnico
            $fechaHora = Carbon::parse($validated['fecha_confirmada'] . ' ' . $validated['hora_confirmada']);
            $fechaHoraFin = (clone $fechaHora)->addHour();

            $this->verificarDisponibilidadTecnico(
                (int) $validated['tecnico_id'],
                $fechaHora,
                $fechaHoraFin,
                $cita->id
            );

            $cita->update([
                'tecnico_id' => $validated['tecnico_id'],
                'fecha_confirmada' => $validated['fecha_confirmada'],
                'hora_confirmada' => $validated['hora_confirmada'],
                'fecha_hora' => $fechaHora,
                'fecha_hora_fin' => $fechaHoraFin,
                'estado' => Cita::ESTADO_PROGRAMADO,
            ]);

            DB::commit();

            return back()->with('success', 'Técnico asignado correctamente. La cita ha sido programada.');

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al asignar técnico: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Error al asignar el técnico.']);
        }
    }

    /**
     * Confirmar hora exacta de una cita
     */
    public function confirmarHora(Request $request, Cita $cita)
    {
        $validated = $request->validate([
            'fecha_confirmada' => [
                'required', 
                'date', 
                'after_or_equal:today',
                function($attribute, $value, $fail) {
                    if (Carbon::parse($value)->isSunday()) {
                        $fail('No se pueden programar citas los domingos.');
                    }
                }
            ],
            'hora_confirmada' => 'required|date_format:H:i',
        ]);

        try {
            $fechaHora = Carbon::parse($validated['fecha_confirmada'] . ' ' . $validated['hora_confirmada']);

            // Bloqueo pesimista del técnico si existe
            if ($cita->tecnico_id) {
                User::where('id', $cita->tecnico_id)->lockForUpdate()->first();
            }

            // Verificar disponibilidad
            if ($cita->tecnico_id && Cita::hayConflictoHorario($cita->tecnico_id, $fechaHora->toDateTimeString(), $cita->id, 60)) {
                return back()->withErrors([
                    'hora_confirmada' => 'El técnico ya tiene una cita en ese horario.'
                ]);
            }

            $fechaHoraFin = (clone $fechaHora)->addHour();

            $cita->update([
                'fecha_confirmada' => $validated['fecha_confirmada'],
                'hora_confirmada' => $validated['hora_confirmada'],
                'fecha_hora' => $fechaHora,
                'fecha_hora_fin' => $fechaHoraFin,
            ]);

            return back()->with('success', 'Hora confirmada correctamente.');

        } catch (Exception $e) {
            Log::error('Error al confirmar hora: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Error al confirmar la hora.']);
        }
    }

    /**
     * Enviar WhatsApp de confirmación al cliente
     */
    public function enviarConfirmacionWhatsApp(Request $request, Cita $cita)
    {
        try {
            // Verificar que la cita tenga fecha y hora confirmada
            if (!$cita->fecha_confirmada || !$cita->hora_confirmada) {
                return back()->withErrors([
                    'general' => 'La cita debe tener fecha y hora confirmada antes de enviar WhatsApp.'
                ]);
            }

            $cita->load(['cliente', 'tecnico']);

            // Construir mensaje
            $nombreCliente = $cita->cliente->nombre_razon_social ?? $cita->cliente->nombre ?? 'Cliente';
            $fechaFormateada = Carbon::parse($cita->fecha_confirmada)->locale('es')->isoFormat('dddd D [de] MMMM');
            $horaInicio = Carbon::parse($cita->hora_confirmada)->format('h:i A');
            $horaFin = Carbon::parse($cita->hora_confirmada)->addHour()->format('h:i A');
            $tecnicoNombre = $cita->tecnico->name ?? 'Nuestro técnico';

            $mensaje = "📅 *¡Cita Confirmada!*\n\n";
            $mensaje .= "Hola {$nombreCliente}, tu cita ha sido programada:\n\n";
            $mensaje .= "✅ *Fecha:* {$fechaFormateada}\n";
            $mensaje .= "⏰ *Hora aproximada:* {$horaInicio} - {$horaFin}\n";
            $mensaje .= "👷 *Técnico:* {$tecnicoNombre}\n";
            $mensaje .= "📍 *Dirección:* {$cita->direccion_completa}\n\n";
            $mensaje .= "El técnico te llamará 30 minutos antes de llegar.\n\n";

            if ($cita->link_seguimiento) {
                $urlSeguimiento = route('agendar.seguimiento', $cita->link_seguimiento);
                $mensaje .= "Puedes ver el estado de tu cita aquí:\n{$urlSeguimiento}\n\n";
            }

            $mensaje .= "- Climas del Desierto";

            // Actualizar estado de envío
            $cita->update([
                'whatsapp_confirmacion_enviado' => true,
                'whatsapp_confirmacion_at' => now(),
            ]);

            // Obtener WhatsApp del cliente
            $telefono = $cita->cliente->telefono ?? $cita->cliente->celular ?? null;

            if (!$telefono) {
                return back()->withErrors([
                    'general' => 'El cliente no tiene número de WhatsApp registrado.'
                ]);
            }

            // Limpiar el número de teléfono (evitar duplicidad de prefijo 52)
            $telefonoLimpio = preg_replace('/\D/', '', $telefono);
            if (str_starts_with($telefonoLimpio, '52') && strlen($telefonoLimpio) > 10) {
                // Ya tiene el 52, no añadir más
            } else {
                $telefonoLimpio = '52' . $telefonoLimpio;
            }

            // Construir URL de WhatsApp
            $urlWhatsApp = "https://wa.me/{$telefonoLimpio}?text=" . urlencode($mensaje);

            return back()->with([
                'success' => 'Mensaje preparado. Se abrirá WhatsApp.',
                'whatsapp_url' => $urlWhatsApp,
            ]);

        } catch (Exception $e) {
            Log::error('Error al enviar WhatsApp de confirmación: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Error al preparar el mensaje de WhatsApp.']);
        }
    }

    // ==================== MÉTODOS PARA MI AGENDA (TÉCNICOS) ====================

    /**
     * Vista Mi Agenda para técnicos - muestra citas del día actual
     */
    public function miAgenda(Request $request)
    {
        $user = auth()->user();
        $empresaId = $user->empresa_id;
        $timezone = config('app.timezone', 'America/Hermosillo');
        $hoy = Carbon::now($timezone)->startOfDay();
        $fecha = $request->input('fecha', $hoy->toDateString());

        // Citas de hoy Y citas pendientes de días anteriores (vencidas)
        $citasHoy = Cita::where('tecnico_id', $user->id)
            ->where(function ($q) use ($fecha, $timezone) {
                // Citas programadas para la fecha seleccionada
                $q->where(function ($sq) use ($fecha) {
                    $sq->whereDate('fecha_confirmada', $fecha)
                        ->orWhereDate('fecha_hora', $fecha);
                })
                    // O citas de días anteriores que siguen pendientes
                    ->orWhere(function ($sq) use ($fecha, $timezone) {
                    $sq->where(function ($ssq) use ($timezone) {
                        $hoyStr = Carbon::now($timezone)->toDateString();
                        $ssq->whereDate('fecha_confirmada', '<', $hoyStr)
                            ->orWhere(function ($sssq) use ($hoyStr) {
                                $sssq->whereNull('fecha_confirmada')
                                    ->whereDate('fecha_hora', '<', $hoyStr);
                            });
                    })
                        ->where(function ($ssq) use ($fecha) {
                            $ssq->whereIn('estado', ['pendiente', 'programado', 'en_proceso', 'reprogramado'])
                                ->orWhere(function ($sssq) use ($fecha) {
                                    $sssq->where('estado', 'completado')
                                        ->whereDate('fin_servicio', $fecha);
                                });
                        });
                });
            })
            ->with(['cliente:id,nombre_razon_social,telefono,email'])
            ->orderByRaw("COALESCE(fecha_confirmada, CAST(fecha_hora AS DATE)) ASC")
            ->orderByRaw("COALESCE(hora_confirmada, CAST(fecha_hora AS TIME)) ASC")
            ->get();

        // Próximas citas (siguientes 7 días)
        $citasProximas = Cita::where('empresa_id', $empresaId)
            ->where('tecnico_id', $user->id)
            ->where(function ($q) use ($fecha) {
                $q->whereDate('fecha_hora', '>', $fecha)
                    ->orWhereDate('fecha_confirmada', '>', $fecha);
            })
            ->whereIn('estado', [Cita::ESTADO_PENDIENTE, Cita::ESTADO_PROGRAMADO])
            ->with(['cliente:id,nombre_razon_social,telefono'])
            ->orderBy('fecha_hora')
            ->limit(10)
            ->get();

        return Inertia::render('Citas/MiAgenda', [
            'citasHoy' => $citasHoy,
            'citasProximas' => $citasProximas,
            'fecha' => $fecha,
            'tecnico' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
        ]);
    }

    /**
     * Iniciar servicio de una cita
     */
    public function iniciar(Request $request, Cita $cita)
    {
        $json = $request->expectsJson();

        try {
            DB::beginTransaction();
            
            // Bloqueo pesimista para evitar colisiones en el cambio de estado
            $cita = Cita::where('id', $cita->id)->lockForUpdate()->firstOrFail();

            // Verificar que el técnico sea el asignado
            $user = auth()->user();
            if ($cita->tecnico_id !== $user->id) {
                DB::rollBack();
                $tecnicoNombre = $cita->tecnico ? $cita->tecnico->name : 'un técnico asignado';
                $errorMsg = "No puedes iniciar este servicio. Debe ser iniciado por el técnico asignado: {$tecnicoNombre}.";
                if ($json) {
                    return response()->json(['success' => false, 'message' => $errorMsg], 403);
                }

                return back()->withErrors(['general' => $errorMsg]);
            }

            // Verificar estado válido
            if (!in_array($cita->estado, [
                Cita::ESTADO_PENDIENTE,
                Cita::ESTADO_PROGRAMADO,
                Cita::ESTADO_REPROGRAMADO,
            ], true)) {
                DB::rollBack();
                if ($json) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La cita no puede ser iniciada desde su estado actual (' . $cita->estado . ').',
                    ], 422);
                }

                return back()->withErrors(['general' => 'La cita no puede ser iniciada desde su estado actual (' . $cita->estado . ').']);
            }

            // Verificar saldo de póliza en tiempo real (Double Check-in #608)
            if ($cita->tipo_servicio === 'soporte_sitio') {
                $poliza = \App\Models\PolizaServicio::where('cliente_id', $cita->cliente_id)->activa()->first();
                if ($poliza && $poliza->excede_limite_visitas) {
                    DB::rollBack();
                    if ($json) {
                        return response()->json([
                            'success' => false,
                            'message' => 'El cliente ha excedido el límite de visitas de su póliza. Requiere autorización o cargo extra para proceder.',
                        ], 422);
                    }

                    return back()->withErrors(['general' => 'El cliente ha excedido el límite de visitas de su póliza. Requiere autorización o cargo extra para proceder.']);
                }
            }

            // Cambiar estado con auditoría
            $cita->cambiarEstado(Cita::ESTADO_EN_PROCESO, 'Servicio iniciado desde Mi Agenda.');
            
            // Forzar actualización de inicio_servicio si no existía (ahora lo maneja cambiarEstado pero aseguramos)
            if (!$cita->inicio_servicio) {
                $cita->update(['inicio_servicio' => now()]);
            }

            DB::commit();
            if ($json) {
                return response()->json([
                    'success' => true,
                    'message' => '¡Servicio iniciado! El reloj está corriendo.',
                    'cita' => $cita->fresh(['cliente', 'tecnico']),
                ]);
            }

            return back()->with('success', '¡Servicio iniciado! El reloj está corriendo.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al iniciar servicio: ' . $e->getMessage());
            if ($json) {
                return response()->json(['success' => false, 'message' => 'Error al iniciar el servicio.'], 500);
            }

            return back()->withErrors(['general' => 'Error al iniciar el servicio.']);
        }
    }

    /**
     * Completar servicio de una cita
     */
    public function completar(Request $request, Cita $cita)
    {
        try {
            DB::beginTransaction();

            // Bloqueo pesimista
            $cita = Cita::where('id', $cita->id)->lockForUpdate()->firstOrFail();

            // Verificar que el técnico sea el asignado
            $user = auth()->user();
            if ($cita->tecnico_id !== $user->id) {
                DB::rollBack();
                $tecnicoNombre = $cita->tecnico ? $cita->tecnico->name : 'un técnico asignado';
                return back()->withErrors(['general' => "No puedes completar este servicio. Debe ser completado por el técnico asignado: {$tecnicoNombre}."]);
            }

            // Verificar estado válido
            if ($cita->estado !== Cita::ESTADO_EN_PROCESO) {
                DB::rollBack();
                return back()->withErrors(['general' => 'La cita debe estar en proceso para completarla.']);
            }

            if ($msg = $cita->bloqueoMensajePorTiempoMinimoCompletar(auth()->user())) {
                DB::rollBack();

                return back()->withErrors(['general' => $msg]);
            }

            $request->validate([
                'trabajo_realizado' => 'nullable|string',
                'fotos_finales.*' => 'nullable|image|max:5120', 
                'cerrar_ticket' => 'nullable|boolean',
                'firma_cliente' => 'nullable|string',
            ]);

            // Procesar firma si se envía
            $firmaPath = $this->saveSignatureToFile($request->input('firma_cliente'), 'cliente_final', $cita->id);

            // Procesar nuevas fotos
            $filePaths = $cita->fotos_finales ?? [];
            if ($request->hasFile('fotos_finales')) {
                foreach ($request->file('fotos_finales') as $index => $file) {
                    $path = $file->store('citas/evidencias_finales', 'public');
                    $filePaths[] = $path;
                }
            }

            // Actualizar datos antes de cambiar el estado final
            $cita->update([
                'trabajo_realizado' => $request->trabajo_realizado,
                'fotos_finales' => $filePaths,
                'firma_cliente' => $firmaPath ?: $cita->firma_cliente,
                'fecha_firma' => $firmaPath ? now() : $cita->fecha_firma,
            ]);

            // Completar cita con auditoría
            $cita->cambiarEstado(Cita::ESTADO_COMPLETADO, 'Servicio completado exitosamente desde Mi Agenda.');

            // Si se marcó para cerrar el ticket
            if ($request->boolean('cerrar_ticket') && $cita->ticket_id) {
                $ticket = \App\Models\Ticket::find($cita->ticket_id);
                if ($ticket && !in_array($ticket->estado, ['resuelto', 'cerrado'])) {
                    $horas = $cita->tiempo_servicio ? round($cita->tiempo_servicio / 60, 2) : null;
                    $ticket->marcarComoResuelto($horas, null, null, true);
                    
                    $ticket->comentarios()->create([
                        'user_id' => auth()->id(),
                        'contenido' => "✅ Ticket resuelto automáticamente desde 'Mi Agenda' al completar la cita #{$cita->id}.",
                        'tipo' => 'estado',
                        'es_interno' => false
                    ]);
                }
            }

            DB::commit();
            return back()->with('success', '✅ ¡Servicio completado exitosamente!');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al completar servicio: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Error al completar el servicio.']);
        }
    }

    /**
     * Cancelar una cita (desde Mi Agenda)
     */
    public function cancelar(Cita $cita)
    {
        try {
            // Verificar que el técnico sea el asignado
            $user = auth()->user();
            if ($cita->tecnico_id !== $user->id) {
                $tecnicoNombre = $cita->tecnico ? $cita->tecnico->name : 'un técnico asignado';
                return back()->withErrors(['general' => "No puedes cancelar este servicio. Debe ser cancelado por el técnico asignado: {$tecnicoNombre}."]);
            }

            // No permitir cancelar citas completadas
            if ($cita->estado === Cita::ESTADO_COMPLETADO) {
                return back()->withErrors(['general' => 'No se puede cancelar una cita completada.']);
            }

            $cita->update([
                'estado' => Cita::ESTADO_CANCELADO,
            ]);

            return back()->with('success', 'Cita cancelada.');

        } catch (Exception $e) {
            Log::error('Error al cancelar cita: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Error al cancelar la cita.']);
        }
    }

    public function enviarRecordatorioReprogramacion(Cita $cita)
    {
        $cita->load('cliente');

        if (!$cita->cliente || !$cita->cliente->telefono) {
            return back()->with('error', 'El cliente no tiene un teléfono registrado.');
        }

        $fecha = Carbon::parse($cita->fecha_confirmada ?? $cita->fecha_hora)->locale('es')->isoFormat('dddd D [de] MMMM');
        $telefono = preg_replace('/\D/', '', $cita->cliente->telefono);

        $mensaje = "Hola *{$cita->cliente->nombre_razon_social}*, te saludamos de *Climas del Desierto*. 🛠️\n\nNotamos que tenías una cita programada para el día *{$fecha}* que no se pudo concretar. \n\n¿Te gustaría que la reprogramemos? Quedamos a tus órdenes para confirmar un nuevo horario. 😊";

        $url = "https://wa.me/52{$telefono}?text=" . urlencode($mensaje);

        return back()->with([
            'success' => 'Mensaje de reprogramación preparado.',
            'whatsapp_url' => $url,
        ]);
    }
    public function checkVisitsLimit(Request $request)
    {
        $clienteId = $request->query('cliente_id');
        if (!$clienteId) {
            return response()->json(['success' => false, 'message' => 'Falta cliente_id']);
        }

        $poliza = \App\Models\PolizaServicio::where('cliente_id', $clienteId)
            ->activa()
            ->first();

        if (!$poliza) {
            return response()->json([
                'has_policy' => false,
                'message' => 'El cliente no tiene una póliza activa.'
            ]);
        }

        return response()->json([
            'has_policy' => true,
            'visitas_incluidas' => $poliza->visitas_sitio_mensuales,
            'visitas_consumidas' => $poliza->visitas_sitio_consumidas_mes,
            'excede_limite' => $poliza->excede_limite_visitas,
            'costo_extra' => $poliza->costo_visita_sitio_extra,
        ]);
    }

    /**
     * Limpia la dirección de URLs y extrae coordenadas si es un link de Google Maps.
     */
    protected function cleanAddressAndExtractGmaps($address)
    {
        if (empty($address)) return [$address, null, null];

        // Regex para buscar URLs (Http o Https)
        $urlRegex = '/https?:\/\/[^\s]+/';
        
        $lat = null;
        $lng = null;

        if (preg_match($urlRegex, $address, $matches)) {
            $url = $matches[0];
            
            // Intentar extraer coordenadas si es de Google Maps
            // Formatos: /@lat,lng o ?q=lat,lng o ll=lat,lng
            if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $coords)) {
                $lat = $coords[1];
                $lng = $coords[2];
            } elseif (preg_match('/[?&]q=(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $coords)) {
                $lat = $coords[1];
                $lng = $coords[2];
            } elseif (preg_match('/[?&]ll=(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $coords)) {
                $lat = $coords[1];
                $lng = $coords[2];
            }
            
            // Remover la URL de la dirección para dejarla limpia
            $address = trim(str_replace($url, '', $address));
            
            // Limpieza estética final: remover comas o guiones sobrantes al final
            $address = rtrim($address, ', -');
        }

        return [$address, $lat, $lng];
    }
}

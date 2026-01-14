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
use Exception;

class CitaController extends Controller
{
    /**
     * Mostrar todas las citas con paginación y filtros.
     */
    public function index(Request $request)
    {
        try {
            $query = Cita::with('tecnico', 'cliente');

            // Filtros de búsqueda
            if ($s = trim((string) $request->input('search', ''))) {
                $query->where(function ($w) use ($s) {
                    $w->where('tipo_servicio', 'like', "%{$s}%")
                        ->orWhere('descripcion', 'like', "%{$s}%")
                        ->orWhere('problema_reportado', 'like', "%{$s}%")
                        ->orWhereHas('cliente', function ($clienteQuery) use ($s) {
                            $clienteQuery->where('nombre_razon_social', 'like', "%{$s}%");
                        })
                        ->orWhereHas('tecnico', function ($tecnicoQuery) use ($s) {
                            $tecnicoQuery->where('name', 'like', "%{$s}%");
                        });
                });
            }

            // Filtros adicionales
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->filled('activo')) {
                $query->where('activo', filter_var($request->activo, FILTER_VALIDATE_BOOLEAN));
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


            // Ordenamiento dinámico
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');

            // Si no hay sort_by específico, mantener el orden por estado
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

            // Paginación - obtener per_page del request o usar default
            $perPage = $request->get('per_page', 10);
            $validPerPage = [10, 15, 25, 50]; // Solo estas opciones válidas
            if (!in_array((int) $perPage, $validPerPage)) {
                $perPage = 50;
            }

            // Paginar con el per_page dinámico
            $citas = $query->paginate((int) $perPage);

            // Estadísticas por estado de cita
            $citasCount = Cita::count();
            $citasPendientes = Cita::where('estado', Cita::ESTADO_PENDIENTE)->count();
            $citasEnProceso = Cita::where('estado', Cita::ESTADO_EN_PROCESO)->count();
            $citasCompletadas = Cita::where('estado', Cita::ESTADO_COMPLETADO)->count();
            $citasCanceladas = Cita::where('estado', Cita::ESTADO_CANCELADO)->count();

            // Datos adicionales para filtros
            $tecnicos = User::tecnicos()->select('id', 'name as nombre')->get();
            $clientes = Cliente::select('id', 'nombre_razon_social')->get();
            $estados = [
                Cita::ESTADO_PENDIENTE => 'Pendiente',
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
                    'pendientes' => $citasPendientes,
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
            $clientes = Cliente::all();
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
        // Parse items if it's a JSON string
        if ($request->has('items') && is_string($request->input('items'))) {
            $items = json_decode($request->input('items'), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $request->merge(['items' => $items]);
            }
        }

        // Validar los datos recibidos con mejoras
        $validated = $request->validate([
            'tecnico_id' => 'required|exists:users,id', // Se podría validar es_tecnico, pero por ahora existe en users es suficiente
            'cliente_id' => 'required|exists:clientes,id',
            'tipo_servicio' => 'required|string|max:255',
            'fecha_hora' => [
                'required',
                'date',
                'after:now',
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
            'prioridad' => 'nullable|string|in:baja,media,alta,urgente',
            'descripcion' => 'nullable|string|max:1000',
            'estado' => 'required|string|in:pendiente,programado,en_proceso,completado,cancelado,reprogramado',
            'evidencias' => 'nullable|string|max:2000',
            'foto_equipo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'foto_hoja_servicio' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'foto_identificacion' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'notas' => 'nullable|string|max:1000',
            'producto_serie_id' => 'nullable|integer|exists:producto_series,id',
            'tipo_equipo' => 'nullable|string|max:255',
            'marca_equipo' => 'nullable|string|max:255',
            'modelo_equipo' => 'nullable|string|max:255',
            'ticket_id' => 'nullable|integer|exists:tickets,id',
        ], [
            'tecnico_id.required' => 'Debe seleccionar un técnico.',
            'cliente_id.required' => 'Debe seleccionar un cliente.',
            'fecha_hora.after' => 'La fecha debe ser posterior a la actual.',
            '*.max:2048' => 'La imagen no debe superar los 2MB.',
            'tipo_equipo.required' => 'El tipo de equipo es obligatorio.',
            'marca_equipo.required' => 'La marca del equipo es obligatoria.',
            'modelo_equipo.required' => 'El modelo del equipo es obligatorio.',
        ]);

        try {
            DB::beginTransaction();

            // Verificar disponibilidad del técnico
            $this->verificarDisponibilidadTecnico(
                $validated['tecnico_id'],
                $validated['fecha_hora']
            );

            // Verificar límite de citas por día para el técnico
            $this->verificarLimiteCitasPorDia(
                $validated['tecnico_id'],
                $validated['fecha_hora']
            );

            // Verificar que el cliente no tenga múltiples citas activas
            $this->verificarCitasClienteActivas(
                $validated['cliente_id'],
                $validated['fecha_hora']
            );

            // Guardar archivos y obtener sus rutas
            $filePaths = $this->saveFiles($request, ['foto_equipo', 'foto_hoja_servicio', 'foto_identificacion']);

            $cita = Cita::create(array_merge($validated, $filePaths, [
                'subtotal' => 0,
                'descuento_general' => 0,
                'descuento_items' => 0,
                'iva' => 0,
                'total' => 0,
                'notas' => $request->notas,
            ]));



            // Si viene de una garantía, asociar la serie con la cita
            if ($request->filled('producto_serie_id')) {
                $productoSerieId = $request->input('producto_serie_id');

                // Verificar que la serie no esté ya asociada a otra cita
                $serieExistente = DB::table('producto_series')
                    ->where('id', $productoSerieId)
                    ->whereNotNull('cita_id')
                    ->first();

                if ($serieExistente) {
                    DB::rollBack();
                    return redirect()
                        ->back()
                        ->withInput()
                        ->with('error', 'Esta serie de garantía ya tiene una cita asociada (Cita #' . $serieExistente->cita_id . '). No se pueden crear múltiples citas para la misma garantía.');
                }

                // Actualizar el producto_serie con el cita_id
                DB::table('producto_series')
                    ->where('id', $productoSerieId)
                    ->update(['cita_id' => $cita->id]);

                Log::info("Serie de garantía asociada a cita", [
                    'cita_id' => $cita->id,
                    'producto_serie_id' => $productoSerieId
                ]);
            }

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
                ->with('error', 'Error al crear la cita. Por favor, intente nuevamente.');
        }
    }

    /**
     * Mostrar formulario para editar una cita existente.
     */
    public function edit(Cita $cita)
    {
        $cita->load(['items.citable']);

        $tecnicos = User::tecnicos()->select('id', 'name as nombre')->get();
        try {
            $clientes = Cliente::all();
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
        ]);

        try {
            DB::beginTransaction();
            $estadoAnterior = $cita->estado;

            // Verificar disponibilidad del técnico si cambió
            if (
                isset($validated['tecnico_id']) &&
                ($validated['tecnico_id'] != $cita->tecnico_id ||
                    isset($validated['fecha_hora']) && $validated['fecha_hora'] != $cita->fecha_hora)
            ) {
                $this->verificarDisponibilidadTecnico(
                    $validated['tecnico_id'],
                    $validated['fecha_hora'] ?? $cita->fecha_hora,
                    $cita->id
                );

                // Verificar límite de citas por día si cambió la fecha
                if (isset($validated['fecha_hora'])) {
                    $this->verificarLimiteCitasPorDia(
                        $validated['tecnico_id'],
                        $validated['fecha_hora']
                    );
                }
            }

            // Verificar citas activas del cliente si cambió la fecha
            if (isset($validated['cliente_id']) && $validated['cliente_id'] != $cita->cliente_id) {
                $this->verificarCitasClienteActivas(
                    $validated['cliente_id'],
                    $validated['fecha_hora'] ?? $cita->fecha_hora
                );
            }

            // Guardar archivos y obtener sus rutas (conservando los archivos existentes si no se suben nuevos)
            $filePaths = $this->saveFiles($request, ['foto_equipo', 'foto_hoja_servicio', 'foto_identificacion'], [
                'foto_equipo' => $cita->foto_equipo,
                'foto_hoja_servicio' => $cita->foto_hoja_servicio,
                'foto_identificacion' => $cita->foto_identificacion,
            ]);

            $dataToUpdate = array_merge(collect($validated)->except(['items'])->toArray(), $filePaths);

            // Manejo de 'trabajo_realizado'
            if ($request->has('trabajo_realizado')) {
                $dataToUpdate['trabajo_realizado'] = $request->input('trabajo_realizado');
            }

            // Manejo de 'nuevas_fotos' (Agregar a fotos_finales existentes)
            if ($request->hasFile('nuevas_fotos')) {
                $currentFotos = $cita->fotos_finales ?? [];
                $newFotos = [];

                foreach ($request->file('nuevas_fotos') as $foto) {
                    $filename = 'cita_' . $cita->id . '_' . time() . '_' . uniqid() . '.' . $foto->getClientOriginalExtension();
                    $path = $foto->storeAs('citas/evidencias_finales', $filename, 'public');
                    $newFotos[] = $path;
                }

                $dataToUpdate['fotos_finales'] = array_merge($currentFotos, $newFotos);
            }

            // Actualizar la cita con los datos validados y las rutas de los archivos
            $cita->update($dataToUpdate);

            // No se procesan items ni se recalculan totales (funcionalidad de venta removida)


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
                ->with('error', 'Error al actualizar la cita. Por favor, intente nuevamente.');
        }
    }

    /**
     * Método mejorado para guardar archivos
     */
    private function saveFiles(Request $request, array $fileFields, $existingFiles = [])
    {
        $filePaths = [];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                try {
                    $file = $request->file($field);

                    // Generar nombre único para evitar conflictos
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $filename = $originalName . '_' . now()->format('YmdHis') . '_' . substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 6) . '.' . $extension;

                    $path = $file->storeAs('citas', $filename, 'public');
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
    private function verificarDisponibilidadTecnico(int $tecnicoId, string $fechaHora, ?int $excludeId = null): void
    {
        $query = Cita::where('tecnico_id', $tecnicoId)
            ->where('fecha_hora', $fechaHora)
            ->where('estado', '!=', 'cancelado');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'fecha_hora' => 'El técnico ya tiene una cita programada en esta fecha y hora.'
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

            // Verificar si se puede eliminar la cita
            $this->verificarPuedeEliminar($cita);

            // Eliminar archivos asociados
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
            'tecnicos' => User::role('tecnico')->get(['id', 'name']),
            'clientes' => Cliente::all(['id', 'nombre_razon_social']),
        ]);
    }


    public function export(Request $request)
    {
        try {
            $query = Cita::with('tecnico', 'cliente');

            // Aplicar los mismos filtros que en index
            if ($s = trim((string) $request->input('search', ''))) {
                $query->where(function ($w) use ($s) {
                    $w->where('tipo_servicio', 'like', "%{$s}%")
                        ->orWhere('descripcion', 'like', "%{$s}%")
                        ->orWhereHas('cliente', function ($clienteQuery) use ($s) {
                            $clienteQuery->where('nombre_razon_social', 'like', "%{$s}%");
                        })
                        ->orWhereHas('tecnico', function ($tecnicoQuery) use ($s) {
                            $tecnicoQuery->where('name', 'like', "%{$s}%");
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


            $citas = $query->get();

            $filename = 'citas_' . date('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($citas) {
                $file = fopen('php://output', 'w');

                fputcsv($file, [
                    'ID',
                    'Cliente',
                    'Técnico',
                    'Tipo Servicio',
                    'Fecha y Hora',
                    'Estado',
                    'Prioridad',
                    'Total',
                    'Fecha Creación'
                ]);

                foreach ($citas as $cita) {
                    fputcsv($file, [
                        $cita->id,
                        $cita->cliente?->nombre_razon_social ?? 'N/A',
                        $cita->tecnico?->name ?? 'N/A',
                        $cita->tipo_servicio,
                        $cita->fecha_hora?->format('d/m/Y H:i:s'),
                        $cita->estado,
                        $cita->prioridad ?? 'N/A',
                        number_format($cita->total ?? 0, 2),
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

        // Límite de 8 citas por día
        if ($citasEnDia >= 8) {
            throw ValidationException::withMessages([
                'fecha_hora' => 'El técnico ya tiene el máximo de 8 citas programadas para este día.'
            ]);
        }
    }

    /**
     * Verificar que el cliente no tenga múltiples citas activas
     */
    private function verificarCitasClienteActivas(int $clienteId, string $fechaHora): void
    {
        $fecha = Carbon::parse($fechaHora);

        // Verificar si el cliente tiene más de 2 citas activas en los próximos 7 días
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
     * Verificar si se puede eliminar la cita (sin relaciones críticas)
     */
    private function verificarPuedeEliminar(Cita $cita): void
    {
        // No permitir eliminar citas completadas con menos de 30 días de antigüedad
        if ($cita->estado === Cita::ESTADO_COMPLETADO) {
            $diasDesdeCreacion = now()->diffInDays($cita->created_at);
            if ($diasDesdeCreacion < 30) {
                throw ValidationException::withMessages([
                    'cita' => 'No se pueden eliminar citas completadas con menos de 30 días de antigüedad por políticas de auditoría.'
                ]);
            }
        }

        // Verificar si la cita está en proceso (solo permitir cancelación)
        if ($cita->estado === Cita::ESTADO_EN_PROCESO) {
            throw ValidationException::withMessages([
                'cita' => 'No se puede eliminar una cita en proceso. Solo se puede cancelar.'
            ]);
        }
    }



    /**
     * Cambiar el estado de una cita (AJAX endpoint)
     */
    public function changeStatus(Request $request, Cita $cita)
    {
        try {
            $validated = $request->validate([
                'estado' => 'required|in:pendiente,programado,en_proceso,completado,cancelado,reprogramado',
            ]);

            $nuevoEstado = $validated['estado'];

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

        // Obtener citas del mes
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
            'fecha_confirmada' => 'required|date|after_or_equal:today',
            'hora_confirmada' => 'required|date_format:H:i',
        ]);

        try {
            DB::beginTransaction();

            // Verificar disponibilidad del técnico
            $fechaHora = Carbon::parse($validated['fecha_confirmada'] . ' ' . $validated['hora_confirmada']);

            if (Cita::hayConflictoHorario($validated['tecnico_id'], $fechaHora->toDateTimeString(), $cita->id)) {
                return back()->withErrors([
                    'tecnico_id' => 'El técnico ya tiene una cita en ese horario.'
                ]);
            }

            $cita->update([
                'tecnico_id' => $validated['tecnico_id'],
                'fecha_confirmada' => $validated['fecha_confirmada'],
                'hora_confirmada' => $validated['hora_confirmada'],
                'fecha_hora' => $fechaHora,
                'estado' => Cita::ESTADO_PROGRAMADO,
            ]);

            DB::commit();

            return back()->with('success', 'Técnico asignado correctamente. La cita ha sido programada.');

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
            'fecha_confirmada' => 'required|date|after_or_equal:today',
            'hora_confirmada' => 'required|date_format:H:i',
        ]);

        try {
            $fechaHora = Carbon::parse($validated['fecha_confirmada'] . ' ' . $validated['hora_confirmada']);

            // Verificar disponibilidad si tiene técnico asignado
            if ($cita->tecnico_id && Cita::hayConflictoHorario($cita->tecnico_id, $fechaHora->toDateTimeString(), $cita->id)) {
                return back()->withErrors([
                    'hora_confirmada' => 'El técnico ya tiene una cita en ese horario.'
                ]);
            }

            $cita->update([
                'fecha_confirmada' => $validated['fecha_confirmada'],
                'hora_confirmada' => $validated['hora_confirmada'],
                'fecha_hora' => $fechaHora,
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

            // Limpiar el número de teléfono
            $telefonoLimpio = preg_replace('/\D/', '', $telefono);

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
        $hoy = Carbon::now('America/Hermosillo')->startOfDay();
        $fecha = $request->input('fecha', $hoy->toDateString());

        // Citas de hoy Y citas pendientes de días anteriores (vencidas)
        $citasHoy = Cita::where('tecnico_id', $user->id)
            ->where(function ($q) use ($fecha) {
                // Citas programadas para la fecha seleccionada
                $q->where(function ($sq) use ($fecha) {
                    $sq->whereDate('fecha_confirmada', $fecha)
                        ->orWhereDate('fecha_hora', $fecha);
                })
                    // O citas de días anteriores que siguen pendientes
                    ->orWhere(function ($sq) use ($fecha) {
                    $sq->where(function ($ssq) {
                        $ssq->whereDate('fecha_confirmada', '<', Carbon::now('America/Hermosillo')->toDateString())
                            ->orWhere(function ($sssq) {
                                $sssq->whereNull('fecha_confirmada')
                                    ->whereDate('fecha_hora', '<', Carbon::now('America/Hermosillo')->toDateString());
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
            ->orderByRaw("COALESCE(fecha_confirmada, fecha_hora::date) ASC")
            ->orderByRaw("COALESCE(hora_confirmada, fecha_hora::time) ASC")
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
    public function iniciar(Cita $cita)
    {
        try {
            // Verificar que el técnico sea el asignado
            if ($cita->tecnico_id !== auth()->id()) {
                return back()->withErrors(['general' => 'No tienes permiso para modificar esta cita.']);
            }

            // Verificar estado válido
            if (!in_array($cita->estado, [Cita::ESTADO_PENDIENTE, Cita::ESTADO_PROGRAMADO])) {
                return back()->withErrors(['general' => 'La cita no puede ser iniciada desde su estado actual.']);
            }

            $cita->update([
                'estado' => Cita::ESTADO_EN_PROCESO,
                'inicio_servicio' => now(),
            ]);

            return back()->with('success', '¡Servicio iniciado! El reloj está corriendo.');

        } catch (Exception $e) {
            Log::error('Error al iniciar servicio: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Error al iniciar el servicio.']);
        }
    }

    /**
     * Completar servicio de una cita
     */
    public function completar(Request $request, Cita $cita)
    {
        try {
            // Verificar que el técnico sea el asignado
            if ($cita->tecnico_id !== auth()->id()) {
                return back()->withErrors(['general' => 'No tienes permiso para modificar esta cita.']);
            }

            // Verificar estado válido
            if ($cita->estado !== Cita::ESTADO_EN_PROCESO) {
                return back()->withErrors(['general' => 'La cita debe estar en proceso para completarla.']);
            }

            $request->validate([
                'trabajo_realizado' => 'nullable|string',
                'fotos_finales.*' => 'nullable|image|max:5120', // Máx 5MB por foto
            ]);

            $finServicio = now();
            $tiempoServicio = null;

            if ($cita->inicio_servicio) {
                $inicio = Carbon::parse($cita->inicio_servicio);
                $tiempoServicio = (int) $inicio->diffInMinutes($finServicio);
            }

            $filePaths = [];
            if ($request->hasFile('fotos_finales')) {
                foreach ($request->file('fotos_finales') as $index => $file) {
                    $path = $file->store('citas/evidencias_finales', 'public');
                    $filePaths[] = $path;
                }
            }

            $cita->update([
                'estado' => Cita::ESTADO_COMPLETADO,
                'fin_servicio' => $finServicio,
                'tiempo_servicio' => $tiempoServicio,
                'trabajo_realizado' => $request->trabajo_realizado,
                'fotos_finales' => count($filePaths) > 0 ? $filePaths : null,
            ]);

            return back()->with('success', '✅ ¡Servicio completado exitosamente!');

        } catch (Exception $e) {
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
            if ($cita->tecnico_id !== auth()->id()) {
                return back()->withErrors(['general' => 'No tienes permiso para modificar esta cita.']);
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

        $mensaje = "Hola *{$cita->cliente->nombre_razon_social}*, te saludamos de *Climas del Desierto*. ❄️\n\nNotamos que tenías una cita programada para el día *{$fecha}* que no se pudo concretar. \n\n¿Te gustaría que la reprogramemos? Quedamos a tus órdenes para confirmar un nuevo horario. 😊";

        $url = "https://wa.me/52{$telefono}?text=" . urlencode($mensaje);

        return Inertia::location($url);
    }
}

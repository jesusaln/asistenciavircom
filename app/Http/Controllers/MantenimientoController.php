<?php

namespace App\Http\Controllers;

use App\Models\Mantenimiento;
use App\Http\Requests\StoreMantenimientoRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class MantenimientoController extends Controller
{
    /**
     * Obtener servicios existentes de un vehículo por tipo
     */
    public function getServiciosPorTipo(Request $request, $carroId, $tipoServicio): JsonResponse
    {
        try {
            // Buscar mantenimientos del vehículo con el tipo específico
            $servicios = Mantenimiento::where('carro_id', $carroId)
                ->where('tipo', $tipoServicio)
                ->select('id', 'tipo', 'fecha', 'created_at')
                ->orderBy('fecha', 'desc')
                ->limit(10) // Últimos 10 servicios del mismo tipo
                ->get();

            return response()->json($servicios);
        } catch (\Exception $e) {
            Log::error('Error obteniendo servicios por tipo: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }

    /**
     * Validar si un servicio puede ser registrado (sin duplicados recientes)
     */
    public function validarServicio(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'carro_id' => 'required|integer|exists:carros,id',
                'tipo' => 'required|string',
                'fecha' => 'required|date',
            ]);

            $diasMinimos = $this->getDiasMinimosEntreServicios($validated['tipo']);

            // Buscar servicios recientes del mismo tipo
            $serviciosRecientes = Mantenimiento::where('carro_id', $validated['carro_id'])
                ->where('tipo', $validated['tipo'])
                ->where('fecha', '>=', now()->subDays($diasMinimos))
                ->exists();

            if ($serviciosRecientes) {
                $tipoLabel = $validated['tipo'] === 'Otro servicio' ? 'otro servicio' : $validated['tipo'];

                return response()->json([
                    'valido' => false,
                    'mensaje' => "Ya existe un servicio de {$tipoLabel} reciente. Debe esperar al menos {$diasMinimos} días entre servicios del mismo tipo."
                ]);
            }

            return response()->json([
                'valido' => true,
                'mensaje' => 'Servicio válido para registro'
            ]);

        } catch (\Exception $e) {
            Log::error('Error validando servicio: ' . $e->getMessage());
            return response()->json([
                'valido' => false,
                'mensaje' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener días mínimos entre servicios según el tipo
     */
    private function getDiasMinimosEntreServicios(string $tipo): int
    {
        return match ($tipo) {
            'Cambio de aceite' => 30,
            'Revisión periódica' => 90,
            'Servicio de frenos' => 180,
            'Servicio de llantas' => 60,
            'Servicio de batería' => 180,
            'Servicio de motor' => 365,
            'Revisión de luces' => 90,
            'Alineación y balanceo' => 90,
            'Cambio de filtros' => 60,
            'Revisión de transmisión' => 365,
            'Otro servicio' => 7,
            default => 30,
        };
    }

    /**
     * ==========================================
     * REGLAS DE NEGOCIO PARA ESTADOS DE MANTENIMIENTO
     * ==========================================
     * Vencido: proximo_mantenimiento < hoy y estado != completado.
     * Por vencer: alert_at <= hoy <= proximo_mantenimiento y estado != completado.
     * Al día: hoy < alert_at y estado != completado.
     * Completado: estado = completado (excluir de alertas).
     *
     * alert_at = proximo_mantenimiento - dias_anticipacion_alerta (días)
     * ==========================================
     */

    /**
     * Calcular fecha de alerta basada en próximo mantenimiento y días de anticipación
     */
    private function calcularFechaAlerta(string $proximoMantenimiento, int $diasAnticipacion): Carbon
    {
        return Carbon::parse($proximoMantenimiento)->subDays($diasAnticipacion);
    }

    /**
     * Determinar el estado de un mantenimiento según las reglas de negocio
     */
    public function calcularEstadoMantenimiento(Mantenimiento $mantenimiento): array
    {
        $hoy = Carbon::today();
        $proximo = Carbon::parse($mantenimiento->proximo_mantenimiento);

        // Si ya está completado, excluir de alertas
        if ($mantenimiento->estado === 'completado') {
            return [
                'estado' => 'completado',
                'descripcion' => 'Servicio completado',
                'clase' => 'text-green-700 bg-green-100',
                'dias_restantes' => 0,
                'es_vencido' => false,
                'es_proximo' => false
            ];
        }

        // Calcular fecha de alerta
        $alertAt = $this->calcularFechaAlerta($mantenimiento->proximo_mantenimiento, $mantenimiento->dias_anticipacion_alerta);

        // Calcular días restantes
        $diasRestantes = $hoy->diffInDays($proximo, false); // false para incluir signo negativo

        // Aplicar reglas de negocio
        if ($proximo->lessThan($hoy)) {
            // VENCIDO: proximo_mantenimiento < hoy y estado != completado
            return [
                'estado' => 'vencido',
                'descripcion' => "Vencido hace {$hoy->diffInDays($proximo)} días",
                'clase' => 'text-red-700 bg-red-100',
                'dias_restantes' => $diasRestantes,
                'es_vencido' => true,
                'es_proximo' => false
            ];
        } elseif ($alertAt->lessThanOrEqualTo($hoy) && $hoy->lessThan($proximo)) {
            // POR VENCER: alert_at <= hoy <= proximo_mantenimiento y estado != completado
            return [
                'estado' => 'por_vencer',
                'descripcion' => "Vence en {$diasRestantes} días (alerta activa)",
                'clase' => 'text-orange-700 bg-orange-100',
                'dias_restantes' => $diasRestantes,
                'es_vencido' => false,
                'es_proximo' => true
            ];
        } else {
            // AL DÍA: hoy < alert_at y estado != completado
            return [
                'estado' => 'al_dia',
                'descripcion' => "Próximo en {$diasRestantes} días",
                'clase' => 'text-blue-700 bg-blue-100',
                'dias_restantes' => $diasRestantes,
                'es_vencido' => false,
                'es_proximo' => false
            ];
        }
    }

    /**
     * Obtener estadísticas de mantenimientos por estado
     */
    public function getEstadisticasMantenimientos(): JsonResponse
    {
        try {
            $mantenimientos = Mantenimiento::with('carro')
                ->where('estado', '!=', 'completado')
                ->get();

            $estadisticas = [
                'total_activos' => $mantenimientos->count(),
                'vencidos' => 0,
                'por_vencer' => 0,
                'al_dia' => 0,
                'detalles' => []
            ];

            foreach ($mantenimientos as $mantenimiento) {
                $estado = $this->calcularEstadoMantenimiento($mantenimiento);

                switch ($estado['estado']) {
                    case 'vencido':
                        $estadisticas['vencidos']++;
                        break;
                    case 'por_vencer':
                        $estadisticas['por_vencer']++;
                        break;
                    case 'al_dia':
                        $estadisticas['al_dia']++;
                        break;
                }

                $estadisticas['detalles'][] = [
                    'id' => $mantenimiento->id,
                    'vehiculo' => $mantenimiento->carro->marca . ' ' . $mantenimiento->carro->modelo,
                    'tipo' => $mantenimiento->tipo,
                    'estado' => $estado,
                    'dias_restantes' => $estado['dias_restantes']
                ];
            }

            return response()->json($estadisticas);

        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas de mantenimientos: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Mostrar mantenimiento específico
     */
    public function show(Mantenimiento $mantenimiento): Response
    {
        try {
            $mantenimiento->load(['carro']);

            return Inertia::render('Mantenimientos/Show', [
                'mantenimiento' => $mantenimiento
            ]);

        } catch (\Exception $e) {
            Log::error('Error mostrando mantenimiento: ' . $e->getMessage());
            return Inertia::render('Mantenimientos/Show', [
                'mantenimiento' => null,
                'error' => 'Error al cargar el mantenimiento'
            ]);
        }
    }

    /**
     * Mostrar formulario para editar mantenimiento
     */
    public function edit(Mantenimiento $mantenimiento): Response
    {
        try {
            $mantenimiento->load(['carro']);
            $carros = \App\Models\Carro::select('id', 'marca', 'modelo', 'placa', 'kilometraje')
                ->where('activo', true)
                ->orderBy('marca')
                ->orderBy('modelo')
                ->get();

            return Inertia::render('Mantenimientos/Edit', [
                'mantenimiento' => $mantenimiento,
                'carros' => $carros
            ]);

        } catch (\Exception $e) {
            Log::error('Error editando mantenimiento: ' . $e->getMessage());
            return Inertia::render('Mantenimientos/Edit', [
                'mantenimiento' => null,
                'carros' => [],
                'error' => 'Error al cargar el formulario de edición'
            ]);
        }
    }

    /**
     * Actualizar mantenimiento
     */
    public function update(Request $request, Mantenimiento $mantenimiento): \Illuminate\Http\RedirectResponse
    {
        try {
            $validated = $request->validate([
                'carro_id' => 'required|integer|exists:carros,id',
                'tipo' => 'required|string|max:100',
                'fecha' => 'required|date|before_or_equal:today',
                'proximo_mantenimiento' => 'required|date|after:fecha',
                'kilometraje_actual' => 'required|integer|min:0',
                'costo' => 'nullable|numeric|min:0',
                'taller' => 'nullable|string|max:100',
                'prioridad' => 'required|in:baja,media,alta,critica',
                'dias_anticipacion_alerta' => 'required|integer|min:1|max:365',
                'requiere_aprobacion' => 'boolean',
                'observaciones_alerta' => 'nullable|string|max:500',
                'notas' => 'nullable|string|max:1000',
            ]);

            $mantenimiento->update($validated);

            Log::info('Mantenimiento actualizado exitosamente', [
                'id' => $mantenimiento->id,
                'tipo' => $mantenimiento->tipo
            ]);

            return redirect()->route('mantenimientos.index')
                ->with('success', 'Mantenimiento actualizado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error actualizando mantenimiento: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al actualizar el mantenimiento')
                ->withInput();
        }
    }

    /**
     * Eliminar mantenimiento
     */
    public function destroy(Mantenimiento $mantenimiento): \Illuminate\Http\RedirectResponse
    {
        try {
            $mantenimiento->delete();

            Log::info('Mantenimiento eliminado exitosamente', [
                'id' => $mantenimiento->id,
                'tipo' => $mantenimiento->tipo
            ]);

            return redirect()->route('mantenimientos.index')
                ->with('success', 'Mantenimiento eliminado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error eliminando mantenimiento: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al eliminar el mantenimiento');
        }
    }

    /**
     * Almacenar nuevo mantenimiento
     */
    public function store(StoreMantenimientoRequest $request): \Illuminate\Http\RedirectResponse
    {
        try {
            $validated = $request->validated();

            $mantenimiento = Mantenimiento::create($validated);

            Log::info('Mantenimiento creado exitosamente', [
                'id' => $mantenimiento->id,
                'tipo' => $mantenimiento->tipo,
                'carro_id' => $mantenimiento->carro_id
            ]);

            return redirect()->route('mantenimientos.index')
                ->with('success', 'Mantenimiento creado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error creando mantenimiento: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al crear el mantenimiento')
                ->withInput();
        }
    }

    /**
     * Mostrar formulario para crear mantenimiento
     */
    public function create(): Response
    {
        try {
            $carros = \App\Models\Carro::select('id', 'marca', 'modelo', 'placa', 'kilometraje')
                ->where('activo', true)
                ->orderBy('marca')
                ->orderBy('modelo')
                ->get();

            return Inertia::render('Mantenimientos/Create', [
                'carros' => $carros
            ]);

        } catch (\Exception $e) {
            Log::error('Error en create de mantenimientos: ' . $e->getMessage());
            return Inertia::render('Mantenimientos/Create', [
                'carros' => [],
                'error' => 'Error al cargar el formulario'
            ]);
        }
    }

    /**
     * Mostrar listado de mantenimientos con filtros avanzados
     */
    public function index(Request $request): Response
    {
        try {
            $query = Mantenimiento::with('carro');

            // Filtros
            if ($search = trim($request->input('search', ''))) {
                $query->where(function ($q) use ($search) {
                    $q->where('tipo', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%")
                      ->orWhereHas('carro', function ($carroQuery) use ($search) {
                          $carroQuery->where('marca', 'like', "%{$search}%")
                                    ->orWhere('modelo', 'like', "%{$search}%")
                                    ->orWhere('placa', 'like', "%{$search}%");
                      });
                });
            }

            if ($estado = $request->input('estado')) {
                $query->where('estado', $estado);
            }

            if ($tipo = $request->input('tipo')) {
                $query->where('tipo', $tipo);
            }

            if ($carroId = $request->input('carro_id')) {
                $query->where('carro_id', $carroId);
            }

            // Ordenamiento
            $sortBy = $request->input('sort_by', 'fecha');
            $sortDirection = $request->input('sort_direction', 'asc'); // Por defecto orden ascendente para mejor flujo cronológico

            $validSortFields = ['fecha', 'tipo', 'costo', 'estado', 'created_at'];
            if (!in_array($sortBy, $validSortFields)) {
                $sortBy = 'fecha';
            }

            $query->orderBy($sortBy, $sortDirection);

            // Paginación
            $perPage = min((int) $request->input('per_page', 10), 50);
            $mantenimientos = $query->paginate($perPage)->appends($request->query());

            // Calcular días restantes para cada mantenimiento
            foreach ($mantenimientos->items() as $mantenimiento) {
                if ($mantenimiento->proximo_mantenimiento) {
                    $fechaProximo = Carbon::parse($mantenimiento->proximo_mantenimiento);
                    $fechaHoy = Carbon::now('America/Hermosillo'); // Usar zona horaria correcta
                    $diasRestantes = round($fechaHoy->diffInDays($fechaProximo, false)); // Redondear a número entero

                    $mantenimiento->dias_restantes = $diasRestantes;
                } else {
                    $mantenimiento->dias_restantes = null;
                }
            }

            // Estadísticas
            $stats = [
                'total' => Mantenimiento::count(),
                'completados' => Mantenimiento::where('estado', Mantenimiento::ESTADO_COMPLETADO)->count(),
                'pendientes' => Mantenimiento::where('estado', Mantenimiento::ESTADO_PENDIENTE)->count(),
                'en_proceso' => Mantenimiento::where('estado', Mantenimiento::ESTADO_EN_PROCESO)->count(),
                'costo_total_mes' => Mantenimiento::whereMonth('fecha', now()->month)->sum('costo'),
                'proximos_vencer' => $this->getMantenimientosProximosAVencer(30)->count(),
            ];

            // Obtener carros activos para el filtro
            $carros = \App\Models\Carro::where('activo', true)
                ->orderBy('marca', 'asc')
                ->orderBy('modelo', 'asc')
                ->get();

            return Inertia::render('Mantenimientos/Index', [
                'mantenimientos' => $mantenimientos,
                'stats' => $stats,
                'filters' => $request->only(['search', 'estado', 'tipo', 'carro_id']),
                'sorting' => ['sort_by' => $sortBy, 'sort_direction' => $sortDirection],
                'carros' => $carros,
                'tiposMantenimiento' => $this->getTiposMantenimiento(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error en MantenimientoController@index: ' . $e->getMessage());
            return Inertia::render('Mantenimientos/Index', [
                'mantenimientos' => collect(),
                'stats' => [],
                'filters' => [],
                'carros' => [],
                'tiposMantenimiento' => [],
                'error' => 'Error al cargar los mantenimientos.'
            ]);
        }
    }

    /**
     * Aplicar filtros a la consulta de mantenimientos
     */
    private function aplicarFiltros($query, Request $request)
    {
        // Debug: Ver filtros aplicados
        Log::info('Filtros aplicados:', $request->only([
            'search', 'estado', 'tipo', 'prioridad', 'carro_id',
            'fecha_desde', 'fecha_hasta', 'ordenar_por', 'orden_direccion'
        ]));

        // Filtro de búsqueda general
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('tipo', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%")
                  ->orWhere('notas', 'like', "%{$search}%")
                  ->orWhereHas('carro', function ($carroQuery) use ($search) {
                      $carroQuery->where('marca', 'like', "%{$search}%")
                                ->orWhere('modelo', 'like', "%{$search}%")
                                ->orWhere('placa', 'like', "%{$search}%");
                  });
            });
        }

        // Filtro por estado
        if ($request->filled('estado')) {
            switch ($request->get('estado')) {
                case 'vencidos':
                    $query->vencidos();
                    break;
                case 'por_vencer':
                    $query->porVencer();
                    break;
                case 'al_dia':
                    $query->alDia();
                    break;
                case 'completados':
                    $query->where('estado', 'completado');
                    break;
                case 'activos':
                    $query->activos();
                    break;
            }
        }

        // Filtro por tipo de servicio
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->get('tipo'));
        }

        // Filtro por prioridad
        if ($request->filled('prioridad')) {
            $query->where('prioridad', $request->get('prioridad'));
        }

        // Filtro por vehículo
        if ($request->filled('carro_id')) {
            $query->where('carro_id', $request->get('carro_id'));
        }

        // Filtro por rango de fechas
        if ($request->filled('fecha_desde')) {
            $query->where('proximo_mantenimiento', '>=', $request->get('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('proximo_mantenimiento', '<=', $request->get('fecha_hasta'));
        }

        // Ordenamiento personalizado
        $ordenPor = $request->get('ordenar_por', 'urgencia');
        $ordenDireccion = $request->get('orden_direccion', 'asc');

        switch ($ordenPor) {
            case 'fecha':
                $query->orderBy('proximo_mantenimiento', $ordenDireccion);
                break;
            case 'tipo':
                $query->orderBy('tipo', $ordenDireccion);
                break;
            case 'prioridad':
                $query->orderBy('prioridad', $ordenDireccion);
                break;
            case 'vehiculo':
                $query->join('carros', 'mantenimientos.carro_id', '=', 'carros.id')
                      ->orderBy('carros.marca', $ordenDireccion)
                      ->orderBy('carros.modelo', $ordenDireccion)
                      ->select('mantenimientos.*');
                break;
            case 'dias_restantes':
                $query->orderBy('dias_restantes', $ordenDireccion);
                break;
            default: // urgencia
                $query->ordenarPorUrgencia();
                break;
        }

        return $query;
    }

    /**
     * Completar mantenimiento
     */
    public function completar(Request $request, Mantenimiento $mantenimiento): JsonResponse
    {
        try {
            Log::info('Iniciando completar mantenimiento', [
                'mantenimiento_id' => $mantenimiento->id,
                'estado_actual' => $mantenimiento->estado,
                'request_data' => $request->all()
            ]);

            $validated = $request->validate([
                'fecha_completado' => 'required|date',
                'notas_completado' => 'nullable|string|max:500',
                'kilometraje_real' => 'nullable|integer|min:0'
            ]);

            Log::info('Validación completada, verificando si necesita mantenimiento recurrente', [
                'tipo' => $mantenimiento->tipo,
                'estado_actual' => $mantenimiento->estado
            ]);

            // Verificar si necesita generar recurrente ANTES de cambiar el estado
            $necesitaRecurrente = $this->debeGenerarMantenimientoRecurrente($mantenimiento);

            Log::info('Resultado verificación recurrente', [
                'necesita_recurrente' => $necesitaRecurrente,
                'tipo' => $mantenimiento->tipo
            ]);

            // Actualizar el mantenimiento a completado
            $mantenimiento->update([
                'estado' => 'completado',
                'fecha' => $validated['fecha_completado'], // Actualizar fecha real de servicio
                'notas' => $validated['notas_completado']
                    ? ($mantenimiento->notas ? $mantenimiento->notas . ' | ' : '') . 'Completado: ' . $validated['notas_completado']
                    : $mantenimiento->notas,
                'kilometraje_actual' => $validated['kilometraje_real'] ?? $mantenimiento->kilometraje_actual
            ]);

            // Generar siguiente mantenimiento recurrente automáticamente
            if ($necesitaRecurrente) {
                $this->generarMantenimientoRecurrente($mantenimiento);
            }

            Log::info('Mantenimiento completado exitosamente', [
                'mantenimiento_id' => $mantenimiento->id,
                'nuevo_estado' => $mantenimiento->estado
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mantenimiento completado exitosamente',
                'mantenimiento' => $mantenimiento->load('carro')
            ]);

        } catch (\Exception $e) {
            Log::error('Error completando mantenimiento: ' . $e->getMessage(), [
                'mantenimiento_id' => $mantenimiento->id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al completar el mantenimiento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Posponer mantenimiento
     */
    public function posponer(Request $request, Mantenimiento $mantenimiento): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nuevos_dias' => 'required|integer|min:1|max:365',
                'motivo' => 'nullable|string|max:255'
            ]);

            $nuevaFecha = Carbon::today('America/Hermosillo')->addDays($validated['nuevos_dias']);

            $mantenimiento->update([
                'proximo_mantenimiento' => $nuevaFecha,
                'notas' => $validated['motivo']
                    ? ($mantenimiento->notas ? $mantenimiento->notas . ' | ' : '') . 'Pospuesto: ' . $validated['motivo']
                    : $mantenimiento->notas
            ]);

            return response()->json([
                'success' => true,
                'message' => "Mantenimiento pospuesto {$validated['nuevos_dias']} días",
                'nueva_fecha' => $nuevaFecha->format('Y-m-d'),
                'mantenimiento' => $mantenimiento->load('carro')
            ]);

        } catch (\Exception $e) {
            Log::error('Error posponiendo mantenimiento: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al posponer el mantenimiento'
            ], 500);
        }
    }

    /**
     * Reprogramar mantenimiento
     */
    public function reprogramar(Request $request, Mantenimiento $mantenimiento): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nueva_fecha' => 'required|date|after:today',
                'nueva_prioridad' => 'nullable|in:baja,media,alta,critica',
                'nuevo_tipo' => 'nullable|string|max:100',
                'motivo' => 'nullable|string|max:255'
            ]);

            $mantenimiento->update([
                'proximo_mantenimiento' => $validated['nueva_fecha'],
                'prioridad' => $validated['nueva_prioridad'] ?? $mantenimiento->prioridad,
                'tipo' => $validated['nuevo_tipo'] ?? $mantenimiento->tipo,
                'notas' => $validated['motivo']
                    ? ($mantenimiento->notas ? $mantenimiento->notas . ' | ' : '') . 'Reprogramado: ' . $validated['motivo']
                    : $mantenimiento->notas
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mantenimiento reprogramado exitosamente',
                'mantenimiento' => $mantenimiento->load('carro')
            ]);

        } catch (\Exception $e) {
            Log::error('Error reprogramando mantenimiento: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al reprogramar el mantenimiento'
            ], 500);
        }
    }

    /**
     * Verificar si un mantenimiento debe generar uno recurrente
     */
    private function debeGenerarMantenimientoRecurrente(Mantenimiento $mantenimiento): bool
    {
        // Solo generar recurrente para ciertos tipos de servicio
        $tiposRecurrentes = [
            'Cambio de aceite',
            'Revisión periódica',
            'Alineación y balanceo',
            'Cambio de filtros'
        ];

        if (!in_array($mantenimiento->tipo, $tiposRecurrentes)) {
            return false;
        }

        // Verificar que tenga fecha de próximo mantenimiento y que esté vencido
        if (!$mantenimiento->proximo_mantenimiento) {
            return false;
        }

        // Solo generar si el mantenimiento está vencido o próximo a vencer
        $hoy = Carbon::today('America/Hermosillo');
        $proximo = Carbon::parse($mantenimiento->proximo_mantenimiento, 'America/Hermosillo');

        return $proximo->lessThanOrEqualTo($hoy);
    }

    /**
     * Generar mantenimiento recurrente automáticamente
     */
    private function generarMantenimientoRecurrente(Mantenimiento $mantenimiento)
    {
        try {
            // Calcular intervalo basado en el tipo de servicio
            $intervaloDias = $this->getIntervaloRecurrente($mantenimiento->tipo);

            if ($intervaloDias <= 0) {
                Log::info('Intervalo no válido para mantenimiento recurrente: ' . $mantenimiento->tipo);
                return;
            }

            // Crear el siguiente mantenimiento
            $nuevaFechaServicio = Carbon::today('America/Hermosillo');
            $nuevaFechaProximo = $nuevaFechaServicio->copy()->addDays($intervaloDias);

            $nuevoMantenimiento = Mantenimiento::create([
                'carro_id' => $mantenimiento->carro_id,
                'tipo' => $mantenimiento->tipo,
                'fecha' => $nuevaFechaServicio->format('Y-m-d'),
                'proximo_mantenimiento' => $nuevaFechaProximo->format('Y-m-d'),
                'descripcion' => $mantenimiento->descripcion,
                'notas' => 'Generado automáticamente desde mantenimiento anterior ID: ' . $mantenimiento->id,
                'costo' => $this->getCostoSugerido($mantenimiento->tipo),
                'estado' => 'pendiente',
                'kilometraje_actual' => $mantenimiento->carro->kilometraje ?? $mantenimiento->kilometraje_actual,
                'prioridad' => $mantenimiento->prioridad,
                'dias_anticipacion_alerta' => $mantenimiento->dias_anticipacion_alerta,
                'requiere_aprobacion' => $mantenimiento->requiere_aprobacion,
                'observaciones_alerta' => $mantenimiento->observaciones_alerta,
            ]);

            Log::info('Mantenimiento recurrente generado exitosamente', [
                'anterior_id' => $mantenimiento->id,
                'nuevo_id' => $nuevoMantenimiento->id,
                'tipo' => $mantenimiento->tipo,
                'intervalo_dias' => $intervaloDias
            ]);

        } catch (\Exception $e) {
            Log::error('Error generando mantenimiento recurrente: ' . $e->getMessage(), [
                'mantenimiento_id' => $mantenimiento->id,
                'tipo' => $mantenimiento->tipo
            ]);
        }
    }

    /**
     * Obtener intervalo en días para mantenimientos recurrentes
     */
    private function getIntervaloRecurrente(string $tipo): int
    {
        return match ($tipo) {
            'Cambio de aceite' => 180, // 6 meses
            'Revisión periódica' => 365, // 1 año
            'Alineación y balanceo' => 180, // 6 meses
            'Cambio de filtros' => 365, // 1 año
            default => 0,
        };
    }

    /**
     * Obtener costo sugerido por tipo de servicio
     */
    private function getCostoSugerido(string $tipo): float
    {
        return match ($tipo) {
            'Cambio de aceite' => 800.00,
            'Revisión periódica' => 1200.00,
            'Alineación y balanceo' => 800.00,
            'Cambio de filtros' => 400.00,
            default => 0.00,
        };
    }

    /**
     * Obtener mantenimientos próximos a vencer
     */
    private function getMantenimientosProximosAVencer(int $dias = 30)
    {
        return Mantenimiento::where('proximo_mantenimiento', '<=', now()->addDays($dias))
            ->where('estado', '!=', Mantenimiento::ESTADO_COMPLETADO);
    }

    /**
     * Obtener tipos de mantenimiento disponibles
     */
    private function getTiposMantenimiento(): array
    {
        return [
            'Cambio de aceite',
            'Revisión periódica',
            'Servicio de frenos',
            'Servicio de llantas',
            'Servicio de batería',
            'Servicio de motor',
            'Revisión de luces',
            'Alineación y balanceo',
            'Cambio de filtros',
            'Revisión de transmisión',
            'Otro servicio'
        ];
    }
}

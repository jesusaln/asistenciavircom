<?php

namespace App\Http\Controllers;

use App\Models\Mantenimiento;
use App\Http\Requests\StoreMantenimientoRequest;
use App\Http\Requests\UpdateMantenimientoRequest;
use App\Models\Carro;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Services\Mantenimiento\MantenimientoStatsService;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MantenimientoController extends Controller
{
    protected $statsService;

    public function __construct(MantenimientoStatsService $statsService)
    {
        $this->statsService = $statsService;
    }
    /**
     * Obtener servicios existentes de un vehículo por tipo
     */
    public function getServiciosPorTipo(Request $request, int $carro, string $tipoServicio): JsonResponse
    {
        try {
            $tipoServicio = urldecode($tipoServicio);
            // Buscar mantenimientos del vehículo con el tipo específico
            $servicios = Mantenimiento::where('carro_id', $carro)
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
        return Mantenimiento::DIAS_MINIMOS_ENTRE_SERVICIOS[$tipo] ?? 30;
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
        return $this->statsService->getEstadoMetadata($mantenimiento);
    }

    /**
     * Obtener estadísticas de mantenimientos por estado
     */
    public function getEstadisticasMantenimientos(): JsonResponse
    {
        try {
            return response()->json($this->statsService->getConsolidatedStats());
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
            $carros = Carro::query()
                ->select('id', 'marca', 'modelo', 'anio', 'placa', 'kilometraje')
                ->where('activo', true)
                ->orderBy('marca')
                ->orderBy('modelo')
                ->get();

            return Inertia::render('Mantenimientos/Edit', [
                'mantenimiento' => $mantenimiento,
                'carros' => $carros,
                'tiposMantenimiento' => $this->getTiposMantenimiento(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error editando mantenimiento: ' . $e->getMessage());
            return Inertia::render('Mantenimientos/Edit', [
                'mantenimiento' => null,
                'carros' => [],
                'tiposMantenimiento' => [],
                'error' => 'Error al cargar el formulario de edición'
            ]);
        }
    }

    /**
     * Actualizar mantenimiento
     */
    public function update(UpdateMantenimientoRequest $request, Mantenimiento $mantenimiento): \Illuminate\Http\RedirectResponse
    {
        try {
            $validated = $this->normalizeMantenimientoPayload($request->validated());

            $mantenimiento->update($validated);
            $this->syncCarroKilometrajeIfNeeded($mantenimiento->fresh());

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
            $validated = $this->normalizeMantenimientoPayload($request->validated());
            $validated['estado'] = $validated['estado'] ?? Mantenimiento::ESTADO_PENDIENTE;

            $mantenimiento = Mantenimiento::create($validated);
            $this->syncCarroKilometrajeIfNeeded($mantenimiento);

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
            $carros = Carro::query()
                ->select('id', 'marca', 'modelo', 'anio', 'placa', 'kilometraje')
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
            // 1. Iniciar consulta base
            $query = Mantenimiento::with('carro');

            // 2. Aplicar filtros avanzados
            $this->aplicarFiltros($query, $request);

            // 3. Clonar consulta para estadísticas (antes de paginar y ordenar)
            $statsQuery = clone $query;
            $estadisticasPanel = $this->statsService->getConsolidatedStats($statsQuery);

            // 4. Ordenamiento
            $sortBy = $request->input('sort_by', 'fecha');
            $sortDirection = $request->input('sort_direction', 'desc');
            $validSortFields = ['fecha', 'tipo', 'costo', 'estado', 'created_at', 'prioridad', 'proximo_mantenimiento'];
            
            if (!in_array($sortBy, $validSortFields)) {
                $sortBy = 'fecha';
            }

            if ($sortBy === 'fecha') {
                $query->orderBy('fecha', 'desc');
            } else {
                $query->orderBy($sortBy, $sortDirection);
            }
            
            // Ordenamiento secundario para estabilidad
            $query->orderBy('id', 'desc');

            // 5. Paginación
            $perPage = (int) $request->input('per_page', 10);
            $perPage = ($perPage > 0 && $perPage <= 100) ? $perPage : 10;
            
            $mantenimientos = $query->paginate($perPage)->appends($request->query());

            // 6. Enriquecer cada mantenimiento con metadatos de estado
            $mantenimientos->getCollection()->transform(function ($mantenimiento) {
                $mantenimiento->estado_metadata = $this->statsService->getEstadoMetadata($mantenimiento);
                $mantenimiento->dias_restantes = $mantenimiento->estado_metadata['dias_restantes'];
                return $mantenimiento;
            });

            // 7. Datos auxiliares
            $filtros = $request->only(['search', 'estado', 'tipo', 'carro_id', 'prioridad', 'fecha_desde', 'fecha_hasta']);
            $carros = Carro::query()->where('activo', true)
                ->orderBy('marca', 'asc')
                ->orderBy('modelo', 'asc')
                ->get();

            return Inertia::render('Mantenimientos/Index', [
                'mantenimientos' => $mantenimientos,
                'estadisticas' => $estadisticasPanel,
                'stats' => $estadisticasPanel, // Mantener ambos por compatibilidad
                'filters' => $filtros,
                'filtros' => $filtros,
                'sorting' => ['sort_by' => $sortBy, 'sort_direction' => $sortDirection],
                'carros' => $carros,
                'tiposMantenimiento' => $this->getTiposMantenimiento(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error en MantenimientoController@index: ' . $e->getMessage());
            return Inertia::render('Mantenimientos/Index', [
                'mantenimientos' => ['data' => [], 'total' => 0],
                'estadisticas' => [],
                'filters' => [],
                'carros' => [],
                'tiposMantenimiento' => [],
                'error' => 'Error al cargar los mantenimientos: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * CSV simple de mantenimientos (respeta filtros del índice).
     */
    public function export(Request $request): StreamedResponse
    {
        $query = Mantenimiento::query()->with('carro')->orderByDesc('fecha');
        $this->aplicarFiltros($query, $request);

        $filename = 'mantenimientos_flota_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['ID', 'Folio', 'Vehículo', 'Placa', 'Tipo', 'Fecha servicio', 'Próximo', 'Estado', 'KM registro', 'Costo']);
            foreach ($query->cursor() as $m) {
                $v = $m->carro;
                fputcsv($out, [
                    $m->id,
                    $m->folio ?? '',
                    $v ? trim(($v->marca ?? '') . ' ' . ($v->modelo ?? '')) : '',
                    $v->placa ?? '',
                    $m->tipo,
                    $m->fecha?->format('Y-m-d') ?? '',
                    $m->proximo_mantenimiento?->format('Y-m-d') ?? '',
                    $m->estado,
                    $m->kilometraje_actual,
                    $m->costo,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }



    /**
     * Totales para el panel (vencido / por vencer / al día según reglas de negocio).
     */
    private function estadisticasParaPanel(): array
    {
        return $this->statsService->getConsolidatedStats();
    }

    private function normalizeMantenimientoPayload(array $data): array
    {
        if (($data['tipo'] ?? '') === 'Otro servicio' && !empty($data['otro_servicio'])) {
            $suffix = trim((string) $data['otro_servicio']);
            $existing = trim((string) ($data['descripcion'] ?? ''));
            $data['descripcion'] = $existing === ''
                ? $suffix
                : $existing . ' — ' . $suffix;
        }
        unset($data['otro_servicio']);

        return $data;
    }

    private function syncCarroKilometrajeIfNeeded(Mantenimiento $mantenimiento): void
    {
        if (!$mantenimiento->carro_id || $mantenimiento->kilometraje_actual === null) {
            return;
        }

        $carro = Carro::query()->find($mantenimiento->carro_id);
        if (!$carro) {
            return;
        }

        $km = (int) $mantenimiento->kilometraje_actual;
        if ($km >= (int) $carro->kilometraje) {
            $carro->update(['kilometraje' => $km]);
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
            $search = $request->input('search');
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
            switch ($request->input('estado')) {
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
            $query->where('tipo', $request->input('tipo'));
        }

        // Filtro por prioridad
        if ($request->filled('prioridad')) {
            $query->where('prioridad', $request->input('prioridad'));
        }

        // Filtro por vehículo
        if ($request->filled('carro_id')) {
            $query->where('carro_id', $request->input('carro_id'));
        }

        // Filtro por rango de fechas
        if ($request->filled('fecha_desde')) {
            $query->where('proximo_mantenimiento', '>=', $request->input('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('proximo_mantenimiento', '<=', $request->input('fecha_hasta'));
        }

        return $query;
    }

    /**
     * Completar mantenimiento (solo cierra el actual, no genera siguiente)
     */
    public function completar(Request $request, Mantenimiento $mantenimiento): JsonResponse
    {
        try {
            $validated = $request->validate([
                'fecha_completado' => 'required|date',
                'notas_completado' => 'nullable|string|max:500',
                'kilometraje_real' => 'nullable|integer|min:0'
            ]);

            $mantenimiento->update([
                'estado' => Mantenimiento::ESTADO_COMPLETADO,
                'fecha' => $validated['fecha_completado'],
                'notas' => ($validated['notas_completado'] ?? null)
                    ? ($mantenimiento->notas ? $mantenimiento->notas . ' | ' : '') . 'Completado: ' . $validated['notas_completado']
                    : $mantenimiento->notas,
                'kilometraje_actual' => $validated['kilometraje_real'] ?? $mantenimiento->kilometraje_actual
            ]);

            $mantenimiento->refresh();
            $this->syncCarroKilometrajeIfNeeded($mantenimiento);

            $esRecurrente = in_array($mantenimiento->tipo, Mantenimiento::TIPOS_RECURRENTES);
            $sugerenciaProximo = null;
            if ($esRecurrente) {
                $intervaloDias = Mantenimiento::INTERVALOS_RECURRENTES[$mantenimiento->tipo] ?? null;
                if ($intervaloDias) {
                    $sugerenciaProximo = now()->addDays($intervaloDias)->format('Y-m-d');
                }
            }

            return response()->json([
                'success' => true,
                'message' => $esRecurrente
                    ? 'Mantenimiento completado. No olvides programar el siguiente.'
                    : 'Mantenimiento completado exitosamente',
                'mantenimiento' => $mantenimiento->load('carro'),
                'requiere_proximo' => $esRecurrente,
                'sugerencia_proximo' => $sugerenciaProximo,
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
     * Programar siguiente mantenimiento (separado de completar)
     */
    public function programarSiguiente(Request $request, Mantenimiento $mantenimiento): JsonResponse
    {
        try {
            if ($mantenimiento->estado !== Mantenimiento::ESTADO_COMPLETADO) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se puede programar el siguiente mantenimiento de uno completado.'
                ], 422);
            }

            $validated = $request->validate([
                'proximo_mantenimiento' => 'required|date|after:today',
                'costo' => 'nullable|numeric|min:0',
            ]);

            $intervaloDias = $this->getIntervaloRecurrente($mantenimiento->tipo);
            $intervaloKm = Mantenimiento::intervaloKmRecurrente($mantenimiento->tipo);

            $proximoKm = $intervaloKm > 0
                ? ($mantenimiento->kilometraje_actual ?? 0) + $intervaloKm
                : null;

            $nuevoMantenimiento = Mantenimiento::create([
                'carro_id' => $mantenimiento->carro_id,
                'tipo' => $mantenimiento->tipo,
                'fecha' => now()->format('Y-m-d'),
                'fecha_programada' => now()->format('Y-m-d'),
                'proximo_mantenimiento' => $validated['proximo_mantenimiento'],
                'proximo_kilometraje' => $proximoKm,
                'descripcion' => $mantenimiento->descripcion,
                'notas' => 'Generado desde mantenimiento anterior ID: ' . $mantenimiento->id,
                'costo' => $validated['costo'] ?? $this->getCostoSugerido($mantenimiento->tipo),
                'estado' => Mantenimiento::ESTADO_PENDIENTE,
                'kilometraje_actual' => $mantenimiento->kilometraje_actual,
                'prioridad' => $mantenimiento->prioridad,
                'dias_anticipacion_alerta' => $mantenimiento->dias_anticipacion_alerta,
                'requiere_aprobacion' => $mantenimiento->requiere_aprobacion,
                'observaciones_alerta' => $mantenimiento->observaciones_alerta,
            ]);

            Log::info('Siguiente mantenimiento programado', [
                'anterior_id' => $mantenimiento->id,
                'nuevo_id' => $nuevoMantenimiento->id,
                'proximo_km' => $proximoKm,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Siguiente mantenimiento programado exitosamente',
                'mantenimiento' => $nuevoMantenimiento->load('carro'),
            ]);

        } catch (\Exception $e) {
            Log::error('Error programando siguiente mantenimiento: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al programar el siguiente mantenimiento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar mantenimiento
     */
    public function cancelar(Request $request, Mantenimiento $mantenimiento): JsonResponse
    {
        try {
            $validated = $request->validate([
                'motivo' => 'nullable|string|max:500',
            ]);

            $mantenimiento->cancelar($validated['motivo'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Mantenimiento cancelado exitosamente',
            ]);

        } catch (\Exception $e) {
            Log::error('Error cancelando mantenimiento: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar el mantenimiento'
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
                'nueva_fecha' => 'required|date|after_or_equal:today',
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
     * Obtener intervalo en días para mantenimientos recurrentes
     */
    private function getIntervaloRecurrente(string $tipo): int
    {
        return Mantenimiento::INTERVALOS_RECURRENTES[$tipo] ?? 0;
    }

    /**
     * Obtener costo sugerido por tipo de servicio
     */
    private function getCostoSugerido(string $tipo): float
    {
        return Mantenimiento::COSTOS_SUGERIDOS[$tipo] ?? 0.00;
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
        return Mantenimiento::TIPOS;
    }
}

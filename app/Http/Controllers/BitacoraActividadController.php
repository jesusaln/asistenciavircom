<?php

namespace App\Http\Controllers;

use App\Models\BitacoraActividad;
use App\Models\Cliente;
use App\Models\User;
use App\Http\Requests\StoreBitacoraRequest;
use App\Http\Requests\UpdateBitacoraRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class BitacoraActividadController extends Controller
{
    const TIPOS = ['soporte', 'mantenimiento', 'instalacion', 'cotizacion', 'visita', 'administrativo', 'otro'];
    // ✅ AHORA EN ESPAÑOL — para que coincida con Requests, Modelo y BD
    const ESTADOS = ['pendiente', 'en_proceso', 'completado', 'cancelado'];

    public function index(Request $request)
    {
        try {
            $filters = $request->only(['q', 'usuario', 'cliente', 'desde', 'hasta', 'tipo', 'estado']);

            Log::info('Filtros recibidos:', $filters);

            $estadoSeleccionado = $filters['estado'] ?? '';
            $estadosAMostrar = $this->determinarEstadosAMostrar($estadoSeleccionado);

            Log::info('Estados a mostrar:', $estadosAMostrar);

            [$desde, $hasta] = $this->validarFechas($filters['desde'] ?? null, $filters['hasta'] ?? null);

            $query = BitacoraActividad::with(['usuario:id,name', 'cliente:id,nombre_razon_social'])
                ->whereIn('estado', $estadosAMostrar);

            $query->rangoFechas($desde, $hasta)
                ->deUsuario($filters['usuario'] ?? null)
                ->deCliente($filters['cliente'] ?? null)
                ->buscar($filters['q'] ?? null)
                ->when($filters['tipo'] ?? null, fn($q, $v) => $q->where('tipo', $v));

            Log::info('SQL generado:', [$query->toSql(), $query->getBindings()]);

            // Paginación con per_page configurable
            $perPage = min((int) $request->input('per_page', 15), 50);
            $actividades = $query->orderByDesc('fecha')
                ->orderByDesc('id')
                ->paginate($perPage)
                ->withQueryString();

            Log::info('Total de actividades encontradas:', ['total' => $actividades->total()]);

            // Estadísticas
            $stats = [
                'total' => BitacoraActividad::count(),
                'pendientes' => BitacoraActividad::where('estado', 'pendiente')->count(),
                'en_proceso' => BitacoraActividad::where('estado', 'en_proceso')->count(),
                'completados' => BitacoraActividad::where('estado', 'completado')->count(),
                'cancelados' => BitacoraActividad::where('estado', 'cancelado')->count(),
                'costo_total_mes' => BitacoraActividad::whereMonth('fecha', now()->month)->sum('costo_mxn'),
                'actividades_mes' => BitacoraActividad::whereMonth('fecha', now()->month)->count(),
            ];

            return Inertia::render('Bitacora/Index', [
                'actividades' => $actividades,
                'stats' => $stats,
                'filters' => $filters,
                'sorting' => ['sort_by' => 'fecha', 'sort_direction' => 'desc'],
                'usuarios' => $this->getUsuarios(),
                'clientes' => $this->getClientes(),
                'tipos' => self::TIPOS,
                'estados' => self::ESTADOS,
            ]);
        } catch (\Exception $e) {
            Log::error('Error en BitacoraActividadController@index: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar las actividades.');
        }
    }

    /**
     * Determina qué estados mostrar según la selección del usuario
     */
    private function determinarEstadosAMostrar(string $estadoSeleccionado): array
    {
        switch ($estadoSeleccionado) {
            case '':
            case 'todos':
                return self::ESTADOS;

            case 'completado':
                return ['completado'];

            case 'cancelado':
                return ['cancelado'];

            case 'pendiente':
                return ['pendiente'];

            case 'en_proceso':
                return ['en_proceso'];

            default:
                // Vista por defecto: mostrar todas las actividades
                return self::ESTADOS;
        }
    }

    /**
     * Determina si debe aplicar el filtro de "solo hoy + mantener en proceso"
     */
    private function debeAplicarFiltroHoy(array $filters): bool
    {
        $sinFiltrosFecha = empty($filters['desde']) && empty($filters['hasta']);
        $sinEstadoEspecifico = empty($filters['estado']);

        return $sinFiltrosFecha && $sinEstadoEspecifico;
    }

    /**
     * Valida y normaliza fechas de entrada
     */
    private function validarFechas(?string $desde, ?string $hasta): array
    {
        try {
            $desde = $desde ? Carbon::parse($desde)->toDateString() : null;
            $hasta = $hasta ? Carbon::parse($hasta)->toDateString() : null;
            if ($desde && $hasta && $desde > $hasta) {
                [$desde, $hasta] = [$hasta, $desde];
            }
        } catch (\Exception $e) {
            $desde = $hasta = null;
        }
        return [$desde, $hasta];
    }

    public function create()
    {
        return Inertia::render('Bitacora/Create', [
            'usuarios' => $this->getUsuarios(),
            'clientes' => $this->getClientes(),
            'tipos'    => self::TIPOS,
            'estados'  => self::ESTADOS,
        ]);
    }

    public function store(StoreBitacoraRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->setFechaHora($data);
        $data['adjuntos'] = $data['adjuntos'] ?? [];

        BitacoraActividad::create($data);

        return redirect()
            ->route('bitacora.index')
            ->with('success', 'Actividad registrada correctamente.');
    }

    public function show(BitacoraActividad $bitacora)
    {
        $bitacora->load(['usuario:id,name', 'cliente:id,nombre_razon_social']);
        return Inertia::render('Bitacora/Show', [
            'actividad' => $bitacora,
        ]);
    }

    public function edit(BitacoraActividad $bitacora)
    {
        return Inertia::render('Bitacora/Edit', [
            'actividad' => $bitacora->only([
                'id',
                'titulo',
                'user_id',
                'cliente_id',
                'tipo',
                'estado',
                'inicio_at',
                'fin_at',
                'fecha',
                'hora',
                'ubicacion',
                'descripcion',
                'prioridad',
                'adjuntos',
                'es_facturable',
                'costo_mxn'
            ]),
            'usuarios' => $this->getUsuarios(),
            'clientes' => $this->getClientes(),
            'tipos'    => self::TIPOS,
            'estados'  => self::ESTADOS,
        ]);
    }

    public function update(UpdateBitacoraRequest $request, BitacoraActividad $bitacora): RedirectResponse
    {
        $data = $request->validated();
        $this->setFechaHora($data);
        $data['adjuntos'] = $data['adjuntos'] ?? $bitacora->adjuntos ?? [];

        $bitacora->update($data);

        return redirect()
            ->route('bitacora.index')
            ->with('success', 'Actividad actualizada correctamente.');
    }

    public function destroy(BitacoraActividad $bitacora): RedirectResponse
    {
        $bitacora->delete();

        return redirect()
            ->back()
            ->with('success', 'Actividad eliminada.');
    }

    /**
     * Cambiar estado de una actividad rápidamente (desde Panel u otras vistas)
     */
    public function cambiarEstado(Request $request, BitacoraActividad $bitacora): RedirectResponse
    {
        $request->validate([
            'estado' => ['required', 'in:pendiente,en_proceso,completado,cancelado'],
        ]);

        $bitacora->update([
            'estado' => $request->estado,
            'fin_at' => $request->estado === 'completado' ? now() : $bitacora->fin_at,
        ]);

        // Limpiar cache del panel si está asignada a alguien
        if ($bitacora->asignado_id) {
            \Cache::forget("panel_bitacora_pendientes_{$bitacora->asignado_id}");
        }

        return redirect()
            ->back()
            ->with('success', 'Estado actualizado correctamente.');
    }

    protected function getUsuarios()
    {
        return User::select('id', 'name')->orderBy('name')->get();
    }

    protected function getClientes()
    {
        return Cliente::select('id', 'nombre_razon_social')->orderBy('nombre_razon_social')->limit(500)->get();
    }

    private function setFechaHora(array &$data)
    {
        if (!empty($data['inicio_at'])) {
            $inicio = Carbon::parse($data['inicio_at']);
            $data['fecha'] = $inicio->toDateString();
            $data['hora'] = $inicio->format('H:i');
        }
    }

    // Exportar actividades a CSV
    public function export(Request $request)
    {
        try {
            $filters = $request->only(['q', 'usuario', 'cliente', 'desde', 'hasta', 'tipo', 'estado']);

            $estadoSeleccionado = $filters['estado'] ?? '';
            $estadosAMostrar = $this->determinarEstadosAMostrar($estadoSeleccionado);

            $aplicarFiltroHoy = $this->debeAplicarFiltroHoy($filters);
            [$desde, $hasta] = $this->validarFechas($filters['desde'] ?? null, $filters['hasta'] ?? null);

            $query = BitacoraActividad::with(['usuario:id,name', 'cliente:id,nombre_razon_social'])
                ->whereIn('estado', $estadosAMostrar);

            if ($aplicarFiltroHoy) {
                $query->soloHoyOMantenerEnProceso();
            }

            $query->rangoFechas($desde, $hasta)
                ->deUsuario($filters['usuario'] ?? null)
                ->deCliente($filters['cliente'] ?? null)
                ->buscar($filters['q'] ?? null)
                ->when($filters['tipo'] ?? null, fn($q, $v) => $q->where('tipo', $v));

            $actividades = $query->orderByDesc('fecha')->orderByDesc('id')->get();

            $filename = 'bitacora_actividades_' . date('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($actividades) {
                $file = fopen('php://output', 'w');

                fputcsv($file, [
                    'ID',
                    'Título',
                    'Usuario',
                    'Cliente',
                    'Tipo',
                    'Estado',
                    'Fecha',
                    'Hora',
                    'Ubicación',
                    'Prioridad',
                    'Es Facturable',
                    'Costo MXN',
                    'Fecha Creación'
                ]);

                foreach ($actividades as $actividad) {
                    fputcsv($file, [
                        $actividad->id,
                        $actividad->titulo,
                        $actividad->usuario?->name ?? 'N/A',
                        $actividad->cliente?->nombre_razon_social ?? 'N/A',
                        $actividad->tipo,
                        $actividad->estado,
                        $actividad->fecha?->format('d/m/Y'),
                        $actividad->hora_fmt ?? 'N/A',
                        $actividad->ubicacion ?? '',
                        $actividad->prioridad ?? '',
                        $actividad->es_facturable ? 'Sí' : 'No',
                        $actividad->costo_mxn ?? 0,
                        $actividad->created_at?->format('d/m/Y H:i:s')
                    ]);
                }
                fclose($file);
            };

            Log::info('Exportación de actividades', ['total' => $actividades->count()]);

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Error en exportación de actividades: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al exportar las actividades.');
        }
    }
}

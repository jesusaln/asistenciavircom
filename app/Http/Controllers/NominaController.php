<?php

namespace App\Http\Controllers;

use App\Models\Nomina;
use App\Models\NominaConcepto;
use App\Models\User;
use App\Models\CatalogoConceptoNomina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use Inertia\Inertia;
use Carbon\Carbon;

class NominaController extends BaseController
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->middleware(['auth', 'role:admin|super-admin']);
    }

    /**
     * Lista de nóminas con filtros
     */
    public function index(Request $request)
    {
        $query = Nomina::with(['empleado:id,name,email']);

        // Filtro por empleado
        if ($request->filled('empleado_id')) {
            $query->where('empleado_id', $request->empleado_id);
        }

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Filtro por tipo de período
        if ($request->filled('tipo_periodo')) {
            $query->where('tipo_periodo', $request->tipo_periodo);
        }

        // Filtro por año
        $anio = $request->input('anio', now()->year);
        $query->where('anio', $anio);

        // Filtro por rango de fechas
        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('periodo_inicio', [$request->fecha_inicio, $request->fecha_fin]);
        }

        // Ordenamiento
        $sortBy = $request->input('sort_by', 'periodo_inicio');
        $sortDir = $request->input('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = min($request->input('per_page', 15), 100);
        $nominas = $query->paginate($perPage)->withQueryString();

        // Estadísticas del período
        $estadisticas = [
            'total' => Nomina::where('anio', $anio)->count(),
            'borradores' => Nomina::where('anio', $anio)->where('estado', 'borrador')->count(),
            'procesadas' => Nomina::where('anio', $anio)->where('estado', 'procesada')->count(),
            'pagadas' => Nomina::where('anio', $anio)->where('estado', 'pagada')->count(),
            'monto_total_pagado' => Nomina::where('anio', $anio)->where('estado', 'pagada')->sum('total_neto'),
            'monto_pendiente' => Nomina::where('anio', $anio)->where('estado', 'procesada')->sum('total_neto'),
        ];

        // Lista de empleados para filtro
        $empleados = User::empleados()
            ->select('id', 'name')
            ->activos()
            ->get()
            ->map(fn($e) => ['value' => $e->id, 'label' => $e->name]);

        // Años disponibles
        $aniosDisponibles = Nomina::selectRaw('DISTINCT anio')
            ->orderBy('anio', 'desc')
            ->pluck('anio');

        if ($aniosDisponibles->isEmpty()) {
            $aniosDisponibles = collect([now()->year]);
        }

        return Inertia::render('Nominas/Index', [
            'nominas' => $nominas,
            'estadisticas' => $estadisticas,
            'empleados' => $empleados,
            'aniosDisponibles' => $aniosDisponibles,
            'anioActual' => $anio,
            'filters' => $request->only(['empleado_id', 'estado', 'tipo_periodo', 'anio']),
            'sorting' => [
                'sort_by' => $sortBy,
                'sort_direction' => $sortDir,
            ],
        ]);
    }

    /**
     * Formulario para crear nómina
     */
    public function create(Request $request)
    {
        $empleados = User::empleados()
            ->select('id', 'name', 'salario_base', 'numero_empleado')
            ->activos()
            ->whereNotNull('salario_base')
            ->get()
            ->map(fn($e) => [
                'value' => $e->id,
                'label' => $e->name,
                'salario_base' => $e->salario_base,
                'numero_empleado' => $e->numero_empleado,
            ]);

        $catalogoPercepciones = CatalogoConceptoNomina::percepcionesParaSelect();
        $catalogoDeducciones = CatalogoConceptoNomina::deduccionesParaSelect();

        // Si viene con empleado preseleccionado
        // Si viene con empleado preseleccionado
        $empleadoPreseleccionado = null;
        if ($request->filled('empleado_id')) {
            $empleadoPreseleccionado = User::empleados()->select('id', 'name')->find($request->empleado_id);
        }

        return Inertia::render('Nominas/Create', [
            'empleados' => $empleados,
            'catalogoPercepciones' => $catalogoPercepciones,
            'catalogoDeducciones' => $catalogoDeducciones,
            'empleadoPreseleccionado' => $empleadoPreseleccionado,
            'tiposPeriodo' => [
                ['value' => 'semanal', 'label' => 'Semanal'],
                ['value' => 'quincenal', 'label' => 'Quincenal'],
                ['value' => 'mensual', 'label' => 'Mensual'],
            ],
        ]);
    }

    /**
     * Guardar nueva nómina
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'empleado_id' => 'required|exists:users,id',
            'periodo_inicio' => 'required|date',
            'periodo_fin' => 'required|date|after_or_equal:periodo_inicio',
            'tipo_periodo' => 'required|in:semanal,quincenal,mensual',
            'dias_trabajados' => 'nullable|numeric|min:0|max:31',
            'horas_extra' => 'nullable|numeric|min:0',
            'monto_horas_extra' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string|max:1000',
            'conceptos' => 'nullable|array',
            'conceptos.*.tipo' => 'required_with:conceptos|in:percepcion,deduccion',
            'conceptos.*.concepto' => 'required_with:conceptos|string|max:100',
            'conceptos.*.monto' => 'required_with:conceptos|numeric|min:0',
            'conceptos.*.clave' => 'nullable|string|max:10',
            'conceptos.*.clave_sat' => 'nullable|string|max:10',
        ]);

        try {
            DB::beginTransaction();

            // Verificar que no exista nómina duplicada para el período
            $existe = Nomina::where('empleado_id', $validated['empleado_id'])
                ->where('periodo_inicio', $validated['periodo_inicio'])
                ->where('periodo_fin', $validated['periodo_fin'])
                ->exists();

            if ($existe) {
                return back()->withErrors([
                    'error' => 'Ya existe una nómina para este empleado en el período seleccionado.'
                ])->withInput();
            }

            $empleado = User::findOrFail($validated['empleado_id']);

            // Calcular salario del período
            $salarioBase = $empleado->calcularSalarioPeriodo($validated['tipo_periodo']);

            // Calcular número de período
            $periodoInicio = Carbon::parse($validated['periodo_inicio']);
            $numeroPeriodo = $this->calcularNumeroPeriodo($periodoInicio, $validated['tipo_periodo']);

            $nomina = Nomina::create([
                'empleado_id' => $validated['empleado_id'],
                'periodo_inicio' => $validated['periodo_inicio'],
                'periodo_fin' => $validated['periodo_fin'],
                'tipo_periodo' => $validated['tipo_periodo'],
                'numero_periodo' => $numeroPeriodo,
                'anio' => $periodoInicio->year,
                'salario_base' => $salarioBase,
                'dias_trabajados' => $validated['dias_trabajados'] ?? $this->calcularDiasPeriodo($validated['tipo_periodo']),
                'horas_extra' => $validated['horas_extra'] ?? 0,
                'monto_horas_extra' => $validated['monto_horas_extra'] ?? 0,
                'estado' => 'borrador',
                'creado_por' => Auth::id(),
                'notas' => $validated['notas'],
            ]);

            // Generar conceptos automáticos (sueldo, préstamos)
            $nomina->generarConceptosAutomaticos();

            // Agregar conceptos adicionales del formulario
            if (!empty($validated['conceptos'])) {
                foreach ($validated['conceptos'] as $concepto) {
                    $nomina->conceptos()->create([
                        'tipo' => $concepto['tipo'],
                        'concepto' => $concepto['concepto'],
                        'clave' => $concepto['clave'] ?? null,
                        'clave_sat' => $concepto['clave_sat'] ?? null,
                        'monto' => $concepto['monto'],
                        'es_automatico' => false,
                    ]);
                }
            }

            $nomina->recalcularTotales();

            DB::commit();

            return redirect()->route('nominas.show', $nomina)
                ->with('success', 'Nómina creada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear nómina: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al crear la nómina.'])->withInput();
        }
    }

    /**
     * Ver detalle de nómina (recibo)
     */
    public function show(Nomina $nomina)
    {
        $nomina->load([
            'empleado:id,name,email,telefono',
            'conceptos' => fn($q) => $q->orderBy('tipo')->orderBy('id'),
            'creadoPor:id,name',
            'procesadoPor:id,name',
            'pagadoPor:id,name',
        ]);

        return Inertia::render('Nominas/Show', [
            'nomina' => $nomina,
        ]);
    }

    /**
     * Editar nómina (solo borradores)
     */
    public function edit(Nomina $nomina)
    {
        if (!$nomina->es_editable) {
            return back()->withErrors(['error' => 'Solo se pueden editar nóminas en estado borrador.']);
        }

        $nomina->load(['empleado:id,name', 'conceptos']);

        $catalogoPercepciones = CatalogoConceptoNomina::percepcionesParaSelect();
        $catalogoDeducciones = CatalogoConceptoNomina::deduccionesParaSelect();

        return Inertia::render('Nominas/Edit', [
            'nomina' => $nomina,
            'catalogoPercepciones' => $catalogoPercepciones,
            'catalogoDeducciones' => $catalogoDeducciones,
        ]);
    }

    /**
     * Actualizar nómina
     */
    public function update(Request $request, Nomina $nomina)
    {
        if (!$nomina->es_editable) {
            return back()->withErrors(['error' => 'Solo se pueden editar nóminas en estado borrador.']);
        }

        $validated = $request->validate([
            'dias_trabajados' => 'nullable|numeric|min:0|max:31',
            'horas_extra' => 'nullable|numeric|min:0',
            'monto_horas_extra' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string|max:1000',
            'conceptos' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            $nomina->update([
                'dias_trabajados' => $validated['dias_trabajados'] ?? $nomina->dias_trabajados,
                'horas_extra' => $validated['horas_extra'] ?? 0,
                'monto_horas_extra' => $validated['monto_horas_extra'] ?? 0,
                'notas' => $validated['notas'],
            ]);

            // Actualizar conceptos si se enviaron
            if (isset($validated['conceptos'])) {
                // Eliminar conceptos manuales existentes
                $nomina->conceptos()->where('es_automatico', false)->delete();

                // Agregar nuevos conceptos
                foreach ($validated['conceptos'] as $concepto) {
                    if (!empty($concepto['concepto']) && isset($concepto['monto'])) {
                        $nomina->conceptos()->create([
                            'tipo' => $concepto['tipo'],
                            'concepto' => $concepto['concepto'],
                            'clave' => $concepto['clave'] ?? null,
                            'clave_sat' => $concepto['clave_sat'] ?? null,
                            'monto' => $concepto['monto'],
                            'es_automatico' => false,
                        ]);
                    }
                }
            }

            $nomina->recalcularTotales();

            DB::commit();

            return redirect()->route('nominas.show', $nomina)
                ->with('success', 'Nómina actualizada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar nómina: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al actualizar la nómina.'])->withInput();
        }
    }

    /**
     * Procesar nómina (cambiar estado de borrador a procesada)
     */
    public function procesar(Nomina $nomina)
    {
        if ($nomina->estado !== 'borrador') {
            return back()->withErrors(['error' => 'Solo se pueden procesar nóminas en estado borrador.']);
        }

        try {
            $nomina->procesar(Auth::id());
            return back()->with('success', 'Nómina procesada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al procesar nómina: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al procesar la nómina.']);
        }
    }

    /**
     * Marcar nómina como pagada
     */
    public function pagar(Request $request, Nomina $nomina)
    {
        if ($nomina->estado !== 'procesada') {
            return back()->withErrors(['error' => 'Solo se pueden pagar nóminas procesadas.']);
        }

        $validated = $request->validate([
            'metodo_pago' => 'nullable|string|max:50',
            'referencia_pago' => 'nullable|string|max:100',
        ]);

        try {
            $nomina->marcarPagada(
                Auth::id(),
                $validated['metodo_pago'] ?? 'transferencia',
                $validated['referencia_pago'] ?? null
            );
            return back()->with('success', 'Nómina marcada como pagada.');
        } catch (\Exception $e) {
            Log::error('Error al marcar nómina como pagada: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al marcar la nómina como pagada.']);
        }
    }

    /**
     * Cancelar nómina
     */
    public function destroy(Nomina $nomina)
    {
        if ($nomina->estado === 'pagada') {
            return back()->withErrors(['error' => 'No se pueden cancelar nóminas ya pagadas.']);
        }

        try {
            $nomina->cancelar();
            return redirect()->route('nominas.index')
                ->with('success', 'Nómina cancelada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al cancelar nómina: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al cancelar la nómina.']);
        }
    }

    /**
     * Generar PDF de recibo de nómina
     */
    public function pdf(Nomina $nomina)
    {
        $nomina->load([
            'empleado:id,name,email,telefono',
            'conceptos',
        ]);

        // Por ahora retornar vista, luego implementar con DomPDF o similar
        return Inertia::render('Nominas/Pdf', [
            'nomina' => $nomina,
        ]);
    }

    /**
     * Generar nóminas masivas para un período
     */
    public function generarMasivo(Request $request)
    {
        $validated = $request->validate([
            'periodo_inicio' => 'required|date',
            'periodo_fin' => 'required|date|after_or_equal:periodo_inicio',
            'tipo_periodo' => 'required|in:semanal,quincenal,mensual',
            'empleado_ids' => 'nullable|array',
            'empleado_ids.*' => 'exists:empleados,id',
        ]);

        try {
            DB::beginTransaction();

            // Si no se especifican empleados, usar todos los activos con salario
            if (empty($validated['empleado_ids'])) {
                $empleados = User::empleados()->activos()->whereNotNull('salario_base')->get();
            } else {
                $empleados = User::whereIn('id', $validated['empleado_ids'])->get();
            }

            $creadas = 0;
            $errores = [];

            foreach ($empleados as $empleado) {
                // Verificar que no exista
                $existe = Nomina::where('empleado_id', $empleado->id)
                    ->where('periodo_inicio', $validated['periodo_inicio'])
                    ->where('periodo_fin', $validated['periodo_fin'])
                    ->exists();

                if ($existe) {
                    $errores[] = "Nómina ya existe para {$empleado->name}";
                    continue;
                }

                $salarioBase = $empleado->calcularSalarioPeriodo($validated['tipo_periodo']);
                $periodoInicio = Carbon::parse($validated['periodo_inicio']);

                $nomina = Nomina::create([
                    'empleado_id' => $empleado->id,
                    'periodo_inicio' => $validated['periodo_inicio'],
                    'periodo_fin' => $validated['periodo_fin'],
                    'tipo_periodo' => $validated['tipo_periodo'],
                    'numero_periodo' => $this->calcularNumeroPeriodo($periodoInicio, $validated['tipo_periodo']),
                    'anio' => $periodoInicio->year,
                    'salario_base' => $salarioBase,
                    'dias_trabajados' => $this->calcularDiasPeriodo($validated['tipo_periodo']),
                    'estado' => 'borrador',
                    'creado_por' => Auth::id(),
                ]);

                $nomina->generarConceptosAutomaticos();
                $creadas++;
            }

            DB::commit();

            $mensaje = "Se crearon {$creadas} nóminas.";
            if (!empty($errores)) {
                $mensaje .= " Errores: " . implode(', ', $errores);
            }

            return back()->with('success', $mensaje);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al generar nóminas masivas: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al generar las nóminas.']);
        }
    }

    /**
     * Calcular número de período del año
     */
    private function calcularNumeroPeriodo(Carbon $fecha, string $tipoPeriodo): int
    {
        return match ($tipoPeriodo) {
            'semanal' => $fecha->weekOfYear,
            'quincenal' => (($fecha->month - 1) * 2) + ($fecha->day <= 15 ? 1 : 2),
            'mensual' => $fecha->month,
            default => 1,
        };
    }

    /**
     * Calcular días de trabajo por tipo de período
     */
    private function calcularDiasPeriodo(string $tipoPeriodo): float
    {
        return match ($tipoPeriodo) {
            'semanal' => 7,
            'quincenal' => 15,
            'mensual' => 30,
            default => 30,
        };
    }
}

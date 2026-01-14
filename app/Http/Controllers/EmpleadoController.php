<?php

namespace App\Http\Controllers;

use App\Models\Nomina;
use App\Models\User;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use Inertia\Inertia;
use App\Traits\ImageOptimizerTrait;

class EmpleadoController extends BaseController
{
    use AuthorizesRequests, ImageOptimizerTrait;

    public function __construct()
    {
        $this->middleware(['auth', 'role:admin|super-admin']);
    }

    /**
     * Lista de empleados con filtros y paginación
     */
    public function index(Request $request)
    {
        $query = User::empleados();

        // Búsqueda
        if ($request->filled('search')) {
            $query->buscar($request->search);
        }

        // Filtro por departamento
        if ($request->filled('departamento')) {
            $query->departamento($request->departamento);
        }

        // Filtro por tipo de contrato
        if ($request->filled('tipo_contrato')) {
            $query->tipoContrato($request->tipo_contrato);
        }

        // Filtro por estado activo/inactivo
        if ($request->filled('activo')) {
            if ($request->activo === 'inactivos') {
                $query->where('activo', false);
            }
        }

        // Ordenamiento
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_direction', 'desc');

        $allowedSorts = ['numero_empleado', 'puesto', 'departamento', 'fecha_contratacion', 'salario_base', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = min($request->input('per_page', 15), 100);
        $empleados = $query->paginate($perPage)->withQueryString();

        // Estadísticas
        $estadisticas = [
            'total' => User::empleados()->count(),
            'activos' => User::empleados()->activos()->count(),
            'inactivos' => User::empleados()->where('activo', false)->count(),
            'por_departamento' => User::empleados()->activos()
                ->select('departamento', DB::raw('count(*) as total'))
                ->whereNotNull('departamento')
                ->groupBy('departamento')
                ->pluck('total', 'departamento'),
        ];

        // Departamentos únicos para filtros
        $departamentos = User::empleados()->whereNotNull('departamento')
            ->distinct()
            ->pluck('departamento')
            ->sort()
            ->values();

        return Inertia::render('Empleados/Index', [
            'empleados' => $empleados,
            'estadisticas' => $estadisticas,
            'departamentos' => $departamentos,
            'filters' => $request->only(['search', 'departamento', 'tipo_contrato', 'activo']),
            'sorting' => [
                'sort_by' => $sortBy,
                'sort_direction' => $sortDir,
            ],
        ]);
    }

    /**
     * Formulario para crear empleado
     */
    public function create()
    {
        // Usuarios que no son empleados todavía
        $usuariosDisponibles = User::where('es_empleado', false)
            ->where('activo', true)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();
        $puestos = User::empleados()->whereNotNull('puesto')
            ->distinct()
            ->pluck('puesto')
            ->sort()
            ->values();

        // Obtenemos departamentos también para el formulario create, que faltaba en la vista anterior? 
        // En el código original $departamentos venia de variable global o algo? No, estaba undefined variable warning.
        // Vamos a definirlo aquí también.
        $departamentos = User::empleados()->whereNotNull('departamento')
            ->distinct()
            ->pluck('departamento')
            ->sort()
            ->values();

        return Inertia::render('Empleados/Create', [
            'usuariosDisponibles' => $usuariosDisponibles,
            'departamentos' => $departamentos,
            'puestos' => $puestos,
            'tiposContrato' => [
                ['value' => 'tiempo_completo', 'label' => 'Tiempo Completo'],
                ['value' => 'medio_tiempo', 'label' => 'Medio Tiempo'],
                ['value' => 'temporal', 'label' => 'Temporal'],
                ['value' => 'honorarios', 'label' => 'Honorarios'],
                ['value' => 'indefinido', 'label' => 'Tiempo Indefinido'],
            ],
            'tiposJornada' => [
                ['value' => 'diurna', 'label' => 'Diurna'],
                ['value' => 'nocturna', 'label' => 'Nocturna'],
                ['value' => 'mixta', 'label' => 'Mixta'],
            ],
            'frecuenciasPago' => [
                ['value' => 'semanal', 'label' => 'Semanal'],
                ['value' => 'quincenal', 'label' => 'Quincenal'],
            ],
        ]);
    }

    /**
     * Guardar nuevo empleado
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id', // Opcional: convertir usuario existente
            'nombre' => 'required_without:user_id|string|max:255',
            'apellido' => 'required_without:user_id|string|max:255',
            'email' => 'required_without:user_id|email|unique:users,email',
            'numero_empleado' => 'nullable|string|max:50|unique:users,numero_empleado',
            'fecha_nacimiento' => 'nullable|date|before:today',
            'curp' => 'nullable|string|size:18',
            'rfc' => 'nullable|string|max:13',
            'nss' => 'nullable|string|max:11',
            'direccion' => 'nullable|string|max:500',
            'puesto' => 'nullable|string|max:100',
            'departamento' => 'nullable|string|max:100',
            'fecha_contratacion' => 'nullable|date',
            'salario_base' => 'nullable|numeric|min:0',
            'tipo_contrato' => 'required|in:tiempo_completo,medio_tiempo,temporal,honorarios,indefinido',
            'tipo_jornada' => 'nullable|in:diurna,nocturna,mixta',
            'horas_jornada' => 'nullable|integer|min:1|max:12',
            'hora_entrada' => 'nullable|date_format:H:i',
            'hora_salida' => 'nullable|date_format:H:i',
            'trabaja_sabado' => 'boolean',
            'hora_entrada_sabado' => 'nullable|date_format:H:i',
            'hora_salida_sabado' => 'nullable|date_format:H:i',
            'frecuencia_pago' => 'required|in:semanal,quincenal',
            'banco' => 'nullable|string|max:100',
            'numero_cuenta' => 'nullable|string|max:50',
            'clabe_interbancaria' => 'nullable|string|size:18',
            'contacto_emergencia_nombre' => 'nullable|string|max:150',
            'contacto_emergencia_telefono' => 'nullable|string|max:20',
            'contacto_emergencia_parentesco' => 'nullable|string|max:50',
            'observaciones' => 'nullable|string|max:1000',
            'contrato_adjunto' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        try {
            DB::beginTransaction();

            // Generar número de empleado si no se proporciona
            if (empty($validated['numero_empleado'])) {
                // Lógica de generación de ID (simplificada)
                $ultimoId = User::max('id');
                $validated['numero_empleado'] = 'EMP-' . str_pad($ultimoId + 1, 4, '0', STR_PAD_LEFT);
            }

            if ($request->filled('user_id')) {
                // Convertir existente
                $empleado = User::find($request->user_id);
                $empleado->update(array_merge($validated, ['es_empleado' => true]));
            } else {
                // Crear nuevo
                $validated['name'] = trim($request->nombre . ' ' . $request->apellido);
                $validated['password'] = bcrypt('password'); // Temporal
                $validated['es_empleado'] = true;
                $empleado = User::create($validated);
            }

            // Manejar archivo de contrato si se subió
            if ($request->hasFile('contrato_adjunto')) {
                $path = $this->saveImageAsWebP($request->file('contrato_adjunto'), 'contratos');
                $empleado->update(['contrato_adjunto' => $path]);
            }

            // Actualizar el flag es_empleado en User (ya hecho arriba)
            // $empleado->user->update(['es_empleado' => true]);

            DB::commit();

            return redirect()->route('empleados.show', $empleado)
                ->with('success', 'Empleado creado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear empleado: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al crear el empleado.'])->withInput();
        }
    }

    /**
     * Ver detalle de empleado
     */
    public function show(User $empleado)
    {
        // Validar que sea empleado
        if (!$empleado->es_empleado)
            abort(404);

        $empleado->load([
            // 'nominas' relationship exists in User but was undefined before? I added it in Step 594.
            'nominas' => fn($q) => $q->orderBy('periodo_inicio', 'desc')->limit(12),
        ]);

        // Historial de nóminas del año
        $nominasAnio = $empleado->nominas()
            ->where('anio', now()->year)
            ->orderBy('periodo_inicio')
            ->get();

        // Resumen anual
        $resumenAnual = [
            'total_percepciones' => $nominasAnio->where('estado', 'pagada')->sum('total_percepciones'),
            'total_deducciones' => $nominasAnio->where('estado', 'pagada')->sum('total_deducciones'),
            'total_neto' => $nominasAnio->where('estado', 'pagada')->sum('total_neto'),
            'nominas_pagadas' => $nominasAnio->where('estado', 'pagada')->count(),
        ];

        return Inertia::render('Empleados/Show', [
            'empleado' => $empleado,
            'nominasRecientes' => $empleado->nominas,
            'resumenAnual' => $resumenAnual,
        ]);
    }

    /**
     * Formulario para editar empleado
     */
    public function edit(User $empleado)
    {
        if (!$empleado->es_empleado)
            abort(404);

        // $empleado->load('user:id,name,email,telefono'); // It IS the user

        // Splitear nombre para formularios legacy
        $parts = explode(' ', $empleado->name, 2);
        $empleado->setAttribute('nombre', $parts[0] ?? '');
        $empleado->setAttribute('apellido', $parts[1] ?? '');

        $departamentos = User::empleados()->whereNotNull('departamento')
            ->distinct()
            ->pluck('departamento')
            ->sort()
            ->values();

        $puestos = User::empleados()->whereNotNull('puesto')
            ->distinct()
            ->pluck('puesto')
            ->sort()
            ->values();

        return Inertia::render('Empleados/Edit', [
            'empleado' => $empleado,
            'departamentos' => $departamentos,
            'puestos' => $puestos,
            'tiposContrato' => [
                ['value' => 'tiempo_completo', 'label' => 'Tiempo Completo'],
                ['value' => 'medio_tiempo', 'label' => 'Medio Tiempo'],
                ['value' => 'temporal', 'label' => 'Temporal'],
                ['value' => 'honorarios', 'label' => 'Honorarios'],
                ['value' => 'indefinido', 'label' => 'Tiempo Indefinido'],
            ],
            'tiposJornada' => [
                ['value' => 'diurna', 'label' => 'Diurna'],
                ['value' => 'nocturna', 'label' => 'Nocturna'],
                ['value' => 'mixta', 'label' => 'Mixta'],
            ],
            'frecuenciasPago' => [
                ['value' => 'semanal', 'label' => 'Semanal'],
                ['value' => 'quincenal', 'label' => 'Quincenal'],
            ],
        ]);
    }

    /**
     * Actualizar empleado
     */
    public function update(Request $request, User $empleado)
    {
        $validated = $request->validate([
            'numero_empleado' => 'nullable|string|max:50|unique:users,numero_empleado,' . $empleado->id,
            'fecha_nacimiento' => 'nullable|date|before:today',
            'curp' => 'nullable|string|size:18',
            'rfc' => 'nullable|string|max:13',
            'nss' => 'nullable|string|max:11',
            'direccion' => 'nullable|string|max:500',
            'puesto' => 'nullable|string|max:100',
            'departamento' => 'nullable|string|max:100',
            'fecha_contratacion' => 'nullable|date',
            'salario_base' => 'nullable|numeric|min:0',
            'tipo_contrato' => 'required|in:tiempo_completo,medio_tiempo,temporal,honorarios,indefinido',
            'tipo_jornada' => 'nullable|in:diurna,nocturna,mixta',
            'horas_jornada' => 'nullable|integer|min:1|max:12',
            'hora_entrada' => 'nullable|date_format:H:i',
            'hora_salida' => 'nullable|date_format:H:i',
            'trabaja_sabado' => 'boolean',
            'hora_entrada_sabado' => 'nullable|date_format:H:i',
            'hora_salida_sabado' => 'nullable|date_format:H:i',
            'frecuencia_pago' => 'required|in:semanal,quincenal',
            'banco' => 'nullable|string|max:100',
            'numero_cuenta' => 'nullable|string|max:50',
            'clabe_interbancaria' => 'nullable|string|size:18',
            'contacto_emergencia_nombre' => 'nullable|string|max:150',
            'contacto_emergencia_telefono' => 'nullable|string|max:20',
            'contacto_emergencia_parentesco' => 'nullable|string|max:50',
            'observaciones' => 'nullable|string|max:1000',
            'activo' => 'boolean',
            'contrato_adjunto' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        try {
            // Manejar archivo de contrato
            if ($request->hasFile('contrato_adjunto')) {
                // Eliminar anterior si existe
                if ($empleado->contrato_adjunto) {
                    Storage::disk('public')->delete($empleado->contrato_adjunto);
                }
                $validated['contrato_adjunto'] = $this->saveImageAsWebP($request->file('contrato_adjunto'), 'contratos');
            }

            $empleado->update($validated);

            return redirect()->route('empleados.show', $empleado)
                ->with('success', 'Empleado actualizado exitosamente.');

        } catch (\Exception $e) {
            Log::error('Error al actualizar empleado: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al actualizar el empleado.'])->withInput();
        }
    }

    /**
     * Dar de baja empleado (soft delete + desactivar)
     */
    public function destroy(User $empleado)
    {
        try {
            DB::beginTransaction();

            // Verificar si tiene nóminas pendientes
            $nominasPendientes = $empleado->nominas()
                ->whereIn('estado', ['borrador', 'procesada'])
                ->count();

            if ($nominasPendientes > 0) {
                return back()->withErrors([
                    'error' => 'No se puede dar de baja al empleado. Tiene nóminas pendientes de pago.'
                ]);
            }

            $empleado->activo = false;
            $empleado->save();
            $empleado->delete();

            // Actualizar flag en User
            $empleado->update(['es_empleado' => false]);

            DB::commit();

            return redirect()->route('empleados.index')
                ->with('success', 'Empleado dado de baja exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al dar de baja empleado: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al dar de baja al empleado.']);
        }
    }

    /**
     * Reactivar empleado
     */
    public function reactivar(User $empleado)
    {
        try {
            $empleado->update(['activo' => true]);
            $empleado->update(['es_empleado' => true]);

            return redirect()->route('empleados.show', $empleado)
                ->with('success', 'Empleado reactivado exitosamente.');

        } catch (\Exception $e) {
            Log::error('Error al reactivar empleado: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al reactivar el empleado.']);
        }
    }

    /**
     * Descargar o ver contrato adjunto
     */
    public function descargarContrato(User $empleado)
    {
        if (!$empleado->contrato_adjunto || !Storage::disk('public')->exists($empleado->contrato_adjunto)) {
            return back()->withErrors(['error' => 'El contrato adjunto no existe.']);
        }

        return response()->file(storage_path('app/public/' . $empleado->contrato_adjunto));
    }

    /**
     * Vista de impresión de contrato
     */
    public function imprimirContrato(User $empleado)
    {
        // $empleado->load('user'); // Self
        $empresa = EmpresaConfiguracion::getConfig();

        return view('empleados.contrato_print', compact('empleado', 'empresa'));
    }
}

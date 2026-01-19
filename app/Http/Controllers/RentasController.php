<?php

namespace App\Http\Controllers;

use App\Models\Renta;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\CuentasPorCobrar;
use App\Services\EmpresaConfiguracionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Carbon\Carbon;

class RentasController extends Controller
{
    /**
     * Muestra una lista de rentas.
     */
    public function index()
    {
        $query = Renta::with([
            'cliente' => function ($q) {
                $q->selectRaw('id, nombre_razon_social, nombre_razon_social as nombre, email');
            },
            'equipos:id,nombre',
            'cuentasPorCobrar' => function ($q) {
                $q->select('id', 'cobrable_id', 'cobrable_type', 'fecha_vencimiento', 'monto_total', 'monto_pendiente', 'estado', 'notas');
            }
        ]);

        // Aplicar filtros
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('numero_contrato', 'like', '%' . $search . '%')
                    ->orWhereHas('cliente', function ($clienteQuery) use ($search) {
                        $clienteQuery->where('nombre_razon_social', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('equipos', function ($equipoQuery) use ($search) {
                        $equipoQuery->where('nombre', 'like', '%' . $search . '%')
                            ->orWhere('codigo', 'like', '%' . $search . '%');
                    });
            });
        }

        if (request('estado')) {
            $query->where('estado', request('estado'));
        }

        // Aplicar ordenamiento
        $sortBy = request('sort_by', 'created_at');
        $sortDirection = request('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $rentas = $query->paginate(request('per_page', 10));

        // Calcular estadísticas básicas
        $stats = [
            'total' => Renta::count(),
            'activas' => Renta::where('estado', 'activo')->count(),
            'vencidas' => Renta::whereIn('estado', ['vencido', 'moroso'])->count(),
        ];

        // ========================
        // ESTADÍSTICAS DE COBRANZA
        // ========================
        $hoy = Carbon::today();
        $en7Dias = Carbon::today()->addDays(7);

        // Cobros próximos en los próximos 7 días
        $cobrosProximos7Dias = CuentasPorCobrar::where('cobrable_type', 'renta')
            ->where('estado', '!=', 'pagado')
            ->whereBetween('fecha_vencimiento', [$hoy, $en7Dias])
            ->count();

        // Monto total pendiente de todas las rentas activas
        $montoPendienteTotal = CuentasPorCobrar::where('cobrable_type', 'renta')
            ->where('estado', '!=', 'pagado')
            ->whereHas('cobrable', function ($q) {
                $q->where('estado', 'activo');
            })
            ->sum('monto_pendiente');

        // Monto vencido (fecha_vencimiento < hoy y no pagado)
        $montoVencido = CuentasPorCobrar::where('cobrable_type', 'renta')
            ->where('estado', '!=', 'pagado')
            ->where('fecha_vencimiento', '<', $hoy)
            ->sum('monto_pendiente');

        // Contratos en mora (rentas con al menos 1 mensualidad vencida no pagada)
        $contratosEnMora = Renta::where('estado', 'activo')
            ->whereHas('cuentasPorCobrar', function ($q) use ($hoy) {
                $q->where('notas', 'Mensualidad')
                    ->where('estado', '!=', 'pagado')
                    ->where('fecha_vencimiento', '<', $hoy);
            })
            ->count();

        // Próximos 5 cobros con detalles completos
        $proximosCobros = CuentasPorCobrar::with(['cobrable.cliente'])
            ->where('cobrable_type', 'renta')
            ->where('estado', '!=', 'pagado')
            ->where('fecha_vencimiento', '>=', $hoy)
            ->orderBy('fecha_vencimiento', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($cuenta) use ($hoy) {
                $fechaVenc = Carbon::parse($cuenta->fecha_vencimiento);
                $diasRestantes = $hoy->diffInDays($fechaVenc, false);

                return [
                    'id' => $cuenta->id,
                    'renta_id' => $cuenta->cobrable_id,
                    'numero_contrato' => $cuenta->cobrable->numero_contrato ?? 'N/A',
                    'cliente' => $cuenta->cobrable->cliente->nombre_razon_social ?? 'Sin cliente',
                    'monto' => $cuenta->monto_pendiente,
                    'fecha_vencimiento' => $cuenta->fecha_vencimiento,
                    'dias_restantes' => $diasRestantes,
                    'notas' => $cuenta->notas,
                ];
            });

        // Ingresos mensuales esperados (suma de monto_mensual de rentas activas)
        $ingresosMensualesEsperados = Renta::where('estado', 'activo')->sum('monto_mensual');

        // Agregar estadísticas de cobranza
        $stats['cobros_proximos_7dias'] = $cobrosProximos7Dias;
        $stats['monto_pendiente_total'] = $montoPendienteTotal;
        $stats['monto_vencido'] = $montoVencido;
        $stats['contratos_en_mora'] = $contratosEnMora;
        $stats['ingresos_mensuales_esperados'] = (float) $ingresosMensualesEsperados;
        $stats['proximos_cobros'] = $proximosCobros;

        return inertia('Rentas/Index', [
            'rentas' => $rentas,
            'stats' => $stats,
            'filters' => request()->only(['search', 'estado']),
            'sorting' => [
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection
            ],
            'defaults' => [
                'ivaPorcentaje' => EmpresaConfiguracionService::getIvaPorcentaje(),
            ]
        ]);
    }

    /**
     * Muestra el formulario para crear una nueva renta.
     */
    public function create()
    {
        return Inertia::render('Rentas/Create', [
            'clientes' => Cliente::select('id', 'nombre_razon_social', 'email', 'telefono')->get(),
            'equipos' => Equipo::where('estado', 'disponible')
                ->select('id', 'codigo', 'nombre', 'marca', 'modelo', 'numero_serie', 'precio_renta_mensual', 'estado', 'descripcion')
                ->get(),
            'defaults' => [
                'fecha_inicio' => now()->format('Y-m-d'),
                'duracion_meses' => 12,
                'dia_pago' => 1,
                'forma_pago' => 'transferencia',
                'ivaPorcentaje' => EmpresaConfiguracionService::getIvaPorcentaje(),
            ]
        ]);
    }

    /**
     * Retorna datos necesarios para el formulario de creación.
     */
    public function createData()
    {
        $clientes = Cliente::select('id', 'nombre_razon_social', 'email')->get();
        $equipos = Equipo::where('estado', 'disponible')
            ->select('id', 'codigo', 'nombre', 'marca', 'modelo', 'numero_serie', 'precio_renta_mensual', 'estado', 'descripcion')
            ->get();

        return inertia('Rentas/Create', compact('clientes', 'equipos'));
    }

    /**
     * Almacena una nueva renta.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cliente_id' => 'required|exists:clientes,id',
            'equipos' => 'required|array|min:1',
            'equipos.*.equipo_id' => 'required|exists:equipos,id',
            'equipos.*.precio_mensual' => 'required|numeric|min:0',
            'fecha_inicio' => 'required|date',
            'duracion_meses' => 'required|integer|in:6,12,18,24',
            'tiene_prorroga' => 'boolean',
            'meses_prorroga' => 'nullable|integer|in:3,6,12',
            'precio_mensual' => 'required|numeric|min:0',
            'deposito_garantia' => 'nullable|numeric|min:0',
            'forma_pago' => 'required|string|in:transferencia,efectivo,tarjeta,cheque',
            'dia_pago' => 'nullable|integer|min:1|max:28',
            'observaciones' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $duracion = $request->duracion_meses;
        $prorroga = $request->tiene_prorroga ? ($request->meses_prorroga ?? 0) : 0;
        $mesesTotales = $duracion + $prorroga;

        $fechaInicio = Carbon::parse($request->fecha_inicio);
        $fechaFin = $fechaInicio->copy()->addMonths($mesesTotales);

        // Generar número de contrato único
        $numeroContrato = $this->generarNumeroContrato();

        // Crear la renta
        $renta = Renta::create([
            'numero_contrato' => $numeroContrato,
            'cliente_id' => $request->cliente_id,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'fecha_firma' => now(),
            'monto_mensual' => $request->precio_mensual,
            'deposito_garantia' => $request->deposito_garantia,
            'forma_pago' => $request->forma_pago,
            'observaciones' => $request->observaciones,
            'renovacion_automatica' => $request->tiene_prorroga,
            'meses_duracion' => $duracion,
            'estado' => 'activo',
            'dia_pago' => $request->input('dia_pago', 1),
        ]);

        // Calcular monto con IVA para cobranzas
        $ivaRate = EmpresaConfiguracionService::getIvaPorcentaje() / 100;
        $montoConIva = round($renta->monto_mensual * (1 + $ivaRate), 2);

        // Crear cobranza del primer vencimiento (mismo mes si fecha_inicio <= dia_pago; si no, siguiente mes)
        $fechaCobro = $fechaInicio->copy()->day(min($renta->dia_pago, $fechaInicio->daysInMonth));
        if ($fechaCobro->lt($fechaInicio)) {
            $fechaCobro = $fechaInicio->copy()->addMonthNoOverflow()->day(min($renta->dia_pago, $fechaInicio->copy()->addMonthNoOverflow()->daysInMonth));
        }
        CuentasPorCobrar::create([
            'empresa_id' => $renta->empresa_id,
            'cliente_id' => $renta->cliente_id,
            'cobrable_id' => $renta->id,
            'cobrable_type' => Renta::class,
            'fecha_vencimiento' => $fechaCobro,
            'monto_total' => $montoConIva,
            'monto_pagado' => 0,
            'monto_pendiente' => $montoConIva,
            'notas' => 'Mensualidad',
            'estado' => 'pendiente',
        ]);

        // Si hay depósito de garantía, crear cobranza para depósito
        if ($renta->deposito_garantia > 0) {
            CuentasPorCobrar::create([
                'empresa_id' => $renta->empresa_id,
                'cliente_id' => $renta->cliente_id,
                'cobrable_id' => $renta->id,
                'cobrable_type' => Renta::class,
                'fecha_vencimiento' => $fechaInicio,
                'monto_total' => $renta->deposito_garantia,
                'monto_pagado' => 0,
                'monto_pendiente' => $renta->deposito_garantia,
                'notas' => 'Depósito de Garantía',
                'estado' => 'pendiente',
            ]);
        }

        // Generar cobranzas restantes del contrato (empezar desde el segundo mes)
        $diaPago = (int) $renta->dia_pago;
        // Primer vencimiento: mismo mes si fecha_inicio <= dia_pago; si no, siguiente mes
        $primerVencimiento = $fechaInicio->copy()->day(min($diaPago, $fechaInicio->daysInMonth));
        if ($primerVencimiento->lt($fechaInicio)) {
            $primerVencimiento = $fechaInicio->copy()->addMonthNoOverflow()->day(min($diaPago, $fechaInicio->copy()->addMonthNoOverflow()->daysInMonth));
        }

        // Empezar desde el segundo mes (el primero ya fue creado arriba)
        $fechaProgramada = $primerVencimiento->copy()->addMonthNoOverflow();
        for ($i = 1; $i < $mesesTotales; $i++) {
            $fechaCorte = $fechaProgramada->copy()->day(min($diaPago, $fechaProgramada->daysInMonth));
            $yaExiste = $renta->cuentasPorCobrar()
                ->whereDate('fecha_vencimiento', $fechaCorte->toDateString())
                ->where('notas', 'Mensualidad')
                ->exists();
            if (!$yaExiste) {
                CuentasPorCobrar::create([
                    'empresa_id' => $renta->empresa_id,
                    'cliente_id' => $renta->cliente_id,
                    'cobrable_id' => $renta->id,
                    'cobrable_type' => Renta::class,
                    'fecha_vencimiento' => $fechaCorte,
                    'monto_total' => $montoConIva,
                    'monto_pagado' => 0,
                    'monto_pendiente' => $montoConIva,
                    'notas' => 'Mensualidad',
                    'estado' => 'pendiente',
                ]);
            }
            $fechaProgramada = $fechaProgramada->copy()->addMonthNoOverflow();
        }

        // Asociar equipos
        foreach ($request->equipos as $equipoData) {
            $renta->equipos()->attach($equipoData['equipo_id'], [
                'precio_mensual' => $equipoData['precio_mensual']
            ]);

            // Actualizar estado del equipo
            $equipo = Equipo::find($equipoData['equipo_id']);
            $equipo->update(['estado' => 'rentado']);
        }

        return redirect()->route('rentas.index')->with('success', 'Renta creada exitosamente con número: ' . $renta->numero_contrato);
    }

    /**
     * Genera un número de contrato único (ej: R-2024-001).
     */
    private function generarNumeroContrato()
    {
        $anio = now()->year;
        // Buscar todos los contratos del año actual (incluyendo soft deleted para evitar conflictos)
        $contratosAnio = Renta::withTrashed()
            ->where('numero_contrato', 'like', 'R-' . $anio . '-%')
            ->pluck('numero_contrato')
            ->toArray();

        $maxNumero = 0;
        foreach ($contratosAnio as $contrato) {
            $numero = intval(substr($contrato, -3));
            if ($numero > $maxNumero) {
                $maxNumero = $numero;
            }
        }

        $numero = $maxNumero + 1;
        return 'R-' . $anio . '-' . str_pad($numero, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Muestra los detalles de una renta.
     */
    public function show(Renta $renta)
    {
        $renta->load('cliente', 'equipos', 'cuentasPorCobrar');

        $cuentasBancarias = \App\Models\CuentaBancaria::activas()
            ->orderBy('banco')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'banco']);

        return inertia('Rentas/Show', [
            'renta' => $renta,
            'cuentasBancarias' => $cuentasBancarias,
        ]);
    }

    /**
     * Muestra el formulario para editar una renta.
     */
    public function edit(Renta $renta)
    {
        $renta->load('cliente', 'equipos');

        return Inertia::render('Rentas/Edit', [
            'renta' => $renta,
            'clientes' => Cliente::select('id', 'nombre_razon_social', 'email', 'telefono')->get(),
            'equipos' => Equipo::where('estado', 'disponible')
                ->orWhereHas('rentas', function ($query) use ($renta) {
                    $query->where('rentas.id', $renta->id);
                })
                ->select('id', 'codigo', 'nombre', 'marca', 'modelo', 'numero_serie', 'precio_renta_mensual', 'estado', 'descripcion')
                ->get(),
            'defaults' => [
                'ivaPorcentaje' => EmpresaConfiguracionService::getIvaPorcentaje(),
            ]
        ]);
    }

    /**
     * Actualiza una renta existente.
     */
    public function update(Request $request, Renta $renta)
    {
        $validator = Validator::make($request->all(), [
            'cliente_id' => 'required|exists:clientes,id',
            'equipos' => 'required|array|min:1',
            'equipos.*.equipo_id' => 'required|exists:equipos,id',
            'equipos.*.precio_mensual' => 'required|numeric|min:0',
            'fecha_inicio' => 'required|date',
            'duracion_meses' => 'required|integer|in:6,12,18,24',
            'tiene_prorroga' => 'boolean',
            'meses_prorroga' => 'nullable|integer|in:3,6,12',
            'precio_mensual' => 'required|numeric|min:0',
            'deposito_garantia' => 'nullable|numeric|min:0',
            'forma_pago' => 'required|string|in:transferencia,efectivo,tarjeta,cheque',
            'dia_pago' => 'nullable|integer|min:1|max:28',
            'observaciones' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $duracion = $request->duracion_meses;
        $prorroga = $request->tiene_prorroga ? ($request->meses_prorroga ?? 0) : 0;
        $mesesTotales = $duracion + $prorroga;

        $fechaInicio = Carbon::parse($request->fecha_inicio);
        $fechaFin = $fechaInicio->copy()->addMonths($mesesTotales);

        // Actualizar datos básicos de la renta
        $renta->update([
            'cliente_id' => $request->cliente_id,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'monto_mensual' => $request->precio_mensual,
            'deposito_garantia' => $request->deposito_garantia,
            'forma_pago' => $request->forma_pago,
            'observaciones' => $request->observaciones,
            'renovacion_automatica' => $request->tiene_prorroga,
            'meses_duracion' => $duracion,
            'dia_pago' => $request->input('dia_pago', $renta->dia_pago),
        ]);

        // Regenerar calendario de cobranzas: conservar pagadas/parciales, recalcular pendientes/vencidas
        $diaPago = (int) $renta->dia_pago;
        $primerVencimiento = $fechaInicio->copy()->day(min($diaPago, $fechaInicio->daysInMonth));
        if ($primerVencimiento->lt($fechaInicio)) {
            $primerVencimiento = $fechaInicio->copy()->addMonthNoOverflow()->day(min($diaPago, $fechaInicio->copy()->addMonthNoOverflow()->daysInMonth));
        }

        $cobranzasPagadasOParciales = $renta->cuentasPorCobrar()
            ->where('notas', 'Mensualidad')
            ->whereIn('estado', ['pagado', 'parcial'])
            ->get();
        $maxFechaPagada = $cobranzasPagadasOParciales->max('fecha_vencimiento');

        $renta->cuentasPorCobrar()
            ->where('notas', 'Mensualidad')
            ->where('estado', 'pendiente')
            ->delete();

        $fechaProgramada = $primerVencimiento->copy();
        for ($i = 0; $i < $mesesTotales; $i++) {
            $fechaCorte = $fechaProgramada->copy()->day(min($diaPago, $fechaProgramada->daysInMonth));

            if ($maxFechaPagada && $fechaCorte->lte($maxFechaPagada)) {
                $fechaProgramada = $fechaProgramada->copy()->addMonthNoOverflow();
                continue;
            }

            $existe = $renta->cuentasPorCobrar()
                ->where('notas', 'Mensualidad')
                ->whereDate('fecha_vencimiento', $fechaCorte->toDateString())
                ->exists();

            if (!$existe) {
                // Calcular monto con IVA para cobranzas
                $ivaRate = EmpresaConfiguracionService::getIvaPorcentaje() / 100;
                $montoConIva = round($renta->monto_mensual * (1 + $ivaRate), 2);

                CuentasPorCobrar::create([
                    'empresa_id' => $renta->empresa_id,
                    'cliente_id' => $renta->cliente_id,
                    'cobrable_id' => $renta->id,
                    'cobrable_type' => Renta::class,
                    'fecha_vencimiento' => $fechaCorte,
                    'monto_total' => $montoConIva,
                    'monto_pagado' => 0,
                    'monto_pendiente' => $montoConIva,
                    'notas' => 'Mensualidad',
                    'estado' => 'pendiente',
                ]);
            }

            $fechaProgramada = $fechaProgramada->copy()->addMonthNoOverflow();
        }

        // Obtener IDs de equipos actuales y nuevos
        $equiposActualesIds = $renta->equipos->pluck('id')->toArray();
        $equiposNuevosIds = collect($request->equipos)->pluck('equipo_id')->toArray();

        // Equipos a quitar (estaban antes pero ya no)
        $equiposAQuitar = array_diff($equiposActualesIds, $equiposNuevosIds);

        // Equipos a agregar (son nuevos)
        $equiposAAgregar = array_diff($equiposNuevosIds, $equiposActualesIds);

        // Quitar equipos que ya no están
        foreach ($equiposAQuitar as $equipoId) {
            $equipo = Equipo::find($equipoId);
            if ($equipo) {
                $equipo->update(['estado' => 'disponible']);
            }
            $renta->equipos()->detach($equipoId);
        }

        // Agregar equipos nuevos
        foreach ($request->equipos as $equipoData) {
            $equipoId = $equipoData['equipo_id'];

            if (in_array($equipoId, $equiposAAgregar)) {
                // Es un equipo nuevo, agregarlo
                $renta->equipos()->attach($equipoId, [
                    'precio_mensual' => $equipoData['precio_mensual']
                ]);

                // Actualizar estado del equipo
                $equipo = Equipo::find($equipoId);
                if ($equipo) {
                    $equipo->update(['estado' => 'rentado']);
                }
            } else {
                // Es un equipo existente, actualizar precio si cambió
                $renta->equipos()->updateExistingPivot($equipoId, [
                    'precio_mensual' => $equipoData['precio_mensual']
                ]);
            }
        }

        return redirect()->route('rentas.index')->with('success', 'Renta actualizada correctamente.');
    }

    /**
     * Elimina una renta (soft delete).
     */
    public function destroy(Renta $renta)
    {
        // Validar si existen pagos o cobranzas pagadas
        if ($renta->pagos()->exists() || $renta->cuentasPorCobrar()->where('estado', 'pagado')->exists()) {
            return redirect()->back()->with('error', 'No se puede eliminar una renta que tiene pagos registrados. Considere finalizarla o suspenderla.');
        }

        // Liberar equipos al eliminar la renta
        foreach ($renta->equipos as $equipo) {
            $equipo->update(['estado' => 'disponible']);
        }

        $renta->delete();
        return redirect()->route('rentas.index')->with('success', 'Renta eliminada correctamente.');
    }

    /**
     * Renovar una renta.
     */
    public function renovar(Request $request, Renta $renta)
    {
        $request->validate(['meses_renovacion' => 'required|integer|min:1']);

        if (!in_array($renta->estado, ['activo', 'proximo_vencimiento', 'vencido'])) {
            return redirect()->back()->with('error', 'No se puede renovar una renta en estado: ' . $renta->estado);
        }

        $nuevaFechaFin = Carbon::parse($renta->fecha_fin)->addMonths($request->meses_renovacion);

        $renta->update([
            'fecha_fin' => $nuevaFechaFin,
            'meses_duracion' => $renta->meses_duracion + $request->meses_renovacion,
            'estado' => 'activo',
            'historial_cambios' => array_merge($renta->historial_cambios ?? [], [
                ['accion' => 'renovacion', 'fecha' => now()->toISOString(), 'meses' => $request->meses_renovacion]
            ])
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Renta renovada exitosamente']);
        }
        return redirect()->back()->with('success', 'Renta renovada exitosamente');
    }

    /**
     * Suspender una renta.
     */
    public function suspender(Request $request, Renta $renta)
    {
        if ($renta->estado !== 'activo') {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => 'Solo se pueden suspender rentas activas'], 422);
            }
            return redirect()->back()->with('error', 'Solo se pueden suspender rentas activas');
        }

        // Liberar equipos cuando se suspende la renta
        foreach ($renta->equipos as $equipo) {
            $equipo->update(['estado' => 'disponible']);
        }

        $renta->update(['estado' => 'suspendido']);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Renta suspendida correctamente']);
        }
        return redirect()->back()->with('success', 'Renta suspendida correctamente');
    }

    /**
     * Reactivar una renta suspendida.
     */
    public function reactivar(Request $request, Renta $renta)
    {
        if ($renta->estado !== 'suspendido') {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => 'Solo se pueden reactivar rentas suspendidas'], 422);
            }
            return redirect()->back()->with('error', 'Solo se pueden reactivar rentas suspendidas');
        }

        // Verificar disponibilidad de equipos antes de reactivar
        foreach ($renta->equipos as $equipo) {
            if ($equipo->estado !== 'disponible') {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'error' => "El equipo {$equipo->codigo} no está disponible ({$equipo->estado})"], 422);
                }
                return redirect()->back()->with('error', "No se puede reactivar: El equipo {$equipo->codigo} no está disponible (Estado: {$equipo->estado})");
            }
        }

        // Volver a marcar equipos como rentados
        foreach ($renta->equipos as $equipo) {
            $equipo->update(['estado' => 'rentado']);
        }

        $renta->update(['estado' => 'activo']);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Renta reactivada correctamente']);
        }
        return redirect()->back()->with('success', 'Renta reactivada correctamente');
    }

    /**
     * Finalizar una renta (liberar equipos).
     */
    public function finalizar(Request $request, Renta $renta)
    {
        if (!in_array($renta->estado, ['activo', 'proximo_vencimiento', 'vencido'])) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => 'No se puede finalizar una renta en estado: ' . $renta->estado], 422);
            }
            return redirect()->back()->with('error', 'No se puede finalizar una renta en estado: ' . $renta->estado);
        }

        // Liberar equipos cuando se finaliza la renta
        foreach ($renta->equipos as $equipo) {
            $equipo->update(['estado' => 'disponible']);
        }

        $renta->update(['estado' => 'finalizado']);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Renta finalizada correctamente']);
        }
        return redirect()->back()->with('success', 'Renta finalizada correctamente');
    }

    /**
     * Duplicar una renta.
     */
    public function duplicate(Renta $renta)
    {
        // Crear una nueva renta basada en la existente
        $nuevaRenta = Renta::create([
            'numero_contrato' => $this->generarNumeroContrato(),
            'cliente_id' => $renta->cliente_id,
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addMonths($renta->meses_duracion),
            'fecha_firma' => now(),
            'monto_mensual' => $renta->monto_mensual,
            'deposito_garantia' => $renta->deposito_garantia,
            'forma_pago' => $renta->forma_pago,
            'observaciones' => 'Duplicado de ' . $renta->numero_contrato . ' - ' . $renta->observaciones,
            'renovacion_automatica' => $renta->renovacion_automatica,
            'meses_duracion' => $renta->meses_duracion,
            'estado' => 'activo',
            'dia_pago' => $renta->dia_pago,
        ]);

        // Copiar equipos de la renta original
        foreach ($renta->equipos as $equipo) {
            $nuevaRenta->equipos()->attach($equipo->id, [
                'precio_mensual' => $equipo->pivot->precio_mensual
            ]);

            // Asegurar que el equipo esté marcado como rentado
            $equipo->update(['estado' => 'rentado']);
        }

        return redirect()->route('rentas.index')->with('success', 'Renta duplicada correctamente con número: ' . $nuevaRenta->numero_contrato);
    }
}

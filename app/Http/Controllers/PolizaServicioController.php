<?php

namespace App\Http\Controllers;

use App\Models\PolizaServicio;
use App\Models\Cliente;
use App\Models\Servicio;
use App\Models\Equipo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PolizaServicioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = PolizaServicio::with('cliente');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('folio', 'like', "%{$search}%")
                    ->orWhere('nombre', 'like', "%{$search}%")
                    ->orWhereHas('cliente', function ($q) use ($search) {
                        $q->where('nombre_razon_social', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        $polizas = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('PolizaServicio/Index', [
            'polizas' => $polizas,
            'filters' => $request->only(['search', 'estado']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('PolizaServicio/Edit', [
            'clientes' => Cliente::activos()->get(['id', 'nombre_razon_social', 'email', 'telefono', 'rfc']),
            'servicios' => Servicio::select('id', 'nombre', 'precio')->active()->get(),
            'poliza' => null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'nombre' => 'required|string|max:255',
            'fecha_inicio' => 'required|date',
            'monto_mensual' => 'required|numeric|min:0',
            'dia_cobro' => 'required|integer|min:1|max:31',
            'limite_mensual_tickets' => 'nullable|integer|min:0',
            'sla_horas_respuesta' => 'nullable|integer|min:1|max:168',
            // Phase 2
            'horas_incluidas_mensual' => 'nullable|integer|min:1',
            'costo_hora_excedente' => 'nullable|numeric|min:0',
            'dias_alerta_vencimiento' => 'nullable|integer|min:1|max:90',
            'mantenimiento_frecuencia_meses' => 'nullable|integer|min:1|max:24',
            'proximo_mantenimiento_at' => 'nullable|date',
            'generar_cita_automatica' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $condiciones = $request->condiciones_especiales ?? [];
            $condiciones['equipos_cliente'] = $request->equipos_cliente ?? [];

            $poliza = PolizaServicio::create([
                'empresa_id' => auth()->user()->empresa_id ?? 1,
                'cliente_id' => $request->cliente_id,
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'monto_mensual' => $request->monto_mensual,
                'dia_cobro' => $request->dia_cobro,
                'estado' => 'activa',
                'limite_mensual_tickets' => $request->limite_mensual_tickets,
                'notificar_exceso_limite' => $request->notificar_exceso_limite ?? true,
                'renovacion_automatica' => $request->renovacion_automatica ?? true,
                'notas' => $request->notas,
                'sla_horas_respuesta' => $request->sla_horas_respuesta,
                'condiciones_especiales' => $condiciones,
                // Phase 2
                'horas_incluidas_mensual' => $request->horas_incluidas_mensual,
                'costo_hora_excedente' => $request->costo_hora_excedente,
                'dias_alerta_vencimiento' => $request->dias_alerta_vencimiento ?? 30,
                'mantenimiento_frecuencia_meses' => $request->mantenimiento_frecuencia_meses,
                'proximo_mantenimiento_at' => $request->proximo_mantenimiento_at,
                'generar_cita_automatica' => $request->generar_cita_automatica ?? false,
            ]);

            if ($request->has('servicios')) {
                foreach ($request->servicios as $item) {
                    $poliza->servicios()->attach($item['id'], [
                        'cantidad' => $item['cantidad'] ?? 1,
                        'precio_especial' => $item['precio_especial'] ?? null,
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('polizas-servicio.index')->with('success', 'Póliza creada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al crear la póliza: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PolizaServicio $polizaServicio)
    {
        $polizaServicio->load([
            'cliente',
            'servicios',
            'equipos',
            'tickets' => function ($q) {
                $q->latest()->take(10);
            },
            'cuentasPorCobrar' => function ($q) {
                $q->latest()->take(10);
            },
            'credenciales'
        ]);

        // Agregar atributos calculados explícitamente
        $polizaServicio->append([
            'porcentaje_horas',
            'porcentaje_tickets',
            'dias_para_vencer',
            'excede_horas'
        ]);

        return Inertia::render('PolizaServicio/Show', [
            'poliza' => $polizaServicio,
            'stats' => [
                'tickets_mes' => $polizaServicio->tickets_mes_actual_count,
                'excede_limite' => $polizaServicio->excede_limite,
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PolizaServicio $polizaServicio)
    {
        $polizaServicio->load(['servicios', 'credenciales']);
        // Aseguramos que el cliente de la póliza esté en la lista, incluso si está inactivo
        $clientesList = Cliente::where('id', $polizaServicio->cliente_id)
            ->orWhere('activo', true)
            ->get(['id', 'nombre_razon_social', 'email', 'telefono', 'rfc']);

        return Inertia::render('PolizaServicio/Edit', [
            'clientes' => $clientesList,
            'servicios' => Servicio::select('id', 'nombre', 'precio')->active()->get(),
            'poliza' => $polizaServicio,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PolizaServicio $polizaServicio)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'monto_mensual' => 'required|numeric|min:0',
            'dia_cobro' => 'required|integer|min:1|max:31',
            'estado' => 'required|string|in:activa,inactiva,vencida,cancelada',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date',
            'sla_horas_respuesta' => 'nullable|integer|min:1|max:168',
            // Phase 2
            'horas_incluidas_mensual' => 'nullable|integer|min:1',
            'costo_hora_excedente' => 'nullable|numeric|min:0',
            'dias_alerta_vencimiento' => 'nullable|integer|min:1|max:90',
            'mantenimiento_frecuencia_meses' => 'nullable|integer|min:1|max:24',
            'mantenimiento_dias_anticipacion' => 'nullable|integer|min:1|max:30',
            'proximo_mantenimiento_at' => 'nullable|date',
            'generar_cita_automatica' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $condiciones = $request->condiciones_especiales ?? [];
            $condiciones['equipos_cliente'] = $request->equipos_cliente ?? [];

            $polizaServicio->update(array_merge($request->only([
                'nombre',
                'descripcion',
                'fecha_inicio',
                'fecha_fin',
                'monto_mensual',
                'dia_cobro',
                'estado',
                'limite_mensual_tickets',
                'notificar_exceso_limite',
                'renovacion_automatica',
                'notas',
                'sla_horas_respuesta',
                // Phase 2
                'horas_incluidas_mensual',
                'costo_hora_excedente',
                'dias_alerta_vencimiento',
                'mantenimiento_frecuencia_meses',
                'mantenimiento_dias_anticipacion',
                'proximo_mantenimiento_at',
                'generar_cita_automatica',
            ]), ['condiciones_especiales' => $condiciones]));

            if ($request->has('servicios')) {
                $syncData = [];
                foreach ($request->servicios as $item) {
                    $syncData[$item['id']] = [
                        'cantidad' => $item['cantidad'] ?? 1,
                        'precio_especial' => $item['precio_especial'] ?? null,
                    ];
                }
                $polizaServicio->servicios()->sync($syncData);
            }

            if ($request->has('equipos')) {
                $polizaServicio->equipos()->sync($request->equipos);
            }

            DB::commit();
            return redirect()->route('polizas-servicio.index')->with('success', 'Póliza actualizada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar la póliza: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PolizaServicio $polizaServicio)
    {
        $polizaServicio->delete();
        return redirect()->route('polizas-servicio.index')->with('success', 'Póliza eliminada correctamente.');
    }

    /**
     * Dashboard de Pólizas - Alertas y Métricas (Phase 3 - Premium)
     */
    public function dashboard()
    {
        // KPIs Financieros Premium
        $ingresosMensuales = PolizaServicio::activa()->sum('monto_mensual');

        // Cobros pendientes de pólizas (deuda activa)
        $cobrosPendientes = \App\Models\CuentasPorCobrar::where('cobrable_type', PolizaServicio::class)
            ->whereIn('estado', ['pendiente', 'vencido'])
            ->sum('monto_total');

        // Polizas con cobros vencidos (bloquear soporte)
        $polizasConDeuda = PolizaServicio::activa()
            ->whereHas('cuentasPorCobrar', function ($q) {
                $q->where('estado', 'vencido');
            })
            ->count();

        // Ingresos potenciales por excedentes de horas
        $ingresosExcedentes = PolizaServicio::activa()
            ->whereNotNull('horas_incluidas_mensual')
            ->whereNotNull('costo_hora_excedente')
            ->where('horas_consumidas_mes', '>', DB::raw('horas_incluidas_mensual'))
            ->get()
            ->sum(function ($p) {
                $exceso = $p->horas_consumidas_mes - $p->horas_incluidas_mensual;
                return $exceso * $p->costo_hora_excedente;
            });

        // Estadísticas generales mejoradas
        $stats = [
            'total_activas' => PolizaServicio::activa()->count(),
            'total_inactivas' => PolizaServicio::where('estado', '!=', 'activa')->count(),
            'ingresos_mensuales' => $ingresosMensuales,
            'ingresos_anuales_proyectados' => $ingresosMensuales * 12,
            'cobros_pendientes' => $cobrosPendientes,
            'polizas_con_deuda' => $polizasConDeuda,
            'ingresos_excedentes' => $ingresosExcedentes,
            'con_exceso_tickets' => PolizaServicio::activa()
                ->whereNotNull('limite_mensual_tickets')
                ->get()
                ->filter(fn($p) => $p->excede_limite)
                ->count(),
            'con_exceso_horas' => PolizaServicio::activa()
                ->whereNotNull('horas_incluidas_mensual')
                ->get()
                ->filter(fn($p) => $p->excede_horas)
                ->count(),
            // Tasa de retención (últimos 12 meses)
            'tasa_retencion' => $this->calcularTasaRetencion(),
        ];

        // Pólizas próximas a vencer (30 días)
        $proximasVencer = PolizaServicio::with('cliente')
            ->proximasAVencer(30)
            ->orderBy('fecha_fin')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'folio' => $p->folio,
                'nombre' => $p->nombre,
                'cliente' => $p->cliente->nombre_razon_social ?? 'N/A',
                'fecha_fin' => $p->fecha_fin ? Carbon::parse($p->fecha_fin)->format('d/m/Y') : 'N/A',
                'dias_restantes' => $p->dias_para_vencer,
                'monto_mensual' => $p->monto_mensual,
            ]);

        // Pólizas con exceso de tickets
        $excesoTickets = PolizaServicio::with('cliente')
            ->activa()
            ->whereNotNull('limite_mensual_tickets')
            ->get()
            ->filter(fn($p) => $p->excede_limite)
            ->map(fn($p) => [
                'id' => $p->id,
                'folio' => $p->folio,
                'nombre' => $p->nombre,
                'cliente' => $p->cliente->nombre_razon_social ?? 'N/A',
                'tickets_usados' => $p->tickets_mes_actual_count,
                'limite' => $p->limite_mensual_tickets,
                'porcentaje' => $p->porcentaje_tickets,
            ]);

        // Pólizas con exceso de horas
        $excesoHoras = PolizaServicio::with('cliente')
            ->activa()
            ->whereNotNull('horas_incluidas_mensual')
            ->get()
            ->filter(fn($p) => $p->excede_horas)
            ->map(fn($p) => [
                'id' => $p->id,
                'folio' => $p->folio,
                'nombre' => $p->nombre,
                'cliente' => $p->cliente->nombre_razon_social ?? 'N/A',
                'horas_usadas' => $p->horas_consumidas_mes,
                'horas_incluidas' => $p->horas_incluidas_mensual,
                'porcentaje' => $p->porcentaje_horas,
                'costo_extra' => $p->costo_hora_excedente,
            ]);

        // Top 10 pólizas por consumo de horas este mes
        $topConsumo = PolizaServicio::with('cliente')
            ->activa()
            ->whereNotNull('horas_incluidas_mensual')
            ->where('horas_consumidas_mes', '>', 0)
            ->orderByDesc('horas_consumidas_mes')
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'folio' => $p->folio,
                'nombre' => $p->nombre,
                'cliente' => $p->cliente->nombre_razon_social ?? 'N/A',
                'horas_usadas' => $p->horas_consumidas_mes,
                'horas_incluidas' => $p->horas_incluidas_mensual,
                'porcentaje' => $p->porcentaje_horas,
            ]);

        // Últimos cobros generados
        $ultimosCobros = \App\Models\CuentasPorCobrar::with('cliente')
            ->where('cobrable_type', PolizaServicio::class)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'fecha' => $c->created_at->format('d/m/Y'),
                'cliente' => $c->cliente->nombre_razon_social ?? 'N/A',
                'monto' => $c->monto_total,
                'estado' => $c->estado,
            ]);

        return Inertia::render('PolizaServicio/Dashboard', [
            'stats' => $stats,
            'proximasVencer' => $proximasVencer,
            'excesoTickets' => $excesoTickets,
            'excesoHoras' => $excesoHoras,
            'topConsumo' => $topConsumo,
            'ultimosCobros' => $ultimosCobros,
        ]);
    }

    /**
     * Historial de consumo de una póliza específica (Phase 3)
     */
    public function historialConsumo(PolizaServicio $polizaServicio)
    {
        $polizaServicio->load([
            'cliente',
            'tickets' => function ($q) {
                $q->whereNotNull('horas_trabajadas')
                    ->orderByDesc('created_at')
                    ->limit(50);
            }
        ]);

        // Agrupar consumo por mes
        $consumoPorMes = $polizaServicio->tickets()
            ->whereNotNull('horas_trabajadas')
            ->selectRaw("DATE_TRUNC('month', created_at) as mes, SUM(horas_trabajadas) as total_horas, COUNT(*) as total_tickets")
            ->groupByRaw("DATE_TRUNC('month', created_at)")
            ->orderByDesc('mes')
            ->limit(12)
            ->get();

        // Tickets recientes con horas
        $ticketsConHoras = $polizaServicio->tickets()
            ->whereNotNull('horas_trabajadas')
            ->with('asignado')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'folio' => $t->folio ?? $t->numero,
                'titulo' => $t->titulo,
                'fecha' => $t->created_at->format('d/m/Y'),
                'horas' => $t->horas_trabajadas,
                'tecnico' => $t->asignado->name ?? 'Sin asignar',
                'estado' => $t->estado,
            ]);

        return Inertia::render('PolizaServicio/HistorialConsumo', [
            'poliza' => $polizaServicio,
            'consumoPorMes' => $consumoPorMes,
            'ticketsConHoras' => $ticketsConHoras,
        ]);
    }

    /**
     * Calcular tasa de retención de pólizas (últimos 12 meses)
     */
    private function calcularTasaRetencion(): float
    {
        $hace12Meses = Carbon::now()->subYear();

        // Pólizas que estaban activas hace 12 meses
        $activasHace12Meses = PolizaServicio::where('fecha_inicio', '<=', $hace12Meses)
            ->where(function ($q) use ($hace12Meses) {
                $q->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', $hace12Meses);
            })
            ->count();

        if ($activasHace12Meses === 0) {
            return 100; // No hay datos históricos
        }

        // De esas, cuántas siguen activas hoy
        $renovadas = PolizaServicio::where('fecha_inicio', '<=', $hace12Meses)
            ->where('estado', 'activa')
            ->count();

        return round(($renovadas / $activasHace12Meses) * 100, 1);
    }

    /**
     * Generar cobro manual para una póliza
     */
    public function generarCobro(PolizaServicio $polizaServicio)
    {
        try {
            $monto = $polizaServicio->monto_mensual;
            $iva = round($monto * 0.16, 2);

            $cobro = \App\Models\CuentasPorCobrar::create([
                'empresa_id' => $polizaServicio->empresa_id,
                'cliente_id' => $polizaServicio->cliente_id,
                'cobrable_type' => PolizaServicio::class,
                'cobrable_id' => $polizaServicio->id,
                'concepto' => "Mensualidad Póliza {$polizaServicio->folio}",
                'monto_subtotal' => $monto,
                'monto_iva' => $iva,
                'monto_total' => $monto + $iva,
                'estado' => 'pendiente',
                'fecha_vencimiento' => Carbon::now()->addDays(15),
                'notas' => "Cobro generado manualmente el " . Carbon::now()->format('d/m/Y'),
            ]);

            $polizaServicio->update(['ultimo_cobro_generado_at' => now()]);

            return back()->with('success', "Cobro generado correctamente por " . number_format((float) $cobro->monto_total, 2) . " MXN");
        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar cobro: ' . $e->getMessage());
        }
    }

    /**
     * Enviar recordatorio de renovación al cliente
     */
    public function enviarRecordatorioRenovacion(PolizaServicio $polizaServicio)
    {
        try {
            $cliente = $polizaServicio->cliente;
            if (!$cliente || !$cliente->email) {
                return back()->with('error', 'El cliente no tiene email configurado');
            }

            $cliente->notify(new \App\Notifications\PolizaRenovacionNotification($polizaServicio));

            return back()->with('success', 'Recordatorio de renovación enviado a ' . $cliente->email);
        } catch (\Exception $e) {
            return back()->with('error', 'Error al enviar recordatorio: ' . $e->getMessage());
        }
    }
}

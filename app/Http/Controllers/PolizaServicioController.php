<?php

namespace App\Http\Controllers;

use App\Models\PolizaServicio;
use App\Models\Cliente;
use App\Models\Servicio;
use App\Models\Equipo;
use App\Models\PlanPoliza;
use App\Services\PolizaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PolizaServicioController extends Controller
{
    protected $polizaService;

    public function __construct(PolizaService $polizaService)
    {
        $this->polizaService = $polizaService;
    }
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
            'planes' => PlanPoliza::activos()->ordenado()->get(),
            'poliza' => null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'nombre' => 'required|string|max:255',
            'fecha_inicio' => 'required|date',
            'monto_mensual' => 'required|numeric|min:0',
            'dia_cobro' => 'required|integer|min:1|max:31',
            'limite_mensual_tickets' => 'nullable|integer|min:0',
            'sla_horas_respuesta' => 'nullable|integer|min:1|max:168',
            'horas_incluidas_mensual' => 'nullable|integer|min:1',
            'costo_hora_excedente' => 'nullable|numeric|min:0',
            'dias_alerta_vencimiento' => 'nullable|integer|min:1|max:90',
            'mantenimiento_frecuencia_meses' => 'nullable|integer|min:1|max:24',
            'proximo_mantenimiento_at' => 'nullable|date',
            'generar_cita_automatica' => 'nullable|boolean',
            'visitas_sitio_mensuales' => 'nullable|integer|min:0',
            'costo_visita_sitio_extra' => 'nullable|numeric|min:0',
            'costo_ticket_extra' => 'nullable|numeric|min:0',
        ]);

        try {
            $this->polizaService->createPoliza($request->all());
            return redirect()->route('polizas-servicio.index')->with('success', 'Póliza creada correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al crear la póliza: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PolizaServicio $polizas_servicio)
    {
        $polizas_servicio->load([
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
        $polizas_servicio->append([
            'porcentaje_horas',
            'porcentaje_tickets',
            'dias_para_vencer',
            'excede_horas'
        ]);

        return Inertia::render('PolizaServicio/Show', [
            'poliza' => $polizas_servicio,
            'stats' => [
                'tickets_mes' => $polizas_servicio->tickets_soporte_mes_count,
                'tickets_asesoria' => $polizas_servicio->tickets_asesoria_mes_count,
                'visitas_mes' => $polizas_servicio->visitas_sitio_consumidas_mes,
                'excede_limite' => $polizas_servicio->excede_limite,
                'excede_visitas' => $polizas_servicio->excede_limite_visitas,
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PolizaServicio $polizas_servicio)
    {
        $polizas_servicio->load(['servicios', 'credenciales', 'cliente']);

        // Aseguramos que el cliente de la póliza esté en la lista, incluso si está inactivo
        $clientesList = Cliente::where('id', $polizas_servicio->cliente_id)
            ->orWhere('activo', true)
            ->get(['id', 'nombre_razon_social', 'email', 'telefono', 'rfc']);

        return Inertia::render('PolizaServicio/Edit', [
            'clientes' => $clientesList,
            'servicios' => Servicio::select('id', 'nombre', 'precio')->active()->get(),
            'planes' => PlanPoliza::activos()->ordenado()->get(),
            'poliza' => $polizas_servicio,
            'clientePoliza' => $polizas_servicio->cliente ? $polizas_servicio->cliente->toArray() : null,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PolizaServicio $polizas_servicio)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'monto_mensual' => 'required|numeric|min:0',
            'dia_cobro' => 'required|integer|min:1|max:31',
            'limite_mensual_tickets' => 'nullable|integer|min:0',
            'sla_horas_respuesta' => 'nullable|integer|min:1|max:168',
            'horas_incluidas_mensual' => 'nullable|integer|min:1',
            'costo_hora_excedente' => 'nullable|numeric|min:0',
            'dias_alerta_vencimiento' => 'nullable|integer|min:1|max:90',
            'mantenimiento_frecuencia_meses' => 'nullable|integer|min:1|max:24',
            'proximo_mantenimiento_at' => 'nullable|date',
            'generar_cita_automatica' => 'nullable|boolean',
            'visitas_sitio_mensuales' => 'nullable|integer|min:0',
            'costo_visita_sitio_extra' => 'nullable|numeric|min:0',
            'costo_ticket_extra' => 'nullable|numeric|min:0',
        ]);

        try {
            $this->polizaService->updatePoliza($polizas_servicio, $request->all());
            return redirect()->route('polizas-servicio.index')->with('success', 'Póliza actualizada correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al actualizar la póliza: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PolizaServicio $polizas_servicio)
    {
        if ($polizas_servicio->tickets()->exists() || $polizas_servicio->citas()->exists()) {
            return redirect()->route('polizas-servicio.index')->with('error', 'No se puede eliminar la póliza porque tiene tickets o citas asociadas.');
        }

        $polizas_servicio->delete();
        return redirect()->route('polizas-servicio.index')->with('success', 'Póliza eliminada correctamente.');
    }

    /**
     * Dashboard de Pólizas - Alertas y Métricas (Phase 3 - Premium)
     */
    public function dashboard()
    {
        $stats = $this->polizaService->getDashboardStats();

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

        // Fase 4: Estadísticas de consumos del mes
        $consumosMes = \App\Models\PolizaConsumo::whereMonth('fecha_consumo', now()->month)
            ->whereYear('fecha_consumo', now()->year)
            ->get();

        $statsConsumo = [
            'tickets_mes' => $consumosMes->where('tipo', 'ticket')->count(),
            'visitas_mes' => $consumosMes->where('tipo', 'visita')->count(),
            'ahorro_total_mes' => $consumosMes->sum('ahorro'),
        ];

        // Últimos consumos (historial reciente)
        $ultimosConsumos = \App\Models\PolizaConsumo::with(['poliza.cliente'])
            ->orderByDesc('fecha_consumo')
            ->limit(15)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'icono' => $c->icono,
                'tipo' => $c->tipo_label,
                'descripcion' => $c->descripcion,
                'cliente' => $c->poliza?->cliente?->nombre_razon_social ?? 'N/A',
                'fecha' => $c->fecha_consumo->format('d/m H:i'),
                'ahorro' => $c->ahorro,
            ]);

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

        return Inertia::render('PolizaServicio/Dashboard', [
            'stats' => array_merge($stats, [
                'tasa_retencion' => $this->calcularTasaRetencion(),
            ]),
            'statsConsumo' => $statsConsumo,
            'proximasVencer' => $proximasVencer,
            'excesoTickets' => $excesoTickets->values(),
            'excesoHoras' => $excesoHoras->values(),
            'topConsumo' => $topConsumo,
            'ultimosCobros' => $ultimosCobros,
            'ultimosConsumos' => $ultimosConsumos,
        ]);
    }

    /**
     * Historial de consumo de una póliza específica (Phase 3)
     */
    public function historialConsumo(PolizaServicio $polizas_servicio)
    {
        $polizas_servicio->load([
            'cliente',
            'tickets' => function ($q) {
                $q->whereNotNull('horas_trabajadas')
                    ->orderByDesc('created_at')
                    ->limit(50);
            }
        ]);

        // Agrupar consumo por mes
        $consumoPorMes = $polizas_servicio->tickets()
            ->whereNotNull('horas_trabajadas')
            ->selectRaw("DATE_TRUNC('month', created_at) as mes, SUM(horas_trabajadas) as total_horas, COUNT(*) as total_tickets")
            ->groupByRaw("DATE_TRUNC('month', created_at)")
            ->orderByDesc('mes')
            ->limit(12)
            ->get();

        // Tickets recientes con horas
        $ticketsConHoras = $polizas_servicio->tickets()
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
            'poliza' => $polizas_servicio,
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
    public function generarCobro(PolizaServicio $polizas_servicio)
    {
        try {
            $monto = $polizas_servicio->monto_mensual;
            $iva = round($monto * 0.16, 2);

            $cobro = \App\Models\CuentasPorCobrar::create([
                'empresa_id' => $polizas_servicio->empresa_id,
                'cliente_id' => $polizas_servicio->cliente_id,
                'cobrable_type' => PolizaServicio::class,
                'cobrable_id' => $polizas_servicio->id,
                'concepto' => "Mensualidad Póliza {$polizas_servicio->folio}",
                'monto_subtotal' => $monto,
                'monto_iva' => $iva,
                'monto_total' => $monto + $iva,
                'estado' => 'pendiente',
                'fecha_vencimiento' => Carbon::now()->addDays(15),
                'notas' => "Cobro generado manualmente el " . Carbon::now()->format('d/m/Y'),
            ]);

            $polizas_servicio->update(['ultimo_cobro_generado_at' => now()]);

            return back()->with('success', "Cobro generado correctamente por " . number_format((float) $cobro->monto_total, 2) . " MXN");
        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar cobro: ' . $e->getMessage());
        }
    }

    /**
     * Enviar recordatorio de renovación al cliente
     */
    public function enviarRecordatorioRenovacion(PolizaServicio $polizas_servicio)
    {
        try {
            $cliente = $polizas_servicio->cliente;
            if (!$cliente || !$cliente->email) {
                return back()->with('error', 'El cliente no tiene email configurado');
            }

            $cliente->notify(new \App\Notifications\PolizaRenovacionNotification($polizas_servicio));

            return back()->with('success', 'Recordatorio de renovación enviado a ' . $cliente->email);
        } catch (\Exception $e) {
            return back()->with('error', 'Error al enviar recordatorio: ' . $e->getMessage());
        }
    }
}

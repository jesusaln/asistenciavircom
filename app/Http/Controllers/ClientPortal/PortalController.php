<?php

namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\PolizaServicio;
use App\Models\PolizaConsumo;
use App\Models\Venta;
use App\Models\Empresa;
use App\Models\EmpresaConfiguracion;
use App\Models\LandingFaq;
use App\Support\EmpresaResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Enums\EstadoVenta;
use Inertia\Inertia;

class PortalController extends Controller
{
    private function getEmpresaBranding()
    {
        $empresaId = EmpresaResolver::resolveId();
        $empresaModel = Empresa::find($empresaId);
        $configuracion = EmpresaConfiguracion::getConfig($empresaId);

        return $empresaModel ? array_merge($empresaModel->toArray(), [
            'color_principal' => $configuracion->color_principal,
            'color_secundario' => $configuracion->color_secundario,
            'color_terciario' => $configuracion->color_terciario,
            'logo_url' => $configuracion->logo_url,
            'favicon_url' => $configuracion->favicon_url,
            'nombre_comercial_config' => $configuracion->nombre_empresa,
            'telefono' => $configuracion->telefono,
            'email' => $configuracion->email,
        ]) : null;
    }
    public function dashboard(Request $request)
    {
        $cliente = Auth::guard('client')->user();

        if (!$cliente->activo) {
            return redirect()->route('catalogo.index')->with('warning', 'Aún no tienes autorización para acceder al Panel Completo. Nuestro equipo se comunicará contigo en breve para habilitar tu acceso. Mientras tanto, puedes explorar nuestro catálogo.');
        }

        $tickets = Ticket::where('cliente_id', $cliente->id)
            ->with(['categoria', 'asignado:id,name'])
            ->orderByDesc('created_at')
            ->paginate(10);

        $polizas = PolizaServicio::where('cliente_id', $cliente->id)
            ->where('estado', '!=', 'cancelada')
            ->with(['equipos']) // Cargamos los equipos vinculados
            ->orderByDesc('created_at')
            ->get()
            ->each(function ($poliza) {
                $poliza->append(['porcentaje_horas', 'porcentaje_tickets', 'dias_para_vencer', 'excede_horas', 'tickets_mes_actual_count']);
            });

        // Ventas pendientes de pago
        $ventasPendientes = Venta::where('cliente_id', $cliente->id)
            ->whereIn('estado', ['pendiente', 'vencida'])
            ->orderBy('fecha')
            ->get()
            ->map(function ($venta) {
                return [
                    'id' => $venta->id,
                    'tipo' => 'venta',
                    'folio' => $venta->folio ?? $venta->numero_venta,
                    'concepto' => 'Venta ' . ($venta->folio ?? $venta->numero_venta),
                    'total' => $venta->total,
                    'fecha' => $venta->fecha,
                    'fecha_vencimiento' => $venta->fecha_vencimiento ?? $venta->fecha,
                    'estado' => $venta->estado,
                    'puede_pagar_credito' => true,
                ];
            });

        // Cuentas por cobrar pendientes (pólizas, servicios, etc.)
        $cxcPendientes = \App\Models\CuentasPorCobrar::where('cliente_id', $cliente->id)
            ->whereIn('estado', ['pendiente', 'vencido'])
            ->where('monto_pendiente', '>', 0)
            ->orderBy('fecha_vencimiento')
            ->get()
            ->map(function ($cxc) {
                $concepto = $cxc->notas ?? 'Cuenta por Cobrar';
                if ($cxc->cobrable_type && $cxc->cobrable) {
                    if (str_contains($cxc->cobrable_type, 'PolizaServicio')) {
                        $concepto = 'Mensualidad Póliza ' . ($cxc->cobrable->folio ?? '');
                    }
                }
                return [
                    'id' => $cxc->id,
                    'tipo' => 'cxc',
                    'folio' => 'CXC-' . str_pad($cxc->id, 5, '0', STR_PAD_LEFT),
                    'concepto' => $concepto,
                    'total' => $cxc->monto_pendiente,
                    'fecha' => $cxc->created_at,
                    'fecha_vencimiento' => $cxc->fecha_vencimiento,
                    'estado' => $cxc->estado,
                    'puede_pagar_credito' => false, // Las CxC no se pagan con crédito desde portal
                ];
            });

        // Combinar ambas listas
        $pagosPendientes = $ventasPendientes->concat($cxcPendientes)
            ->sortBy('fecha_vencimiento')
            ->values();

        $rentas = \App\Models\Renta::where('cliente_id', $cliente->id)
            ->where('estado', 'activo')
            ->with(['equipos'])
            ->get();

        $citas = \App\Models\Cita::where('cliente_id', $cliente->id)
            ->where('fecha_hora', '>=', now())
            ->orderBy('fecha_hora', 'asc')
            ->with('tecnico:id,name')
            ->take(3)
            ->get();

        return Inertia::render('Portal/Dashboard', [
            'tickets' => $tickets,
            'polizas' => $polizas,
            'pagosPendientes' => $pagosPendientes,
            'rentas' => $rentas,
            'citas' => $citas,
            'pedidos' => \App\Models\Pedido::where('cliente_id', $cliente->id)->orderByDesc('created_at')->limit(10)->get(),
            'ventas' => Venta::where('cliente_id', $cliente->id)->orderByDesc('fecha')->paginate(20)->withQueryString(), // Historial de ventas paginado
            'credenciales' => $cliente->credenciales()->get()->map(function ($c) {
                // No enviamos el password real al dashboard inicial, solo metadatos
                return [
                    'id' => $c->id,
                    'nombre' => $c->nombre,
                    'usuario' => $c->usuario,
                    'host' => $c->host,
                    'puerto' => $c->puerto,
                    'notas' => $c->notas,
                ];
            }),
            'cliente' => $cliente->only(
                'id',
                'nombre_razon_social',
                'email',
                'telefono',
                'rfc',
                'curp',
                'regimen_fiscal',
                'uso_cfdi',
                'domicilio_fiscal_cp',
                'calle',
                'numero_exterior',
                'numero_interior',
                'colonia',
                'municipio',
                'estado',
                'codigo_postal',
                'credito_activo',
                'limite_credito',
                'dias_credito',
                'estado_credito',
                'saldo_pendiente',
                'credito_disponible',
                'tipo_persona'
            ),
            'empresa' => $this->getEmpresaBranding(),
            'faqs' => LandingFaq::where('empresa_id', EmpresaResolver::resolveId())->where('activo', true)->orderBy('orden')->get(),
            'catalogos' => [
                'regimenes' => \Illuminate\Support\Facades\Cache::remember('sat_regimenes_fiscales', 86400, fn() => \App\Models\SatRegimenFiscal::orderBy('clave')->get()),
                'usos_cfdi' => \Illuminate\Support\Facades\Cache::remember('sat_usos_cfdi', 86400, fn() => \App\Models\SatUsoCfdi::orderBy('clave')->get()),
                'estados' => \Illuminate\Support\Facades\Cache::remember('sat_estados_activos', 86400, fn() => \App\Models\SatEstado::where('activo', true)->orderBy('nombre')->get()),
            ]
        ]);
    }

    public function create()
    {
        return Inertia::render('Portal/CreateTicket', [
            'categorias' => TicketCategory::activas()->ordenadas()->get(),
            'empresa' => $this->getEmpresaBranding(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'categoria_id' => 'nullable|exists:ticket_categories,id',
            'prioridad' => 'required|in:baja,media,alta,urgente',
        ]);

        $cliente = Auth::guard('client')->user();

        // =====================================================
        // FASE 1 - Mejora 1.1: Validación de Póliza Activa
        // =====================================================
        $polizaActiva = PolizaServicio::where('cliente_id', $cliente->id)
            ->whereIn('estado', ['activa', 'vencida_en_gracia'])
            ->where(function ($q) {
                $q->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', now()->subDays(5)); // Considera periodo de gracia
            })
            ->first();

        // Si no tiene póliza activa, verificar si la categoría requiere póliza
        $categoria = TicketCategory::find($validated['categoria_id']);
        $requierePoliza = $categoria && $categoria->consume_poliza;

        if ($requierePoliza && !$polizaActiva) {
            return back()->withErrors([
                'poliza' => 'No tienes una póliza de servicio activa. Para solicitar soporte técnico, primero contrata un plan o regulariza tu póliza pendiente de pago.'
            ])->withInput();
        }

        // Advertir si la póliza está en periodo de gracia
        $advertenciaGracia = null;
        if ($polizaActiva && $polizaActiva->estado === 'vencida_en_gracia') {
            $diasRestantes = $polizaActiva->dias_gracia_restantes ?? 0;
            $advertenciaGracia = "⚠️ Tu póliza está en periodo de gracia. Tienes {$diasRestantes} días para renovar antes de que se suspenda el servicio.";
        }

        // Verificar si excede límite de tickets (pero permitir crear con advertencia)
        $advertenciaLimite = null;
        if ($polizaActiva && $polizaActiva->limite_mensual_tickets) {
            $ticketsUsados = $polizaActiva->tickets_soporte_consumidos_mes ?? 0;
            if ($ticketsUsados >= $polizaActiva->limite_mensual_tickets) {
                $costoExtra = $polizaActiva->planPoliza?->costo_ticket_extra ?? 150;
                $advertenciaLimite = "Has alcanzado tu límite de {$polizaActiva->limite_mensual_tickets} tickets mensuales. Este ticket tendrá un costo adicional de \${$costoExtra}.";
            }
        }

        // Generar número de folio
        $year = date('Y');
        $lastTicket = Ticket::whereYear('created_at', $year)->latest()->first();
        $sequence = $lastTicket ? (int) substr($lastTicket->numero, -5) + 1 : 1;
        $numero = 'TKT-' . $year . '-' . str_pad($sequence, 5, '0', STR_PAD_LEFT);

        // Calcular SLA basado en póliza (prioritaria) o categoría
        $fechaLimite = null;
        if ($polizaActiva && $polizaActiva->sla_horas_respuesta) {
            $fechaLimite = now()->addHours($polizaActiva->sla_horas_respuesta);
        } elseif ($categoria && $categoria->sla_horas) {
            $fechaLimite = now()->addHours($categoria->sla_horas);
        }

        $ticket = Ticket::create([
            'numero' => $numero,
            'titulo' => $validated['titulo'],
            'descripcion' => $validated['descripcion'],
            'estado' => 'abierto',
            'prioridad' => $validated['prioridad'],
            'origen' => 'portal',
            'categoria_id' => $validated['categoria_id'],
            'cliente_id' => $cliente->id,
            'poliza_id' => $polizaActiva?->id, // Vincular automáticamente a la póliza
            'nombre_contacto' => $cliente->nombre_razon_social,
            'email_contacto' => $cliente->email,
            'telefono_contacto' => $cliente->telefono ?? $cliente->celular,
            'fecha_limite' => $fechaLimite,
            'empresa_id' => $cliente->empresa_id,
        ]);

        // Construir mensaje de éxito con advertencias si aplica
        $mensajeExito = "Ticket {$numero} creado exitosamente.";
        if ($advertenciaGracia) {
            $mensajeExito .= " " . $advertenciaGracia;
        }
        if ($advertenciaLimite) {
            $mensajeExito .= " " . $advertenciaLimite;
        }

        return redirect()->route('portal.dashboard')
            ->with('success', $mensajeExito);
    }

    public function show(Ticket $ticket)
    {
        // Verificar que el ticket pertenezca al cliente logueado
        if ($ticket->cliente_id !== Auth::guard('client')->id()) {
            abort(403);
        }

        $ticket->load(['categoria', 'asignado:id,name', 'comentarios.user']);

        return Inertia::render('Portal/ShowTicket', [
            'ticket' => $ticket,
            'empresa' => $this->getEmpresaBranding(),
        ]);
    }

    public function storeComment(Request $request, Ticket $ticket)
    {
        if ($ticket->cliente_id !== Auth::guard('client')->id()) {
            abort(403);
        }

        $request->validate([
            'contenido' => 'required|string',
        ]);

        $ticket->comentarios()->create([
            'contenido' => $request->contenido,
            'es_interno' => false,
            'user_id' => null,
            'tipo' => 'respuesta', // Ajustar según enum si existe
        ]);

        return back();
    }

    public function polizasIndex()
    {
        $cliente = Auth::guard('client')->user();
        $polizas = PolizaServicio::where('cliente_id', $cliente->id)
            ->where('estado', '!=', 'cancelada')
            ->with(['equipos'])
            ->withCount([
                'tickets as tickets_mes_actual_count' => function ($query) {
                    $query->where('tipo_servicio', '!=', 'costo')
                        ->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->whereHas('categoria', function ($q) {
                            $q->where('consume_poliza', true);
                        });
                }
            ])
            ->orderByDesc('created_at')
            ->get()
            ->each(function ($poliza) {
                $poliza->append(['dias_para_vencer']);
            });

        return Inertia::render('Portal/Polizas/Index', [
            'polizas' => $polizas,
            'empresa' => $this->getEmpresaBranding(),
        ]);
    }

    public function polizaShow(PolizaServicio $poliza)
    {
        if ($poliza->cliente_id !== Auth::guard('client')->id()) {
            abort(403);
        }

        $poliza->load([
            'planPoliza',
            'equipos',
            'servicios',
            'tickets' => function ($q) {
                $q->orderByDesc('created_at')->limit(10);
            },
            'cuentasPorCobrar' => function ($q) {
                $q->orderByDesc('created_at')->limit(12);
            },
            // Cargar configuración de mantenimientos y próximas ejecuciones
            'mantenimientos' => function ($q) {
                $q->where('activo', true);
            },
            'mantenimientos.ejecuciones' => function ($q) {
                $q->orderByDesc('fecha_programada')->limit(5);
            }
        ])->loadCount([
                    'tickets as tickets_mes_actual_count' => function ($query) {
                        $query->where('tipo_servicio', '!=', 'costo')
                            ->whereMonth('created_at', now()->month)
                            ->whereYear('created_at', now()->year)
                            ->whereHas('categoria', function ($q) {
                                $q->where('consume_poliza', true);
                            });
                    }
                ]);

        // FASE 4 - Mejora 4.1 y 4.4: Datos para gráfica y tickets del mes
        $ticketsMesActual = $poliza->tickets()
            ->where('tipo_servicio', '!=', 'costo')
            ->whereMonth('tickets.created_at', now()->month)
            ->whereYear('tickets.created_at', now()->year)
            ->whereHas('categoria', function ($q) {
                $q->where('consume_poliza', true);
            })
            ->latest()
            ->get();

        $historicoConsumo = $poliza->consumos()
            ->selectRaw('EXTRACT(YEAR FROM fecha_consumo) as year, EXTRACT(MONTH FROM fecha_consumo) as month, tipo, SUM(cantidad) as total')
            ->where('fecha_consumo', '>=', now()->subMonths(6))
            ->groupBy('year', 'month', 'tipo')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $poliza->append(['porcentaje_horas', 'porcentaje_tickets', 'dias_para_vencer', 'excede_horas', 'tickets_mes_actual_count']);

        return Inertia::render('Portal/Polizas/Show', [
            'poliza' => $poliza,
            'ticketsMesActual' => $ticketsMesActual,
            'historicoConsumo' => $historicoConsumo,
            'empresa' => $this->getEmpresaBranding(),
        ]);
    }

    /**
     * Exportar fechas clave a calendario (.ics)
     * FASE 4 - Mejora 4.5
     */
    public function exportCalendar(PolizaServicio $poliza)
    {
        if ($poliza->cliente_id !== Auth::guard('client')->id()) {
            abort(403);
        }

        $events = [];

        // Evento 1: Vencimiento de la póliza
        if ($poliza->fecha_fin) {
            $fechaFin = Carbon::parse($poliza->fecha_fin);
            $events[] = [
                'start' => $fechaFin->format('Ymd'),
                'end' => $fechaFin->copy()->addDay()->format('Ymd'),
                'summary' => "Vencimiento Póliza: {$poliza->folio}",
                'description' => "Tu póliza {$poliza->nombre} vence hoy. Contacta a soporte para renovación.",
            ];
        }

        // Evento 2: Próximo cobro
        $diaCobro = $poliza->dia_cobro ?: 1;
        $fechaCobro = now()->day($diaCobro);
        if ($fechaCobro->isPast())
            $fechaCobro->addMonth();

        $events[] = [
            'start' => $fechaCobro->format('Ymd'),
            'end' => $fechaCobro->copy()->addDay()->format('Ymd'),
            'summary' => "Cobro Póliza: {$poliza->folio}",
            'description' => "Fecha programada para el cobro mensual de tu póliza de servicio.",
        ];

        // Generar ICS
        $ics = "BEGIN:VCALENDAR\nVERSION:2.0\nPRODID:-//AsistenciaVircom//NONSGML v1.0//EN\nCALSCALE:GREGORIAN\n";

        foreach ($events as $event) {
            $ics .= "BEGIN:VEVENT\n";
            $ics .= "DTSTART;VALUE=DATE:" . $event['start'] . "\n";
            $ics .= "DTEND;VALUE=DATE:" . $event['end'] . "\n";
            $ics .= "SUMMARY:" . $event['summary'] . "\n";
            $ics .= "DESCRIPTION:" . $event['description'] . "\n";
            $ics .= "END:VEVENT\n";
        }

        $ics .= "END:VCALENDAR";

        return response($ics)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="poliza-' . $poliza->folio . '.ics"');
    }

    public function polizaHistorial(Request $request, PolizaServicio $poliza)
    {
        if ($poliza->cliente_id !== Auth::guard('client')->id()) {
            abort(403);
        }

        $mes = $request->input('mes', now()->format('Y-m'));
        $date = Carbon::parse($mes);

        $consumos = PolizaConsumo::where('poliza_id', $poliza->id)
            ->whereMonth('fecha_consumo', $date->month)
            ->whereYear('fecha_consumo', $date->year)
            ->orderByDesc('fecha_consumo')
            ->get();

        $stats = [
            'total_ahorro' => $consumos->sum('ahorro'),
            'total_tickets' => $consumos->where('tipo', 'ticket')->sum('cantidad'),
            'total_visitas' => $consumos->where('tipo', 'visita')->sum('cantidad'),
            'total_horas' => $consumos->where('tipo', 'hora')->sum('cantidad'),
        ];

        return Inertia::render('Portal/Polizas/Historial', [
            'poliza' => $poliza,
            'consumos' => $consumos,
            'stats' => $stats,
            'filtros' => [
                'mes' => $mes
            ],
            'empresa' => $this->getEmpresaBranding(),
        ]);
    }

    public function pedidosIndex()
    {
        $cliente = Auth::guard('client')->user();
        $pedidos = \App\Models\Pedido::where('cliente_id', $cliente->id)
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Portal/Pedidos/Index', [
            'pedidos' => $pedidos,
            'empresa' => $this->getEmpresaBranding(),
        ]);
    }

    public function pedidoShow($id)
    {
        $pedido = \App\Models\Pedido::with(['items.pedible'])->findOrFail($id);

        if ($pedido->cliente_id !== Auth::guard('client')->id()) {
            abort(403);
        }

        return Inertia::render('Portal/Pedidos/Show', [
            'pedido' => $pedido,
            'empresa' => $this->getEmpresaBranding(),
        ]);
    }

    public function imprimirContrato(PolizaServicio $poliza)
    {
        // Verificar que la póliza pertenezca al cliente logueado
        if ($poliza->cliente_id !== Auth::guard('client')->id()) {
            abort(403);
        }

        $empresaId = EmpresaResolver::resolveId();
        $empresa = EmpresaConfiguracion::getConfig($empresaId);
        $cliente = $poliza->cliente;

        // Renderizar vista Blade optimizada para impresión/PDF
        return view('portal.impresion.poliza', [
            'poliza' => $poliza,
            'empresa' => $empresa,
            'cliente' => $cliente,
            'logo' => $empresa->logo_url ?? asset('images/logo.png'),
        ]);
    }

    public function citasIndex()
    {
        $cliente = Auth::guard('client')->user();
        $citas = \App\Models\Cita::where('cliente_id', $cliente->id)
            ->with('tecnico:id,name')
            ->orderByDesc('fecha_hora')
            ->paginate(15);

        return Inertia::render('Portal/Citas/Index', [
            'citas' => $citas,
            'empresa' => $this->getEmpresaBranding(),
        ]);
    }

    public function showManual()
    {
        try {
            $manualPath = base_path('docs/MANUAL_CLIENTE.md');
            $manualContent = \Illuminate\Support\Facades\File::get($manualPath);
        } catch (\Exception $e) {
            Log::error('No se pudo encontrar el manual del cliente: ' . $e->getMessage());
            $manualContent = "El manual del usuario no se encuentra disponible en este momento. Por favor, contacte a soporte.";
        }

        return Inertia::render('Portal/Manual', [
            'manualContent' => $manualContent,
            'empresa' => $this->getEmpresaBranding(),
        ]);
    }

    public function revelarCredencial(Request $request, $id)
    {
        $cliente = Auth::guard('client')->user();
        $credencial = $cliente->credenciales()->findOrFail($id);

        // Registrar el acceso en el log para auditoría
        $credencial->registrarAcceso('revelado_portal_cliente');

        return response()->json([
            'password' => $credencial->password,
        ]);
    }

    public function payVentaWithCredit(Request $request)
    {
        $validated = $request->validate([
            'venta_id' => 'required|exists:ventas,id',
        ]);

        $cliente = Auth::guard('client')->user();
        $venta = Venta::where('cliente_id', $cliente->id)->findOrFail($validated['venta_id']);

        if ($venta->estado === EstadoVenta::Pagado) {
            return response()->json(['success' => false, 'message' => 'Esta factura ya está pagada.'], 400);
        }

        if (!$cliente->credito_activo || $cliente->estado_credito !== 'autorizado') {
            return response()->json(['success' => false, 'message' => 'Su línea de crédito no está activa o autorizada.'], 403);
        }

        // Capturar saldo antes del pago para auditoría
        $saldoAntes = $cliente->saldo_pendiente;
        $creditoDisponibleAntes = $cliente->credito_disponible;

        if ($creditoDisponibleAntes < $venta->total) {
            return response()->json(['success' => false, 'message' => 'Saldo insuficiente en su línea de crédito.'], 400);
        }

        DB::beginTransaction();
        try {
            // Actualizar Venta
            $venta->estado = EstadoVenta::Pagado;
            $venta->pagado = true;
            $venta->fecha_pago = now();
            $venta->metodo_pago = 'credito';
            $venta->notas_pago = 'Pagado desde Portal de Clientes usando Crédito Comercial.';
            $venta->save();

            // Si tiene Cuenta por Cobrar, marcarla como pagada
            if ($venta->cuentaPorCobrar) {
                $cxc = $venta->cuentaPorCobrar;
                $cxc->setAttribute('monto_pendiente', 0);
                $cxc->estado = 'pagado';
                $cxc->save();
            }

            DB::commit();

            // Registrar auditoría del pago con crédito
            Log::info('Pago con Crédito Comercial', [
                'cliente_id' => $cliente->id,
                'cliente_nombre' => $cliente->nombre_razon_social,
                'venta_id' => $venta->id,
                'venta_folio' => $venta->folio ?? $venta->numero_venta,
                'monto' => $venta->total,
                'saldo_pendiente_antes' => $saldoAntes,
                'credito_disponible_antes' => $creditoDisponibleAntes,
                'saldo_pendiente_despues' => $cliente->fresh()->saldo_pendiente,
                'credito_disponible_despues' => $cliente->fresh()->credito_disponible,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json(['success' => true, 'message' => 'Factura pagada con éxito usando su crédito comercial.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error pagando venta con crédito: ' . $e->getMessage(), [
                'cliente_id' => $cliente->id,
                'venta_id' => $venta->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'message' => 'Hubo un error al procesar el pago.'], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        $cliente = Auth::guard('client')->user();

        $validated = $request->validate([
            'nombre_razon_social' => 'required|string|max:255',
            'email' => 'required|email|unique:clientes,email,' . $cliente->id,
            'telefono' => 'nullable|string|max:20',
            'calle' => 'nullable|string|max:255',
            'numero_exterior' => 'nullable|string|max:20',
            'numero_interior' => 'nullable|string|max:20',
            'colonia' => 'nullable|string|max:255',
            'municipio' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:5',
            'codigo_postal' => 'nullable|string|max:10',
            'domicilio_fiscal_cp' => 'nullable|string|max:10',
            'rfc' => 'nullable|string|max:20',
            'regimen_fiscal' => 'nullable|string|max:10',
            'uso_cfdi' => 'nullable|string|max:10',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = bcrypt($validated['password']);
        }

        $cliente->update($validated);

        return back()->with('success', 'Perfil actualizado correctamente.');
    }
    public function descargarVentaPdf(Request $request, $id)
    {
        $cliente = Auth::guard('client')->user();
        $venta = Venta::where('cliente_id', $cliente->id)
            ->with([
                'cliente',
                'almacen',
                'items.series.productoSerie',
                'vendedor'
            ])
            ->with([
                'items.ventable' => function ($morphTo) {
                    $morphTo->morphWith([
                        \App\Models\Producto::class => ['kitItems.item'],
                    ]);
                }
            ])
            ->findOrFail($id);

        try {
            /** @var \App\Services\PdfGeneratorService $pdfService */
            $pdfService = app(\App\Services\PdfGeneratorService::class);

            $piePagina = EmpresaConfiguracion::getPiePagina('ventas');

            $pdf = $pdfService->loadView('ventas.pdf', [
                'venta' => $venta,
                'piePagina' => $piePagina
            ]);

            $filename = 'venta-' . $venta->numero_venta . '.pdf';

            // Siempre descargar para el cliente
            return $pdfService->download($pdf, $filename);

        } catch (\Exception $e) {
            Log::error('Error generando PDF de venta desde portal: ' . $e->getMessage());
            return back()->with('error', 'No se pudo generar el documento. Intente más tarde.');
        }
    }
    public function descargarBeneficiosPdf(PolizaServicio $poliza)
    {
        if ($poliza->cliente_id !== Auth::guard('client')->id()) {
            abort(403);
        }

        try {
            /** @var \App\Services\PdfGeneratorService $pdfService */
            $pdfService = app(\App\Services\PdfGeneratorService::class);

            $empresa = $this->getEmpresaBranding();

            $pdf = $pdfService->loadView('portal.impresion.beneficios', [
                'poliza' => $poliza,
                'cliente' => $poliza->cliente,
                'empresa' => $empresa
            ]);

            return $pdfService->download($pdf, 'beneficios-poliza-' . $poliza->id . '.pdf');
        } catch (\Exception $e) {
            Log::error('Error generando PDF beneficios: ' . $e->getMessage());
            return back()->with('error', 'Error generando documento.');
        }
    }

    public function descargarContratoPdf(PolizaServicio $poliza)
    {
        if ($poliza->cliente_id !== Auth::guard('client')->id()) {
            abort(403);
        }

        try {
            /** @var \App\Services\PdfGeneratorService $pdfService */
            $pdfService = app(\App\Services\PdfGeneratorService::class);

            // Usamos un helper para obtener config como objeto, ya que poliza.blade.php usa sintaxis de objeto ($empresa->nombre)
            // Ojo: getEmpresaBranding devuelve array. poliza.blade.php usa $empresa->nombre_comercial
            // Debo adaptar o pasar el objeto correcto.
            $empresaId = EmpresaResolver::resolveId();
            $empresaConfig = EmpresaConfiguracion::getConfig($empresaId);
            $empresaModel = Empresa::find($empresaId); // Para datos fiscales

            // Combinamos para que la vista tenga todo
            // poliza.blade.php usa $logo (url) y $empresa (objeto)

            $pdf = $pdfService->loadView('portal.impresion.poliza', [
                'poliza' => $poliza,
                'cliente' => $poliza->cliente,
                'empresa' => $empresaConfig, // Objeto con propiedades
                'logo' => $empresaConfig->logo_url
            ]);

            return $pdfService->download($pdf, 'contrato-poliza-' . $poliza->id . '.pdf');
        } catch (\Exception $e) {
            Log::error('Error generando PDF contrato: ' . $e->getMessage());
            return back()->with('error', 'Error generando contrato.');
        }
    }

    public function descargarReporteMensualPdf(Request $request, PolizaServicio $poliza)
    {
        if ($poliza->cliente_id !== Auth::guard('client')->id()) {
            abort(403);
        }

        $mes = $request->input('mes', now()->subMonth()->month);
        $anio = $request->input('anio', now()->subMonth()->year);

        try {
            /** @var \App\Services\PdfGeneratorService $pdfService */
            $pdfService = app(\App\Services\PdfGeneratorService::class);

            $poliza->load(['cliente', 'equipos']);

            // Tickets del mes solicitado
            $tickets = $poliza->tickets()
                ->whereMonth('created_at', $mes)
                ->whereYear('created_at', $anio)
                ->with(['categoria', 'asignado'])
                ->get();

            $empresaId = EmpresaResolver::resolveId();
            $empresa = EmpresaConfiguracion::getConfig($empresaId);
            $mesNombre = Carbon::createFromDate($anio, $mes, 1)->locale('es')->monthName;

            $pdf = $pdfService->loadView('pdf.poliza-reporte-mensual', [
                'poliza' => $poliza,
                'empresa' => $empresa,
                'tickets' => $tickets,
                'mes_nombre' => ucfirst($mesNombre),
                'anio' => $anio,
                'fecha_generacion' => now()->format('d/m/Y H:i'),
                'total_horas' => $tickets->sum('horas_trabajadas'),
                'tickets_resueltos' => $tickets->whereIn('estado', ['resuelto', 'cerrado'])->count(),
            ]);

            return $pdfService->download($pdf, "Reporte-{$poliza->folio}-{$mesNombre}-{$anio}.pdf");
        } catch (\Exception $e) {
            Log::error('Error generando PDF reporte mensual: ' . $e->getMessage());
            return back()->with('error', 'Error generando reporte mensual.');
        }
    }
}

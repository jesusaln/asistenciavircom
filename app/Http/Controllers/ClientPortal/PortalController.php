<?php

namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\PolizaServicio;
use App\Models\Venta;
use App\Models\Empresa;
use App\Models\EmpresaConfiguracion;
use App\Models\LandingFaq;
use App\Support\EmpresaResolver;
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

        $pagosPendientes = Venta::where('cliente_id', $cliente->id)
            ->whereIn('estado', ['pendiente', 'vencida'])
            ->orderBy('fecha')
            ->get();

        $rentas = \App\Models\Renta::where('cliente_id', $cliente->id)
            ->where('estado', 'activo')
            ->with(['equipos'])
            ->get();

        return Inertia::render('Portal/Dashboard', [
            'tickets' => $tickets,
            'polizas' => $polizas,
            'pagosPendientes' => $pagosPendientes,
            'rentas' => $rentas,
            'pedidos' => \App\Models\Pedido::where('cliente_id', $cliente->id)->orderByDesc('created_at')->limit(10)->get(),
            'ventas' => Venta::where('cliente_id', $cliente->id)->orderByDesc('fecha')->limit(50)->get(), // Agregamos historial de ventas
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
            'faqs' => \App\Models\LandingFaq::where('empresa_id', \App\Support\EmpresaResolver::resolveId())->where('activo', true)->orderBy('orden')->get(),
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
            'categoria_id' => 'required|exists:ticket_categories,id',
            'prioridad' => 'required|in:baja,media,alta,urgente',
        ]);

        $cliente = Auth::guard('client')->user();

        // Generar número de folio
        $year = date('Y');
        $lastTicket = Ticket::whereYear('created_at', $year)->latest()->first();
        $sequence = $lastTicket ? (int) substr($lastTicket->numero, -5) + 1 : 1;
        $numero = 'TKT-' . $year . '-' . str_pad($sequence, 5, '0', STR_PAD_LEFT);

        // Calcular SLA basado en categoría
        $categoria = TicketCategory::find($validated['categoria_id']);
        $fechaLimite = null;
        if ($categoria && $categoria->sla_horas) {
            // $fechaLimite = now()->addBusinessHours($categoria->sla_horas); // Requiere paquete extra
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
            'nombre_contacto' => $cliente->nombre_razon_social,
            'email_contacto' => $cliente->email,
            'telefono_contacto' => $cliente->telefono ?? $cliente->celular,
            'fecha_limite' => $fechaLimite,
            'empresa_id' => $cliente->empresa_id,
        ]);

        return redirect()->route('portal.dashboard')
            ->with('success', "Ticket {$numero} creado exitosamente.");
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
            ->orderByDesc('created_at')
            ->get() // Usamos get para enviar todas, o paginate si son muchas
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
            'equipos',
            'servicios',
            'tickets' => function ($q) {
                $q->orderByDesc('created_at')->limit(10);
            },
            'cuentasPorCobrar' => function ($q) {
                $q->orderByDesc('created_at')->limit(12);
            }
        ]);

        $poliza->append(['porcentaje_horas', 'porcentaje_tickets', 'dias_para_vencer', 'excede_horas', 'tickets_mes_actual_count']);

        return Inertia::render('Portal/Polizas/Show', [
            'poliza' => $poliza,
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
        $venta = \App\Models\Venta::where('cliente_id', $cliente->id)->findOrFail($validated['venta_id']);

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
                $cxc->monto_pendiente = 0;
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
}

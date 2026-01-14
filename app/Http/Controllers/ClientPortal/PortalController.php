<?php

namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\PolizaServicio;
use App\Models\Venta;
use App\Models\Empresa;
use App\Models\EmpresaConfiguracion;
use App\Support\EmpresaResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            ->orderByDesc('created_at')
            ->get();

        $pagosPendientes = Venta::where('cliente_id', $cliente->id)
            ->whereIn('estado', ['pendiente', 'vencida'])
            ->orderBy('fecha')
            ->get();

        return Inertia::render('Portal/Dashboard', [
            'tickets' => $tickets,
            'polizas' => $polizas,
            'pagosPendientes' => $pagosPendientes,
            'ventas' => Venta::where('cliente_id', $cliente->id)->orderByDesc('fecha')->limit(50)->get(), // Agregamos historial de ventas
            'cliente' => $cliente->only('id', 'nombre_razon_social', 'email'),
            'empresa' => $this->getEmpresaBranding(),
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
            'empresa_id' => null, // O la empresa del cliente si existe
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
}

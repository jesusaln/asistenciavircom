<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketComment;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Producto;
use App\Models\EmpresaConfiguracion;
use App\Models\Empresa;
use App\Support\EmpresaResolver;
use App\Models\Venta; // <<< NUEVO
use App\Models\VentaItem; // <<< NUEVO
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        if (!\Illuminate\Support\Facades\Gate::allows('view soporte')) {
            abort(403);
        }
        $empresaId = EmpresaResolver::resolveId();

        $query = Ticket::with(['cliente', 'asignado', 'categoria', 'creador']);
        // ->where('empresa_id', $empresaId); // Deshabilitado temporalmente hasta tener multi-tenancy completo

        // Filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('prioridad')) {
            $query->where('prioridad', $request->prioridad);
        }

        if ($request->filled('asignado_id')) {
            if ($request->asignado_id === 'sin_asignar') {
                $query->whereNull('asignado_id');
            } else {
                $query->where('asignado_id', $request->asignado_id);
            }
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('numero', 'ilike', "%{$buscar}%")
                    ->orWhere('titulo', 'ilike', "%{$buscar}%")
                    ->orWhere('telefono_contacto', 'ilike', "%{$buscar}%")
                    ->orWhereHas('cliente', fn($c) => $c->where('nombre', 'ilike', "%{$buscar}%"));
            });
        }

        // Ordenamiento
        $ordenCampo = $request->get('orden', 'created_at');
        $ordenDir = $request->get('dir', 'desc');
        $query->orderBy($ordenCampo, $ordenDir);

        $tickets = $query->paginate(20)->appends($request->all());

        // Estadísticas rápidas
        $stats = [
            'abiertos' => Ticket::whereIn('estado', ['abierto', 'en_progreso', 'pendiente'])->count(),
            'sin_asignar' => Ticket::abiertos()->whereNull('asignado_id')->count(),
            'vencidos' => Ticket::vencidos()->count(),
            'resueltos_hoy' => Ticket::where('estado', 'resuelto')->whereDate('completed_at', today())->count(),
        ];

        return Inertia::render('Soporte/Index', [
            'tickets' => $tickets,
            'stats' => $stats,
            'categorias' => TicketCategory::where('empresa_id', $empresaId)->activas()->ordenadas()->get(),
            'usuarios' => User::all(['id', 'name']),
            'filtros' => $request->only(['estado', 'prioridad', 'asignado_id', 'categoria_id', 'buscar']),
        ]);
    }

    public function dashboard()
    {
        $empresaId = EmpresaResolver::resolveId();

        // Tickets por estado
        $porEstado = Ticket::select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado');

        // Tickets por prioridad (solo abiertos)
        $porPrioridad = Ticket::abiertos()
            ->select('prioridad', DB::raw('count(*) as total'))
            ->groupBy('prioridad')
            ->pluck('total', 'prioridad');

        // Tickets por técnico asignado
        $porTecnico = Ticket::abiertos()
            ->select('asignado_id', DB::raw('count(*) as total'))
            ->groupBy('asignado_id')
            ->with('asignado:id,name')
            ->get();

        // Tiempo promedio de resolución (últimos 30 días) - usando completed_at
        $tiempoPromedioResolucion = Ticket::where('empresa_id', $empresaId)
            ->where('estado', 'resuelto')
            ->whereNotNull('completed_at')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (completed_at - created_at))/3600) as promedio_horas')
            ->value('promedio_horas');

        // Tickets creados últimos 7 días
        $ticketsUltimos7Dias = Ticket::where('empresa_id', $empresaId)
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw("DATE(created_at) as fecha, count(*) as total")
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        // Estadísticas de resueltos hoy (usando completed_at)
        $resueltosHoy = Ticket::where('empresa_id', $empresaId)
            ->where('estado', 'resuelto')
            ->whereDate('completed_at', today())
            ->count();

        return Inertia::render('Soporte/Dashboard', [
            'porEstado' => $porEstado,
            'porPrioridad' => $porPrioridad,
            'porTecnico' => $porTecnico,
            'tiempoPromedioResolucion' => round($tiempoPromedioResolucion ?? 0, 1),
            'ticketsUltimos7Dias' => $ticketsUltimos7Dias,
            'cumplimientoSla' => 100, // SLA simplificado por ahora
            'stats' => [
                'total_abiertos' => Ticket::where('empresa_id', $empresaId)->abiertos()->count(),
                'urgentes' => Ticket::where('empresa_id', $empresaId)->abiertos()->where('prioridad', 'urgente')->count(),
                'vencidos' => 0, // Simplificado - no hay scheduled_at usado de esta forma
                'resueltos_hoy' => $resueltosHoy,
            ],
        ]);
    }

    public function create()
    {
        $empresaId = EmpresaResolver::resolveId();

        return Inertia::render('Soporte/Create', [
            'categorias' => TicketCategory::where('empresa_id', $empresaId)->activas()->ordenadas()->get(),
            'usuarios' => User::all(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'prioridad' => 'required|in:baja,media,alta,urgente',
            'categoria_id' => 'nullable|exists:ticket_categories,id',
            'cliente_id' => 'nullable|exists:clientes,id',
            'asignado_id' => 'nullable|exists:users,id',
            'producto_id' => 'nullable|exists:productos,id',
            'origen' => 'required|in:telefono,email,web,whatsapp,presencial',
            'tipo_servicio' => 'required|in:garantia,costo',
            'telefono_contacto' => 'nullable|string|max:20',
            'email_contacto' => 'nullable|email|max:255',
            'nombre_contacto' => 'nullable|string|max:255',
            'folio_manual' => 'nullable|string|max:50',
            'poliza_id' => 'nullable|exists:polizas_servicio,id',
            'archivos.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $rutasArchivos = [];

        DB::beginTransaction();

        try {
            // Procesar archivos si existen
            if ($request->hasFile('archivos')) {
                foreach ($request->file('archivos') as $archivo) {
                    $ordenArchivo = 'ticket_' . time() . '_' . uniqid() . '.webp';
                    $rutaRelativa = 'tickets/' . $ordenArchivo;
                    $rutaCompleta = storage_path('app/public/' . $rutaRelativa);

                    // Asegurar directorio
                    if (!file_exists(dirname($rutaCompleta))) {
                        mkdir(dirname($rutaCompleta), 0755, true);
                    }

                    // Validación Estricta (Magic Bytes) via getimagesize
                    $imagenInfo = @getimagesize($archivo->getPathname());
                    if (!$imagenInfo) {
                        throw new \Exception("El archivo {$archivo->getClientOriginalName()} no es una imagen válida o está corrupto.");
                    }

                    $mime = $imagenInfo['mime'];
                    $img = match ($mime) {
                        'image/jpeg' => imagecreatefromjpeg($archivo->getPathname()),
                        'image/png' => imagecreatefrompng($archivo->getPathname()),
                        'image/webp' => imagecreatefromwebp($archivo->getPathname()),
                        default => null,
                    };

                    if (!$img) {
                        throw new \Exception("No se pudo procesar la imagen {$archivo->getClientOriginalName()}.");
                    }

                    imagewebp($img, $rutaCompleta, 80);
                    imagedestroy($img);
                    $rutasArchivos[] = $rutaRelativa;
                }
            }

            $validated['empresa_id'] = EmpresaResolver::resolveId();
            $validated['user_id'] = auth()->id();
            $validated['estado'] = 'abierto';

            // Agregar archivos si existen
            if (!empty($rutasArchivos)) {
                $validated['archivos'] = $rutasArchivos;
            }

            $ticket = Ticket::create($validated);

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json($ticket);
            }

            return redirect()->route('soporte.show', $ticket)
                ->with('success', "Ticket {$ticket->numero} creado exitosamente");

        } catch (\Exception $e) {
            DB::rollBack();

            // Limpieza de archivos físicos creados en este intento fallido
            foreach ($rutasArchivos as $ruta) {
                if (file_exists(storage_path('app/public/' . $ruta))) {
                    @unlink(storage_path('app/public/' . $ruta));
                }
            }

            \Log::error('Error creando ticket: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json(['error' => 'Error al crear ticket: ' . $e->getMessage()], 500);
            }

            return back()->with('error', 'Error al crear ticket: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Ticket $ticket)
    {
        $ticket->load([
            'cliente',
            'asignado',
            'categoria',
            'creador',
            'producto',
            'venta',
            'poliza',
            'comentarios.user',
            'citas',
        ]);

        // Historial del cliente (otros tickets)
        $historialCliente = null;
        if ($ticket->cliente_id) {
            $historialCliente = Ticket::where('cliente_id', $ticket->cliente_id)
                ->where('id', '!=', $ticket->id)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(['id', 'numero', 'titulo', 'estado', 'created_at']);
        }

        $empresaId = EmpresaResolver::resolveId();

        return Inertia::render('Soporte/Show', [
            'ticket' => $ticket,
            'historialCliente' => $historialCliente,
            'categorias' => TicketCategory::where('empresa_id', $empresaId)->activas()->get(),
            'usuarios' => User::all(['id', 'name']),
        ]);
    }

    public function update(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'titulo' => 'sometimes|string|max:255',
            'descripcion' => 'sometimes|string',
            'prioridad' => 'sometimes|in:baja,media,alta,urgente',
            'categoria_id' => 'nullable|exists:ticket_categories,id',
            'asignado_id' => 'nullable|exists:users,id',
            'notas_internas' => 'nullable|string',
        ]);

        $ticket->update($validated);

        return back()->with('success', 'Ticket actualizado');
    }

    public function cambiarEstado(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'estado' => 'required|in:abierto,en_progreso,pendiente,resuelto,cerrado',
            'horas_trabajadas' => 'nullable|numeric|min:0|max:999',
        ]);

        // Si se está resolviendo o cerrando y hay horas trabajadas
        if (in_array($validated['estado'], ['resuelto', 'cerrado']) && isset($validated['horas_trabajadas'])) {
            $horas = (float) $validated['horas_trabajadas'];

            if ($validated['estado'] === 'resuelto') {
                $ticket->marcarComoResuelto($horas);
            } else {
                $ticket->cerrar($horas);
            }
        } else {
            $ticket->cambiarEstado($validated['estado']);
        }

        return back()->with('success', 'Estado actualizado');
    }

    public function asignar(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'asignado_id' => 'required|exists:users,id',
        ]);

        $usuario = User::findOrFail($validated['asignado_id']);
        $ticket->asignarA($usuario);

        return back()->with('success', "Ticket asignado a {$usuario->name}");
    }

    public function agregarComentario(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'contenido' => 'required|string',
            'es_interno' => 'boolean',
        ]);

        $ticket->comentarios()->create([
            'user_id' => auth()->id(),
            'contenido' => $validated['contenido'],
            'es_interno' => $validated['es_interno'] ?? false,
            'tipo' => 'respuesta',
        ]);

        // Marcar primera respuesta si es el primer comentario público
        if (!$ticket->primera_respuesta_at && !($validated['es_interno'] ?? false)) {
            $ticket->update(['primera_respuesta_at' => now()]);
        }

        // Si está abierto, cambiar a en_progreso
        if ($ticket->estado === 'abierto') {
            $ticket->update(['estado' => 'en_progreso']);
        }

        return back()->with('success', 'Comentario agregado');

    }

    public function buscarClientePorTelefono(Request $request)
    {
        // MODO 1: Obtener detalles de un cliente específico por ID
        if ($request->has('id')) {
            $cliente = Cliente::find($request->input('id'));

            if (!$cliente) {
                return response()->json(['found' => false]);
            }

            // Obtener historial reciente
            $ticketsRecientes = Ticket::where('cliente_id', $cliente->id)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(['id', 'numero', 'titulo', 'estado', 'created_at']);

            return response()->json([
                'found' => true,
                'cliente' => [
                    'id' => $cliente->id,
                    'nombre' => $cliente->nombre_razon_social,
                    'email' => $cliente->email,
                    'telefono' => $cliente->telefono,
                ],
                'tickets_recientes' => $ticketsRecientes,
                'poliza_activa' => \App\Models\PolizaServicio::where('cliente_id', $cliente->id)
                    ->activa()
                    ->with('equipos')
                    ->first(),
            ]);
        }

        // MODO 2: Buscar clientes por nombre (query)
        $termino = $request->get('query') ?? $request->get('telefono');

        if (!$termino || strlen($termino) < 3) {
            return response()->json(['found' => false, 'results' => []]);
        }

        $clientes = Cliente::where('nombre_razon_social', 'ilike', "%{$termino}%")
            ->orWhere('telefono', 'ilike', "%{$termino}%")
            ->limit(10)
            ->get(['id', 'nombre_razon_social', 'email', 'telefono']);

        return response()->json([
            'found' => false,
            'results' => $clientes->map(fn($c) => [
                'id' => $c->id,
                'nombre' => $c->nombre_razon_social,
                'email' => $c->email,
                'telefono' => $c->telefono,
            ])
        ]);
    }

    public function destroy(Ticket $ticket)
    {
        $this->authorize('delete', $ticket);
        $ticket->delete();
        return redirect()->route('soporte.index')->with('success', 'Ticket eliminado');
    }

    public function generarVenta(Request $request, Ticket $ticket)
    {
        // Verificar que el ticket sea de tipo "costo"
        if ($ticket->tipo_servicio !== 'costo') {
            return back()->with('error', 'Este ticket no es de tipo "Con Costo", no se puede generar venta.');
        }

        // Verificar que no tenga ya una venta asociada
        if ($ticket->venta_id) {
            return redirect()->route('ventas.edit', $ticket->venta_id)
                ->with('info', 'Este ticket ya tiene una venta asociada.');
        }

        // Verificar que tenga cliente
        if (!$ticket->cliente_id) {
            return back()->with('error', 'El ticket debe tener un cliente asociado para generar venta.');
        }

        DB::beginTransaction();

        try {
            $user = auth()->user();
            $almacenId = $user->almacen_venta_id ?? \App\Models\Almacen::first()->id;

            // Generar numero_venta
            $prefijo = 'V';
            $ultimaVenta = Venta::where('numero_venta', 'like', 'V%')
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            $numero = 1;
            if ($ultimaVenta && preg_match('/V(\d+)/', $ultimaVenta->numero_venta, $matches)) {
                $numero = ((int) $matches[1]) + 1;
            }
            $numeroVenta = $prefijo . str_pad($numero, 4, '0', STR_PAD_LEFT);

            // Buscar o crear servicio de soporte
            // Usar la categoría del primer servicio existente como base
            $categoriaId = \App\Models\Servicio::whereNotNull('categoria_id')->value('categoria_id') ?? 1;

            $servicioSoporte = \App\Models\Servicio::firstOrCreate(
                ['nombre' => 'Servicio de Soporte Técnico'],
                [
                    'descripcion' => 'Servicio técnico de soporte generado desde tickets',
                    'codigo' => 'SOPORTE-TKT',
                    'categoria_id' => $categoriaId,
                    'precio' => 650, // Precio por defecto del servicio de soporte
                    'duracion' => 60, // Duración estimada en minutos
                    'estado' => 'activo',
                    'margen_ganancia' => 100,
                    'comision_vendedor' => 0,
                ]
            );

            // Crear la venta con detalles del servicio
            $notasVenta = "Ticket #{$ticket->numero}: {$ticket->titulo}\n\nDetalles del servicio:\n{$ticket->descripcion}";

            $subtotalBase = 650;
            $ivaPorcentaje = \App\Services\EmpresaConfiguracionService::getIvaPorcentaje() / 100;
            $ivaMonto = $subtotalBase * $ivaPorcentaje;
            $totalMonto = $subtotalBase + $ivaMonto;

            $venta = Venta::create([
                'cliente_id' => $ticket->cliente_id,
                'almacen_id' => $almacenId,
                'numero_venta' => $numeroVenta,
                'fecha' => now(),
                'estado' => 'pendiente',
                'subtotal' => $subtotalBase,
                'iva' => $ivaMonto,
                'total' => $totalMonto,
                'notas' => $notasVenta,
            ]);

            // Agregar el servicio de soporte como item de la venta
            VentaItem::create([
                'venta_id' => $venta->id,
                'ventable_type' => \App\Models\Servicio::class,
                'ventable_id' => $servicioSoporte->id,
                'cantidad' => 1,
                'precio' => 650,
                'descuento' => 0,
                'subtotal' => 650,
            ]);

            // Asociar la venta al ticket
            $ticket->update(['venta_id' => $venta->id]);

            DB::commit();

            return redirect()->route('ventas.edit', $venta)
                ->with('success', "Venta #{$venta->numero_venta} creada con servicio de soporte. Ajusta el precio del servicio.");

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error generando venta desde ticket: ' . $e->getMessage());
            return back()->with('error', 'Error al generar la venta: ' . $e->getMessage());
        }
    }
}

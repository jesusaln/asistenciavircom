<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaItem;
use App\Models\Servicio;
use App\Models\Almacen;
use Illuminate\Http\Request;

class TicketApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Ticket::with(['cliente', 'categoria', 'asignado'])
            ->where('asignado_id', $user->id);

        // Filtrar por estado si se especifica
        if ($request->filled('estado')) {
            $estado = $request->estado;
            if ($estado === 'pendientes') {
                $query->whereIn('estado', ['abierto', 'en_progreso', 'pendiente']);
            } elseif ($estado === 'resueltos') {
                $query->whereIn('estado', ['resuelto']);
            } elseif ($estado === 'cancelados') {
                $query->whereIn('estado', ['cerrado', 'cancelado']);
            } else {
                $query->where('estado', $estado);
            }
        }

        // Orden: pendientes primero, luego resueltos, luego cancelados
        $query->orderByRaw("
            CASE 
                WHEN estado IN ('abierto', 'en_progreso', 'pendiente') THEN 1
                WHEN estado = 'resuelto' THEN 2
                WHEN estado IN ('cerrado', 'cancelado') THEN 3
                ELSE 4
            END ASC
        ");
        $query->orderByRaw("
            CASE prioridad 
                WHEN 'urgente' THEN 1 
                WHEN 'alta' THEN 2 
                WHEN 'media' THEN 3 
                WHEN 'baja' THEN 4 
                ELSE 5 
            END ASC
        ");
        $query->orderBy('created_at', 'desc');

        $tickets = $query->paginate($request->per_page ?? 20);

        // Contar por estado para los filtros
        $counts = Ticket::where('asignado_id', $user->id)
            ->selectRaw("
                COUNT(CASE WHEN estado IN ('abierto', 'en_progreso', 'pendiente') THEN 1 END) as pendientes,
                COUNT(CASE WHEN estado = 'resuelto' THEN 1 END) as resueltos,
                COUNT(CASE WHEN estado IN ('cerrado', 'cancelado') THEN 1 END) as cancelados
            ")->first();

        return response()->json([
            'data' => $tickets->items(),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
            'counts' => $counts,
        ]);
    }

    public function show($id)
    {
        $ticket = Ticket::with(['cliente', 'categoria', 'asignado', 'comentarios.user', 'poliza'])->findOrFail($id);
        return response()->json($ticket);
    }

    public function update(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $validated = $request->validate([
            'estado' => 'sometimes|in:abierto,en_progreso,pendiente,resuelto,cerrado',
            'nota' => 'nullable|string',
            'trabajo_realizado' => 'nullable|string',
            'servicio_inicio_at' => 'nullable|date',
            'servicio_fin_at' => 'nullable|date|after:servicio_inicio_at',
            'fotos' => 'nullable|array',
            'fotos.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'con_cobro' => 'nullable|boolean',
            'items' => 'nullable|json',
        ]);

        if ($request->has('estado')) {
            $ticket->estado = $validated['estado'];

            if ($validated['estado'] === 'resuelto') {
                if (!$ticket->resuelto_at) {
                    $ticket->resuelto_at = now();
                }

                if ($request->has('servicio_inicio_at') && $request->has('servicio_fin_at')) {
                    $ticket->servicio_inicio_at = $validated['servicio_inicio_at'];
                    $ticket->servicio_fin_at = $validated['servicio_fin_at'];
                    $start = \Carbon\Carbon::parse($validated['servicio_inicio_at']);
                    $end = \Carbon\Carbon::parse($validated['servicio_fin_at']);
                    $diff = $start->diffInMinutes($end) / 60;
                    $ticket->horas_trabajadas = round($diff, 2);
                }

                // Guardar fotos de evidencia
                $fotosPrevias = $ticket->archivos ?? [];
                if ($request->hasFile('fotos')) {
                    $nuevasFotos = [];
                    foreach ($request->file('fotos') as $foto) {
                        $path = $foto->store('tickets/evidencias', 'public');
                        if ($path) $nuevasFotos[] = $path;
                    }
                    $ticket->archivos = array_merge($fotosPrevias, $nuevasFotos);
                }

                // Si no tiene póliza, forzar con cobro
                if (!$ticket->poliza_id) {
                    $ticket->tipo_servicio = 'costo';
                }
            }
        }

        $ticket->save();

        // Guardar comentario con el trabajo realizado
        $comentarioTexto = $validated['trabajo_realizado'] ?? $validated['nota'] ?? '';
        if ($request->filled('trabajo_realizado') || $request->filled('nota')) {
            $ticket->comentarios()->create([
                'user_id' => $request->user()->id,
                'contenido' => $comentarioTexto,
                'es_interno' => false,
                'tipo' => 'respuesta',
                'metadata' => !empty($ticket->archivos) ? ['archivos' => array_slice((array) $ticket->archivos, -5)] : null,
            ]);
        }

        // Generar venta si es con cobro (sin poliza O con items facturables)
        $itemsFacturables = $request->filled('items') ? json_decode($request->input('items'), true) : [];
        $debeGenerarVenta = (!$ticket->poliza_id && !$ticket->venta_id && $ticket->cliente_id) || (!empty($itemsFacturables) && !$ticket->venta_id);

        if ($validated['estado'] === 'resuelto' && $debeGenerarVenta) {
            try {
                \Illuminate\Support\Facades\DB::beginTransaction();
                $user = $request->user();
                $almacenId = \App\Models\Almacen::first()->id;
                $numeroVenta = app(\App\Services\Folio\FolioService::class)->getNextFolio('venta');
                $folioExterno = $ticket->folio_externo ? "Folio Cliente: {$ticket->folio_externo}\n" : '';
                $notasVenta = "{$folioExterno}FOLIO INTERNO: {$ticket->numero}\nTicket #{$ticket->numero}: {$ticket->titulo}\n\nCompletado por: {$user->name}";

                $servicioSoporte = \App\Models\Servicio::firstOrCreate(
                    ['nombre' => 'Servicio de Soporte Técnico'],
                    ['codigo' => 'SOPORTE-TKT', 'precio' => 650, 'estado' => 'activo']
                );

                $subtotalBase = 0;
                $itemsVenta = [];

                // Agregar horas de servicio si es sin poliza
                if (!$ticket->poliza_id) {
                    $horasFacturar = max(1, (int) ($ticket->horas_trabajadas ?? 1));
                    $subtotalBase += 650 * $horasFacturar;
                    $itemsVenta[] = [
                        'ventable_type' => \App\Models\Servicio::class,
                        'ventable_id' => $servicioSoporte->id,
                        'cantidad' => $horasFacturar,
                        'precio' => 650,
                    ];
                }

                // Agregar items facturables (productos extra)
                if (!empty($itemsFacturables)) {
                    foreach ($itemsFacturables as $itemData) {
                        $producto = \App\Models\Producto::find($itemData['id'] ?? 0);
                        if ($producto) {
                            $precio = (float) ($itemData['precio'] ?? $producto->precio_venta);
                            $cantidad = (int) ($itemData['cantidad'] ?? 1);
                            $subtotalItem = $precio * $cantidad;
                            $subtotalBase += $subtotalItem;
                            $itemsVenta[] = [
                                'ventable_type' => \App\Models\Producto::class,
                                'ventable_id' => $producto->id,
                                'cantidad' => $cantidad,
                                'precio' => $precio,
                            ];
                        }
                    }
                }

                if ($subtotalBase <= 0) $subtotalBase = 650;
                $ivaMonto = $subtotalBase * 0.16;
                $totalMonto = $subtotalBase + $ivaMonto;

                $venta = \App\Models\Venta::create([
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

                foreach ($itemsVenta as $itemData) {
                    \App\Models\VentaItem::create(array_merge($itemData, [
                        'venta_id' => $venta->id,
                        'descuento' => 0,
                        'subtotal' => $itemData['precio'] * $itemData['cantidad'],
                    ]));
                }

                $ticket->update(['venta_id' => $venta->id]);
                \Illuminate\Support\Facades\DB::commit();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\DB::rollBack();
                \Log::error('Error generando venta desde API: ' . $e->getMessage());
            }
        }

        return response()->json($ticket->load(['cliente', 'categoria', 'asignado', 'comentarios.user', 'poliza']));
    }

    public function completar(Request $request, $id)
    {
        $request->merge(['estado' => 'resuelto']);
        return $this->update($request, $id);
    }
}

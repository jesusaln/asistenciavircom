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
        $esAdmin = $user->hasRole('super-admin') || $user->hasRole('admin');

        $query = Ticket::with(['cliente', 'categoria', 'asignado', 'poliza']);

        // Super admin ve todos los tickets; técnicos solo los suyos
        if (!$esAdmin) {
            $query->where('asignado_id', $user->id);
        }

        // Filtrar por técnico específico
        if ($esAdmin && $request->filled('asignado_id')) {
            $query->where('asignado_id', $request->asignado_id);
        }

        // Filtrar por estado
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

        // Orden
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

        // Contar por estado
        $countsQuery = $esAdmin ? Ticket::query() : Ticket::where('asignado_id', $user->id);
        if ($esAdmin && $request->filled('asignado_id')) {
            $countsQuery->where('asignado_id', $request->asignado_id);
        }
        $counts = $countsQuery->selectRaw("
            COUNT(CASE WHEN estado IN ('abierto', 'en_progreso', 'pendiente') THEN 1 END) as pendientes,
            COUNT(CASE WHEN estado = 'resuelto' THEN 1 END) as resueltos,
            COUNT(CASE WHEN estado IN ('cerrado', 'cancelado') THEN 1 END) as cancelados
        ")->first();

        // Técnicos para filtro (solo admin)
        $tecnicos = $esAdmin ? \App\Models\User::whereIn('id',
            \App\Models\Ticket::whereNotNull('asignado_id')
                ->distinct()->pluck('asignado_id')
        )->get(['id', 'name']) : [];


        return response()->json([
            'data' => $tickets->items(),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
            'counts' => $counts,
            'tecnicos' => $tecnicos,
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

        // La creación de ventas ahora se maneja de forma explícita redirigiendo al usuario al formulario de ventas.

        return response()->json($ticket->load(['cliente', 'categoria', 'asignado', 'comentarios.user', 'poliza']));
    }

    public function completar(Request $request, Ticket $ticket)
    {
        $request->merge(['estado' => 'resuelto']);
        return $this->update($request, $ticket->id);
    }
}

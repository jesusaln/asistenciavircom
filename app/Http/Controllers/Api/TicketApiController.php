<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Consultar tickets donde el usuario es el ASIGNADO
        $query = Ticket::with(['cliente', 'categoria', 'asignado'])
            ->where('asignado_id', $user->id)
            ->where('estado', '!=', 'cerrado')
            ->orderByRaw("
                CASE prioridad 
                    WHEN 'urgente' THEN 1 
                    WHEN 'alta' THEN 2 
                    WHEN 'media' THEN 3 
                    WHEN 'baja' THEN 4 
                    ELSE 5 
                END ASC
            ")
            ->orderBy('created_at', 'desc');

        return response()->json($query->paginate(20));
    }

    public function show($id)
    {
        $ticket = Ticket::with(['cliente', 'categoria', 'asignado', 'comentarios.user', 'poliza'])->findOrFail($id);
        return response()->json($ticket);
    }

    public function update(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        // Validación básica
        $validated = $request->validate([
            'estado' => 'sometimes|in:abierto,en_progreso,pendiente,resuelto,cerrado',
            'nota' => 'nullable|string',
            'servicio_inicio_at' => 'nullable|date', // Soportar registro de horas desde App
            'servicio_fin_at' => 'nullable|date|after:servicio_inicio_at',
        ]);

        if ($request->has('estado')) {
            $ticket->estado = $validated['estado'];

            if ($validated['estado'] === 'resuelto' || $validated['estado'] === 'cerrado') {
                if (!$ticket->resuelto_at) {
                    $ticket->resuelto_at = now();
                }

                // Si envían horas
                if ($request->has('servicio_inicio_at') && $request->has('servicio_fin_at')) {
                    $ticket->servicio_inicio_at = $validated['servicio_inicio_at'];
                    $ticket->servicio_fin_at = $validated['servicio_fin_at'];

                    $start = \Carbon\Carbon::parse($validated['servicio_inicio_at']);
                    $end = \Carbon\Carbon::parse($validated['servicio_fin_at']);
                    $diff = $start->diffInMinutes($end) / 60;
                    $ticket->horas_trabajadas = round($diff, 2);
                }
            }
        }

        $ticket->save();

        if ($request->filled('nota')) {
            $ticket->comentarios()->create([
                'user_id' => $request->user()->id,
                'contenido' => $validated['nota'],
                'es_interno' => false
            ]);
        }

        return response()->json($ticket);
    }
}

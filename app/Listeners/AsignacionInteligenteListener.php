<?php

namespace App\Listeners;

use App\Events\TicketCreado;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class AsignacionInteligenteListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TicketCreado $event): void
    {
        $ticket = $event->ticket;

        // Si el ticket ya tiene un agente asignado, no hacer nada.
        if ($ticket->asignado_id) {
            return;
        }

        // Buscar el cliente del ticket
        $cliente = $ticket->cliente;
        $esKlaudiaKarina = false;

        if ($cliente) {
            $nombreCliente = strtolower($cliente->nombre_razon_social ?? $cliente->nombre ?? '');
            if (str_contains($nombreCliente, 'klaudia karina')) {
                $esKlaudiaKarina = true;
            }
        }

        if ($esKlaudiaKarina) {
            // Asignar a Alan Aranda Esquer
            $tecnico = User::where('name', 'ILIKE', '%Alan%Aranda%Esquer%')->first();
            if ($tecnico) {
                $ticket->asignado_id = $tecnico->id;
                $ticket->save();
                Log::info("Ticket #{$ticket->folio} asignado automáticamente a Alan Aranda Esquer por cliente Klaudia Karina.");
                return;
            }
        }

        // Para todos los demás, asignar a Miriam López
        $tecnico = User::where('name', 'ILIKE', '%Miriam%Lopez%')
            ->orWhere('name', 'ILIKE', '%Miriam%L\u00f3pez%')
            ->first();

        if ($tecnico) {
            $ticket->asignado_id = $tecnico->id;
            $ticket->save();
            Log::info("Ticket #{$ticket->folio} asignado automáticamente a Miriam López.");
            return;
        }

        // Fallback si no se encuentra ninguno de los dos
        $admin = User::role('super-admin')->orderBy('id')->first();

        if ($admin) {
            $ticket->asignado_id = $admin->id;
            $ticket->save();

            Log::info("Ticket #{$ticket->folio} asignado automáticamente al admin '{$admin->name}' (fallback).");
        } else {
            Log::warning("No se encontró un super-admin para la asignación automática del ticket #{$ticket->folio}.");
        }
    }
}

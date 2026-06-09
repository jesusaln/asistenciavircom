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

        // Lógica v1: Asignar al primer super-admin disponible.
        // En futuras versiones, esto podría expandirse para buscar agentes
        // con menos carga de trabajo, habilidades específicas o asignados al cliente.
        $admin = User::role('super-admin')->orderBy('id')->first();

        if ($admin) {
            $ticket->asignado_id = $admin->id;
            $ticket->save();

            Log::info("Ticket #{$ticket->folio} asignado automáticamente al admin '{$admin->name}'.");
        } else {
            Log::warning("No se encontró un super-admin para la asignación automática del ticket #{$ticket->folio}.");
        }
    }
}

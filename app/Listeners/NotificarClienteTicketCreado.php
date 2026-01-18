<?php

namespace App\Listeners;

use App\Events\TicketCreado;
use App\Notifications\TicketRecibido;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotificarClienteTicketCreado implements ShouldQueue
{
    use InteractsWithQueue;

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
        $cliente = $ticket->cliente;

        if (!$cliente || !$cliente->email) {
            Log::warning("No se pudo notificar al cliente para el ticket #{$ticket->folio}: Cliente o email no encontrado.");
            return;
        }

        try {
            Notification::send($cliente, new TicketRecibido($ticket));
            Log::info("Notificación de ticket #{$ticket->folio} enviada al cliente '{$cliente->nombre_razon_social}'.");
        } catch (\Exception $e) {
            Log::error("Fallo al enviar notificación para ticket #{$ticket->folio}: " . $e->getMessage());
        }
    }
}

<?php

namespace App\Listeners;

use App\Events\CitaCompletada;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ActualizarTicketDesdeCita implements ShouldQueue
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
    public function handle(CitaCompletada $event): void
    {
        $cita = $event->cita;

        if (!$cita->ticket_id) {
            return; // No hay ticket que actualizar
        }

        $ticket = $cita->ticket;

        if (!$ticket) {
            Log::warning("No se encontró el ticket ID #{$cita->ticket_id} para actualizar desde la cita #{$cita->folio}.");
            return;
        }

        try {
            // Añadir comentario privado al ticket
            $comentario = "Visita de servicio completada (Cita #{$cita->folio}).";
            if ($cita->trabajo_realizado) {
                $comentario .= "\n\nResumen del trabajo: " . $cita->trabajo_realizado;
            }

            $ticket->comentarios()->create([
                'user_id' => $cita->tecnico_id ?? auth()->id(), // Usar técnico de la cita o el usuario actual
                'contenido' => $comentario,
                'es_interno' => true,
                'tipo' => 'sistema',
            ]);

            // Marcar el ticket como resuelto si no está ya cerrado
            if ($ticket->estado !== 'cerrado') {
                $ticket->marcarComoResuelto();
            }

            Log::info("Ticket #{$ticket->folio} actualizado automáticamente desde la cita #{$cita->folio}.");

        } catch (\Exception $e) {
            Log::error("Fallo al actualizar ticket #{$ticket->folio} desde cita #{$cita->folio}: " . $e->getMessage());
        }
    }
}

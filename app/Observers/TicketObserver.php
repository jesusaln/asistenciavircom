<?php

namespace App\Observers;

use App\Models\Ticket;

use App\Models\EmpresaConfiguracion;

class TicketObserver
{
    /**
     * Handle the Ticket "created" event.
     */
    public function created(Ticket $ticket): void
    {
        //
    }

    /**
     * Handle the Ticket "creating" event.
     */
    public function creating(Ticket $ticket): void
    {
        // Estrategia 1: Tickets del Portal (externos).
        // Si el creador es un cliente (no tiene rol de staff/admin), asignar al "Cazador por Defecto" o dejar NULL.
        if (auth()->check() && !auth()->user()->hasRole(['admin', 'soporte', 'vendedor'])) {
            $defaultAssignee = EmpresaConfiguracion::getConfig()->ticket_default_assignee_id;
            $ticket->asignado_id = $defaultAssignee; // Puede ser un ID o NULL
        }
    }

    /**
     * Handle the Ticket "updating" event.
     */
    public function updating(Ticket $ticket): void
    {
        // Estrategia 2: "El que lo cierra, se lo queda" (Closer Takes All)
        // Si el estado cambia a resuelto/cerrado y la acción la hace un humano autenticado
        if (
            $ticket->isDirty('estado') &&
            in_array($ticket->estado, ['resuelto', 'cerrado']) &&
            auth()->check()
        ) {

            $user = auth()->user();

            // Si el usuario es staff (tiene rol soporte/admin), asignárselo
            if ($user->hasRole(['admin', 'soporte'])) {
                // Solo si el ticket no estaba asignado o si queremos forzar el cambio
                // Aquí forzamos para que el crédito sea de quien resolvió.
                $ticket->asignado_id = $user->id;
            }
        }
    }

    /**
     * Handle the Ticket "deleted" event.
     */
    public function deleted(Ticket $ticket): void
    {
        //
    }

    /**
     * Handle the Ticket "restored" event.
     */
    public function restored(Ticket $ticket): void
    {
        //
    }

    /**
     * Handle the Ticket "force deleted" event.
     */
    public function forceDeleted(Ticket $ticket): void
    {
        //
    }
}

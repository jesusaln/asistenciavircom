<?php
 // Create this event observer or modify model for correct assignment
 namespace App\Observers;
 
 use App\Models\Ticket;
 
 class TicketObserver
 {
     public function updating(Ticket $ticket)
     {
         // Estrategia 2: Si el ticket se marca como Resuelto/Cerrado y no tiene asignado, 
         // o si el usuario que lo cierra es diferente al asignado (y es soporte), 
         // asignarlo a quien lo cierra.
         if ($ticket->isDirty("estado") && 
             in_array($ticket->estado, ["resuelto", "cerrado"]) && 
             auth()->check()) {
             
             // Si no tiene asignado, o si el usuario actual es staff
             $user = auth()->user();
             if ($user) {
                 $ticket->asignado_id = $user->id;
             }
         }
     }
 }
 

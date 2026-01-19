<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TicketPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view tickets');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        return $user->can('view tickets') && $user->empresa_id === $ticket->empresa_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create tickets');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        // Super-admin y admin pueden editar cualquier ticket
        if ($user->hasRole(['super-admin', 'admin'])) {
            return true;
        }

        // Otros usuarios necesitan el permiso y deben pertenecer a la misma empresa
        return $user->can('edit tickets') && (
            !$ticket->empresa_id ||
            $user->empresa_id === $ticket->empresa_id
        );
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        // super-admin y admin pueden eliminar tickets
        return $user->hasRole(['super-admin', 'admin']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Ticket $ticket): bool
    {
        return $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Ticket $ticket): bool
    {
        return $user->hasRole('super-admin');
    }
}

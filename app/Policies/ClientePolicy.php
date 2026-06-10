<?php

namespace App\Policies;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ClientePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view clientes');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Cliente $cliente): bool
    {
        return $user->can('view clientes');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create clientes');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Cliente $cliente): bool
    {
        return $user->can('edit clientes');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Cliente $cliente): bool
    {
        return $user->can('delete clientes');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Cliente $cliente): bool
    {
        return $user->can('edit clientes');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Cliente $cliente): bool
    {
        return $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can toggle client status.
     */
    public function toggle(User $user, Cliente $cliente): bool
    {
        return $user->can('edit clientes');
    }

    /**
     * Determine whether the user can export clients.
     */
    public function export(User $user): bool
    {
        return $user->can('export clientes');
    }

    /**
     * Determine whether the user can view client statistics.
     */
    public function stats(User $user): bool
    {
        return $user->can('stats clientes') || $user->can('view clientes');
    }
}

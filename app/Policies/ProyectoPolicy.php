<?php

namespace App\Policies;

use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProyectoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view proyectos');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Proyecto $proyecto): bool
    {
        // El usuario puede ver si es ADMIN (manejado globalmente por Gate::before)
        // O si es el dueño
        // O si es miembro del proyecto
        return $user->id === $proyecto->owner_id ||
            $proyecto->members->contains($user->id) ||
            $user->can('view all proyectos'); // Opcional: permiso para ver todo
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create proyectos');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Proyecto $proyecto): bool
    {
        // Solo el dueño puede editar su proyecto
        return $user->id === $proyecto->owner_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Proyecto $proyecto): bool
    {
        // Solo el dueño puede eliminar su proyecto
        return $user->id === $proyecto->owner_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Proyecto $proyecto): bool
    {
        return $user->id === $proyecto->owner_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Proyecto $proyecto): bool
    {
        return $user->id === $proyecto->owner_id;
    }
}

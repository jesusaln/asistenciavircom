<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determina si el usuario puede realizar cualquier acción.
     */
    public function before(User $user, $ability): bool|null
    {
        // Si el usuario tiene el rol 'admin', se le otorgan todos los permisos
        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            return true;
        }

        return null; // Continúa con las verificaciones normales si no es admin
    }

    /**
     * Determina si el usuario puede ver la lista de usuarios.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view usuarios');
    }

    /**
     * Determina si el usuario puede ver un usuario específico.
     */
    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->can('view usuarios');
    }

    /**
     * Determina si el usuario puede crear usuarios.
     */
    public function create(User $user): bool
    {
        return $user->can('create usuarios');
    }

    /**
     * Determina si el usuario puede actualizar usuarios.
     */
    public function update(User $user, User $model): bool
    {
        // El usuario puede editarse a sí mismo o si tiene el permiso específico
        return $user->id === $model->id || $user->can('edit usuarios');
    }

    /**
     * Determina si el usuario puede eliminar usuarios.
     */
    public function delete(User $user, User $model): bool
    {
        // No se puede eliminar a sí mismo
        return $user->id !== $model->id && $user->can('delete usuarios');
    }
}

<?php

namespace App\Policies;

use App\Models\User;
use App\Support\SystemRoles;

class UserPolicy
{
    /**
     * Bypass para roles elevados salvo eliminación (debe evaluarse en delete()).
     */
    public function before(User $user, string $ability): bool|null
    {
        if (in_array($ability, ['delete', 'forceDelete'], true)) {
            return null;
        }

        if ($user->hasAnyRole(SystemRoles::ELEVATED)) {
            return true;
        }

        return null;
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
     *
     * - Nadie puede eliminar a un usuario con rol super-admin (alias en {@see SystemRoles::SUPER_ADMIN}).
     * - Solo un super-admin puede eliminar a usuarios con rol de aplicación admin (alias en {@see SystemRoles::ADMIN}).
     * - El resto: roles elevados o permiso delete usuarios.
     */
    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        if (SystemRoles::userIsSuperAdmin($model)) {
            return false;
        }

        if (SystemRoles::userIsAppAdmin($model)) {
            return SystemRoles::userIsSuperAdmin($user);
        }

        if ($user->hasAnyRole(SystemRoles::ELEVATED)) {
            return true;
        }

        return $user->can('delete usuarios');
    }
}

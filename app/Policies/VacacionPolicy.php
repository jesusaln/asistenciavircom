<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vacacion;
use Illuminate\Auth\Access\Response;

class VacacionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Los administradores pueden ver todas las vacaciones
        // Los empleados pueden ver las vacaciones generales (para crear nuevas)
        return $user->hasAnyRole(['admin', 'super-admin']) || $user->es_empleado;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Vacacion $vacacion): bool
    {
        // Los administradores pueden ver cualquier vacación
        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            return true;
        }

        // Los empleados pueden ver sus propias vacaciones
        if ($user->es_empleado && $vacacion->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Los empleados pueden crear sus propias vacaciones
        // Los administradores pueden crear vacaciones para cualquier empleado
        return $user->es_empleado || $user->hasAnyRole(['admin', 'super-admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Vacacion $vacacion): bool
    {
        // Solo los administradores pueden aprobar/rechazar vacaciones pendientes
        if ($vacacion->estado !== 'pendiente') {
            return false;
        }

        // Verificar si el usuario es administrador
        return $user->roles()->whereIn('name', ['admin', 'super-admin'])->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Vacacion $vacacion): bool
    {
        // Solo los administradores pueden eliminar vacaciones
        if (!in_array($vacacion->estado, ['pendiente', 'rechazada'])) {
            return false;
        }

        // Verificar si el usuario es administrador
        return $user->roles()->whereIn('name', ['admin', 'super-admin'])->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Vacacion $vacacion): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Vacacion $vacacion): bool
    {
        return false;
    }
}

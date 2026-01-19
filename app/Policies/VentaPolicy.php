<?php

namespace App\Policies;

use App\Models\Venta;
use App\Models\User;

class VentaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super-admin', 'admin']) || $user->can('view ventas');
    }

    public function view(User $user, Venta $venta): bool
    {
        if ($user->hasRole(['super-admin', 'admin'])) {
            return true;
        }
        return $user->can('view ventas') && $user->empresa_id === $venta->empresa_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['super-admin', 'admin']) || $user->can('create ventas');
    }

    public function update(User $user, Venta $venta): bool
    {
        if ($user->hasRole(['super-admin', 'admin'])) {
            return true;
        }
        return $user->can('edit ventas') && $user->empresa_id === $venta->empresa_id;
    }

    public function delete(User $user, Venta $venta): bool
    {
        if ($user->hasRole(['super-admin', 'admin'])) {
            return true;
        }
        return $user->can('delete ventas') && $user->empresa_id === $venta->empresa_id;
    }
}

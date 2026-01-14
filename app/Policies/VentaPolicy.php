<?php

namespace App\Policies;

use App\Models\Venta;
use App\Models\User;

class VentaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view ventas');
    }

    public function view(User $user, Venta $venta): bool
    {
        return $user->can('view ventas');
    }

    public function create(User $user): bool
    {
        return $user->can('create ventas');
    }

    public function update(User $user, Venta $venta): bool
    {
        return $user->can('edit ventas');
    }

    public function delete(User $user, Venta $venta): bool
    {
        return $user->can('delete ventas');
    }
}

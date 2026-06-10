<?php

namespace App\Policies;

use App\Models\Pedido;
use App\Models\User;

class PedidoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view pedidos');
    }

    public function view(User $user, Pedido $pedido): bool
    {
        return $user->can('view pedidos');
    }

    public function create(User $user): bool
    {
        return $user->can('create pedidos');
    }

    public function update(User $user, Pedido $pedido): bool
    {
        return $user->can('edit pedidos');
    }

    public function delete(User $user, Pedido $pedido): bool
    {
        return $user->can('delete pedidos');
    }
}

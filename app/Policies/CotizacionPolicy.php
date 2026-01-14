<?php

namespace App\Policies;

use App\Models\Cotizacion;
use App\Models\User;

class CotizacionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view cotizaciones');
    }

    public function view(User $user, Cotizacion $cotizacion): bool
    {
        return $user->can('view cotizaciones');
    }

    public function create(User $user): bool
    {
        return $user->can('create cotizaciones');
    }

    public function update(User $user, Cotizacion $cotizacion): bool
    {
        return $user->can('edit cotizaciones');
    }

    public function delete(User $user, Cotizacion $cotizacion): bool
    {
        return $user->can('delete cotizaciones');
    }
}

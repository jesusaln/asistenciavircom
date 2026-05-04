<?php

namespace App\Policies;

use App\Models\Cotizacion;
use App\Models\User;

/**
 * Autorización API + web. La app móvil usa roles (vendedor/técnico) y a veces
 * faltan permisos Spatie granulares; no forzar solo `delete cotizaciones`.
 */
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
        if ($user->can('delete cotizaciones') || $user->can('edit cotizaciones')) {
            return true;
        }

        // Cotizaciones antiguas sin created_by: perfiles de campo (el seeder usa rol `ventas`, no `vendedor`)
        if ($cotizacion->created_by === null) {
            return $user->hasAnyRole(['vendedor', 'ventas', 'tecnico', 'admin', 'super-admin']);
        }

        if ((int) $cotizacion->created_by !== (int) $user->id) {
            return false;
        }

        if ($user->can('create cotizaciones') || $user->can('view cotizaciones')) {
            return true;
        }

        return $user->hasAnyRole(['vendedor', 'ventas', 'tecnico']);
    }
}

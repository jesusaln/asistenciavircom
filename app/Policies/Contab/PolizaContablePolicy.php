<?php

namespace App\Policies\Contab;

use App\Models\Contab\PolizaContable;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PolizaContablePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, PolizaContable $poliza)
    {
        return $user->empresa_id === $poliza->empresa_id;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, PolizaContable $poliza)
    {
        return $user->empresa_id === $poliza->empresa_id;
    }

    public function delete(User $user, PolizaContable $poliza)
    {
        return $user->empresa_id === $poliza->empresa_id;
    }
}

<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Producto;
use App\Policies\ClientePolicy;
use App\Policies\ProductoPolicy;

use App\Policies\UserPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Cliente::class => ClientePolicy::class,
        Producto::class => ProductoPolicy::class,
        \App\Models\Cotizacion::class => \App\Policies\CotizacionPolicy::class,
        \App\Models\Pedido::class => \App\Policies\PedidoPolicy::class,
        \App\Models\Venta::class => \App\Policies\VentaPolicy::class,
        \App\Models\Proyecto::class => \App\Policies\ProyectoPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Superadmin: cualquier usuario con rol 'admin' o 'super-admin' tiene acceso a todas las habilidades
        Gate::before(function ($user, $ability) {
            return ($user->hasRole('admin') || $user->hasRole('super-admin')) ? true : null;
        });
    }
}

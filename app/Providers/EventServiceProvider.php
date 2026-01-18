<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \App\Events\ClientCreated::class => [
            \App\Listeners\StoreClientNotification::class,
        ],

        \App\Events\TicketCreado::class => [
            \App\Listeners\AsignacionInteligenteListener::class,
            \App\Listeners\NotificarClienteTicketCreado::class,
        ],

        \App\Events\CitaCompletada::class => [
            \App\Listeners\ActualizarTicketDesdeCita::class,
        ],

        \App\Events\PolizaProximaAVencer::class => [
            \App\Listeners\CrearTicketRenovacion::class,
        ],
        
        // Venta Events
        \App\Events\VentaCreated::class => [
            \App\Listeners\LogVentaCreated::class,
        ],
        \App\Events\VentaUpdated::class => [
            \App\Listeners\LogVentaUpdated::class,
        ],
        \App\Events\VentaCancelled::class => [
            \App\Listeners\LogVentaCancelled::class,
        ],
    ];

    public function boot(): void
    {
        // Registrar observers
        \App\Models\Producto::observe(\App\Observers\KitObserver::class);
    }
}

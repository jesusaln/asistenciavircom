<?php

namespace App\Providers;

use Inertia\Inertia;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ...
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        if (str_starts_with(config('app.url'), 'https')) {
            URL::forceScheme('https');
        }

        // Nota: Para manejar UTF-8 malformado, trata los datos de origen
        // (BD/strings) antes de pasarlos a Inertia. Evitamos usar métodos
        // inexistentes en la versión actual de inertia-laravel.
        // Registrar el evento y el listener
        Event::listen(
            \App\Events\ClientCreated::class, // El evento
            \App\Listeners\StoreClientNotification::class // El listener
        );

        // Mapeo polimórfico: usar alias cortos y permitir FQCN por compatibilidad
        // Activamos enforce para evitar nombres no mapeados en el futuro
        Relation::enforceMorphMap([
            // Aliases preferidos
            'producto' => \App\Models\Producto::class,
            'servicio' => \App\Models\Servicio::class,
            'cliente' => \App\Models\Cliente::class,
            'prestamo' => \App\Models\Prestamo::class,
            'pago_prestamo' => \App\Models\PagoPrestamo::class,
            'historial_pago_prestamo' => \App\Models\HistorialPagoPrestamo::class,
            'venta' => \App\Models\Venta::class,
            'compra' => \App\Models\Compra::class,
            'renta' => \App\Models\Renta::class,
            'cuentas_por_cobrar' => \App\Models\CuentasPorCobrar::class,
            'cuentas_por_pagar' => \App\Models\CuentasPorPagar::class,
            'entrega_dinero' => \App\Models\EntregaDinero::class,
            'poliza_servicio' => \App\Models\PolizaServicio::class,
            'ticket' => \App\Models\Ticket::class,
            'factura' => \App\Models\Factura::class,
            // Nota: Para modelos de terceros como User/Tecnico usados por spatie/permission,
            // no definimos alias cortos para no romper pivotes existentes

            // Compatibilidad por si existen tipos almacenados con FQCN
            'App\\Models\\Producto' => \App\Models\Producto::class,
            'App\\Models\\Servicio' => \App\Models\Servicio::class,
            'App\\Models\\Cliente' => \App\Models\Cliente::class,
            'App\\Models\\User' => \App\Models\User::class,
            // 'App\\Models\\Tecnico' => \App\Models\Tecnico::class, // REMOVED
            'App\\Models\\Prestamo' => \App\Models\Prestamo::class,
            'App\\Models\\PagoPrestamo' => \App\Models\PagoPrestamo::class,
            'App\\Models\\HistorialPagoPrestamo' => \App\Models\HistorialPagoPrestamo::class,
            'App\\Models\\Venta' => \App\Models\Venta::class,
            'App\\Models\\Compra' => \App\Models\Compra::class,
            'App\\Models\\Renta' => \App\Models\Renta::class,
            'App\\Models\\CuentasPorCobrar' => \App\Models\CuentasPorCobrar::class,
            'App\\Models\\CuentasPorPagar' => \App\Models\CuentasPorPagar::class,
            'App\\Models\\EntregaDinero' => \App\Models\EntregaDinero::class,
            'App\\Models\\PolizaServicio' => \App\Models\PolizaServicio::class,
            'App\\Models\\Factura' => \App\Models\Factura::class,
        ]);


        // Registrar Observers
        \App\Models\CuentasPorCobrar::observe(\App\Observers\CuentasPorCobrarObserver::class);
        \App\Models\Herramienta::observe(\App\Observers\HerramientaObserver::class);

        // ✅ FIX #2: Observer para sincronizar inventarios con producto_series
        \App\Models\ProductoSerie::observe(\App\Observers\ProductoSerieObserver::class);

        // ✅ FIX: Observer para sincronizar CxC cuando venta.pagado cambia
        \App\Models\Venta::observe(\App\Observers\VentaObserver::class);

        // Cita Observer (Microsoft To Do)
        \App\Models\Cita::observe(\App\Observers\CitaObserver::class);
    }
}

<?php

namespace App\Providers;

use Inertia\Inertia;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
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
        // Mapeo polimórfico: usamos morphMap en lugar de enforceMorphMap para evitar bloqueos restrictivos, pero permitimos leer los alias cortos en la BD
        Relation::morphMap([
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
            'Venta' => \App\Models\Venta::class, // Robustez para mayúsculas
            'Renta' => \App\Models\Renta::class, // Robustez para mayúsculas
            'cuentas_por_cobrar' => \App\Models\CuentasPorCobrar::class,
            'cuentas_por_pagar' => \App\Models\CuentasPorPagar::class,
            'entrega_dinero' => \App\Models\EntregaDinero::class,
            'poliza_servicio' => \App\Models\PolizaServicio::class,
            'ticket' => \App\Models\Ticket::class,
            'factura' => \App\Models\Factura::class,
            'cita' => \App\Models\Cita::class,

            // Compatibilidad por si existen tipos almacenados con FQCN
            'App\\Models\\Producto' => \App\Models\Producto::class,
            'App\\Models\\Servicio' => \App\Models\Servicio::class,
            'App\\Models\\Cliente' => \App\Models\Cliente::class,
            'App\\Models\\User' => \App\Models\User::class,
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
            'App\\Models\\Ticket' => \App\Models\Ticket::class,
            'App\\Models\\Factura' => \App\Models\Factura::class,
            'App\\Models\\Cita' => \App\Models\Cita::class,
        ]);

        // ✅ Desactivar Rate Limiting en Local para evitar Error 429
        RateLimiter::for('api', function (Request $request) {
            return Limit::none();
        });

        // Desactivar pre-carga automática de CSS para evitar advertencias de "preloaded but not used" en Chrome
        \Illuminate\Support\Facades\Vite::usePreloadTagAttributes(false);

        if ($request = $this->getCurrentRequest()) {
            // $this->applyRequestUrlForLocalHosts($request);
        }

        // Forzar HTTPS en producción o cuando se detecte el encabezado de proxy
        if (!app()->isLocal() || request()->header('X-Forwarded-Proto') === 'https' || str_contains(request()->getHost(), 'climasdeldesierto.com') || str_contains(request()->getHost(), 'asistenciavircom.com')) {
            URL::forceScheme('https');
            // Aseguramos que el objeto Request reconozca que es HTTPS para la validación de firmas
            if (request()->header('X-Forwarded-Proto') === 'https') {
                request()->server->set('HTTPS', 'on');
            }
        }

        // Nota: Para manejar UTF-8 malformado, trata los datos de origen
        // (BD/strings) antes de pasarlos a Inertia. Evitamos usar métodos
        // inexistentes en la versión actual de inertia-laravel.
        // Registrar el evento y el listener
        Event::listen(
            \App\Events\ClientCreated::class, // El evento
            \App\Listeners\StoreClientNotification::class // El listener
        );

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

    private function getCurrentRequest(): ?Request
    {
        try {
            return request();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function applyRequestUrlForLocalHosts(Request $request): void
    {
        $host = strtolower($request->getHost());

        if (
            $host !== 'localhost'
            && !str_ends_with($host, '.localhost')
            && !str_ends_with($host, '.nip.io')
            && !(
                filter_var($host, FILTER_VALIDATE_IP)
                && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false
            )
        ) {
            return;
        }

        $scheme = $request->isSecure() ? 'https' : 'http';
        $appUrl = "{$scheme}://{$request->getHttpHost()}";

        config([
            'app.url' => $appUrl,
            'session.domain' => null,
            'session.secure' => $request->isSecure(),
        ]);

        URL::forceRootUrl($appUrl);
    }
}

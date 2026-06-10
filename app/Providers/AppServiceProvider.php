<?php

namespace App\Providers;

use Inertia\Inertia;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Queue;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\AI\AIManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ✅ BLINDAJE DE BASE DE DATOS (Modo estricto completo: N+1, Atributos inexistentes y Descarte silencioso)
        Model::shouldBeStrict(!app()->isProduction() && !app()->runningUnitTests());
        DB::whenQueryingForLongerThan(300, function ($connection) {
            Log::warning("Consulta DB lenta detectada: {$connection->totalQueryDuration()}ms en conexión {$connection->getName()}");
        });
        DB::listen(function ($query) {
            if ($query->time > 200) {
                Log::debug('Slow query >200ms', ['sql' => $query->sql, 'bindings' => $query->bindings, 'time' => $query->time]);
            }
        });

        // Dead Letter Queue: alerta automática cuando un job falla definitivamente
        Queue::failing(function (\Illuminate\Queue\Events\JobFailed $event) {
            Log::channel('whatsapp')->error('JOB FAILED (Dead Letter)', [
                'job' => $event->job->resolveName(),
                'uuid' => $event->job->uuid(),
                'exception' => $event->exception->getMessage(),
                'failed_at' => now()->toDateTimeString(),
            ]);
        });

        // ✅ BLINDAJE SMTP: Ignorar fallos de verificación SSL/TLS con servidores de correo en Symfony Mailer
        \Illuminate\Support\Facades\Mail::extend('smtp', function (array $config) {
            $factory = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
            $scheme = $config['scheme'] ?? null;
            if (!$scheme) {
                $scheme = ($config['port'] == 465) ? 'smtps' : 'smtp';
            }
            $transport = $factory->create(new \Symfony\Component\Mailer\Transport\Dsn(
                $scheme,
                $config['host'],
                $config['username'] ?? null,
                $config['password'] ?? null,
                $config['port'] ?? null,
                $config
            ));
            $stream = $transport->getStream();
            if ($stream instanceof \Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream) {
                if (isset($config['source_ip'])) {
                    $stream->setSourceIp($config['source_ip']);
                }
                if (isset($config['timeout'])) {
                    $stream->setTimeout($config['timeout']);
                }
                if (isset($config['stream'])) {
                    $stream->setStreamOptions($config['stream']);
                }
            }
            return $transport;
        });

        // ✅ BLINDAJE SMART: Detección inteligente de entorno para evitar estar moviendo el .env
        $request = $this->getCurrentRequest();
        $isLocalAccess = $request ? $this->isLocalAccess($request) : false;

        // Forzar HTTPS solo en producción real (fuera de red local o nip.io)
        if (!app()->environment('local') && !$isLocalAccess) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Aplicar configuración dinámica si es acceso local para que el .env no rompa la app
        if ($isLocalAccess && $request) {
            $this->applyDynamicLocalConfig($request);
        }

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
            'cobranza' => \App\Models\Cobranza::class,
            'Venta' => \App\Models\Venta::class, // Robustez para mayúsculas
            'Renta' => \App\Models\Renta::class, // Robustez para mayúsculas
            'cuentas_por_cobrar' => \App\Models\CuentasPorCobrar::class,
            'cuentas_por_pagar' => \App\Models\CuentasPorPagar::class,
            'entrega_dinero' => \App\Models\EntregaDinero::class,
            'poliza_servicio' => \App\Models\PolizaServicio::class,
            'ticket' => \App\Models\Ticket::class,
            'factura' => \App\Models\Factura::class,
            'cita' => \App\Models\Cita::class,
            'gasto' => \App\Models\Compra::class,
            'todo' => \App\Models\Todo::class,
            'user' => \App\Models\User::class,

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

        // ✅ Google Drive Filesystem Driver
        Storage::extend('google-drive', function ($app, $config) {
            if (empty($config['client_id']) || empty($config['client_secret']) || empty($config['refresh_token'])) {
                return null;
            }
            $client = new \Google\Client();
            $client->setClientId($config['client_id']);
            $client->setClientSecret($config['client_secret']);
            $client->refreshToken($config['refresh_token']);
            $client->setApplicationName('CDD Backups');

            $service = new \Google\Service\Drive($client);
            $options = [];
            if (!empty($config['team_drive_id'])) {
                $options['teamDriveId'] = $config['team_drive_id'];
            }
            $adapter = new \Masbug\Flysystem\GoogleDriveAdapter($service, $config['folder_id'] ?? '/', $options);
            return new \League\Flysystem\Filesystem($adapter);
        });

        // ✅ Desactivar Rate Limiting en Local para evitar Error 429
        RateLimiter::for('api', function (Request $request) {
            return Limit::none();
        });

        // Desactivar pre-carga automática de CSS para evitar advertencias de "preloaded but not used" en Chrome
        \Illuminate\Support\Facades\Vite::usePreloadTagAttributes(false);



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

        // ✅ FIX: Invalidador de caché general para ventas
        \App\Models\Servicio::observe(\App\Observers\GeneralCacheObserver::class);
        \App\Models\Almacen::observe(\App\Observers\GeneralCacheObserver::class);
        \App\Models\PriceList::observe(\App\Observers\GeneralCacheObserver::class);
        \App\Models\Producto::observe(\App\Observers\GeneralCacheObserver::class);



        // Todo Observer (Advanced Features)
        \App\Models\Todo::observe(\App\Observers\TodoObserver::class);
    }

    private function getCurrentRequest(): ?Request
    {
        try {
            return request();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function isLocalAccess(Request $request): bool
    {
        $host = strtolower($request->getHost());
        
        return $host === 'localhost' 
            || str_ends_with($host, '.localhost') 
            || str_ends_with($host, '.nip.io')
            || $host === '127.0.0.1'
            || (filter_var($host, FILTER_VALIDATE_IP) && !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE));
    }

    /**
     * Ajusta la configuración en tiempo de ejecución para que la app funcione localmente 
     * sin importar lo que diga el .env (Ideal para pruebas en red local o nip.io)
     */
    private function applyDynamicLocalConfig(Request $request): void
    {
        $scheme = $request->isSecure() ? 'https' : 'http';
        $appUrl = "{$scheme}://{$request->getHttpHost()}";
        $host = $request->getHost();

        config([
            'app.url' => $appUrl,
            'session.domain' => null, // Permite que las cookies funcionen en cualquier IP local
            'session.secure' => $request->isSecure(),
            
            // Ajustar Reverb para que apunte al host actual (ej. 192.168.1.14) y no a producción
            'reverb.apps.0.options.host' => $host,
            'reverb.apps.0.options.port' => $request->isSecure() ? 443 : 8080,
            'reverb.apps.0.options.scheme' => $scheme,
            'reverb.apps.0.options.useTLS' => $request->isSecure(),
            
            // También para el driver de broadcasting de Laravel
            'broadcasting.connections.reverb.options.host' => $host,
            'broadcasting.connections.reverb.options.port' => $request->isSecure() ? 443 : 8080,
            'broadcasting.connections.reverb.options.scheme' => $scheme,
        ]);

        URL::forceRootUrl($appUrl);
    }
}

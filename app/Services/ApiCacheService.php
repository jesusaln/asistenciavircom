<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ApiCacheService - Sistema de caché para llamadas a APIs externas
 *
 * Proporciona caché automático para llamadas a Facturapi, CVA y otras APIs.
 *
 * @example
 *   $cache = app(ApiCacheService::class);
 *
 *   // obtener con caché automático
 *   $data = $cache->remember('facturapi_comprobante_' . $id, now()->addHours(1), function() use ($id) {
 *       return app(FacturapiService::class)->getComprobante($id);
 *   });
 */
class ApiCacheService
{
    /**
     * Prefijo para claves de caché
     */
    protected string $prefix = 'api_cache';

    /**
     * TTL por defecto en minutos
     */
    protected int $defaultTtl = 60;

    /**
     * Instancia del servicio (para llamadas encadenadas)
     */
    protected ?object $serviceInstance = null;

    /**
     * Nombre del servicio para logging
     */
    protected string $serviceName = 'ApiCache';

    /**
     * Obtener valor con caché
     *
     * @param string $key Clave única para el caché
     * @param \DateTimeInterface|\DateInterval|int $ttl Tiempo de vida
     * @param callable $callback Función que obtiene los datos si no hay caché
     * @param array $tags Tags para el caché (opcional)
     * @return mixed
     */
    public function remember(
        string $key,
        \DateTimeInterface|\DateInterval|int $ttl,
        callable $callback,
        ?array $tags = null
    ): mixed {
        $fullKey = $this->buildKey($key);
        $ttlMinutes = $this->parseTtl($ttl);

        // Intentar obtener del caché
        try {
            $cached = $this->getFromCache($fullKey, $tags);

            if ($cached !== null) {
                $this->log('cache_hit', ['key' => $fullKey]);
                return $cached;
            }
        } catch (Throwable $e) {
            $this->log('cache_error', ['key' => $fullKey, 'error' => $e->getMessage()]);
            // Continuar sin caché si hay error
        }

        // Obtener datos frescos
        $this->log('cache_miss', ['key' => $fullKey]);

        try {
            $data = $callback();

            // Guardar en caché
            $this->saveToCache($fullKey, $data, $ttlMinutes, $tags);

            return $data;
        } catch (Throwable $e) {
            $this->log('api_error', ['key' => $fullKey, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Obtener valor con caché usando el método del servicio
     *
     * @param string $method Nombre del método a llamar
     * @param array $args Argumentos del método
     * @param \DateTimeInterface|\DateInterval|int $ttl Tiempo de vida
     * @return mixed
     */
    public function rememberMethod(
        string $method,
        array $args = [],
        \DateTimeInterface|\DateInterval|int $ttl = null
    ): mixed {
        $ttl = $ttl ?? $this->defaultTtl;

        // Generar clave única basada en método y argumentos
        $key = $this->generateMethodKey($method, $args);

        return $this->remember($key, $ttl, function () use ($method, $args) {
            if ($this->serviceInstance === null) {
                throw new \RuntimeException('Service instance not set. Use setService() first.');
            }

            return call_user_func_array([$this->serviceInstance, $method], $args);
        });
    }

    /**
     * Olvidar (invalidar) una clave de caché
     *
     * @param string $key Clave a invalidar
     * @param array|null $tags Tags asociados (opcional)
     * @return bool
     */
    public function forget(string $key, ?array $tags = null): bool
    {
        $fullKey = $this->buildKey($key);

        try {
            if ($tags) {
                Cache::tags($tags)->forget($fullKey);
            } else {
                Cache::forget($fullKey);
            }

            $this->log('cache_forget', ['key' => $fullKey]);
            return true;
        } catch (Throwable $e) {
            $this->log('cache_forget_error', ['key' => $fullKey, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Olvidar todas las claves de un prefijo
     *
     * @param string $prefix Prefijo de claves
     * @return int Número de claves eliminadas
     */
    public function forgetByPrefix(string $prefix): int
    {
        $fullPrefix = $this->buildKey($prefix);
        $count = 0;

        try {
            $keys = Cache::get($fullPrefix . '_keys', []);

            foreach ($keys as $key) {
                if (Cache::forget($key)) {
                    $count++;
                }
            }

            Cache::forget($fullPrefix . '_keys');
            $this->log('cache_forget_prefix', ['prefix' => $fullPrefix, 'count' => $count]);

            return $count;
        } catch (Throwable $e) {
            $this->log('cache_forget_prefix_error', ['prefix' => $fullPrefix, 'error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Invalidar todas las claves de un servicio
     *
     * @param string $serviceName Nombre del servicio
     * @return int
     */
    public function flushService(string $serviceName): int
    {
        return $this->forgetByPrefix($serviceName);
    }

    /**
     * Establecer el servicio a usar
     *
     * @param object $service Instancia del servicio
     * @return self
     */
    public function setService(object $service): self
    {
        $this->serviceInstance = $service;
        $this->serviceName = get_class($service);
        return $this;
    }

    /**
     * Configurar TTL por defecto
     *
     * @param int $minutes Minutos
     * @return self
     */
    public function setDefaultTtl(int $minutes): self
    {
        $this->defaultTtl = $minutes;
        return $this;
    }

    /**
     * Configurar prefijo de claves
     *
     * @param string $prefix Prefijo
     * @return self
     */
    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Verificar si una clave existe en caché
     *
     * @param string $key Clave
     * @return bool
     */
    public function has(string $key): bool
    {
        try {
            return Cache::has($this->buildKey($key));
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Obtener TTL restante de una clave
     *
     * @param string $key Clave
     * @return int|null Segundos restantes o null
     */
    public function ttl(string $key): ?int
    {
        try {
            return Cache::ttl($this->buildKey($key));
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Refrescar una clave (obtener nuevos datos y guardar)
     *
     * @param string $key Clave
     * @param callable $callback Función para obtener nuevos datos
     * @param \DateTimeInterface|\DateInterval|int $ttl Tiempo de vida
     * @return mixed
     */
    public function refresh(
        string $key,
        callable $callback,
        \DateTimeInterface|\DateInterval|int $ttl = null
    ): mixed {
        $ttl = $ttl ?? $this->defaultTtl;
        $this->forget($key);
        return $this->remember($key, $ttl, $callback);
    }

    // ==================== Métodos Protegidos ====================

    /**
     * Construir clave completa
     */
    protected function buildKey(string $key): string
    {
        return "{$this->prefix}:{$key}";
    }

    /**
     * Generar clave única basada en método y argumentos
     */
    protected function generateMethodKey(string $method, array $args): string
    {
        $argsHash = md5(json_encode($args));
        return "method:{$method}:{$argsHash}";
    }

    /**
     * Parsear TTL a minutos
     */
    protected function parseTtl(\DateTimeInterface|\DateInterval|int $ttl): int
    {
        if ($ttl instanceof \DateTimeInterface) {
            return now()->diffInMinutes($ttl);
        }

        if ($ttl instanceof \DateInterval) {
            return ($ttl->d * 24 * 60) + ($ttl->h * 60) + $ttl->i;
        }

        return (int) $ttl;
    }

    /**
     * Obtener del caché
     */
    protected function getFromCache(string $key, ?array $tags = null)
    {
        if ($tags) {
            return Cache::tags($tags)->get($key);
        }

        return Cache::get($key);
    }

    /**
     * Guardar en caché
     */
    protected function saveToCache(string $key, mixed $data, int $ttlMinutes, ?array $tags = null): void
    {
        // Trackear claves para limpieza por prefijo
        $keysKey = $this->prefix . '_keys';
        $currentKeys = Cache::get($keysKey, []);
        $currentKeys[] = $key;
        Cache::put($keysKey, $currentKeys, now()->addDays(7));

        if ($tags) {
            Cache::tags($tags)->put($key, $data, $ttlMinutes);
        } else {
            Cache::put($key, $data, $ttlMinutes);
        }
    }

    /**
     * Loguear operación
     */
    protected function log(string $action, array $context = []): void
    {
        Log::channel('daily')->info("{$this->serviceName}:{$action}", $context);
    }
}

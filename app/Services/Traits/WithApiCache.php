<?php

namespace App\Services\Traits;

use App\Services\ApiCacheService;

/**
 * WithApiCache - Trait para añadir caché a servicios de API
 *
 * @example
 *   class CvaService
 *   {
 *       use WithApiCache;
 *
 *       public function __construct()
 *       {
 *           $this->initCache('cva', now()->addHours(2));
 *       }
 *   }
 */
trait WithApiCache
{
    /**
     * Instancia del servicio de caché
     */
    protected ApiCacheService $apiCache;

    /**
     * TTL por defecto para este servicio
     */
    protected int $cacheTtl = 60;

    /**
     * Inicializar el caché
     *
     * @param string $prefix Prefijo para las claves
     * @param \DateTimeInterface|\DateInterval|int $ttl TTL por defecto
     * @return self
     */
    protected function initCache(
        string $prefix,
        \DateTimeInterface|\DateInterval|int $ttl = 60
    ): self {
        $this->apiCache = app(ApiCacheService::class);
        $this->apiCache->setPrefix($prefix);
        $this->apiCache->setDefaultTtl($this->parseTtl($ttl));
        $this->apiCache->setService($this);

        return $this;
    }

    /**
     * Configurar TTL por defecto
     *
     * @param int $minutes Minutos
     * @return self
     */
    protected function setCacheTtl(int $minutes): self
    {
        $this->cacheTtl = $minutes;
        $this->apiCache->setDefaultTtl($minutes);
        return $this;
    }

    /**
     * Obtener datos con caché
     *
     * @param string $key Clave única
     * @param callable $callback Función para obtener datos
     * @param array|null $tags Tags de caché
     * @return mixed
     */
    protected function getCached(
        string $key,
        callable $callback,
        ?array $tags = null
    ): mixed {
        return $this->apiCache->remember($key, $this->cacheTtl, $callback, $tags);
    }

    /**
     * Obtener datos con caché usando el método del servicio
     *
     * @param string $method Nombre del método
     * @param array $args Argumentos
     * @param int|null $ttl TTL personalizado
     * @return mixed
     */
    protected function getCachedMethod(
        string $method,
        array $args = [],
        ?int $ttl = null
    ): mixed {
        return $this->apiCache->rememberMethod($method, $args, $ttl ?? $this->cacheTtl);
    }

    /**
     * Invalidar caché de una clave
     *
     * @param string $key Clave
     * @return bool
     */
    protected function invalidateCache(string $key): bool
    {
        return $this->apiCache->forget($key);
    }

    /**
     * Invalidar todo el caché del servicio
     *
     * @return int
     */
    protected function flushCache(): int
    {
        return $this->apiCache->flushService($this->getCachePrefix());
    }

    /**
     * Verificar si hay caché válido
     *
     * @param string $key Clave
     * @return bool
     */
    protected function hasCached(string $key): bool
    {
        return $this->apiCache->has($key);
    }

    /**
     * Obtener TTL restante
     *
     * @param string $key Clave
     * @return int|null
     */
    protected function getCacheTtl(string $key): ?int
    {
        return $this->apiCache->ttl($key);
    }

    /**
     * Refrescar una entrada de caché
     *
     * @param string $key Clave
     * @param callable $callback Función para obtener nuevos datos
     * @return mixed
     */
    protected function refreshCache(string $key, callable $callback)
    {
        return $this->apiCache->refresh($key, $callback, $this->cacheTtl);
    }

    /**
     * Obtener el prefijo de caché configurado
     */
    protected function getCachePrefix(): string
    {
        return strtolower(class_basename($this));
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
     * Construir clave de caché
     *
     * @param string $suffix Sufijo
     * @return string
     */
    protected function cacheKey(string $suffix): string
    {
        return $this->getCachePrefix() . ':' . $suffix;
    }
}

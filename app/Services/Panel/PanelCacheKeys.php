<?php

namespace App\Services\Panel;

use App\Support\EmpresaResolver;
use Carbon\Carbon;

/**
 * Claves y TTL coherentes para caché del panel (por empresa / fecha).
 */
final class PanelCacheKeys
{
    public static function ttl(string $type = 'default'): int
    {
        return (int) config("panel.cache_ttl.{$type}", config('panel.cache_ttl.default', 120));
    }

    /**
     * Sufijo único por empresa (evita mezclar datos entre tenants).
     */
    public static function key(string $suffix): string
    {
        $eid = EmpresaResolver::resolveId();
        $connection = config('database.default');
        $fullKey = 'panel:'.$connection.':'.$suffix.':'.($eid ?? 'global');
        
        // \Illuminate\Support\Facades\Log::debug("PanelCacheKey: " . $fullKey);
        
        return $fullKey;
    }

    /**
     * Para datos del día (p. ej. citas de hoy): la clave incluye la fecha local.
     */
    public static function keyForDate(string $suffix, ?Carbon $date = null): string
    {
        $date = $date ?? Carbon::now(config('app.timezone', 'America/Hermosillo'));

        return self::key($suffix.'_'.$date->format('Y-m-d'));
    }
}

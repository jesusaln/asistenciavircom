<?php

/**
 * Sistema básico de Feature Flags con aislamiento por empresa en caché.
 */

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class FeatureFlag
{
    private const CACHE_TTL = 3600;

    private static ?bool $featureFlagsTableExists = null;

    private static ?bool $userFeaturesTableExists = null;

    /**
     * ID de inquilino para claves de caché (0 = sin contexto).
     */
    private static function tenantId(): int
    {
        $id = EmpresaResolver::resolveId();

        return $id !== null ? (int) $id : 0;
    }

    private static function cacheKey(string $feature): string
    {
        return 'feature.' . self::tenantId() . '.' . $feature;
    }

    private static function featureFlagsTableExists(): bool
    {
        if (self::$featureFlagsTableExists !== null) {
            return self::$featureFlagsTableExists;
        }

        try {
            self::$featureFlagsTableExists = \Schema::hasTable('feature_flags');
        } catch (\Throwable $e) {
            self::$featureFlagsTableExists = false;
        }

        return self::$featureFlagsTableExists;
    }

    private static function userFeaturesTableExists(): bool
    {
        if (self::$userFeaturesTableExists !== null) {
            return self::$userFeaturesTableExists;
        }

        try {
            self::$userFeaturesTableExists = \Schema::hasTable('user_features');
        } catch (\Throwable $e) {
            self::$userFeaturesTableExists = false;
        }

        return self::$userFeaturesTableExists;
    }

    public static function isEnabled(string $feature, bool $default = false): bool
    {
        $key = self::cacheKey($feature);
        if (Cache::has($key)) {
            return (bool) Cache::get($key);
        }

        $flagsJson = config('features.flags_json', []);
        if (is_array($flagsJson) && array_key_exists($feature, $flagsJson)) {
            $value = (bool) $flagsJson[$feature];
            Cache::put($key, $value, self::CACHE_TTL);

            return $value;
        }

        $dbValue = self::getFromDatabase($feature);
        if ($dbValue !== null) {
            Cache::put($key, $dbValue, self::CACHE_TTL);

            return $dbValue;
        }

        Cache::put($key, $default, self::CACHE_TTL);

        return $default;
    }

    public static function enable(string $feature): bool
    {
        return self::set($feature, true);
    }

    public static function disable(string $feature): bool
    {
        return self::set($feature, false);
    }

    public static function set(string $feature, bool $value): bool
    {
        Cache::put(self::cacheKey($feature), $value, self::CACHE_TTL);
        self::saveToDatabase($feature, $value);

        return true;
    }

    private static function getFromDatabase(string $feature): ?bool
    {
        if (! self::featureFlagsTableExists()) {
            return null;
        }

        try {
            $record = \DB::table('feature_flags')
                ->where('feature_key', $feature)
                ->first();

            return $record ? (bool) $record->is_enabled : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private static function saveToDatabase(string $feature, bool $value): void
    {
        if (! self::featureFlagsTableExists()) {
            return;
        }

        try {
            \DB::table('feature_flags')
                ->updateOrInsert(
                    ['feature_key' => $feature],
                    [
                        'is_enabled' => $value,
                        'updated_at' => now(),
                    ]
                );
        } catch (\Exception $e) {
            // Silenciar si no hay BD
        }
    }

    public static function all(): array
    {
        $defaults = config('features.defaults', []);
        $result = [];

        foreach ($defaults as $key => $default) {
            $result[$key] = self::isEnabled((string) $key, (bool) $default);
        }

        return $result;
    }

    public static function isEnabledForUser(string $feature, ?int $userId = null, bool $default = false): bool
    {
        if (! $userId) {
            return self::isEnabled($feature, $default);
        }

        $userFeature = "{$feature}_user_{$userId}";
        $userKey = self::cacheKey($userFeature);

        if (Cache::has($userKey)) {
            return (bool) Cache::get($userKey);
        }

        if (self::userFeaturesTableExists()) {
            try {
                $record = \DB::table('user_features')
                    ->where('user_id', $userId)
                    ->where('feature_key', $feature)
                    ->first();

                if ($record) {
                    $value = (bool) $record->is_enabled;
                    Cache::put($userKey, $value, self::CACHE_TTL);

                    return $value;
                }
            } catch (\Exception $e) {
                // Continuar con el valor por defecto
            }
        }

        $resolved = self::isEnabled($feature, $default);
        Cache::put($userKey, $resolved, self::CACHE_TTL);

        return $resolved;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para limitar el envío de correos electrónicos.
 * 
 * Límites configurables:
 * - Máximo X correos en Y minutos (burst limit)
 * - Máximo Z correos por día (daily limit)
 * 
 * Configuración en config/mail.php:
 * 'rate_limit' => [
 *     'enabled' => true,
 *     'burst_limit' => 10,      // máximo 10 correos
 *     'burst_window' => 10,     // en 10 minutos
 *     'daily_limit' => 100,     // máximo 100 correos al día
 * ]
 */
class EmailRateLimiterService
{
    private const CACHE_PREFIX = 'email_rate_limit:';
    private const BURST_KEY = 'burst';
    private const DAILY_KEY = 'daily';

    private bool $enabled;
    private int $burstLimit;
    private int $burstWindowMinutes;
    private int $dailyLimit;

    public function __construct()
    {
        $this->enabled = config('mail.rate_limit.enabled', true);
        $this->burstLimit = config('mail.rate_limit.burst_limit', 10);
        $this->burstWindowMinutes = config('mail.rate_limit.burst_window', 10);
        $this->dailyLimit = config('mail.rate_limit.daily_limit', 100);
    }

    /**
     * Verifica si se puede enviar un correo según los límites.
     * 
     * @return bool True si se puede enviar, false si está limitado
     */
    public function canSendEmail(): bool
    {
        if (!$this->enabled) {
            return true;
        }

        // Verificar límite de ráfaga (burst)
        if ($this->getBurstCount() >= $this->burstLimit) {
            Log::warning('EmailRateLimiter: Límite de ráfaga alcanzado', [
                'burst_count' => $this->getBurstCount(),
                'burst_limit' => $this->burstLimit,
                'window_minutes' => $this->burstWindowMinutes,
            ]);
            return false;
        }

        // Verificar límite diario
        if ($this->getDailyCount() >= $this->dailyLimit) {
            Log::warning('EmailRateLimiter: Límite diario alcanzado', [
                'daily_count' => $this->getDailyCount(),
                'daily_limit' => $this->dailyLimit,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Registra el envío de un correo exitoso.
     * Debe llamarse después de enviar el correo.
     */
    public function recordEmailSent(): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->incrementBurstCount();
        $this->incrementDailyCount();

        Log::debug('EmailRateLimiter: Correo registrado', [
            'burst_count' => $this->getBurstCount(),
            'daily_count' => $this->getDailyCount(),
        ]);
    }

    /**
     * Intenta enviar un correo con rate limiting.
     * 
     * @param callable $sendCallback La función que envía el correo
     * @return array ['success' => bool, 'message' => string]
     */
    public function attemptSend(callable $sendCallback): array
    {
        if (!$this->canSendEmail()) {
            $remaining = $this->getTimeUntilBurstReset();
            return [
                'success' => false,
                'message' => "Límite de correos alcanzado. Por favor espere {$remaining} minutos antes de enviar más correos.",
                'retry_after' => $remaining,
            ];
        }

        try {
            $sendCallback();
            $this->recordEmailSent();
            return [
                'success' => true,
                'message' => 'Correo enviado exitosamente',
            ];
        } catch (\Exception $e) {
            Log::error('EmailRateLimiter: Error enviando correo', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Obtiene el conteo de correos en la ventana de ráfaga.
     */
    public function getBurstCount(): int
    {
        return (int) Cache::get($this->getBurstCacheKey(), 0);
    }

    /**
     * Obtiene el conteo de correos del día.
     */
    public function getDailyCount(): int
    {
        return (int) Cache::get($this->getDailyCacheKey(), 0);
    }

    /**
     * Obtiene el tiempo restante hasta que se resetee el límite de ráfaga.
     * 
     * @return int Minutos restantes
     */
    public function getTimeUntilBurstReset(): int
    {
        $store = Cache::getStore();
        if (method_exists($store, 'ttl')) {
            $ttl = $store->ttl($this->getBurstCacheKey());
            return $ttl ? (int) ceil($ttl / 60) : 0;
        }

        // Fallback for drivers that don't support TTL (like database/file)
        // We can't know for sure, so we return a conservative estimate or look for hints
        return $this->burstWindowMinutes;
    }

    /**
     * Obtiene el tiempo restante hasta que se resetee el límite diario.
     * 
     * @return int Horas restantes
     */
    public function getTimeUntilDailyReset(): int
    {
        $now = now();
        $endOfDay = $now->copy()->endOfDay();
        return (int) $now->diffInHours($endOfDay);
    }

    /**
     * Obtiene estadísticas de uso para mostrar en panel de admin.
     */
    public function getStats(): array
    {
        return [
            'enabled' => $this->enabled,
            'burst' => [
                'current' => $this->getBurstCount(),
                'limit' => $this->burstLimit,
                'window_minutes' => $this->burstWindowMinutes,
                'remaining' => max(0, $this->burstLimit - $this->getBurstCount()),
                'reset_in_minutes' => $this->getTimeUntilBurstReset(),
            ],
            'daily' => [
                'current' => $this->getDailyCount(),
                'limit' => $this->dailyLimit,
                'remaining' => max(0, $this->dailyLimit - $this->getDailyCount()),
                'reset_in_hours' => $this->getTimeUntilDailyReset(),
            ],
        ];
    }

    /**
     * Resetea los contadores (solo para testing/admin).
     */
    public function resetCounters(): void
    {
        Cache::forget($this->getBurstCacheKey());
        Cache::forget($this->getDailyCacheKey());
        Log::info('EmailRateLimiter: Contadores reseteados');
    }

    // --- Métodos privados ---

    private function getBurstCacheKey(): string
    {
        return self::CACHE_PREFIX . self::BURST_KEY;
    }

    private function getDailyCacheKey(): string
    {
        return self::CACHE_PREFIX . self::DAILY_KEY . ':' . now()->format('Y-m-d');
    }

    private function incrementBurstCount(): void
    {
        $key = $this->getBurstCacheKey();
        $seconds = $this->burstWindowMinutes * 60;

        if (Cache::has($key)) {
            Cache::increment($key);
        } else {
            Cache::put($key, 1, $seconds);
        }
    }

    private function incrementDailyCount(): void
    {
        $key = $this->getDailyCacheKey();
        $secondsUntilMidnight = now()->diffInSeconds(now()->endOfDay());

        if (Cache::has($key)) {
            Cache::increment($key);
        } else {
            Cache::put($key, 1, $secondsUntilMidnight);
        }
    }
}

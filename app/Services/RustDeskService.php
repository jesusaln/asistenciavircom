<?php

namespace App\Services;

use App\Contracts\RustDeskClientInterface;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class RustDeskService implements RustDeskClientInterface
{
    public function getDeviceStatus(string $rustdeskId): array
    {
        $rustdeskId = trim($rustdeskId);
        if ($rustdeskId === '') {
            return $this->failure('RustDesk ID vacío', null);
        }

        $runtime = $this->getRuntimeConfig();
        if (!$runtime['enabled']) {
            return $this->failure('Integración RustDesk no configurada', null);
        }

        try {
            $url = $this->buildUrl(
                $runtime['api_url'],
                (string) config('rustdesk.endpoints.device_status', '/api/devices/{id}'),
                $rustdeskId
            );

            $response = $this->httpClient($runtime)->get($url);
            if (!$response->successful()) {
                return $this->failure('Error consultando estado del equipo', $response->status());
            }

            $payload = (array) $response->json();
            $online = $this->extractOnlineStatus($payload);

            return [
                'ok' => true,
                'data' => [
                    'rustdesk_id' => $rustdeskId,
                    'online' => $online,
                    'raw' => $payload,
                ],
                'error' => null,
                'status' => $response->status(),
            ];
        } catch (Throwable $e) {
            Log::channel('rustdesk')->error('RustDesk status request failed', [
                'rustdesk_id' => $rustdeskId,
                'exception' => $e->getMessage(),
            ]);
            return $this->failure('No fue posible consultar RustDesk', null);
        }
    }

    public function listDevices(?string $search = null): array
    {
        $runtime = $this->getRuntimeConfig();
        if (!$runtime['enabled']) {
            return $this->failure('Integración RustDesk no configurada', null);
        }

        try {
            $url = $this->buildUrl(
                $runtime['api_url'],
                (string) config('rustdesk.endpoints.devices', '/api/devices')
            );

            $query = [];
            if (!empty($search)) {
                $query[(string) config('rustdesk.devices_search_key', 'search')] = $search;
            }

            $response = $this->httpClient($runtime)->get($url, $query);
            if (!$response->successful()) {
                return $this->failure('Error consultando listado de dispositivos', $response->status());
            }

            $payload = (array) $response->json();
            $devices = $payload['data'] ?? $payload['devices'] ?? $payload;
            if (!is_array($devices)) {
                $devices = [];
            }

            return [
                'ok' => true,
                'data' => [
                    'devices' => $devices,
                    'raw' => $payload,
                ],
                'error' => null,
                'status' => $response->status(),
            ];
        } catch (Throwable $e) {
            Log::channel('rustdesk')->error('RustDesk devices request failed', [
                'search' => $search,
                'exception' => $e->getMessage(),
            ]);
            return $this->failure('No fue posible consultar dispositivos en RustDesk', null);
        }
    }

    public function syncAlias(string $rustdeskId, string $alias): bool
    {
        $rustdeskId = trim($rustdeskId);
        $alias = trim($alias);

        if ($rustdeskId === '' || $alias === '') {
            return false;
        }

        $runtime = $this->getRuntimeConfig();
        if (!$runtime['enabled']) {
            return false;
        }

        try {
            $url = $this->buildUrl(
                $runtime['api_url'],
                (string) config('rustdesk.endpoints.sync_alias', '/api/devices/{id}/alias'),
                $rustdeskId
            );

            $payload = ['alias' => $alias];
            $method = strtolower((string) config('rustdesk.alias_method', 'patch'));

            $response = $this->sendAliasSync($this->httpClient($runtime), $method, $url, $payload);
            if ($response->successful()) {
                return true;
            }

            if ($response->status() === 405 && $method === 'patch') {
                $fallback = $this->httpClient($runtime)->put($url, $payload);
                return $fallback->successful();
            }

            Log::channel('rustdesk')->warning('RustDesk alias sync failed', [
                'rustdesk_id' => $rustdeskId,
                'status' => $response->status(),
            ]);

            return false;
        } catch (Throwable $e) {
            Log::channel('rustdesk')->error('RustDesk alias sync exception', [
                'rustdesk_id' => $rustdeskId,
                'exception' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function sendAliasSync(PendingRequest $client, string $method, string $url, array $payload)
    {
        return match ($method) {
            'post' => $client->post($url, $payload),
            'put' => $client->put($url, $payload),
            default => $client->patch($url, $payload),
        };
    }

    private function getRuntimeConfig(): array
    {
        $dbConfig = EmpresaConfiguracion::getConfig();
        $apiUrl = trim((string) (config('rustdesk.api_url') ?: $dbConfig->rustdesk_api_url));
        $token = trim((string) (config('rustdesk.api_token') ?: $dbConfig->rustdesk_api_token));
        $timeout = (int) config('rustdesk.timeout', 8);
        $retryTimes = (int) config('rustdesk.retry_times', 2);
        $retrySleepMs = (int) config('rustdesk.retry_sleep_ms', 250);

        return [
            'enabled' => $apiUrl !== '',
            'api_url' => rtrim($apiUrl, '/'),
            'api_token' => $token,
            'timeout' => $timeout > 0 ? $timeout : 8,
            'retry_times' => max(0, $retryTimes),
            'retry_sleep_ms' => max(0, $retrySleepMs),
        ];
    }

    private function httpClient(array $runtime): PendingRequest
    {
        $client = Http::acceptJson()
            ->timeout($runtime['timeout'])
            ->retry($runtime['retry_times'], $runtime['retry_sleep_ms'], null, false);

        $authHeader = (string) config('rustdesk.auth.header', 'Authorization');
        $authPrefix = trim((string) config('rustdesk.auth.prefix', 'Bearer'));
        $token = (string) $runtime['api_token'];

        if ($token !== '') {
            if (strtolower($authHeader) === 'authorization') {
                return $client->withToken($token, $authPrefix !== '' ? $authPrefix : 'Bearer');
            }
            return $client->withHeaders([$authHeader => $token]);
        }

        return $client;
    }

    private function buildUrl(string $apiUrl, string $endpoint, ?string $rustdeskId = null): string
    {
        $path = ltrim($endpoint, '/');
        if ($rustdeskId !== null) {
            $path = str_replace('{id}', urlencode($rustdeskId), $path);
        }

        return $apiUrl . '/' . $path;
    }

    private function extractOnlineStatus(array $payload): ?bool
    {
        $raw = data_get($payload, 'online');
        $raw ??= data_get($payload, 'is_online');
        $raw ??= data_get($payload, 'data.online');
        $raw ??= data_get($payload, 'data.is_online');
        $raw ??= data_get($payload, 'status');
        $raw ??= data_get($payload, 'data.status');

        if (is_bool($raw)) {
            return $raw;
        }

        if (is_numeric($raw)) {
            return ((int) $raw) === 1;
        }

        if (is_string($raw)) {
            $status = strtolower(trim($raw));
            if (in_array($status, ['online', 'connected', 'up', '1', 'true'], true)) {
                return true;
            }
            if (in_array($status, ['offline', 'disconnected', 'down', '0', 'false'], true)) {
                return false;
            }
        }

        return null;
    }

    private function failure(string $message, ?int $status): array
    {
        return [
            'ok' => false,
            'data' => null,
            'error' => $message,
            'status' => $status,
        ];
    }
}

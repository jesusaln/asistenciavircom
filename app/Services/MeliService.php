<?php

namespace App\Services;

use App\Models\EmpresaConfiguracion;
use App\Models\MercadoLibreListing;
use App\Models\MeliCategoryMapping;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MeliService
{
    const AUTH_URL = 'https://auth.mercadolibre.com.mx/authorization';
    const API_URL = 'https://api.mercadolibre.com';
    const TOKEN_URL = 'https://api.mercadolibre.com/oauth/token';

    public ?EmpresaConfiguracion $config;

    public function __construct()
    {
        $this->config = EmpresaConfiguracion::getConfig();
    }

    public function isConfigured(): bool
    {
        return $this->config
            && $this->config->meli_active
            && $this->config->meli_app_id
            && $this->config->meli_client_secret;
    }

    public function getAuthUrl(string $redirectUri): string
    {
        return self::AUTH_URL
            . '?response_type=code'
            . '&client_id=' . $this->config->meli_app_id
            . '&redirect_uri=' . urlencode($redirectUri);
    }

    public function authenticate(string $code, string $redirectUri): array
    {
        $response = Http::post(self::TOKEN_URL, [
            'grant_type' => 'authorization_code',
            'client_id' => $this->config->meli_app_id,
            'client_secret' => $this->config->meli_client_secret,
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ]);

        if (!$response->successful()) {
            Log::error('Meli auth error', ['status' => $response->status(), 'body' => $response->body()]);
            return ['error' => 'Error al autenticar con MercadoLibre'];
        }

        $data = $response->json();
        $this->saveToken($data);

        return ['success' => true, 'user_id' => $data['user_id'] ?? null];
    }

    public function refreshToken(): bool
    {
        if (!$this->config->meli_refresh_token) {
            return false;
        }

        $response = Http::post(self::TOKEN_URL, [
            'grant_type' => 'refresh_token',
            'client_id' => $this->config->meli_app_id,
            'client_secret' => $this->config->meli_client_secret,
            'refresh_token' => $this->config->meli_refresh_token,
        ]);

        if (!$response->successful()) {
            Log::error('Meli refresh error', ['status' => $response->status()]);
            return false;
        }

        $this->saveToken($response->json());
        return true;
    }

    protected function saveToken(array $data): void
    {
        $this->config->update([
            'meli_access_token' => $data['access_token'],
            'meli_refresh_token' => $data['refresh_token'] ?? $this->config->meli_refresh_token,
            'meli_user_id' => $data['user_id'] ?? $this->config->meli_user_id,
            'meli_token_expires_at' => now()->addSeconds($data['expires_in'] ?? 21600),
        ]);
        EmpresaConfiguracion::clearCache();
        $this->config = EmpresaConfiguracion::getConfig();
    }

    public function get(string $path, array $query = []): array
    {
        $this->ensureToken();

        try {
            $response = Http::withToken($this->config->meli_access_token)
                ->timeout(15)
                ->get(self::API_URL . $path, $query);

            if ($response->status() === 401) {
                if ($this->refreshToken()) {
                    return $this->get($path, $query);
                }
            }

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error('Meli GET error', ['path' => $path, 'msg' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    public function post(string $path, array $data = []): array
    {
        $this->ensureToken();

        try {
            $response = Http::withToken($this->config->meli_access_token)
                ->timeout(15)
                ->post(self::API_URL . $path, $data);

            if ($response->status() === 401) {
                if ($this->refreshToken()) {
                    return $this->post($path, $data);
                }
            }

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error('Meli POST error', ['path' => $path, 'msg' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    public function put(string $path, array $data = []): array
    {
        $this->ensureToken();

        try {
            $response = Http::withToken($this->config->meli_access_token)
                ->timeout(15)
                ->put(self::API_URL . $path, $data);

            if ($response->status() === 401) {
                if ($this->refreshToken()) {
                    return $this->put($path, $data);
                }
            }

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error('Meli PUT error', ['path' => $path, 'msg' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    protected function ensureToken(): void
    {
        if (!$this->config->meli_access_token) {
            return;
        }

        if ($this->config->meli_token_expires_at && $this->config->meli_token_expires_at->isPast()) {
            $this->refreshToken();
        }
    }

    // ─── Catálogo ────────────────────────────────────────────────────

    public function getUser(): array
    {
        return $this->get('/users/me');
    }

    public function getCategories(string $siteId = 'MLM'): array
    {
        return $this->get("/sites/{$siteId}/categories");
    }

    public function getCategoryAttributes(string $categoryId): array
    {
        return $this->get("/categories/{$categoryId}/attributes");
    }

    // ─── Listings ────────────────────────────────────────────────────

    public function createItem(array $itemData): array
    {
        $result = $this->post('/items', $itemData);

        if (isset($result['id'])) {
            $listingData = [
                'empresa_id' => $this->config->empresa_id,
                'listing_id' => $result['id'],
                'permalink' => $result['permalink'] ?? null,
                'status' => 'active',
                'price' => $itemData['price'] ?? 0,
                'stock_published' => $itemData['available_quantity'] ?? 0,
                'meli_category_id' => $itemData['category_id'] ?? null,
                'title' => $result['title'] ?? $itemData['title'] ?? null,
                'thumbnail' => $result['thumbnail'] ?? null,
            ];

            if (isset($itemData['producto_id'])) {
                $listingData['producto_id'] = $itemData['producto_id'];
            }

            MercadoLibreListing::create($listingData);
        }

        return $result;
    }

    public function updateItem(string $listingId, array $data): array
    {
        return $this->put("/items/{$listingId}", $data);
    }

    public function getItem(string $listingId): array
    {
        return $this->get("/items/{$listingId}");
    }

    // ─── Órdenes ─────────────────────────────────────────────────────

    public function getOrder(string $orderId): array
    {
        return $this->get("/orders/{$orderId}");
    }

    public function getOrders(array $filters = []): array
    {
        return $this->get('/orders/search', $filters);
    }

    public function getShipment(string $shipmentId): array
    {
        return $this->get("/shipments/{$shipmentId}");
    }

    // ─── Notificaciones ──────────────────────────────────────────────

    public function getTopic(string $topic, string $resource): array
    {
        return $this->get("/{$topic}/{$resource}");
    }
}

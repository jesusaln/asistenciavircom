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

    public function getCategoryPriceSuggestions(string $categoryId): array
    {
        return $this->get("/categories/{$categoryId}/prices");
    }

    // ─── Búsqueda y Sugerencia de Precios ─────────────────────────────────

    /**
     * Obtener detalles de un producto de ML para analizar competencia
     */
    public function getItemDetails(string $meliItemId): array
    {
        $item = $this->get("/items/{$meliItemId}");

        if (isset($item['error'])) {
            return ['error' => 'Item no encontrado', 'details' => $item];
        }

        return [
            'id' => $item['id'],
            'title' => $item['title'] ?? '',
            'category_id' => $item['category_id'] ?? '',
            'price' => $item['price'] ?? 0,
            'currency_id' => $item['currency_id'] ?? 'MXN',
            'condition' => $item['condition'] ?? '',
            'listing_type_id' => $item['listing_type_id'] ?? '',
            'sold_quantity' => $item['sold_quantity'] ?? 0,
            'available_quantity' => $item['available_quantity'] ?? 0,
            'thumbnail' => $item['thumbnail'] ?? '',
            'permalink' => $item['permalink'] ?? '',
        ];
    }

    /**
     * Buscar productos similares en ML por categoría y búsqueda
     */
    public function searchSimilarProducts(string $query, string $categoryId = '', int $limit = 20): array
    {
        $params = [
            'q' => $query,
            'limit' => $limit,
            'offset' => 0,
        ];

        if ($categoryId) {
            $params['category'] = $categoryId;
        }

        $results = $this->get('/sites/MLM/search', $params);

        if (isset($results['error'])) {
            return ['error' => 'Error en búsqueda', 'details' => $results];
        }

        $items = $results['results'] ?? [];

        $products = array_map(function ($item) {
            return [
                'id' => $item['id'],
                'title' => mb_substr($item['title'] ?? '', 0, 60),
                'price' => (float) ($item['price'] ?? 0),
                'currency' => $item['currency_id'] ?? 'MXN',
                'condition' => $item['condition'] ?? '',
                'sold_quantity' => $item['sold_quantity'] ?? 0,
                'available_quantity' => $item['available_quantity'] ?? 0,
                'listing_type' => $item['listing_type_id'] ?? '',
                'thumbnail' => $item['thumbnail'] ?? '',
                'permalink' => $item['permalink'] ?? '',
            ];
        }, $items);

        return [
            'paging' => [
                'total' => $results['paging']['total'] ?? 0,
                'limit' => $limit,
            ],
            'products' => $products,
        ];
    }

    /**
     * Analizar precios de la competencia y sugerir un precio
     * Acepta un ID de publicación de ML (MLM...) o una consulta de búsqueda (nombre de producto)
     */
    public function analyzeCompetition(string $meliItemIdOrQuery, int $competitorLimit = 15): array
    {
        // Detectar si es un ID de ML (comienza con ML) o una búsqueda
        $isMlId = preg_match('/^ML[A-Z]\d+$/i', trim($meliItemIdOrQuery));

        if ($isMlId) {
            // Es un ID de publicación de ML - obtener detalles
            $sourceItem = $this->getItemDetails($meliItemIdOrQuery);

            if (isset($sourceItem['error'])) {
                return $sourceItem;
            }

            $query = $sourceItem['title'];
            $categoryId = $sourceItem['category_id'];
        } else {
            // Es una consulta de búsqueda (nombre de producto)
            $query = trim($meliItemIdOrQuery);

            // Buscar en ML para obtener categoría del primer resultado
            $searchResult = $this->searchSimilarProducts($query, '', 5);

            if (isset($searchResult['error']) || empty($searchResult['products'])) {
                return [
                    'error' => 'No se encontraron productos similares para: ' . $query,
                    'query' => $query,
                ];
            }

            $firstResult = $searchResult['products'][0];
            $sourceItem = [
                'id' => $firstResult['id'] ?? '',
                'title' => $firstResult['title'] ?? $query,
                'price' => $firstResult['price'] ?? 0,
            ];
            $categoryId = '';
        }

        $similar = $this->searchSimilarProducts($query, $categoryId, $competitorLimit * 2);

        if (isset($similar['error'])) {
            return $similar;
        }

        $products = $similar['products'];

        if (empty($products)) {
            return [
                'error' => 'No se encontraron productos similares',
                'source' => $sourceItem,
            ];
        }

        $prices = array_column($products, 'price');
        sort($prices);

        $minPrice = min($prices);
        $maxPrice = max($prices);
        $avgPrice = array_sum($prices) / count($prices);
        $medianPrice = $prices[count($prices) / 2] ?? $avgPrice;

        $bottom25 = array_slice($prices, 0, (int)(count($prices) * 0.25));
        $avgBottom25 = count($bottom25) > 0 ? array_sum($bottom25) / count($bottom25) : $minPrice;

        $soldQuantities = array_column($products, 'sold_quantity');
        $totalSold = array_sum($soldQuantities);

        $mostSoldIndex = array_search(max($soldQuantities), $soldQuantities);
        $mostSoldProduct = $products[$mostSoldIndex] ?? null;

        $cheapestIndex = array_search($minPrice, $prices);
        $cheapestProduct = $products[$cheapestIndex] ?? null;

        return [
            'source' => $sourceItem,
            'competitors' => [
                'count' => count($products),
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'avg_price' => round($avgPrice, 2),
                'median_price' => round($medianPrice, 2),
                'avg_bottom_25' => round($avgBottom25, 2),
            ],
            'most_sold' => $mostSoldProduct,
            'cheapest' => $cheapestProduct,
            'sample_products' => array_slice($products, 0, 5),
            'suggestion' => [
                'price' => round($avgBottom25 * 0.95, 2),
                'strategy' => 'competitive',
                'label' => 'Precio competitivo (5% debajo del promedio de los más vendidos)',
                'formula' => 'avg_bottom_25 × 0.95',
            ],
        ];
    }

    /**
     * Sugerir precio para un producto basándose en un ID de ML
     */
    public function suggestPrice(string $meliItemId, ?float $ourCost = null): array
    {
        $analysis = $this->analyzeCompetition($meliItemId);

        if (isset($analysis['error'])) {
            return $analysis;
        }

        $suggestedPrice = $analysis['suggestion']['price'];

        if ($ourCost !== null && $ourCost > 0) {
            $minPrice = $analysis['competitors']['min_price'];
            $maxPrice = $analysis['competitors']['max_price'];

            $breakEven = $ourCost * 1.30;

            if ($breakEven > $maxPrice) {
                return [
                    'error' => 'Tu costo es muy alto para ser competitivo',
                    'suggested_price' => $suggestedPrice,
                    'our_cost' => $ourCost,
                    'break_even_30pct' => $breakEven,
                    'competitor_range' => ['min' => $minPrice, 'max' => $maxPrice],
                    'recommendation' => 'Considera reducir costos o buscar otro producto',
                ];
            }

            $optimalPrice = max($suggestedPrice, $breakEven);

            return [
                'suggested_price' => round($optimalPrice, 2),
                'competitor_avg' => $analysis['competitors']['avg_price'],
                'competitor_min' => $minPrice,
                'competitor_max' => $maxPrice,
                'our_cost' => $ourCost,
                'break_even_30pct' => $breakEven,
                'potential_profit' => round($optimalPrice - $ourCost, 2),
                'profit_margin' => round((($optimalPrice - $ourCost) / $optimalPrice) * 100, 1),
                'source_product' => [
                    'id' => $analysis['source']['id'],
                    'title' => $analysis['source']['title'],
                    'price' => $analysis['source']['price'],
                ],
                'most_sold' => $analysis['most_sold'],
            ];
        }

        return [
            'suggested_price' => $suggestedPrice,
            'competitor_avg' => $analysis['competitors']['avg_price'],
            'competitor_min' => $analysis['competitors']['min_price'],
            'competitor_max' => $analysis['competitors']['max_price'],
            'source_product' => [
                'id' => $analysis['source']['id'],
                'title' => $analysis['source']['title'],
                'price' => $analysis['source']['price'],
            ],
            'most_sold' => $analysis['most_sold'],
            'sample_products' => $analysis['sample_products'],
        ];
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

    /**
     * Obtener precio sugerido de ML para un producto basado en búsqueda
     */
    public function getSuggestedPriceFromML(string $query): ?float
    {
        $analysis = $this->analyzeCompetition($query);

        if (isset($analysis['error']) || !isset($analysis['suggestion']['price'])) {
            return null;
        }

        return (float) $analysis['suggestion']['price'];
    }
}

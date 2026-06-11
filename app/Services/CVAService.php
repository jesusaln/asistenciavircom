<?php

namespace App\Services;

use App\Models\EmpresaConfiguracion;
use App\Models\Producto;
use App\Models\Marca;
use App\Models\Categoria;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CVAService
{
    protected string $baseUrl;
    protected string $shippingUrl;
    protected ?EmpresaConfiguracion $config;

    public function __construct()
    {
        $this->baseUrl = config('services.cva.base_url', 'https://apicvaservices.grupocva.com/api/v2');
        $this->shippingUrl = config('services.cva.shipping_url', 'https://www.grupocva.com/api/paqueteria_service');
        $this->config = EmpresaConfiguracion::getConfig();
    }

    protected function getToken(): ?string
    {
        if (!$this->config || !$this->config->cva_active) {
            return null;
        }

        return Cache::remember('cva_api_token', now()->addHours(11), function () {
            $response = Http::timeout(15)
                ->post("{$this->baseUrl}/user/login", [
                    'user' => $this->config->cva_user,
                    'password' => $this->config->cva_password,
                ]);

            if ($response->successful()) {
                return $response->json('token');
            }

            Log::error('CVA Auth failed', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        });
    }

    protected function authHeaders(): array
    {
        $token = $this->getToken();
        if (!$token) {
            return [];
        }
        return ['Authorization' => "Bearer {$token}"];
    }

    protected function get(string $endpoint, array $query = []): array
    {
        $headers = $this->authHeaders();
        if (empty($headers)) {
            return ['error' => 'CVA no configurado o token no disponible'];
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->get("{$this->baseUrl}/{$endpoint}", $query);

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            if ($response->status() === 403) {
                Cache::forget('cva_api_token');
            }

            Log::warning('CVA GET error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['error' => $response->json('message') ?? 'Error al consultar CVA', 'status' => $response->status()];
        } catch (\Exception $e) {
            Log::error('CVA GET exception', ['endpoint' => $endpoint, 'msg' => $e->getMessage()]);
            return ['error' => 'Error de conexión con CVA: ' . $e->getMessage()];
        }
    }

    protected function post(string $endpoint, array $data = [], bool $withAuth = true): array
    {
        $headers = ['Content-Type' => 'application/json'];
        if ($withAuth) {
            $token = $this->getToken();
            if (!$token) {
                return ['error' => 'CVA no configurado o token no disponible'];
            }
            $headers['Authorization'] = "Bearer {$token}";
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post("{$this->baseUrl}/{$endpoint}", $data);

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            if ($response->status() === 403) {
                Cache::forget('cva_api_token');
            }

            Log::warning('CVA POST error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['error' => $response->json('message') ?? 'Error al enviar datos a CVA', 'status' => $response->status()];
        } catch (\Exception $e) {
            Log::error('CVA POST exception', ['endpoint' => $endpoint, 'msg' => $e->getMessage()]);
            return ['error' => 'Error de conexión con CVA: ' . $e->getMessage()];
        }
    }

    // ─── Catálogo ─────────────────────────────────────────────────────────

    public function getCatalogo(array $filters = []): array
    {
        $query = [];
        $allowedFilters = ['codigo', 'clave', 'marca', 'grupo', 'desc', 'page', 'exist',
            'porcentaje', 'promos', 'sucursales', 'images', 'completos',
            'codigosat', 'subgpo', 'tipo', 'depto', 'dt', 'dc', 'upc', 'pdf',
            'MonedaPesos', 'TipoCompra', 'Solucion', 'grupo2', 'dimen', 'trans',
            'procesador', 'tc'];

        foreach ($allowedFilters as $filter) {
            if (isset($filters[$filter])) {
                $query[$filter] = $filters[$filter];
            }
        }

        if (!isset($query['page'])) {
            $query['page'] = 1;
        }

        return $this->get('catalogo_clientes/lista_precios', $query);
    }

    public function getPreciosStockOfertas(array $filters = []): array
    {
        $query = [];
        $allowedFilters = ['clave', 'codigo', 'batch', 'MonedaPesos', 'TipoCompra', 'porcentaje', 'page'];

        foreach ($allowedFilters as $filter) {
            if (isset($filters[$filter])) {
                $query[$filter] = $filters[$filter];
            }
        }

        return $this->get('catalogo_clientes/precios_stock_ofertas', $query);
    }

    protected function resolveUtilityPercentage(float $precioCompra): float
    {
        $tiers = $this->config->cva_utility_tiers ?? null;

        if (!empty($tiers) && is_array($tiers)) {
            $tiers = collect($tiers)->sortBy('min')->values()->all();

            foreach ($tiers as $tier) {
                $min = (float) ($tier['min'] ?? 0);
                $pct = (float) ($tier['percentage'] ?? $tier['percent'] ?? 0);
                $max = array_key_exists('max', $tier) && $tier['max'] !== null
                    ? (float) $tier['max']
                    : INF;

                if ($precioCompra >= $min && $precioCompra < $max) {
                    return $pct;
                }
            }
        }

        return (float) ($this->config->cva_utility_percentage ?? 15);
    }

    // ─── Normalización ────────────────────────────────────────────────────

    public function normalizeProduct(array $item): array
    {
        $tipoCambio = (float) ($this->config->cva_tipo_cambio ?? 20.50);
        $tcBuffer = (float) ($this->config->cva_tipo_cambio_buffer ?? 2.00);
        $tcEfectivo = $tipoCambio * (1 + $tcBuffer / 100);

        $moneda = $item['moneda'] ?? 'Pesos';
        $precioBase = (float) ($item['precio'] ?? 0);
        $precioCompra = $precioBase;

        if (str_contains(strtolower($moneda), 'dolar')) {
            $precioCompra = round($precioBase * $tcEfectivo, 2);
        }

        $utilityPct = $this->resolveUtilityPercentage($precioCompra);
        $precioVenta = round($precioCompra * (1 + $utilityPct / 100), 2);
        $precioConIva = round($precioVenta * 1.16, 2);

        $stockLocal = (int) ($item['disponible'] ?? 0);
        $stockCedis = (int) ($item['disponibleCD'] ?? 0);

        if (isset($item['disponibilidad_sucursales']) && is_array($item['disponibilidad_sucursales'])) {
            foreach ($item['disponibilidad_sucursales'] as $suc) {
                if (isset($suc['nombre']) && str_contains(strtoupper($suc['nombre']), 'HERMOSILLO')) {
                    $stockLocal = (int) ($suc['disponible'] ?? 0);
                    break;
                }
            }
        }

        $nombre = $item['descripcion'] ?? $item['codigo_fabricante'] ?? 'SIN NOMBRE';
        $descripcion = $item['descripcion'] ?? 'Sin descripcion disponible';
        $imagenUrl = $item['imagen'] ?? null;
        if (!empty($item['imagenes']) && is_array($item['imagenes'])) {
            $imagenUrl = $item['imagenes'][0] ?? $imagenUrl;
        }

        return [
            'id' => $item['id'] ?? null,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'clave' => $item['clave'] ?? '',
            'codigo_fabricante' => $item['codigo_fabricante'] ?? '',
            'precio_compra' => $precioCompra,
            'precio' => $precioVenta,
            'precio_con_iva' => $precioConIva,
            'moneda' => 'Pesos',
            'imagen_url' => $imagenUrl,
            'imagenes' => $item['imagenes'] ?? null,
            'categoria' => $item['grupo'] ?? $item['principal'] ?? 'OTRO',
            'marca' => $item['marca'] ?? 'GENERICO',
            'garantia' => $item['garantia'] ?? 'SG',
            'stock' => $stockLocal + $stockCedis,
            'stock_local' => $stockLocal,
            'stock_cedis' => $stockCedis,
            'disponible' => $stockLocal + $stockCedis,
            'en_transito' => (int) ($item['en_transito'] ?? 0),
            'stock_desglose' => $item['disponibilidad_sucursales'] ?? [],
            'ficha_tecnica' => $item['ficha_tecnica'] ?? null,
            'ficha_comercial' => $item['ficha_comercial'] ?? null,
            'promociones' => $item['promociones'] ?? null,
            'inventario' => $item['inventario'] ?? [],
            'sat_info' => $item['sat_info'] ?? null,
            'producto_paquete' => $item['producto_paquete'] ?? null,
            'disponible_suc' => $stockLocal,
            'disponible_cd' => $stockCedis,
        ];
    }

    // ─── Detalle de producto ──────────────────────────────────────────────

    public function getProductDetails(string $clave, bool $full = false): ?array
    {
        $result = $this->get('catalogo_clientes/lista_precios', ['clave' => $clave]);

        if (isset($result['error'])) {
            return null;
        }

        if (isset($result['articulos']) && is_array($result['articulos'])) {
            if (count($result['articulos']) === 0) {
                return null;
            }
            $item = $result['articulos'][0];
        } else {
            $item = $result;
        }

        return $this->normalizeProduct($item);
    }

    public function getTechnicalSpecs(string $clave): ?array
    {
        $result = $this->get('catalogo_clientes/informacion_tecnica', ['clave' => $clave]);

        if (isset($result['error'])) {
            return null;
        }

        return $result['especificaciones'] ?? [];
    }

    public function getHighResImages(string $clave): array
    {
        $result = $this->get('catalogo_clientes/imagenes_alta', ['clave' => $clave]);

        if (isset($result['error'])) {
            return [];
        }

        return $result['imagenes'] ?? [];
    }

    // ─── Importación ──────────────────────────────────────────────────────

    public function importProduct(string $clave): array
    {
        $producto = $this->getOrCreateLocalProduct($clave);

        if (!$producto) {
            return ['error' => 'No se pudo importar el producto'];
        }

        return [
            'success' => true,
            'producto_id' => $producto->id,
            'producto' => $producto,
        ];
    }

    public function getOrCreateLocalProduct(string $cvaClave): ?Producto
    {
        $producto = Producto::where('cva_clave', $cvaClave)->first();
        if ($producto) {
            return $producto;
        }

        $detalle = $this->getProductDetails($cvaClave, true);
        if (!$detalle || empty($detalle['clave'])) {
            return null;
        }

        $empresaId = $this->config->empresa_id ?? 1;

        $marcaNombre = strtoupper(trim($detalle['marca'] ?? 'GENERICO'));
        $marca = Marca::firstOrCreate(
            ['nombre' => $marcaNombre, 'empresa_id' => $empresaId],
            ['estado' => 'activo']
        );

        $catNombre = strtoupper(trim($detalle['categoria'] ?? 'OTRO'));
        $categoria = Categoria::firstOrCreate(
            ['nombre' => $catNombre, 'empresa_id' => $empresaId],
            ['estado' => 'activo']
        );

        try {
            $producto = Producto::create([
                'empresa_id' => $empresaId,
                'nombre' => $detalle['nombre'],
                'descripcion' => $detalle['descripcion'],
                'codigo' => $detalle['clave'],
                'codigo_barras' => $detalle['clave'],
                'marca_id' => $marca->id,
                'categoria_id' => $categoria->id,
                'precio_compra' => $detalle['precio_compra'],
                'precio_venta' => $detalle['precio'],
                'stock' => $detalle['stock_local'],
                'stock_cedis' => $detalle['stock_cedis'],
                'imagen' => $detalle['imagen_url'],
                'origen' => 'CVA',
                'cva_clave' => $detalle['clave'],
                'cva_last_sync' => now(),
                'estado' => 'activo',
                'unidad_medida' => 'Pieza',
                'incluye_iva' => true,
                'sat_clave_prod_serv' => '43211500',
                'sat_clave_unidad' => 'H87',
                'sat_objeto_imp' => '02',
            ]);

            return $producto;
        } catch (\Exception $e) {
            Log::error('Error creating local product from CVA', [
                'clave' => $cvaClave,
                'msg' => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ─── Catálogos (Marcas, Categorías) ───────────────────────────────────

    public function syncCategories(): array
    {
        $result = $this->get('catalogo_clientes/grupos');

        if (isset($result['error'])) {
            return $result;
        }

        $grupos = $result['grupos'] ?? [];
        $count = 0;
        $empresaId = $this->config->empresa_id ?? 1;

        foreach ($grupos as $grupo) {
            $nombre = strtoupper(trim($grupo['grupo'] ?? ''));
            if (empty($nombre)) continue;

            try {
                Categoria::firstOrCreate(
                    ['nombre' => $nombre, 'empresa_id' => $empresaId],
                    ['estado' => 'activo']
                );
                $count++;
            } catch (\Exception $e) {
                Log::error('Error syncing CVA category', ['nombre' => $nombre, 'msg' => $e->getMessage()]);
            }
        }

        return ['success' => true, 'count' => $count];
    }

    public function syncBrands(): array
    {
        $result = $this->get('catalogo_clientes/marcas');

        if (isset($result['error'])) {
            return $result;
        }

        $marcas = $result['marcas'] ?? [];
        $count = 0;
        $empresaId = $this->config->empresa_id ?? 1;

        foreach ($marcas as $m) {
            $nombre = strtoupper(trim($m['marca'] ?? ''));
            if (empty($nombre)) continue;

            try {
                Marca::firstOrCreate(
                    ['nombre' => $nombre, 'empresa_id' => $empresaId],
                    ['estado' => 'activo']
                );
                $count++;
            } catch (\Exception $e) {
                Log::error('Error syncing CVA brand', ['nombre' => $nombre, 'msg' => $e->getMessage()]);
            }
        }

        return ['success' => true, 'count' => $count];
    }

    // ─── Envío ────────────────────────────────────────────────────────────

    public function getSucursales(): array
    {
        $result = $this->get('catalogo_clientes/sucursales');

        if (isset($result['error'])) {
            return [];
        }

        return $result['sucursales'] ?? [];
    }

    public function getPaqueterias(): array
    {
        $result = $this->get('catalogo_clientes/paqueteria');

        if (isset($result['error'])) {
            return [];
        }

        return $result['paqueterias'] ?? [];
    }

    public function calculateShippingCost(string $cp, array $items): array
    {
        $codigoSucursal = $this->config->cva_codigo_sucursal ?? 1;
        $sucursales = $this->getSucursales();
        $cpSucursal = '44900';
        foreach ($sucursales as $s) {
            if (($s['clave'] ?? '') == $codigoSucursal) {
                $cpSucursal = $s['cp'] ?? '44900';
                break;
            }
        }

        $payload = [
            'paqueteria' => $this->config->cva_paqueteria_envio ?? 4,
            'cp' => $cp,
            'cp_sucursal' => $cpSucursal,
            'productos' => $items,
        ];

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->timeout(15)
                ->post($this->shippingUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['result'] ?? '') === 'success') {
                    return [
                        'success' => true,
                        'costo' => $data['cotizacion']['montoTotal'] ?? 0,
                        'cajas' => $data['cotizacion']['cajas'] ?? 0,
                        'subtotal' => $data['cotizacion']['subtotal'] ?? 0,
                        'iva' => $data['cotizacion']['iva'] ?? 0,
                        'cotizacion' => $data['cotizacion'] ?? [],
                    ];
                }
                return ['error' => 'No se pudo cotizar el flete'];
            }

            return ['error' => 'Error al cotizar flete', 'status' => $response->status()];
        } catch (\Exception $e) {
            Log::error('CVA shipping quote error', ['msg' => $e->getMessage()]);
            return ['error' => 'Error de conexión al cotizar flete'];
        }
    }

    // ─── Pedidos ──────────────────────────────────────────────────────────

    public function createOrder(array $orderData): array
    {
        $payload = array_merge([
            'test' => 0,
            'codigo_sucursal' => $this->config->cva_codigo_sucursal ?? 1,
        ], $orderData);

        $result = $this->post('pedidos_web/crear_orden', $payload);

        if (isset($result['error'])) {
            return [
                'success' => false,
                'error' => $result['error'],
                'details' => $result,
            ];
        }

        return [
            'success' => true,
            'data' => [
                'pedido' => $result['pedido'] ?? null,
                'subtotal' => $result['subtotal'] ?? 0,
                'iva' => $result['iva'] ?? 0,
                'total' => $result['total'] ?? 0,
                'moneda' => $result['moneda'] ?? 'MXN',
                'email_agente' => $result['email_agente'] ?? null,
                'email_almacen' => $result['email_almacen'] ?? null,
                'flete' => $result['flete'] ?? null,
            ],
        ];
    }

    public function listOrders(): array
    {
        $result = $this->get('pedidos_web/lista_pedidos');

        if (isset($result['error'])) {
            return [];
        }

        return $result['pedidos'] ?? [];
    }

    public function getOrderDetails(string $reference): ?array
    {
        $result = $this->post('pedidos_web/consultar_pedido', ['pedido' => $reference]);

        if (isset($result['error'])) {
            return null;
        }

        return $result['pedido'] ?? null;
    }

    // ─── Tipo de Cambio ───────────────────────────────────────────────────

    public function updateExchangeRate(): ?float
    {
        $result = $this->get('catalogo_clientes/lista_precios', [
            'clave' => 'KB-890',
            'tc' => 'true',
            'limit' => 1,
        ]);

        if (isset($result['error'])) {
            return null;
        }

        $tc = $result['tipo_cambio'] ?? null;

        if ($tc && is_numeric($tc)) {
            $tc = (float) $tc;

            if ($this->config) {
                $this->config->update([
                    'cva_tipo_cambio' => $tc,
                    'cva_tipo_cambio_last_update' => now(),
                ]);
                EmpresaConfiguracion::clearCache();
            }

            return $tc;
        }

        Log::warning('CVA: No se pudo obtener tipo de cambio', ['result' => $result]);
        return null;
    }

    // ─── Búsqueda de productos ────────────────────────────────────────────

    public function getProductosCatalogo(): \Illuminate\Support\Collection
    {
        $result = $this->get('catalogo_clientes/lista_precios', ['page' => 1]);

        if (isset($result['error'])) {
            return collect();
        }

        $items = $result['articulos'] ?? [];
        return collect($items)->map(fn($item) => $this->normalizeProduct($item));
    }

    public function buscarProductos(string $query): \Illuminate\Support\Collection
    {
        $result = $this->get('catalogo_clientes/lista_precios', ['desc' => $query]);

        if (isset($result['error'])) {
            return collect();
        }

        $items = $result['articulos'] ?? [];
        return collect($items)->map(fn($item) => $this->normalizeProduct($item));
    }
}

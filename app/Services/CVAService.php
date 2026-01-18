<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\EmpresaConfiguracion;

class CVAService
{
    protected $baseUrl = 'https://apicvaservices.grupocva.com/api/v2';
    protected $config;

    public function __construct()
    {
        $this->config = EmpresaConfiguracion::getConfig();
    }

    /**
     * Obtener el token de autenticación (desde caché o API)
     */
    public function getToken()
    {
        if (!$this->config->cva_active || !$this->config->cva_user || !$this->config->cva_password) {
            return null;
        }

        $cacheKey = 'cva_api_token_' . $this->config->empresa_id;

        return Cache::remember($cacheKey, now()->addHours(11), function () {
            try {
                $response = Http::post("{$this->baseUrl}/user/login", [
                    'user' => $this->config->cva_user,
                    'password' => $this->config->cva_password,
                ]);

                if ($response->successful()) {
                    return $response->json('token');
                }

                Log::error('CVA API Login Failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('CVA API Exception (Login)', ['message' => $e->getMessage()]);
                return null;
            }
        });
    }

    /**
     * Consultar catálogo de productos con Cache
     */
    public function getCatalogo(array $filters = [])
    {
        $token = $this->getToken();
        if (!$token)
            return ['error' => 'API CVA no configurada o inaccesible'];

        // Crear una llave de cache basada en los filtros (2 horas para velocidad)
        $cacheKey = 'cva_cat_' . md5(serialize($filters) . $this->config->empresa_id);

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($token, $filters) {
            // Parámetros optimizados para el listado (sin descripciones pesadas)
            $sucursal = $this->config->cva_codigo_sucursal ?: 9; // Default: Hermosillo

            // Si el filtro es por texto (search), relajamos restricciones para encontrar más
            $isSearch = isset($filters['desc']) && strlen($filters['desc']) > 0;

            $params = array_merge([
                'page' => $filters['page'] ?? $filters['pagina'] ?? 1,
                'images' => 1,
                'completos' => 1,
                'MonedaPesos' => 1,
                'exist' => 0,
                'promos' => 1,
                'sucursales' => 1,
                'dimen' => 1,
                'upc' => 1,
                'codigosat' => 1,
                'pdf' => 1,
                'TipoCompra' => (bool) ($this->config->cva_tipo_compra_me ?? true),
                'porcentaje' => (int) $this->config->cva_utility_percentage ?: 15,
            ], $filters);

            try {
                $response = Http::withToken($token)
                    ->get("{$this->baseUrl}/catalogo_clientes/lista_precios", $params);

                if ($response->successful()) {
                    $data = $response->json();

                    // Si estamos buscando, vamos a aplicar un ordenamiento de RELEVANCIA local
                    if ($isSearch && isset($data['articulos']) && is_array($data['articulos'])) {
                        $term = strtolower($filters['desc']);

                        usort($data['articulos'], function ($a, $b) use ($term) {
                            $descA = strtolower($a['descripcion'] ?? '');
                            $descB = strtolower($b['descripcion'] ?? '');
                            $grupoA = strtolower($a['grupo'] ?? '');
                            $grupoB = strtolower($b['grupo'] ?? '');

                            // 1. Prioridad: Coincidencia exacta con el GRUPO (ej: busco 'mouse' y el grupo es 'MOUSE')
                            $matchGroupA = ($grupoA === $term || $grupoA === $term . 's');
                            $matchGroupB = ($grupoB === $term || $grupoB === $term . 's');
                            if ($matchGroupA && !$matchGroupB)
                                return -1;
                            if (!$matchGroupA && $matchGroupB)
                                return 1;

                            // 2. Prioridad: Empieza con la palabra (ej: 'Mouse Gamer' vs 'Kit Teclado y Mouse')
                            $startsA = str_starts_with($descA, $term);
                            $startsB = str_starts_with($descB, $term);
                            if ($startsA && !$startsB)
                                return -1;
                            if (!$startsA && $startsB)
                                return 1;

                            // 3. Prioridad: Stock disponible (Cualquier almacén)
                            $stockA = (int) ($a['disponible'] ?? 0) + (int) ($a['disponibleCD'] ?? 0);
                            $stockB = (int) ($b['disponible'] ?? 0) + (int) ($b['disponibleCD'] ?? 0);
                            if ($stockA > 0 && $stockB <= 0)
                                return -1;
                            if ($stockA <= 0 && $stockB > 0)
                                return 1;

                            return 0;
                        });
                    }

                    return $data;
                }

                return ['error' => 'Error al consultar productos CVA', 'status' => $response->status()];
            } catch (\Exception $e) {
                Log::error('CVA API Exception (Catalogo)', ['message' => $e->getMessage()]);
                return ['error' => 'Excepción de comunicación con CVA'];
            }
        });
    }

    /**
     * Consultar stock y precio de un producto individual (Tiempo Real)
     */
    public function getProductDetails($clave, $normalize = false)
    {
        $token = $this->getToken();
        if (!$token)
            return null;

        // Para detalles sí pedimos descripciones técnicas y comerciales
        try {
            $params = [
                'clave' => $clave,
                'MonedaPesos' => 1,
                'dt' => 1,
                'dc' => 1,
                'images' => 1,
                'promos' => 1,
                'trans' => 1,
                'dimen' => 1,
                'upc' => 1,
                'codigosat' => 1,
                'pdf' => 1,
                'sucursales' => 1,
                'TipoCompra' => (bool) ($this->config->cva_tipo_compra_me ?? true),
                'porcentaje' => (int) $this->config->cva_utility_percentage ?: 15,
            ];

            $response = Http::withToken($token)
                ->get("{$this->baseUrl}/catalogo_clientes/lista_precios", $params);

            if ($response->successful()) {
                $data = $response->json();
                $item = null;

                // Extraer el producto de cualquier estructura posible
                if (isset($data['clave'])) {
                    $item = $data;
                } elseif (isset($data['articulos']) && is_array($data['articulos']) && !empty($data['articulos'])) {
                    $item = $data['articulos'][0];
                } elseif (is_array($data) && !empty($data) && isset($data[0]['clave'])) {
                    $item = $data[0];
                }

                // Fallback: Si no se encontró por clave, intentar por código de parte (Numero de parte)
                if (!$item || (isset($item['descripcion']) && empty($item['descripcion']))) {
                    $responseAlt = Http::withToken($token)
                        ->get("{$this->baseUrl}/catalogo_clientes/lista_precios", array_merge($params, ['codigo' => $clave, 'clave' => null]));
                    if ($responseAlt->successful()) {
                        $dataAlt = $responseAlt->json();
                        if (isset($dataAlt['articulos'][0]))
                            $item = $dataAlt['articulos'][0];
                        elseif (isset($dataAlt[0]['clave']))
                            $item = $dataAlt[0];
                    }
                }

                if ($item) {
                    // Intentar obtener imágenes de alta resolución
                    if (empty($item['imagenes'])) {
                        $item['imagenes'] = $this->getHighResImages($item['clave'] ?? $clave);
                    }

                    if ($normalize) {
                        return $this->normalizeProduct($item);
                    }
                }

                return $item;
            }
            return null;
        } catch (\Exception $e) {
            Log::error('CVA API Exception (Details)', ['message' => $e->getMessage(), 'clave' => $clave]);
            return null;
        }
    }

    /**
     * Versión interna de getProductDetails para recursividad/fallbacks
     */
    protected function getProductDetailsInternal($clave, $normalize, $extraParams)
    {
        $token = $this->getToken();
        if (!$token)
            return null;

        $params = array_merge([
            'clave' => $clave,
            'MonedaPesos' => true,
            'dt' => true,
            'dc' => true,
            'images' => true,
            'porcentaje' => (int) $this->config->cva_utility_percentage ?: 15,
        ], $extraParams);

        // Si estamos buscando por codigo, quitamos la clave
        if (isset($params['codigo']))
            unset($params['clave']);

        try {
            $response = Http::withToken($token)->get("{$this->baseUrl}/catalogo_clientes/lista_precios", $params);
            if ($response->successful()) {
                $data = $response->json();
                $item = null;
                if (isset($data['clave']))
                    $item = $data;
                elseif (isset($data['articulos'][0]))
                    $item = $data['articulos'][0];
                elseif (isset($data[0]['clave']))
                    $item = $data[0];

                if ($item && $normalize)
                    return $this->normalizeProduct($item);
                return $item;
            }
        } catch (\Exception $e) {
            Log::error('CVA API Exception (Details Internal)', ['message' => $e->getMessage(), 'clave' => $clave, 'params' => $params]);
        }
        return null;
    }

    /**
     * FASE 1: Obtener imágenes de alta resolución
     * Endpoint: /catalogo_clientes_xml/imagenes_alta.xml
     */
    public function getHighResImages($clave)
    {
        $cacheKey = 'cva_img_hd_v2_' . $clave;

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($clave) {
            $token = $this->getToken();
            if (!$token)
                return [];

            try {
                $response = Http::withToken($token)
                    ->get("{$this->baseUrl}/catalogo_clientes/imagenes_alta", [
                        'clave' => $clave
                    ]);

                if ($response->successful()) {
                    return $response->json('imagenes') ?: [];
                }
                return [];
            } catch (\Exception $e) {
                Log::error('CVA High Res Images Error (V2)', ['clave' => $clave, 'error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * FASE 2: Obtener productos compatibles/similares
     * Endpoint: /catalogo_clientes_xml/productos_compatibles.xml
     */
    public function getCompatibleProducts($clave)
    {
        $cacheKey = 'cva_compat_' . $clave;

        return Cache::remember($cacheKey, now()->addHours(2), function () use ($clave) {
            try {
                $response = Http::get("https://www.grupocva.com/catalogo_clientes_xml/productos_compatibles.xml", [
                    'clave' => $clave
                ]);

                if ($response->successful()) {
                    $xmlString = $response->body();
                    $xml = simplexml_load_string($xmlString);

                    if ($xml && isset($xml->producto)) {
                        $products = [];
                        foreach ($xml->producto as $prod) {
                            $products[] = [
                                'clave' => (string) ($prod->clave ?? ''),
                                'descripcion' => (string) ($prod->descripcion ?? '')
                            ];
                        }
                        return $products;
                    }
                }
                return [];
            } catch (\Exception $e) {
                Log::error('CVA Compatible Products Error', ['clave' => $clave, 'error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * FASE 3: Obtener información técnica desglosada
     * Endpoint: /catalogo_clientes_xml/informacion_tecnica.xml
     */
    public function getTechnicalSpecs($clave)
    {
        $cacheKey = 'cva_specs_v2_' . $clave;

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($clave) {
            $token = $this->getToken();
            if (!$token)
                return [];

            try {
                $response = Http::withToken($token)
                    ->get("{$this->baseUrl}/catalogo_clientes/informacion_tecnica", [
                        'clave' => $clave
                    ]);

                if ($response->successful()) {
                    $specs = $response->json('especificaciones') ?: [];
                    $formatted = [];
                    foreach ($specs as $spec) {
                        // CVA API V2 regresa un array de objetos { "nombre": "...", "valor": "..." }
                        if (isset($spec['nombre'])) {
                            $formatted[$spec['nombre']] = $spec['valor'] ?? 'N/A';
                        }
                    }
                    return $formatted;
                }
                return [];
            } catch (\Exception $e) {
                Log::error('CVA Technical Specs Error (V2)', ['clave' => $clave, 'error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * FASE 5: Parsear disponibilidad por sucursal
     */
    public function parseBranchAvailability($item)
    {
        $branches = [];

        // Formato nuevo de la API: disponibilidad_sucursales es un array
        if (isset($item['disponibilidad_sucursales']) && is_array($item['disponibilidad_sucursales'])) {
            foreach ($item['disponibilidad_sucursales'] as $branch) {
                $nombre = $branch['nombre'] ?? '';
                $disponible = (int) ($branch['disponible'] ?? 0);

                if ($disponible > 0 && $nombre !== 'TOTAL') {
                    // Limpiar nombre de sucursal
                    $nombre = str_replace(['VENTAS ', 'CENTRO DE DISTRIBUCION '], '', $nombre);
                    $branches[$nombre] = $disponible;
                }
            }
        }

        // Formato antiguo: campos con prefijo VENTAS_
        foreach ($item as $key => $value) {
            if (str_starts_with($key, 'VENTAS_') && (int) $value > 0) {
                $branchName = str_replace('VENTAS_', '', $key);
                $branchName = str_replace('_', ' ', $branchName);
                $branchName = ucwords(strtolower($branchName));
                $branches[$branchName] = (int) $value;
            }
        }

        // Si HERMOSILLO no está y hay stock en 'disponible', agregarlo (ya que CVA lo toma como local)
        if (!isset($branches['HERMOSILLO']) && isset($item['disponible']) && (int) $item['disponible'] > 0) {
            $branches['HERMOSILLO'] = (int) $item['disponible'];
        }

        // Ordenar por cantidad descendente
        arsort($branches);

        // Asegurar que HERMOSILLO esté al principio si existe
        if (isset($branches['HERMOSILLO'])) {
            $hermosilloQty = $branches['HERMOSILLO'];
            unset($branches['HERMOSILLO']);
            $branches = ['HERMOSILLO' => $hermosilloQty] + $branches;
        }

        return $branches;
    }

    /**
     * Obtener stock de Hermosillo específicamente
     */
    public function getHermosilloStock($item): int
    {
        // Primero intentar desde disponibilidad_sucursales
        if (isset($item['disponibilidad_sucursales']) && is_array($item['disponibilidad_sucursales'])) {
            foreach ($item['disponibilidad_sucursales'] as $branch) {
                $nombre = strtoupper($branch['nombre'] ?? '');
                if (str_contains($nombre, 'HERMOSILLO')) {
                    return (int) ($branch['disponible'] ?? 0);
                }
            }
        }

        // Si el usuario está registrado en Hermosillo, disponible ya tiene el stock local
        return (int) ($item['disponible'] ?? 0);
    }

    /**
     * Obtener porcentaje de utilidad escalonado según precio base
     * Precios bajos = mayor margen, Precios altos = menor margen
     * Valores configurables desde EmpresaConfiguracion o defaults realistas
     */
    protected function getTieredUtilityPercentage(float $precio): float
    {
        // 1. Intentar leer configuración personalizada de tiers desde la DB
        $tiers = $this->config->cva_utility_tiers ?? null;

        if ($tiers && is_array($tiers) && count($tiers) > 0) {
            // Ordenar tiers por el campo 'max' para asegurar que el loop funcione
            usort($tiers, fn($a, $b) => $a['max'] <=> $b['max']);

            foreach ($tiers as $tier) {
                if ($precio <= $tier['max']) {
                    return (float) $tier['percent'] / 100;
                }
            }
        }

        // 2. Si no hay tiers, usar el porcentaje fijo configurado (default 15%)
        if (!empty($this->config->cva_utility_percentage) && (float) $this->config->cva_utility_percentage > 0) {
            return (float) $this->config->cva_utility_percentage / 100;
        }

        // 3. Fallback Industrial (Tiers MÁS AGRESIVOS para PyME con Servicio)
        if ($precio <= 500)
            return 0.50; // Accesorios, cables
        if ($precio <= 1500)
            return 0.35; // Periféricos
        if ($precio <= 4000)
            return 0.25; // Monitores, discos
        if ($precio <= 8000)
            return 0.20; // Componentes PC
        if ($precio <= 15000)
            return 0.15; // Laptops Home/Office
        if ($precio <= 30000)
            return 0.12; // Laptops Gamer/Pro
        if ($precio <= 60000)
            return 0.10; // Servidores/Workstations

        return 0.08; // Proyectos muy grandes (+ $0k)
    }

    /**
     * Normalizar producto CVA al formato estándar de la tienda local
     */
    public function normalizeProduct($item)
    {
        $precioBase = (float) ($item['precio'] ?? 0);

        // CVA puede regresar el descuento en un nodo 'promociones' o 'promocion'
        $promocion = $item['promociones'] ?? $item['promocion'] ?? null;
        if ($promocion && isset($promocion['precio_descuento'])) {
            $precioBase = (float) $promocion['precio_descuento'];
        } elseif ($promocion && isset($promocion['precio'])) {
            $precioBase = (float) $promocion['precio'];
        }

        // Porcentaje de utilidad escalonado según precio
        $utility = $this->getTieredUtilityPercentage($precioBase);
        $precioVenta = $precioBase * (1 + $utility);

        // Detectar URL de imagen en múltiples campos posibles
        $imagenUrl = null;

        // Priorizar imágenes de alta resolución (V2) si existen
        if (!empty($item['imagenes']) && is_array($item['imagenes']) && !empty($item['imagenes'][0])) {
            $imagenUrl = trim($item['imagenes'][0]);
        } elseif (!empty($item['imagen'])) {
            $imagenUrl = trim($item['imagen']);
        } elseif (!empty($item['imagen_fabricante'])) {
            $imagenUrl = trim($item['imagen_fabricante']);
        } elseif (!empty($item['url_imagen_local'])) {
            $imagenUrl = trim($item['url_imagen_local']);
        }

        return [
            'id' => 'CVA-' . ($item['clave'] ?? $item['id'] ?? 'unknown'),
            'clave' => $item['clave'] ?? null,
            'upc' => $item['upc'] ?? null,
            'codigo_fabricante' => $item['codigo_fabricante'] ?? $item['codigo'] ?? null,
            'nombre' => $item['descripcion'] ?? $item['desc'] ?? $item['nombre'] ?? 'Sin nombre',
            'descripcion' => $item['descripcion'] ?? $item['desc'] ?? $item['ficha_comercial'] ?? '',
            'marca' => $item['marca'] ?? 'Genérico',
            'categoria' => $item['grupo'] ?? $item['principal'] ?? 'Tecnología',
            'precio' => $precioVenta,
            'precio_compra' => $precioBase,
            'precio_con_iva' => round($precioVenta * 1.16, 2),
            'imagen_url' => $imagenUrl,
            'disponible' => $this->getHermosilloStock($item),
            'stock_local' => $this->getHermosilloStock($item),
            'disponibleCD' => (int) ($item['disponibleCD'] ?? 0),
            'stock_cedis' => (int) ($item['disponibleCD'] ?? 0),
            'stock' => (int) ($item['disponible'] ?? 0) + (int) ($item['disponibleCD'] ?? 0),
            'origen' => 'CVA',
            'moneda' => $item['moneda'] ?? 'Pesos',
            'garantia' => $item['garantia'] ?? null,
            'imagenes' => is_array($item['imagenes'] ?? null) ? $item['imagenes'] : (!empty($imagenUrl) ? [$imagenUrl] : []),
            'ficha_tecnica' => $item['ficha_tecnica'] ?? null,
            'ficha_tecnica_pdf' => $item['ficha_tecnica_pdf'] ?? null,
            'ficha_comercial' => $item['ficha_comercial'] ?? null,
            'promocion' => $promocion,
            'codigo_sat' => $item['sat_info']['clave'] ?? null,
            'sat_descripcion' => $item['sat_info']['descripcion'] ?? null,
            'peso' => isset($item['peso']) ? (float) $item['peso'] : (isset($item['dimensiones']['peso']) ? (float) $item['dimensiones']['peso'] : 0),
            'dimensiones' => $item['dimensiones'] ?? null,
            'en_transito' => (int) ($item['en_transito'] ?? 0),
            'stock_desglose' => $this->parseBranchAvailability($item),
        ];
    }

    /**
     * Calcular costo de envío estimado (Calculadora Fase 2.2)
     * Utiliza el peso de los productos y una tarifa base configurable o estándar.
     */
    public function calculateShippingCost($cp, $items)
    {
        // 1. Validar CP (Básico)
        if (strlen($cp) !== 5) {
            return ['error' => 'Código Postal inválido'];
        }

        // TARIFA LOGÍSTICA PROPIA (Envío Local)
        // Usar prefijo configurado o fallback a '83' (Sonora)
        $localPrefix = $this->config->shipping_local_cp_prefix ?? '83';

        $isLocal = str_starts_with($cp, $localPrefix);

        if ($isLocal) {
            $costoLocal = (float) ($this->config->shipping_local_cost ?? 100);
            return [
                'success' => true,
                'cp' => $cp,
                'costo' => $costoLocal,
                'moneda' => 'MXN',
                'tipo_entrega' => 'Local',
                'proveedor' => 'Logística Vircom',
                'tiempo_entrega' => '24 a 48 horas'
            ];
        }

        // 2. Intentar cotización real con CVA API (Paquetexpress)
        try {
            $productosParaCotizar = array_map(function ($item) {
                return [
                    'clave' => str_replace('CVA-', '', $item['id'] ?? $item['clave'] ?? 'unknown'),
                    'cantidad' => $item['cantidad'] ?? 1
                ];
            }, $items);

            $response = Http::post('https://www.grupocva.com/api/paqueteria_service/', [
                'paqueteria' => $this->config->cva_paqueteria_envio ?: 4,
                'cp' => $cp,
                'cp_sucursal' => 44900, // Guadalajara CD (Mucho stock ahí)
                'productos' => $productosParaCotizar
            ]);

            if ($response->successful() && $response->json('result') === 'success') {
                $cotizacion = $response->json('cotizacion');
                return [
                    'success' => true,
                    'cp' => $cp,
                    'costo' => round($cotizacion['montoTotal'], 2),
                    'moneda' => 'MXN',
                    'tipo_entrega' => 'Paquetería nacional',
                    'proveedor' => 'Paquetexpress (CVA API)',
                    'tiempo_entrega' => '3 a 5 días hábiles'
                ];
            }
        } catch (\Exception $e) {
            Log::error('CVA Shipping API Error', ['msg' => $e->getMessage()]);
        }

        // 3. Fallback: Cálculo manual basado en peso si la API falla o no es local
        $totalWeight = 0;
        foreach ($items as $item) {
            $qty = $item['cantidad'] ?? 1;
            // Si el peso es 0 o null, asumimos 2kg por seguridad logística
            $peso = (float) ($item['peso'] ?? 2);
            if ($peso <= 0)
                $peso = 2;
            $totalWeight += $peso * $qty;
        }

        // Tarifa plana realista para México (Estafeta/Paquetexpress a través de CVA)
        // $220 base (hasta 5kg) + $35 por kg extra + IVA
        $baseCost = 220;
        $extraCostPerKg = 35;
        $shippingCost = $baseCost + (max(0, $totalWeight - 5) * $extraCostPerKg);
        $shippingCostWithIva = round($shippingCost * 1.16, 2);

        return [
            'success' => true,
            'cp' => $cp,
            'costo' => $shippingCostWithIva,
            'moneda' => 'MXN',
            'tipo_entrega' => 'Envío Nacional',
            'proveedor' => 'Paquetería Convenio CVA',
            'tiempo_entrega' => '3 a 6 días hábiles (Estimado)',
            'peso_total' => $totalWeight
        ];
    }

    /**
     * Importar un producto de CVA al catálogo local
     */
    public function importProduct($clave)
    {
        $item = $this->getProductDetails($clave);
        if (!$item || empty($item['clave'])) {
            return ['error' => 'No se encontraron detalles para la clave ' . $clave];
        }

        // Verificar si ya existe (por código o clave)
        $existing = \App\Models\Producto::where('codigo', $item['clave'])
            ->orWhere('codigo_barras', $item['codigo_fabricante'] ?? $item['clave'])
            ->first();

        if ($existing) {
            return ['error' => 'El producto ya existe en el catálogo local con el ID: ' . $existing->id];
        }

        // Resolver Marca y Categoría
        $marca = \App\Models\Marca::firstOrCreate(['nombre' => $item['marca']]);
        $categoria = \App\Models\Categoria::firstOrCreate(['nombre' => $item['grupo'] ?? 'Tecnología']);

        // Resolver Proveedor (Opcional pero recomendado)
        $proveedor = \App\Models\Proveedor::firstOrCreate(
            ['nombre_razon_social' => 'GRUPO CVA'],
            ['rfc' => 'GCV000101XXX', 'email' => 'ventas@grupocva.com', 'estado' => 'activo']
        );

        $utility = $this->getTieredUtilityPercentage((float) $item['precio']);

        // Datos base
        $data = [
            'nombre' => $item['descripcion'],
            'descripcion' => $item['descripcion'],
            'codigo' => $item['clave'],
            'codigo_barras' => $item['codigo_fabricante'] ?? $item['clave'],
            'categoria_id' => $categoria->id,
            'marca_id' => $marca->id,
            'proveedor_id' => $proveedor->id,
            'precio_compra' => (float) $item['precio'],
            'precio_venta' => (float) $item['precio'] * (1 + $utility),
            'stock' => 0,
            'tipo_producto' => 'fisico',
            'estado' => 'activo',
            'unidad_medida' => 'Pieza',
        ];

        // Descargar imagen si existe
        $sourceImage = null;
        if (!empty($item['imagenes']) && is_array($item['imagenes']) && !empty($item['imagenes'][0])) {
            $sourceImage = $item['imagenes'][0];
        } elseif (!empty($item['imagen'])) {
            $sourceImage = $item['imagen'];
        }

        if ($sourceImage) {
            try {
                $imgResponse = Http::get($sourceImage);
                if ($imgResponse->successful()) {
                    $ext = pathinfo($sourceImage, PATHINFO_EXTENSION) ?: 'jpg';
                    // Reemplazar caracteres especiales en la clave para el nombre de archivo
                    $fileName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $item['clave']);
                    $path = 'productos/' . $fileName . '.' . $ext;
                    \Illuminate\Support\Facades\Storage::disk('public')->put($path, $imgResponse->body());
                    $data['imagen'] = $path;
                }
            } catch (\Exception $e) {
                Log::warning('Error downloading CVA image: ' . $e->getMessage());
            }
        }

        $producto = \App\Models\Producto::create($data);

        return ['success' => true, 'producto' => $producto];
    }

    /**
     * Obtener o crear producto local desde CVA (para ventas/pedidos)
     * Este método se usa cuando un cliente compra un producto CVA
     * para tenerlo registrado en la base de datos local.
     */
    public function getOrCreateLocalProduct($cvaId)
    {
        // Extraer clave CVA (quitar prefijo CVA-)
        $clave = str_starts_with($cvaId, 'CVA-') ? str_replace('CVA-', '', $cvaId) : $cvaId;

        // Buscar si ya existe en la base de datos local
        $existing = \App\Models\Producto::where('codigo', $clave)
            ->orWhere('codigo', 'CVA-' . $clave)
            ->orWhere('codigo_barras', $clave)
            ->first();

        if ($existing) {
            return $existing;
        }

        // No existe, importarlo desde CVA
        $result = $this->importProduct($clave);

        if (isset($result['success']) && $result['producto']) {
            return $result['producto'];
        }

        // Si falló la importación (producto ya existe con otro código), intentar buscarlo de nuevo
        if (isset($result['error']) && str_contains($result['error'], 'ya existe')) {
            // Intentar extraer el ID del mensaje de error
            preg_match('/ID: (\d+)/', $result['error'], $matches);
            if (!empty($matches[1])) {
                return \App\Models\Producto::find($matches[1]);
            }
        }

        return null;
    }

    /**
     * Sincronizar categorías de CVA a la base de datos local
     */
    public function syncCategories()
    {
        $grupos = $this->getGrupos();
        $created = 0;

        foreach ($grupos as $grupo) {
            $nombre = $grupo['grupo'] ?? $grupo;
            if (!empty($nombre)) {
                \App\Models\Categoria::firstOrCreate(['nombre' => $nombre]);
                $created++;
            }
        }

        return ['success' => true, 'categorias_sincronizadas' => $created];
    }

    /**
     * Sincronizar marcas de CVA a la base de datos local
     */
    public function syncBrands()
    {
        $cacheKey = 'cva_marcas';

        $marcas = Cache::remember($cacheKey, now()->addDays(1), function () {
            try {
                $response = Http::get("https://www.grupocva.com/catalogo_clientes_xml/marcas.xml");
                if ($response->successful()) {
                    $xml = simplexml_load_string($response->body());
                    $marcas = [];
                    if ($xml && isset($xml->marca)) {
                        foreach ($xml->marca as $m) {
                            $marcas[] = (string) $m;
                        }
                    }
                    return $marcas;
                }
                return [];
            } catch (\Exception $e) {
                return [];
            }
        });

        $created = 0;
        foreach ($marcas as $nombre) {
            if (!empty($nombre)) {
                \App\Models\Marca::firstOrCreate(['nombre' => $nombre]);
                $created++;
            }
        }

        return ['success' => true, 'marcas_sincronizadas' => $created];
    }

    /**
     * Obtener listado de grupos (categorías) CVA
     */
    public function getGrupos()
    {
        return Cache::remember('cva_grupos', now()->addDays(1), function () {
            try {
                $response = Http::get("{$this->baseUrl}/catalogo_clientes/grupos");
                if ($response->successful()) {
                    return $response->json('grupos') ?: [];
                }
                return [];
            } catch (\Exception $e) {
                Log::error('CVA API Exception (Grupos)', ['message' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Obtener listado de sucursales CVA
     */
    public function getSucursales()
    {
        // Este endpoint es público según doc, pero usaremos try/catch
        try {
            // Nota: La doc dice que no requiere token, pero por consistencia podríamos usarlo o no.
            // "Para consultar este endpoint no se necesita estar autenticado con token."
            $response = Http::get("{$this->baseUrl}/catalogo_clientes/sucursales");

            if ($response->successful()) {
                return $response->json('sucursales');
            }
            return [];
        } catch (\Exception $e) {
            Log::error('CVA API Exception (Sucursales)', ['message' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtener listado de paqueterías CVA
     */
    public function getPaqueterias()
    {
        try {
            $response = Http::get("{$this->baseUrl}/catalogo_clientes/paqueteria");

            if ($response->successful()) {
                return $response->json('paqueterias');
            }
            return [];
        } catch (\Exception $e) {
            Log::error('CVA API Exception (Paqueterias)', ['message' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Crear Orden de Compra en CVA
     * 
     * @param array $orderData Datos de la orden (productos, dirección, etc.)
     * @return array Respueta de la API
     */
    public function createOrder(array $orderData)
    {
        $token = $this->getToken();
        if (!$token)
            return ['error' => 'API CVA no autenticada'];

        // Valores por defecto
        $payload = array_merge([
            'codigo_sucursal' => $this->config->cva_codigo_sucursal ?? 1, // Default GDL si no hay config
            'tipo_flete' => 'SF', // Sin Flete por defecto
            'cotiza_flete' => 0,
            'test' => env('CVA_TEST_MODE', false), // Usar variable de entorno para modo test o false.
        ], $orderData);

        // Si hay paquetería configurada y es envío, agregarla si no viene en orderData
        if ($payload['tipo_flete'] !== 'SF' && !isset($payload['flete']['paqueteria'])) {
            if (isset($this->config->cva_paqueteria_envio)) {
                $payload['flete']['paqueteria'] = $this->config->cva_paqueteria_envio;
            }
        }

        try {
            // Log payload for debugging (remove in production if sensitive)
            Log::info('CVA Creating Order Payload', $payload);

            $response = Http::withToken($token)
                ->post("{$this->baseUrl}/pedidos_web/crear_orden", $payload);

            if ($response->successful()) {
                Log::info('CVA Order Created Successfully', $response->json());
                return ['success' => true, 'data' => $response->json()];
            }

            Log::error('CVA Create Order Failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Error al crear la orden en CVA',
                'details' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('CVA API Exception (CreateOrder)', ['message' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Listar Pedidos
     */
    public function listOrders()
    {
        $token = $this->getToken();
        if (!$token)
            return [];

        try {
            $response = Http::withToken($token)
                ->get("{$this->baseUrl}/pedidos_web/lista_pedidos");

            if ($response->successful()) {
                return $response->json('pedidos');
            }
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Consultar detalle de un pedido
     */
    public function getOrderDetails($pedidoReferencia)
    {
        $token = $this->getToken();
        if (!$token)
            return null;

        try {
            $response = Http::withToken($token)
                ->post("{$this->baseUrl}/pedidos_web/consultar_pedido", [
                    'pedido' => $pedidoReferencia
                ]);

            if ($response->successful()) {
                return $response->json('pedido');
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}

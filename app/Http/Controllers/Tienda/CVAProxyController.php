<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use App\Services\CVAService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CVAProxyController extends Controller
{
    protected $cva;
    protected $config;

    public function __construct(CVAService $cva)
    {
        $this->cva = $cva;
        $this->config = \App\Models\EmpresaConfiguracion::getConfig();
    }

    /**
     * Listar productos de CVA con filtros
     */
    public function index(Request $request)
    {
        $filters = $request->only(['desc', 'marca', 'grupo', 'page', 'promociones']);
        $result = $this->cva->getCatalogo($filters);

        if (isset($result['error'])) {
            return response()->json($result, 500);
        }

        // Normalizar los resultados antes de enviarlos al frontend
        if (isset($result['articulos'])) {
            $articulos = collect($result['articulos'])->map(function ($item) {
                return $this->cva->normalizeProduct($item);
            });

            // Ordenar: Productos con stock local (Hermosillo) primero
            $result['articulos'] = $articulos->sortByDesc(function ($item) {
                return $item['stock_local'] ?? $item['disponible'] ?? 0;
            })->values();
        }

        return response()->json($result);
    }

    /**
     * Búsqueda predictiva (Live Search)
     */
    public function suggestions(Request $request)
    {
        $request->validate(['q' => 'required|string|min:3']);
        $query = $request->q;
        $suggestions = collect();

        // 1. Búsqueda Local (Prioridad)
        $localProducts = \App\Models\Producto::where('estado', 'activo')
            ->where(function ($q) use ($query) {
                $q->where('nombre', 'like', "%{$query}%")
                    ->orWhere('codigo', 'like', "%{$query}%")
                    ->orWhere('codigo_barras', 'like', "%{$query}%")
                    ->orWhereHas('marca', function ($q) use ($query) {
                        $q->where('nombre', 'like', "%{$query}%");
                    });
            })
            ->limit(5)
            ->get();

        foreach ($localProducts as $product) {
            $suggestions->push([
                'id' => $product->id,
                'label' => $product->nombre,
                'category' => $product->categoria->nombre ?? 'Local',
                'price' => round($product->precio_venta * 1.16, 2),
                'image' => $product->imagen ? (str_starts_with($product->imagen, 'http') ? $product->imagen : Storage::url($product->imagen)) : null,
                'origen' => 'local'
            ]);
        }

        // 2. Búsqueda en CVA (Si no se ha llenado el cupo de 10)
        if ($this->config->cva_active && $suggestions->count() < 10) {
            $needed = 10 - $suggestions->count();

            // Usar cache para no saturar la API en cada tecla
            $cacheKey = 'cva_sugg_' . md5($query);
            $cvaAds = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($query, $needed) {
                $result = $this->cva->getCatalogo([
                    'desc' => $query,
                    'sucursales' => 1, // Necesario para el stock local
                ]);

                if (!isset($result['error']) && isset($result['articulos'])) {
                    return collect($result['articulos'])
                        ->map(function ($item) {
                            try {
                                return $this->cva->normalizeProduct($item);
                            } catch (\Exception $e) {
                                return null;
                            }
                        })
                        ->filter()
                        ->sortByDesc('stock_local') // Primero los de Hermosillo
                        ->values();
                }
                return collect();
            });

            foreach ($cvaAds->take($needed) as $product) {
                $suggestions->push([
                    'id' => $product['id'],
                    'label' => $product['nombre'],
                    'category' => $product['categoria'],
                    'price' => $product['precio_con_iva'],
                    'image' => $product['imagen_url'],
                    'stock' => $product['stock_local'],
                    'stock_cedis' => $product['stock_cedis'],
                    'origen' => 'CVA'
                ]);
            }
        }

        return response()->json($suggestions);
    }

    /**
     * Consultar detalles de un producto (Para el Carrito)
     */
    public function show($clave)
    {
        $result = $this->cva->getProductDetails($clave, true); // true for normalized

        if (!$result) {
            return response()->json(['error' => 'No se encontró el producto'], 404);
        }

        return response()->json($result);
    }

    /**
     * Importar producto a catálogo local
     */
    public function import(Request $request)
    {
        $request->validate([
            'clave' => 'required|string'
        ]);

        $result = $this->cva->importProduct($request->clave);

        if (isset($result['error'])) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * Sincronizar producto CVA a base de datos local (usado al vender)
     */
    public function syncToLocal(Request $request)
    {
        $request->validate([
            'cva_id' => 'required|string'
        ]);

        $producto = $this->cva->getOrCreateLocalProduct($request->cva_id);

        if (!$producto) {
            return response()->json(['error' => 'No se pudo sincronizar el producto'], 422);
        }

        return response()->json([
            'success' => true,
            'producto_id' => $producto->id,
            'producto' => $producto
        ]);
    }

    /**
     * Sincronizar todas las categorías de CVA
     */
    public function syncCategories()
    {
        $result = $this->cva->syncCategories();
        return response()->json($result);
    }

    /**
     * Sincronizar todas las marcas de CVA
     */
    public function syncBrands()
    {
        $result = $this->cva->syncBrands();
        return response()->json($result);
    }
    /**
     * Calcular costo de envío (Fase 2.2)
     */
    public function calculateShipping(Request $request)
    {
        $request->validate([
            'cp' => 'required|string|size:5',
            'items' => 'required|array'
        ]);

        $result = $this->cva->calculateShippingCost($request->cp, $request->items);

        if (isset($result['error'])) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }
}

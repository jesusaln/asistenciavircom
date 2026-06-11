<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class CatalogoController extends Controller
{
    /**
     * Mostrar el catálogo público de productos
     */
    public function index(Request $request)
    {
        $empresa = EmpresaConfiguracion::getConfig();
        $tieneCatalogoWeb = Schema::hasColumn('productos', 'catalogo_web');

        $query = Producto::query()
            ->where('estado', 'activo')
            ->with(['categoria', 'marca']);

        if ($tieneCatalogoWeb) {
            $query->where('catalogo_web', true);
            
            // Regla General: Solo mostrar productos que tengan imagen configurada
            $query->whereNotNull('imagen')
                  ->where('imagen', '!=', '');
        }

        // Filtro por categoría
        if ($request->filled('categoria')) {
            $categoriaVal = $request->categoria;
            if (is_numeric($categoriaVal)) {
                $query->where('categoria_id', $categoriaVal);
            } else {
                $cat = Categoria::where('nombre', 'ilike', $categoriaVal)->first();
                if ($cat) {
                    $query->where('categoria_id', $cat->id);
                } else {
                    $query->whereRaw('1=0');
                }
            }
        }

        // Filtro por marca
        if ($request->filled('marca')) {
            $marcaVal = $request->marca;
            if (is_numeric($marcaVal)) {
                $query->where('marca_id', $marcaVal);
            } else {
                $brand = Marca::where('nombre', 'ilike', $marcaVal)->first();
                if ($brand) {
                    $query->where('marca_id', $brand->id);
                } else {
                    $query->whereRaw('1=0');
                }
            }
        }

        // Filtro por existencia (Local o CEDIS) - DEFAULT: FALSE (mostrar todo)
        $soloExistencia = $request->has('existencia') ? $request->boolean('existencia') : false;
        if ($soloExistencia) {
            $query->where(function ($q) {
                $q->where('stock', '>', 0)
                    ->orWhere('stock_cedis', '>', 0);
            });
        }

        // Filtro por entrega inmediata (Solo Local)
        if ($request->boolean('local')) {
            $query->where('stock', '>', 0);
        }

        // Búsqueda por nombre
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'ilike', "%{$search}%")
                    ->orWhere('descripcion', 'ilike', "%{$search}%")
                    ->orWhere('codigo', 'ilike', "%{$search}%")
                    ->orWhere('cva_clave', 'ilike', "%{$search}%");
            });
        }

        // Filtro por productos sin foto
        if ($request->boolean('sin_foto')) {
            $query->where(function ($q) {
                $q->whereNull('imagen')
                    ->orWhere('imagen', '');
            });
        }

        // Filtro por rango de precio
        if ($request->filled('precio_min')) {
            $query->where('precio_venta', '>=', $request->precio_min);
        }
        if ($request->filled('precio_max')) {
            $query->where('precio_venta', '<=', $request->precio_max);
        }

        // Ordenamiento
        $orden = $request->get('orden', 'recientes');
        $search = $request->get('search');

        if ($search) {
            // Relevancia para búsqueda: 
            // 1. Nombre empieza con el término
            // 2. Nombre contiene el término
            // 3. Stock disponible
            $query->orderByRaw("CASE 
                WHEN nombre ILIKE ? THEN 1 
                WHEN nombre ILIKE ? THEN 2 
                ELSE 3 END ASC", [$search . '%', '%' . $search . '%'])
                ->orderByRaw('CASE WHEN stock > 0 OR stock_cedis > 0 THEN 1 ELSE 0 END DESC');
        } else {
            switch ($orden) {
                case 'precio_asc':
                    $query->orderBy('precio_venta', 'asc');
                    break;
                case 'precio_desc':
                    $query->orderBy('precio_venta', 'desc');
                    break;
                case 'nombre':
                    $query->orderBy('nombre', 'asc');
                    break;
                default:
                    // 1. Disponibles en Hermosillo (stock > 0)
                    // 2. Disponibles en CEDIS/Otros (stock_cedis > 0)
                    // 3. Los demás (sin stock)
                    $query->orderByRaw('CASE 
                        WHEN stock > 0 THEN 1 
                        WHEN stock_cedis > 0 THEN 2 
                        ELSE 3 
                    END ASC')
                        ->orderBy('created_at', 'desc');
            }
        }

        $productos = $query->paginate(15)->withQueryString()->through(function ($item) {
            return $this->transformModelToView($item);
        });

        // Cache por 1 hora para mejorar velocidad de búsqueda
        $cacheKey = "catalogo_filters_empresa_v2_" . ($empresa->id ?? 'default');

        $filterData = \Cache::remember($cacheKey, 3600, function () use ($tieneCatalogoWeb) {
            // Categorías con conteo de productos activos
            $categorias = Categoria::withCount([
                'productos' => function ($q) use ($tieneCatalogoWeb) {
                    $q->where('estado', 'activo');
                    if ($tieneCatalogoWeb) {
                        $q->where('catalogo_web', true)
                            ->whereNotNull('imagen')
                            ->where('imagen', '!=', '');
                    }
                }
            ])->whereHas('productos', function ($q) use ($tieneCatalogoWeb) {
                $q->where('estado', 'activo');
                if ($tieneCatalogoWeb) {
                    $q->where('catalogo_web', true)
                        ->whereNotNull('imagen')
                        ->where('imagen', '!=', '');
                }
            })->orderBy('nombre')->get();

            // Marcas con conteo
            $marcas = Marca::withCount([
                'productos' => function ($q) use ($tieneCatalogoWeb) {
                    $q->where('estado', 'activo');
                    if ($tieneCatalogoWeb) {
                        $q->where('catalogo_web', true)
                            ->whereNotNull('imagen')
                            ->where('imagen', '!=', '');
                    }
                }
            ])->whereHas('productos', function ($q) use ($tieneCatalogoWeb) {
                $q->where('estado', 'activo');
                if ($tieneCatalogoWeb) {
                    $q->where('catalogo_web', true)
                        ->whereNotNull('imagen')
                        ->where('imagen', '!=', '');
                }
            })->orderBy('nombre')->get();

            return compact('categorias', 'marcas');
        });

        // Obtener límites de precio (Cacheado por 30 min)
        $prices = \Cache::remember("catalogo_prices_v2_" . ($empresa->id ?? '8'), 1800, function () use ($query) {
            $q = clone $query;
            return [
                'min' => $q->min('precio_venta') ?: 0,
                'max' => $q->max('precio_venta') ?: 100000
            ];
        });

        $minPrice = $prices['min'];
        $maxPrice = $prices['max'];

        $categorias = $filterData['categorias'];
        $marcas = $filterData['marcas'];

        return Inertia::render('Catalogo/Index', [
            'productos' => $productos,
            'categorias' => $categorias,
            'marcas' => $marcas,
            'priceRange' => [
                'min' => floor($minPrice),
                'max' => ceil($maxPrice)
            ],
            'empresa' => $empresa ? [
                'nombre' => $empresa->nombre_comercial ?? $empresa->razon_social ?? 'Tienda',
                'logo' => $empresa->logo ?? null,
                'telefono' => $empresa->telefono ?? null,
                'email' => $empresa->email ?? null,
                'whatsapp' => $empresa->whatsapp ?? $empresa->telefono ?? null,
                'color_principal' => $empresa->color_principal ?? '#FF6B35',
                'cva_active' => $empresa->cva_active,
            ] : null,
            'filters' => [
                'categoria' => $request->categoria,
                'marca' => $request->marca,
                'search' => $request->search,
                'orden' => $orden,
                'precio_min' => $request->precio_min,
                'precio_max' => $request->precio_max,
                'existencia' => $soloExistencia,
                'local' => $request->boolean('local'),
                'sin_foto' => $request->boolean('sin_foto'),
            ],
            'cliente' => session('cliente_tienda'),
            'canLogin' => true,
        ]);
    }

    /**
     * Mostrar detalle de un producto (Híbrido DB/API)
     */
    public function show($id)
    {
        $empresa = EmpresaConfiguracion::getConfig();
        $tieneCatalogoWeb = Schema::hasColumn('productos', 'catalogo_web');
        $isCvaId = str_starts_with($id, 'CVA-');
        $productoModel = null;
        $cvaClave = null;

        if ($isCvaId) {
            $cvaClave = str_replace('CVA-', '', $id);
        } elseif (is_numeric($id)) {
            $productoModel = Producto::with(['categoria', 'marca'])->find($id);
            if ($productoModel && $productoModel->origen === 'CVA') {
                $cvaClave = $productoModel->cva_clave;
            }
        } else {
            // Slug / SKU fallback (e.g. from FB Ads)
            $productoModel = Producto::with(['categoria', 'marca'])
                ->where('sku', $id)
                ->first();
        }

        if (!$productoModel && !$cvaClave) {
            abort(404, 'Producto no encontrado');
        }

        if ($productoModel && $tieneCatalogoWeb && !$productoModel->catalogo_web) {
            abort(404, 'Producto no disponible en tienda');
        }

        if ($cvaClave) {
            $service = app(\App\Services\CVAService::class);
            $item = $service->getProductDetails($cvaClave, true);

            if (!$item || isset($item['error'])) {
                if ($productoModel) {
                    $producto = $this->transformModelToView($productoModel);
                } else {
                    abort(404, 'Producto CVA no encontrado o API inaccesible');
                }
            } else {
                $especificaciones = $service->getTechnicalSpecs($cvaClave);
                $imagenes = $item['imagenes'] ?? [];

                // Actualizar cache local si existe el producto
                if ($productoModel || ($pModel = Producto::where('cva_clave', $cvaClave)->first())) {
                    $target = $productoModel ?? $pModel;
                    $target->update([
                        'precio_compra' => $item['precio_compra'] ?? $target->precio_compra,
                        'precio_venta' => $item['precio'],
                        'stock' => $item['stock_local'] ?? 0,
                        'stock_cedis' => $item['stock_cedis'] ?? 0,
                        'cva_last_sync' => now(),
                    ]);
                }

                $producto = [
                    'id' => $productoModel ? $productoModel->id : 'CVA-' . $cvaClave,
                    'nombre' => $item['nombre'],
                    'descripcion' => $item['descripcion'],
                    'precio_venta' => $item['precio'],
                    'precio_con_iva' => $item['precio_con_iva'],
                    'codigo' => $item['clave'],
                    'unidad_medida' => 'PZA',
                    'stock' => $item['stock'],
                    'imagen' => !empty($imagenes) ? $imagenes[0] : $item['imagen_url'],
                    'categoria' => ['nombre' => $item['categoria']],
                    'marca' => ['nombre' => $item['marca']],
                    'origen' => 'CVA',
                    'garantia' => $item['garantia'],
                    'imagenes' => $imagenes,
                    'ficha_tecnica' => $item['ficha_tecnica'],
                    'ficha_comercial' => $item['ficha_comercial'],
                    'especificaciones' => $especificaciones,
                    'stock_local' => $item['stock_local'] ?? 0,
                    'stock_cedis' => $item['stock_cedis'] ?? 0,
                    'en_transito' => $item['en_transito'] ?? 0,
                    'stock_desglose' => $item['stock_desglose'] ?? [],
                ];
            }
            $relacionados = [];
        } else {
            $productoModel = Producto::with(['categoria', 'marca'])
                ->where('estado', 'activo');
            if ($tieneCatalogoWeb) {
                $productoModel->where('catalogo_web', true)
                    ->whereNotNull('imagen')
                    ->where('imagen', '!=', '');
            }
            $productoModel = $productoModel->findOrFail($id);

            $producto = $this->transformModelToView($productoModel);

            $relacionados = Producto::where('estado', 'activo')
                ->where('categoria_id', $productoModel->categoria_id)
                ->where('id', '!=', $productoModel->id)
                ->limit(4)
                ->get()
                ->map(fn($rel) => $this->transformModelToView($rel, true));
            if ($tieneCatalogoWeb) {
                $relacionados = Producto::where('estado', 'activo')
                    ->where('catalogo_web', true)
                    ->whereNotNull('imagen')
                    ->where('imagen', '!=', '')
                    ->where('categoria_id', $productoModel->categoria_id)
                    ->where('id', '!=', $productoModel->id)
                    ->limit(4)
                    ->get()
                    ->map(fn($rel) => $this->transformModelToView($rel, true));
            }
        }

        // Tracking: Meta CAPI ViewContent
        try {
            $metaId = $productoModel->sku ?: ('CDD-' . $productoModel->id);
            $metaService = app(\App\Services\MetaConversionService::class);
            $metaService->sendEvent('ViewContent', [], [
                'content_ids' => [(string) $metaId],
                'content_name' => $producto['nombre'],
                'content_type' => 'product',
                'value' => (float) $producto['precio_con_iva'],
                'currency' => 'MXN'
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Error tracking ViewContent: " . $e->getMessage());
        }

        return Inertia::render('Catalogo/Show', [
            'producto' => $producto,
            'relacionados' => $relacionados,
            'empresa' => $empresa ? [
                'nombre' => $empresa->nombre_comercial ?? $empresa->razon_social ?? 'Tienda',
                'whatsapp' => $empresa->whatsapp ?? $empresa->telefono ?? null,
                'color_principal' => $empresa->color_principal ?? '#FF6B35',
            ] : null,
            'canLogin' => true,
        ]);
    }

    public function searchSuggestions(Request $request)
    {
        $query = $request->get('q', '');
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $productos = Producto::where('estado', 'activo')
            ->where(function ($q) use ($query) {
                $q->where('nombre', 'ilike', "%{$query}%")
                  ->orWhere('codigo', 'ilike', "%{$query}%")
                  ->orWhere('cva_clave', 'ilike', "%{$query}%");
            })
            ->whereNotNull('imagen')
            ->where('imagen', '!=', '')
            ->where('stock', '>', 0)
            ->with('categoria')
            ->limit(8)
            ->get();

        $results = $productos->map(function ($p) {
            $precio = $p->precio_tienda_online ?? $p->precio_venta;
            return [
                'id' => $p->id,
                'label' => $p->nombre,
                'codigo' => $p->codigo,
                'price' => round($precio * 1.16, 2),
                'image' => $p->imagen,
                'stock' => (int) $p->stock,
                'category' => $p->categoria?->nombre ?? 'General',
                'origen' => $p->origen ?? 'local',
            ];
        });

        return response()->json($results);
    }

    public function categoriasParaNav()
    {
        $categorias = Categoria::whereHas('productos', function ($q) {
            $q->where('estado', 'activo')
              ->whereNotNull('imagen')
              ->where('imagen', '!=', '')
              ->where('stock', '>', 0);
        })->withCount(['productos' => function ($q) {
            $q->where('estado', 'activo')
              ->whereNotNull('imagen')
              ->where('imagen', '!=', '')
              ->where('stock', '>', 0);
        }])->orderBy('productos_count', 'desc')
          ->limit(12)
          ->get(['id', 'nombre']);

        return response()->json($categorias);
    }

    private function transformModelToView($model, $lite = false)
    {
        // Usar precio de tienda online si está configurado, si no el precio de venta normal
        $precioMostrar = $model->precio_tienda_online ?? $model->precio_venta;
        $precio_con_iva = round(($precioMostrar ?? 0) * 1.16, 2);

        // Debugging
        if ($model->origen === 'CVA') {
            \Illuminate\Support\Facades\Log::info("Transforming CVA Product: {$model->nombre} | Price: {$precioMostrar} | Final: {$precio_con_iva}");
        }

        $data = [
            'id' => $model->id,
            'nombre' => $model->nombre,
            'descripcion' => $model->descripcion,
            'precio' => (float) $precioMostrar,
            'precio_venta' => (float) $precioMostrar,
            'precio_con_iva' => $precio_con_iva,
            'precio_original' => (float) $model->precio_venta,
            'tiene_precio_ml' => $model->precio_tienda_online !== null,
            'codigo' => $model->codigo,
            'unidad_medida' => $model->unidad_medida,
            'stock' => (int) $model->stock,
            'stock_local' => (int) $model->stock,
            'stock_cedis' => (int) ($model->stock_cedis ?? 0),
            'imagen' => $model->imagen,
            'origen' => $model->origen ?? 'local',
        ];

        if (!$lite) {
            $data['categoria'] = ['nombre' => $model->categoria?->nombre ?? 'N/A'];
            $data['marca'] = ['nombre' => $model->marca?->nombre ?? 'N/A'];
        }

        return $data;
    }
}

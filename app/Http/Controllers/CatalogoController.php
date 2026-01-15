<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CatalogoController extends Controller
{
    /**
     * Mostrar el catálogo público de productos
     */
    public function index(Request $request)
    {
        $empresa = EmpresaConfiguracion::getConfig();

        $query = Producto::query()
            ->where('estado', 'activo')
            ->with(['categoria', 'marca']);

        // Filtro por categoría
        if ($request->filled('categoria')) {
            $query->where('categoria_id', $request->categoria);
        }

        // Filtro por marca
        if ($request->filled('marca')) {
            $query->where('marca_id', $request->marca);
        }

        // Filtro por existencia (Local o CEDIS) - DEFAULT: TRUE si no se especifica
        $soloExistencia = $request->has('existencia') ? $request->boolean('existencia') : true;
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

        $productos = $query->paginate(28)->withQueryString()->through(function ($item) {
            return $this->transformModelToView($item);
        });

        // Cache por 1 hora para mejorar velocidad de búsqueda
        $cacheKey = "catalogo_filters_empresa_" . ($empresa->id ?? 'default');

        $filterData = \Cache::remember($cacheKey, 3600, function () {
            // Categorías con conteo de productos activos
            $categorias = Categoria::withCount(['productos' => fn($q) => $q->where('estado', 'activo')])
                ->whereHas('productos', fn($q) => $q->where('estado', 'activo'))
                ->orderBy('nombre')
                ->get();

            // Marcas con conteo
            $marcas = Marca::withCount(['productos' => fn($q) => $q->where('estado', 'activo')])
                ->whereHas('productos', fn($q) => $q->where('estado', 'activo'))
                ->orderBy('nombre')
                ->get();

            return compact('categorias', 'marcas');
        });

        // Obtener límites de precio (Cacheado por 30 min)
        $prices = \Cache::remember("catalogo_prices_" . ($empresa->id ?? '8'), 1800, function () use ($query) {
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
                'color_principal' => $empresa->color_principal ?? '#3B82F6',
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
        $isCvaId = str_starts_with($id, 'CVA-');
        $productoModel = null;
        $cvaClave = null;

        if (!$isCvaId && is_numeric($id)) {
            $productoModel = Producto::with(['categoria', 'marca'])->find($id);
            if ($productoModel && $productoModel->origen === 'CVA') {
                $cvaClave = $productoModel->cva_clave;
            }
        } elseif ($isCvaId) {
            $cvaClave = str_replace('CVA-', '', $id);
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
                ->where('estado', 'activo')
                ->findOrFail($id);

            $producto = $this->transformModelToView($productoModel);

            $relacionados = Producto::where('estado', 'activo')
                ->where('categoria_id', $productoModel->categoria_id)
                ->where('id', '!=', $productoModel->id)
                ->limit(4)
                ->get()
                ->map(fn($rel) => $this->transformModelToView($rel, true));
        }

        return Inertia::render('Catalogo/Show', [
            'producto' => $producto,
            'relacionados' => $relacionados,
            'empresa' => $empresa ? [
                'nombre' => $empresa->nombre_comercial ?? $empresa->razon_social ?? 'Tienda',
                'whatsapp' => $empresa->whatsapp ?? $empresa->telefono ?? null,
                'color_principal' => $empresa->color_principal ?? '#3B82F6',
            ] : null,
            'canLogin' => true,
        ]);
    }

    private function transformModelToView($model, $lite = false)
    {
        $precio_con_iva = round(($model->precio_venta ?? 0) * 1.16, 2);

        // Debugging
        if ($model->origen === 'CVA') {
            \Illuminate\Support\Facades\Log::info("Transforming CVA Product: {$model->nombre} | Price: {$model->precio_venta} | Final: {$precio_con_iva}");
        }

        $data = [
            'id' => $model->id,
            'nombre' => $model->nombre,
            'descripcion' => $model->descripcion,
            'precio' => (float) $model->precio_venta,
            'precio_venta' => (float) $model->precio_venta,
            'precio_con_iva' => $precio_con_iva,
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

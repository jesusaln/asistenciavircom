<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ProductoSerie;
use App\Models\Servicio;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Proveedor;
use App\Models\Almacen;
use App\Models\ProductoPrecioHistorial;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\SatClaveUnidad;
use App\Models\SatObjetoImp;
use App\Models\SatClaveProdServ;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use App\Support\EmpresaResolver;
use App\Traits\ImageOptimizerTrait;

class ProductoController extends Controller
{
    use ImageOptimizerTrait;

    public function __construct()
    {
        // Nota: edit() y show() usan $id en lugar de Model binding,
        // por lo que la autorización se hace manualmente en esos métodos si es necesario.
        // Para los demás métodos que usan Model binding, authorizeResource funciona.
        $this->authorizeResource(Producto::class, 'producto');
    }
    /**
     * Muestra una lista de todos los productos con paginación y filtros.
     */
    public function index(Request $request)
    {
        try {
            $query = Producto::query()->with(['categoria', 'marca', 'proveedor', 'almacen']);

            // Filtros
            if ($search = trim($request->input('search', ''))) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('codigo', 'like', "%{$search}%")
                        ->orWhere('codigo_barras', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%");
                });
            }

            if ($estado = $request->input('estado')) {
                if ($estado === 'activo') {
                    $query->where('estado', 'activo');
                } elseif ($estado === 'inactivo') {
                    $query->where('estado', 'inactivo');
                } elseif ($estado === 'agotado') {
                    $query->where('stock', '<=', 0);
                }
            }

            // Ordenamiento
            $sortBy = $request->input('sort_by', 'nombre');
            $sortDirection = $request->input('sort_direction', 'asc');

            $validSortFields = ['nombre', 'codigo', 'precio_venta', 'stock', 'created_at'];
            if (!in_array($sortBy, $validSortFields)) {
                $sortBy = 'nombre';
            }

            $query->orderBy($sortBy, $sortDirection);

            // Paginación
            $perPage = min((int) $request->input('per_page', 25), 50);
            $productos = $query->paginate($perPage);

            // Agregar permisos a cada producto
            foreach ($productos->items() as $producto) {
                $producto->can_delete = $this->canDeleteProducto($producto);
                $producto->can_toggle_in_index = false; // No mostrar botón de cambiar estado en el índice
                $producto->can_toggle_in_modal = true; // Sí mostrar en el modal
            }

            // Estadísticas basadas en estado del producto
            $stats = [
                'total' => Producto::count(),
                'activos' => Producto::where('estado', 'activo')->count(),
                'inactivos' => Producto::where('estado', 'inactivo')->count(),
                'agotado' => Producto::where('stock', '<=', 0)->count(),
            ];

            return Inertia::render('Productos/Index', [
                'productos' => $productos,
                'stats' => $stats,
                'filters' => $request->only(['search', 'estado']),
                'sorting' => ['sort_by' => $sortBy, 'sort_direction' => $sortDirection],
            ]);
        } catch (\Exception $e) {
            Log::error('Error en ProductoController@index: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar los productos.');
        }
    }

    /**
     * Muestra el formulario para crear un nuevo producto.
     */
    public function create()
    {
        return Inertia::render('Productos/Create', [
            'categorias' => Categoria::select('id', 'nombre')->get(),
            'marcas' => Marca::select('id', 'nombre')->get(),
            'proveedores' => Proveedor::select('id', 'nombre_razon_social')->get(),
            'almacenes' => Almacen::select('id', 'nombre')->get(),
            'priceLists' => \App\Models\PriceList::where('activa', true)->get(),
            'defaults' => [
                'ivaPorcentaje' => \App\Services\EmpresaConfiguracionService::getIvaPorcentaje(),
            ],
            'satCatalogos' => [
                'unidades' => SatClaveUnidad::getOptions(), // Devuelve las de uso común
                'objetosImp' => SatObjetoImp::getOptions(),
            ]
        ]);
    }

    /**
     * Devuelve catálogos necesarios para crear productos vía AJAX.
     */
    public function getCatalogs()
    {
        return response()->json([
            'categorias' => Categoria::select('id', 'nombre')->orderBy('nombre')->get(),
            'marcas' => Marca::select('id', 'nombre')->orderBy('nombre')->get(),
            'unidades' => \App\Models\UnidadMedida::activas()->select('id', 'nombre', 'abreviatura')->get(),
            // 'almacenes' => Almacen::select('id', 'nombre')->where('estado', 'activo')->get(),
        ]);
    }

    /**
     * Obtiene el siguiente código de producto disponible.
     */
    public function nextCodigo()
    {
        try {
            $nextCodigo = app(\App\Services\Folio\FolioService::class)->previewNextFolio('producto');
            return response()->json(['codigo' => $nextCodigo]);
        } catch (\Exception $e) {
            Log::error('Error generating next code for producto: ' . $e->getMessage());
            return response()->json(['codigo' => 'PROD-' . time()], 500);
        }
    }

    /**
     * Almacena un nuevo producto en la base de datos.
     */
    public function store(Request $request)
    {
        // FIX: Inertia requests should NOT be treated as AJAX (they use X-Inertia header)
        // Only pure AJAX/API calls (without X-Inertia) should use simplified validation
        $isInertia = $request->header('X-Inertia') !== null;
        $isAjax = ($request->expectsJson() || $request->ajax()) && !$isInertia;

        // Debug logging for stock_minimo_por_almacen
        Log::debug('ProductoController@store - Request data', [
            'isInertia' => $isInertia,
            'isAjax' => $isAjax,
            'stock_minimo_por_almacen' => $request->input('stock_minimo_por_almacen'),
            'precio_venta' => $request->input('precio_venta'),
            'precio_compra' => $request->input('precio_compra'),
        ]);

        if ($isAjax) {
            // Validación simplificada para creación rápida (desde XML o modal)
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'codigo' => 'nullable|string',
                'precio_compra' => 'nullable|numeric|min:0',
                'unidad_medida' => 'nullable|string',
                'requiere_serie' => 'nullable|boolean',
                // Campos SAT del XML
                'sat_clave_prod_serv' => 'nullable|string|max:8',
                'sat_clave_unidad' => 'nullable|string|max:3',
                'sat_objeto_imp' => 'nullable|string|max:2',
                // Stock y otros opcionales
                'stock' => 'nullable|numeric|min:0',
                'precio_venta' => 'nullable|numeric|min:0',
                'categoria_id' => 'nullable|exists:categorias,id',
                'marca_id' => 'nullable|exists:marcas,id',
                'descripcion' => 'nullable|string',
            ]);

            // Asignar defaults para campos requeridos

            // 1. Categoría y Marca (usar del request si viene, sino tomar la primera disponible)
            if (empty($validated['categoria_id'])) {
                $defaultCat = Categoria::first();
                $validated['categoria_id'] = $defaultCat ? $defaultCat->id : 1;
            }

            if (empty($validated['marca_id'])) {
                $defaultMarca = Marca::first();
                $validated['marca_id'] = $defaultMarca ? $defaultMarca->id : 1;
            }

            // 2. Precio Venta (usar del request si viene, sino calcular)
            if (empty($validated['precio_venta'])) {
                $precioCompra = $validated['precio_compra'] ?? 0;
                $validated['precio_venta'] = $precioCompra > 0 ? $precioCompra * 1.30 : 0;
            }

            // 3. Código de Barras y Código
            // Si el código viene, verificar si es único. Si no, o si está duplicado, usar null o generarlo.
            if (!empty($validated['codigo'])) {
                if (Producto::where('codigo', $validated['codigo'])->exists()) {
                    $validated['codigo'] = null; // No usar código duplicado
                }
            }
            // Generar código de barras único
            if (!empty($validated['codigo']) && !Producto::where('codigo_barras', $validated['codigo'])->exists()) {
                $validated['codigo_barras'] = $validated['codigo'];
            } else {
                $validated['codigo_barras'] = 'GEN-' . time() . rand(100, 999);
            }

            // 4. Otros defaults (solo si no vienen del request)
            $validated['descripcion'] = $validated['descripcion'] ?? $validated['nombre'];
            $validated['tipo_producto'] = 'fisico';
            $validated['estado'] = 'activo';
            $validated['stock'] = $validated['stock'] ?? 0;
            $validated['reservado'] = 0;
            $validated['expires'] = false;
            $validated['requiere_serie'] = $validated['requiere_serie'] ?? false;
            $validated['almacen_id'] = null;
            $validated['comision_vendedor'] = 0;
            $validated['margen_ganancia'] = 0;

        } else {
            // Validación estricta para formulario normal
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'codigo' => 'nullable|string|unique:productos,codigo',
                'codigo_barras' => 'required|string|unique:productos,codigo_barras',
                'categoria_id' => 'required|exists:categorias,id',
                'marca_id' => 'required|exists:marcas,id',
                'proveedor_id' => 'nullable|exists:proveedores,id',
                'almacen_id' => 'nullable|exists:almacenes,id',
                'expires' => 'boolean',
                'requiere_serie' => 'boolean',
                'precio_compra' => 'required|numeric|min:0',
                'precio_venta' => 'required|numeric|min:0',
                'unidad_medida' => 'required|string',
                'fecha_vencimiento' => 'nullable|date',
                'tipo_producto' => 'required|in:fisico,digital',
                'imagen' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'estado' => 'required|in:activo,inactivo',
                'comision_vendedor' => 'nullable|numeric|min:0|max:100',
                'sat_clave_prod_serv' => 'nullable|string|max:8',
                'sat_clave_unidad' => 'nullable|string|max:3',
                'sat_objeto_imp' => 'nullable|string|max:2',
                'stock_minimo_por_almacen' => 'nullable|array',
                'stock_minimo_por_almacen.*' => 'integer|min:0',
                'prices' => 'nullable|array',
                'prices.*.price_list_id' => 'required|exists:price_lists,id',
                'prices.*.precio' => 'nullable|numeric|min:0',
            ]);

            if ($request->hasFile('imagen')) {
                $validated['imagen'] = $this->saveImageAsWebP($request->file('imagen'), 'productos');
            }

            // Defaults para normal form
            $validated['stock'] = 0;
            if (empty($validated['descripcion'])) {
                $validated['descripcion'] = 'Sin descripción disponible';
            }
            $validated['reservado'] = $validated['reservado'] ?? 0;
            $validated['expires'] = $validated['expires'] ?? false;
            $validated['requiere_serie'] = $validated['requiere_serie'] ?? false;
            $validated['margen_ganancia'] = $validated['margen_ganancia'] ?? 0;
            $validated['comision_vendedor'] = $validated['comision_vendedor'] ?? 0;
        }

        $producto = Producto::create($validated);

        // Debug logging after product creation
        Log::debug('ProductoController@store - Product created', [
            'producto_id' => $producto->id,
            'precio_venta_saved' => $producto->precio_venta,
            'validated_stock_minimo' => $validated['stock_minimo_por_almacen'] ?? 'not set',
        ]);

        if (!$isAjax) {
            // Lógica adicional solo para formulario completo (inventarios, precios extra)
            // ... (inventarios y precios extra que dependen de inputs del form completo)

            // Crear registros de inventario para cada almacén con stock mínimo configurado
            if (!empty($validated['stock_minimo_por_almacen'])) {
                Log::debug('ProductoController@store - Creating inventarios', [
                    'stock_minimo_por_almacen' => $validated['stock_minimo_por_almacen'],
                ]);
                foreach ($validated['stock_minimo_por_almacen'] as $almacenId => $stockMinimo) {
                    Log::debug('Creating inventario record', [
                        'producto_id' => $producto->id,
                        'almacen_id' => $almacenId,
                        'stock_minimo' => $stockMinimo,
                    ]);
                    \App\Models\Inventario::create([
                        'producto_id' => $producto->id,
                        'almacen_id' => $almacenId,
                        'cantidad' => 0,
                        'stock_minimo' => $stockMinimo,
                    ]);
                }
            } else {
                Log::debug('ProductoController@store - No stock_minimo_por_almacen in validated data');
            }

            // Guardar precios por lista si existen
            if (!empty($validated['prices'])) {
                foreach ($validated['prices'] as $priceData) {
                    if (isset($priceData['precio']) && $priceData['precio'] !== null && $priceData['precio'] !== '') {
                        \App\Models\ProductPrice::create([
                            'producto_id' => $producto->id,
                            'price_list_id' => $priceData['price_list_id'],
                            'precio' => $priceData['precio'],
                        ]);
                    }
                }
            }
        }

        // Log initial prices
        ProductoPrecioHistorial::create([
            'producto_id' => $producto->id,
            'precio_compra_anterior' => null,
            'precio_compra_nuevo' => $producto->precio_compra,
            'precio_venta_anterior' => null,
            'precio_venta_nuevo' => $producto->precio_venta,
            'tipo_cambio' => 'creacion',
            'notas' => 'Precio inicial al crear el producto',
            'user_id' => Auth::id(),
        ]);

        if ($isAjax && !$request->header('X-Inertia')) {
            return response()->json([
                'success' => true,
                'producto' => $producto,
                'id' => $producto->id,
                'message' => 'Producto creado correctamente.'
            ]);
        }

        return redirect()->route('productos.index')->with('success', 'Producto creado correctamente.');
    }

    /**
     * Muestra el formulario para editar un producto existente.
     */
    public function edit(Producto $producto)
    {
        $producto->load(['inventarios', 'productPrices.priceList']);

        // Validación de estado (advertencia)
        if ($producto->estado === 'inactivo') {
            session()->flash('warning', 'Este producto está inactivo. Los cambios no serán visibles para ventas hasta que se active.');
        }

        $categorias = Categoria::all(['id', 'nombre']);
        $marcas = Marca::all(['id', 'nombre']);
        $proveedores = Proveedor::all(['id', 'nombre_razon_social']);
        $almacenes = Almacen::all(['id', 'nombre']);
        $unidadesMedida = \App\Models\UnidadMedida::activas()->select('id', 'nombre', 'abreviatura')->get();

        // Obtener listas de precios activas
        $priceLists = \App\Models\PriceList::activas()
            ->get(['id', 'nombre', 'descripcion'])
            ->map(function ($lista) use ($producto) {
                // Buscar si existe un precio para esta lista
                $productPrice = $producto->productPrices->firstWhere('price_list_id', $lista->id);

                return [
                    'id' => $lista->id,
                    'nombre' => $lista->nombre,
                    'descripcion' => $lista->descripcion,
                    'precio' => $productPrice ? $productPrice->precio : null,
                ];
            });

        return Inertia::render('Productos/Edit', [
            'producto' => $producto,
            'categorias' => $categorias,
            'marcas' => $marcas,
            'proveedores' => $proveedores,
            'almacenes' => $almacenes,
            'unidadesMedida' => $unidadesMedida,
            'priceLists' => $priceLists,
            'defaults' => [
                'ivaPorcentaje' => \App\Services\EmpresaConfiguracionService::getIvaPorcentaje(),
            ],
            'satCatalogos' => [
                'unidades' => SatClaveUnidad::getOptions(),
                'objetosImp' => SatObjetoImp::getOptions(),
                'claveProdServActual' => $producto->satClaveProdServ
                    ? ['clave' => $producto->sat_clave_prod_serv, 'descripcion' => $producto->satClaveProdServ->descripcion]
                    : null,
            ]
        ]);
    }

    /**
     * Actualiza un producto existente en la base de datos.
     */
    public function update(Request $request, Producto $producto)
    {
        // Debug logging for update
        Log::debug('ProductoController@update - Request data', [
            'producto_id' => $producto->id,
            'stock_minimo_por_almacen' => $request->input('stock_minimo_por_almacen'),
            'precio_venta' => $request->input('precio_venta'),
            'precio_compra' => $request->input('precio_compra'),
            'has_image_file' => $request->hasFile('imagen'),
            'image_file_name' => $request->hasFile('imagen') ? $request->file('imagen')->getClientOriginalName() : null,
            'content_type' => $request->header('Content-Type'),
        ]);

        // FIX: Bloquear para prevenir race conditions
        DB::beginTransaction();

        try {
            // Recargar con lock
            $producto = Producto::where('id', $producto->id)->lockForUpdate()->firstOrFail();
            $empresaId = EmpresaResolver::resolveId();

            $validated = $request->validate([
                // Datos requeridos
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string|max:1000',
                'precio_venta' => 'required|numeric|min:0',
                'categoria_id' => 'required|exists:categorias,id',
                'proveedor_id' => 'nullable|exists:proveedores,id',

                // Validaciones para editar (permitir mismos códigos del propio producto)
                'codigo' => [
                    'nullable',
                    'string',
                    Rule::unique('productos', 'codigo')->ignore($producto->id),
                ],
                'codigo_barras' => [
                    'nullable',
                    'string',
                    Rule::unique('productos', 'codigo_barras')->ignore($producto->id),
                ],
                'marca_id' => 'required|exists:marcas,id',
                'almacen_id' => 'nullable|exists:almacenes,id',
                'expires' => 'boolean',
                'requiere_serie' => 'boolean',
                'precio_compra' => 'nullable|numeric|min:0',
                'unidad_medida' => 'required|string',
                'fecha_vencimiento' => 'nullable|date',
                'tipo_producto' => 'nullable|in:fisico,digital',
                'estado' => 'nullable|in:activo,inactivo',
                'comision_vendedor' => 'nullable|numeric|min:0|max:100',
                'sat_clave_prod_serv' => 'nullable|string|max:8',
                'sat_clave_unidad' => 'nullable|string|max:3',
                'sat_objeto_imp' => 'nullable|string|max:2',

                // Imagen
                'imagen' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',

                // Stock mínimo por almacén
                'stock_minimo_por_almacen' => 'nullable|array',
                'stock_minimo_por_almacen.*' => 'integer|min:0',
            ]);

            if ($request->hasFile('imagen')) {
                // Eliminar imagen anterior si existe
                if ($producto->imagen) {
                    Storage::disk('public')->delete($producto->imagen);
                }

                try {
                    $validated['imagen'] = $this->saveImageAsWebP($request->file('imagen'), 'productos');
                } catch (\Exception $e) {
                    Log::error("Error processing image (WebP conversion failed): " . $e->getMessage());
                    // Fallback to standard storage
                    $path = $request->file('imagen')->store('productos', 'public');
                    $validated['imagen'] = $path;
                }
            }

            // Stock is managed through purchases, so don't update it from the form
            // Keep the existing stock value
            unset($validated['stock']);

            // Set default values for missing fillable fields in update
            $validated['reservado'] = $validated['reservado'] ?? 0;
            $validated['expires'] = $validated['expires'] ?? false;
            $validated['requiere_serie'] = $validated['requiere_serie'] ?? false;
            $validated['margen_ganancia'] = $validated['margen_ganancia'] ?? 0;
            $validated['comision_vendedor'] = $validated['comision_vendedor'] ?? 0;

            // Actualizar stock mínimo por almacén
            if (!empty($validated['stock_minimo_por_almacen'])) {
                foreach ($validated['stock_minimo_por_almacen'] as $almacenId => $stockMinimo) {
                    \App\Models\Inventario::updateOrCreate(
                        [
                            'producto_id' => $producto->id,
                            'almacen_id' => $almacenId,
                        ],
                        [
                            'cantidad' => \App\Models\Inventario::where('producto_id', $producto->id)
                                ->where('almacen_id', $almacenId)
                                ->value('cantidad') ?? 0,
                            'stock_minimo' => $stockMinimo,
                        ]
                    );
                }
            }

            // Check for price changes before updating
            $precioCompraChanged = isset($validated['precio_compra']) && $validated['precio_compra'] != $producto->precio_compra;
            $precioVentaChanged = isset($validated['precio_venta']) && $validated['precio_venta'] != $producto->precio_venta;

            if ($precioCompraChanged || $precioVentaChanged) {
                ProductoPrecioHistorial::create([
                    'producto_id' => $producto->id,
                    'precio_compra_anterior' => $precioCompraChanged ? $producto->precio_compra : null,
                    'precio_compra_nuevo' => $precioCompraChanged ? $validated['precio_compra'] : $producto->precio_compra,
                    'precio_venta_anterior' => $precioVentaChanged ? $producto->precio_venta : null,
                    'precio_venta_nuevo' => $precioVentaChanged ? $validated['precio_venta'] : $producto->precio_venta,
                    'tipo_cambio' => 'manual',
                    'notas' => 'Actualización manual de precios',
                    'user_id' => Auth::id(),
                ]);
            }

            $producto->update($validated);

            DB::commit();

            return redirect()->route('productos.index')->with('success', 'Producto actualizado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error actualizando producto: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el producto: ' . $e->getMessage());
        }
    }

    /**
     * Actualiza solo la clave SAT de producto/servicio.
     */
    public function updateSat(Request $request, Producto $producto)
    {
        $validated = $request->validate([
            'sat_clave_prod_serv' => 'required|string|max:8',
            'sat_clave_unidad' => 'nullable|string|max:3',
            'sat_objeto_imp' => 'nullable|string|max:2',
        ]);

        $producto->update($validated);

        return response()->json([
            'message' => 'Clave SAT actualizada correctamente',
            'producto' => $producto,
        ]);
    }

    /**
     * Actualiza los precios del producto en todas las listas de precios
     */
    public function updatePrices(Request $request, Producto $producto)
    {
        $validated = $request->validate([
            'prices' => 'required|array',
            'prices.*.price_list_id' => 'required|exists:price_lists,id',
            'prices.*.precio' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['prices'] as $priceData) {
                $priceListId = $priceData['price_list_id'];
                $precio = $priceData['precio'];

                if ($precio === null || $precio === '') {
                    // Si el precio es null, eliminar el registro si existe
                    \App\Models\ProductPrice::where('producto_id', $producto->id)
                        ->where('price_list_id', $priceListId)
                        ->delete();
                } else {
                    // Crear o actualizar el precio
                    \App\Models\ProductPrice::updateOrCreate(
                        [
                            'producto_id' => $producto->id,
                            'price_list_id' => $priceListId,
                        ],
                        [
                            'precio' => $precio,
                        ]
                    );
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Precios actualizados correctamente',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar precios: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar los precios',
            ], 500);
        }
    }

    /**
     * Elimina un producto de la base de datos.
     */
    public function destroy(Producto $producto)
    {
        // FIX: Bloquear para prevenir race conditions
        DB::beginTransaction();

        try {
            // Recargar con lock
            $producto = Producto::where('id', $producto->id)->lockForUpdate()->firstOrFail();

            // Verificar si puede ser eliminado usando la nueva lógica
            if (!$this->canDeleteProducto($producto)) {
                $razon = $producto->estado === 'activo'
                    ? 'está activo'
                    : 'está siendo utilizado en documentos de negocio';
                DB::rollBack();
                return redirect()->back()->with('error', "No se puede eliminar el producto porque {$razon}.");
            }

            if ($producto->imagen) {
                Storage::disk('public')->delete($producto->imagen);
            }

            // FIX: Resetear stock antes de eliminar para evitar "ghost stock"
            $producto->stock = 0;
            $producto->save();

            // Resetear inventarios asociados
            $producto->inventarios()->update(['cantidad' => 0]);

            // Eliminar series asociadas (soft delete)
            $producto->series()->delete();

            $producto->delete();

            DB::commit();

            return redirect()->route('productos.index')->with('success', 'Producto eliminado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error eliminando producto: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al eliminar el producto: ' . $e->getMessage());
        }
    }

    /**
     * Muestra un producto en JSON (incluye URL de la imagen si existe).
     */
    public function show($id)
    {
        $producto = Producto::with(['categoria', 'marca', 'proveedor', 'almacen'])->find($id);

        if (!$producto) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        $producto->imagen_url = $producto->imagen ? $this->generateCorrectStorageUrl($producto->imagen) : null;

        return response()->json($producto);
    }

    /**
     * Devuelve las series del producto (en stock y vendidas) y sus conteos.
     */
    public function series(Request $request, Producto $producto): JsonResponse
    {
        try {
            $almacenId = $request->get('almacen_id');

            // Base query para series en stock
            $queryEnStock = DB::table('producto_series')
                ->where('producto_id', $producto->id)
                ->where('estado', 'en_stock')
                ->whereNull('deleted_at');

            // Base query para series vendidas
            $queryVendidas = DB::table('producto_series')
                ->where('producto_id', $producto->id)
                ->where('estado', 'vendido')
                ->whereNull('deleted_at');

            // Aplicar filtro de almacén si se especifica
            if ($almacenId) {
                $queryEnStock->where('almacen_id', $almacenId);
                $queryVendidas->where('almacen_id', $almacenId);
            }

            // Obtener series en stock
            $seriesEnStock = $queryEnStock
                ->orderBy('numero_serie')
                ->get(['id', 'numero_serie', 'estado', 'almacen_id', 'created_at']);

            // Obtener series vendidas
            $seriesVendidas = $queryVendidas
                ->orderBy('numero_serie')
                ->get(['id', 'numero_serie', 'estado', 'almacen_id', 'created_at']);

            // Agregar información de almacén a las series
            $seriesEnStock = $seriesEnStock->map(function ($serie) {
                $almacen = $serie->almacen_id ? Almacen::find($serie->almacen_id) : null;
                $serie->almacen_nombre = $almacen ? $almacen->nombre : 'Sin almacén';
                return $serie;
            });

            $seriesVendidas = $seriesVendidas->map(function ($serie) {
                $almacen = $serie->almacen_id ? Almacen::find($serie->almacen_id) : null;
                $serie->almacen_nombre = $almacen ? $almacen->nombre : 'Sin almacén';
                return $serie;
            });

            return response()->json([
                'producto' => [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'codigo' => $producto->codigo,
                    'requiere_serie' => (bool) ($producto->requiere_serie ?? false),
                    'stock_total' => (int) ($producto->stock ?? 0),
                ],
                'counts' => [
                    'en_stock' => $seriesEnStock->count(),
                    'vendido' => $seriesVendidas->count(),
                ],
                'series' => [
                    'en_stock' => $seriesEnStock,
                    'vendido' => $seriesVendidas,
                ],
                'almacenes' => \App\Models\Almacen::select('id', 'nombre')->where('estado', 'activo')->orderBy('nombre')->get(),
                'filtro_almacen' => $almacenId,
            ]);
        } catch (\Exception $e) {
            Log::error('Error en ProductoController@series: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al cargar las series',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registra series nuevas para un producto (no genera números, solo captura los proporcionados).
     */
    public function storeSeries(Request $request, Producto $producto): JsonResponse
    {
        $validated = $request->validate([
            'series' => 'required|array|min:1',
            'series.*' => 'required|string|max:191|distinct',
            'almacen_id' => 'required|exists:almacenes,id',
        ]);

        // Evitar duplicados (incluyendo soft deletes para no reutilizar números)
        $duplicadas = DB::table('producto_series')
            ->where('producto_id', $producto->id)
            ->whereIn('numero_serie', $validated['series'])
            ->pluck('numero_serie')
            ->all();

        if (!empty($duplicadas)) {
            return response()->json([
                'error' => 'Las siguientes series ya existen para este producto: ' . implode(', ', $duplicadas),
            ], 422);
        }

        foreach ($validated['series'] as $numeroSerie) {
            ProductoSerie::create([
                'producto_id' => $producto->id,
                'almacen_id' => $validated['almacen_id'],
                'numero_serie' => trim($numeroSerie),
                'estado' => 'en_stock',
            ]);
        }

        return response()->json([
            'success' => true,
            'creadas' => count($validated['series']),
        ]);
    }



    /**
     * Actualiza el número de serie (solo en_stock).
     */
    public function updateSerie(Request $request, Producto $producto, $serieId): JsonResponse
    {
        try {
            $request->validate([
                'numero_serie' => 'required|string|max:255',
            ]);

            $serie = DB::table('producto_series')
                ->where('id', $serieId)
                ->where('producto_id', $producto->id)
                ->whereNull('deleted_at')
                ->first();

            if (!$serie) {
                return response()->json([
                    'error' => 'Serie no encontrada'
                ], 404);
            }

            if ($serie->estado !== 'en_stock') {
                return response()->json([
                    'error' => 'Solo se pueden modificar series que estén en stock.'
                ], 422);
            }

            // Verificar que el número de serie no exista para otro producto
            $existe = DB::table('producto_series')
                ->where('numero_serie', $request->numero_serie)
                ->where('producto_id', $producto->id)
                ->where('id', '!=', $serieId)
                ->whereNull('deleted_at')
                ->exists();

            if ($existe) {
                return response()->json([
                    'error' => 'El número de serie ya existe para este producto'
                ], 422);
            }

            DB::table('producto_series')
                ->where('id', $serieId)
                ->update([
                    'numero_serie' => trim($request->numero_serie),
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Serie actualizada correctamente.',
                'serie' => [
                    'id' => $serieId,
                    'numero_serie' => trim($request->numero_serie),
                    'estado' => $serie->estado,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error en ProductoController@updateSerie: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al actualizar la serie',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vista de inventario del producto.
     */
    public function showInventario($id)
    {
        $producto = Producto::findOrFail($id);
        $inventarios = $producto->inventarios;

        return Inertia::render('Productos/Inventario', [
            'producto' => $producto,
            'inventarios' => $inventarios,
        ]);
    }

    /**
     * Valida stock/precios de una lista de items (producto/servicio).
     */
    public function validateStock(Request $request): JsonResponse
    {
        $request->validate([
            'productos' => 'required|array',
            'productos.*.id' => 'required|integer',
            'productos.*.tipo' => 'required|string|in:producto,servicio',
            'productos.*.cantidad' => 'required|numeric|min:1',
            'productos.*.precio' => 'required|numeric|min:0',
        ]);

        $items = $request->input('productos');
        $errors = [];
        $pricesUpdated = [];
        $valid = true;

        foreach ($items as $item) {
            if ($item['tipo'] === 'producto') {
                // Validar stock de productos
                $producto = Producto::find($item['id']);

                if (!$producto) {
                    $errors[] = [
                        'producto' => "Producto ID {$item['id']}",
                        'mensaje' => 'Producto no encontrado',
                    ];
                    $valid = false;
                    continue;
                }

                if ($producto->stock_disponible < $item['cantidad']) {
                    $errors[] = [
                        'producto' => $producto->nombre,
                        'mensaje' => "Stock insuficiente. Disponible: {$producto->stock_disponible}, Solicitado: {$item['cantidad']}",
                    ];
                    $valid = false;
                }

                // Verificar si el precio ha cambiado
                if (isset($item['precio']) && (float) $producto->precio_venta !== (float) $item['precio']) {
                    $pricesUpdated[] = [
                        'id' => $producto->id,
                        'tipo' => 'producto',
                        'nombre' => $producto->nombre,
                        'nuevoPrecio' => $producto->precio_venta,
                    ];
                }
            } else {
                // Servicios: solo verificar existencia
                $servicio = Servicio::find($item['id']);

                if (!$servicio) {
                    $errors[] = [
                        'producto' => "Servicio ID {$item['id']}",
                        'mensaje' => 'Servicio no encontrado',
                    ];
                    $valid = false;
                }
            }
        }

        return response()->json([
            'valid' => $valid,
            'errors' => $errors,
            'pricesUpdated' => $pricesUpdated,
        ]);
    }

    /**
     * Exporta productos a CSV
     */
    public function export(Request $request)
    {
        try {
            $query = Producto::query()->with(['categoria', 'marca', 'proveedor', 'almacen']);

            // Aplicar los mismos filtros que en index
            if ($search = trim($request->input('search', ''))) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('codigo', 'like', "%{$search}%")
                        ->orWhere('codigo_barras', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%");
                });
            }

            if ($estado = $request->input('estado')) {
                if ($estado === 'activo') {
                    $query->where('estado', 'activo');
                } elseif ($estado === 'inactivo') {
                    $query->where('estado', 'inactivo');
                } elseif ($estado === 'agotado') {
                    $query->where('stock', '<=', 0);
                }
            }

            $productos = $query->get();

            $filename = 'productos_' . date('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($productos) {
                $file = fopen('php://output', 'w');

                fputcsv($file, [
                    'ID',
                    'Nombre',
                    'Código',
                    'Código de Barras',
                    'Descripción',
                    'Categoría',
                    'Marca',
                    'Proveedor',
                    'Precio Venta',
                    'Stock',
                    'Stock Mínimo',
                    'Estado',
                    'Fecha Creación'
                ]);

                foreach ($productos as $producto) {
                    fputcsv($file, [
                        $producto->id,
                        $producto->nombre,
                        $producto->codigo,
                        $producto->codigo_barras,
                        $producto->descripcion,
                        $producto->categoria?->nombre ?? '',
                        $producto->marca?->nombre ?? '',
                        $producto->proveedor?->nombre_razon_social ?? '',
                        $producto->precio_venta,
                        $producto->stock,
                        $producto->stock_minimo,
                        $producto->estado,
                        $producto->created_at?->format('d/m/Y H:i:s')
                    ]);
                }
                fclose($file);
            };

            Log::info('Exportación de productos', ['total' => $productos->count()]);

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Error en exportación de productos: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al exportar los productos.');
        }
    }

    /**
     * Alterna el estado de un producto (activo/inactivo).
     */
    public function toggle(Producto $producto)
    {
        try {
            $producto->update(['estado' => $producto->estado === 'activo' ? 'inactivo' : 'activo']);

            $mensaje = $producto->estado === 'activo' ? 'Producto activado correctamente' : 'Producto desactivado correctamente';

            return redirect()->back()->with('success', $mensaje);
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado del producto: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cambiar el estado del producto.');
        }
    }

    /**
     * Obtiene el detalle de stock por almacén para un producto.
     */
    public function getStockDetalle($id)
    {
        $producto = Producto::findOrFail($id);

        $stockPorAlmacen = \App\Models\Inventario::with('almacen')
            ->where('producto_id', $id)
            ->where('cantidad', '>', 0)
            ->get()
            ->map(function ($inventario) {
                return [
                    'almacen_id' => $inventario->almacen_id,
                    'almacen_nombre' => $inventario->almacen->nombre,
                    'cantidad' => $inventario->cantidad,
                    'stock_minimo' => $inventario->stock_minimo,
                ];
            });

        return response()->json([
            'producto' => [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'codigo' => $producto->codigo,
                'stock_total' => $producto->stock,
            ],
            'stock_por_almacen' => $stockPorAlmacen,
        ]);
    }

    /**
     * Verifica si un producto puede ser eliminado.
     * Reglas:
     * - Solo productos inactivos pueden ser eliminados
     * - No debe estar siendo usado en ningún documento de negocio
     */
    private function canDeleteProducto(Producto $producto): bool
    {
        // Solo productos inactivos pueden ser eliminados
        if ($producto->estado === 'activo') {
            return false;
        }

        // Verificar si está siendo usado en documentos de negocio
        if ($producto->cotizacionItems()->count() > 0) {
            return false; // Tiene cotizaciones
        }

        if ($producto->pedidoItems()->count() > 0) {
            return false; // Tiene pedidos
        }

        if ($producto->ventaItems()->count() > 0) {
            return false; // Tiene ventas
        }

        if ($producto->compras()->count() > 0) {
            return false; // Tiene compras
        }

        if ($producto->ordenesCompra()->count() > 0) {
            return false; // Tiene órdenes de compra
        }

        return true; // Puede ser eliminado
    }

    /**
     * Recalcula precios de productos según la lista de precios seleccionada
     */
    public function recalcularPrecios(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'productos' => 'required|array',
            'productos.*.id' => 'required|integer',
            'productos.*.tipo' => 'required|string|in:producto,servicio',
            'price_list_id' => 'nullable|exists:price_lists,id',
            'almacen_id' => 'required|exists:almacenes,id',
        ]);

        try {
            $precios = [];
            $precioService = app(\App\Services\PrecioService::class);

            foreach ($validated['productos'] as $productoData) {
                $key = $productoData['tipo'] . '-' . $productoData['id'];

                if ($productoData['tipo'] === 'producto') {
                    $producto = Producto::find($productoData['id']);
                    if ($producto) {
                        $precio = $precioService->obtenerPrecio(
                            $producto,
                            $validated['price_list_id'],
                            $validated['almacen_id']
                        );
                        $precios[$key] = $precio;
                    }
                } else {
                    // Para servicios, usar precio estándar por ahora
                    $servicio = Servicio::find($productoData['id']);
                    if ($servicio) {
                        $precios[$key] = $servicio->precio;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'precios' => $precios,
            ]);

        } catch (\Exception $e) {
            Log::error('Error recalculando precios: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al recalcular precios',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generar URL de storage correcta independientemente de APP_URL
     */
    private function generateCorrectStorageUrl($path)
    {
        $scheme = request()->isSecure() ? 'https' : 'http';
        $host = request()->getHost();
        $port = request()->getPort();

        // No agregar puerto si es el puerto estándar
        $portString = (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) ? ':' . $port : '';

        return "{$scheme}://{$host}{$portString}/storage/{$path}";
    }
    /**
     * Helper para guardar imagen como WebP
     */
    private function saveImageAsWebP($file, $path = 'productos', $disk = 'public')
    {
        // Ensure directory exists
        $fullPathDir = storage_path("app/public/{$path}");
        if (!file_exists($fullPathDir)) {
            mkdir($fullPathDir, 0755, true);
        }

        // Crear imagen desde string
        $imageString = file_get_contents($file);
        $image = @imagecreatefromstring($imageString);

        if (!$image) {
            throw new \Exception("Formato de imagen no soportado o archivo corrupto.");
        }

        // Generar nombre único
        $filename = uniqid() . '.webp';
        $fullPath = "{$fullPathDir}/{$filename}";

        // Convertir a WebP (calidad 80)
        imagewebp($image, $fullPath, 80);
        imagedestroy($image);

        return "{$path}/{$filename}";
    }

    /**
     * Alternar el estado de destacado de un producto para la landing page.
     */
    public function toggleDestacado(Producto $producto)
    {
        $producto->update(['destacado' => !$producto->destacado]);

        return back()->with('success', 'Visibilidad en index actualizada.');
    }
}

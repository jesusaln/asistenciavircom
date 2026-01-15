<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\ProductoSerie;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Proveedor;
use App\Models\Almacen;
use App\Models\ProductoPrecioHistorial;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Traits\ImageOptimizerTrait;

class ProductoController extends Controller
{
    use ImageOptimizerTrait;
    /**
     * Obtener el siguiente código disponible para un nuevo producto.
     */
    public function nextCodigo()
    {
        try {
            $siguienteCodigo = Producto::generateNextCodigo();
            return response()->json([
                'success' => true,
                'data' => [
                    'siguiente_codigo' => $siguienteCodigo
                ],
                'message' => 'Código siguiente disponible obtenido correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el siguiente código',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar todos los productos con paginación y filtros.
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
                } elseif ($estado === 'agotado' || $estado === 'sin_stock') {
                    $query->where('stock', '<=', 0);
                } elseif ($estado === 'con_stock') {
                    $query->where('stock', '>', 0);
                }
            }

            if ($categoriaId = $request->input('categoria_id')) {
                $query->where('categoria_id', $categoriaId);
            }

            if ($almacenId = $request->input('almacen_id')) {
                $query->whereHas('inventarios', function ($inv) use ($almacenId) {
                    $inv->where('almacen_id', $almacenId);
                });
            }

            // Ordenamiento
            $sortBy = $request->input('sort_by', 'nombre');
            $sortDirection = $request->input('sort_direction', 'asc');

            $validSortFields = ['nombre', 'codigo', 'precio_venta', 'stock', 'created_at', 'updated_at'];
            if (!in_array($sortBy, $validSortFields)) {
                $sortBy = 'nombre';
            }

            $query->orderBy($sortBy, $sortDirection);

            // Paginación
            $perPage = min((int) $request->input('per_page', 30), 100);

            if ($request->has('nopaginate') || $request->input('all') == '1') {
                $productos = $query->get()->map(function ($producto) {
                    $producto->imagen_url = $producto->imagen ? $this->generateCorrectStorageUrl($producto->imagen) : null;
                    return $producto;
                });
                return response()->json([
                    'success' => true,
                    'data' => $productos
                ]);
            }

            $paginator = $query->paginate($perPage);

            // Transformar items
            $paginator->getCollection()->transform(function ($producto) {
                $producto->imagen_url = $producto->imagen ? $this->generateCorrectStorageUrl($producto->imagen) : null;

                $producto->stock_por_almacen = $producto->inventarios()
                    ->with('almacen:id,nombre')
                    ->where('cantidad', '>', 0)
                    ->get()
                    ->map(function ($inv) {
                        return [
                            'almacen_id' => $inv->almacen_id,
                            'almacen_nombre' => $inv->almacen?->nombre ?? 'Desconocido',
                            'cantidad' => $inv->cantidad,
                        ];
                    });

                return $producto;
            });

            // Estadísticas
            $stats = [];
            if ($request->boolean('with_stats')) {
                $stats = [
                    'total' => Producto::count(),
                    'activos' => Producto::where('estado', 'activo')->count(),
                    'inactivos' => Producto::where('estado', 'inactivo')->count(),
                    'con_stock' => Producto::where('stock', '>', 0)->count(),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error en ProductoController@index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los productos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo producto.
     */
    public function store(Request $request)
    {
        Log::debug('Api\ProductoController@store', $request->all());

        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'codigo' => 'nullable|string|unique:productos,codigo',
                'codigo_barras' => 'nullable|string|unique:productos,codigo_barras',
                'categoria_id' => 'nullable|exists:categorias,id',
                'marca_id' => 'nullable|exists:marcas,id',
                'proveedor_id' => 'nullable|exists:proveedores,id',
                'almacen_id' => 'nullable|exists:almacenes,id',
                'stock' => 'nullable|integer|min:0', // Legacy, se prefiere inventario por almacen
                'stock_minimo' => 'nullable|integer|min:0', // Legacy
                'precio_compra' => 'required|numeric|min:0',
                'precio_venta' => 'required|numeric|min:0',
                'impuesto' => 'nullable|numeric|min:0',
                'unidad_medida' => 'required|string',
                'fecha_vencimiento' => 'nullable|date',
                'tipo_producto' => 'required|in:fisico,digital',
                'requiere_serie' => 'boolean',
                'imagen' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'estado' => 'required|in:activo,inactivo',

                // Nuevos campos
                'expires' => 'boolean',
                'comision_vendedor' => 'nullable|numeric|min:0|max:100',
                'stock_minimo_por_almacen' => 'nullable|array',
                'series' => 'nullable|array',
                'series.*' => 'nullable|string|max:100',
            ]);

            DB::beginTransaction();

            // Defaults y Generación de Códigos
            if (empty($validated['codigo_barras'])) {
                $validated['codigo_barras'] = 'GEN-' . time() . rand(100, 999);
            }

            $validated['stock'] = $validated['stock'] ?? 0;
            $validated['stock_minimo'] = $validated['stock_minimo'] ?? 0;
            $validated['impuesto'] = $validated['impuesto'] ?? 0;
            $validated['reservado'] = 0;
            $validated['margen_ganancia'] = 0;

            if ($request->hasFile('imagen')) {
                $validated['imagen'] = $this->saveImageAsWebP($request->file('imagen'), 'productos');
            }

            $producto = Producto::create($validated);

            // Crear registros de inventario
            if (!empty($validated['stock_minimo_por_almacen'])) {
                foreach ($validated['stock_minimo_por_almacen'] as $almacenId => $stockMinimo) {
                    \App\Models\Inventario::create([
                        'producto_id' => $producto->id,
                        'almacen_id' => $almacenId,
                        'cantidad' => 0,
                        'stock_minimo' => $stockMinimo,
                    ]);
                }
            } else {
                // Si no se especifica, crear inventario básico para el almacén seleccionado o todos
                // Por ahora, lógica simple: si viene stock inicial, asignarlo al almacén por defecto o el seleccionado
                if ($validated['stock'] > 0 && isset($validated['almacen_id'])) {
                    \App\Models\Inventario::updateOrCreate(
                        ['producto_id' => $producto->id, 'almacen_id' => $validated['almacen_id']],
                        ['cantidad' => $validated['stock'], 'stock_minimo' => $validated['stock_minimo'] ?? 0]
                    );
                }
            }

            // Historial de Precios
            ProductoPrecioHistorial::create([
                'producto_id' => $producto->id,
                'precio_compra_nuevo' => $producto->precio_compra,
                'precio_venta_nuevo' => $producto->precio_venta,
                'tipo_cambio' => 'creacion',
                'notas' => 'Creación desde API',
                'user_id' => Auth::id() ?? 1, // Fallback si no hay auth user (ej. token máquina)
            ]);

            // Crear series si requiere_serie y hay series proporcionadas
            if (!empty($validated['requiere_serie']) && $request->has('series')) {
                $seriesData = $request->input('series', []);
                $almacenId = $validated['almacen_id'] ?? Almacen::where('estado', 'activo')->first()?->id;

                foreach ($seriesData as $numeroSerie) {
                    if (!empty(trim($numeroSerie))) {
                        ProductoSerie::create([
                            'producto_id' => $producto->id,
                            'almacen_id' => $almacenId,
                            'numero_serie' => trim($numeroSerie),
                            'estado' => 'en_stock',
                        ]);
                    }
                }

                Log::debug("Created " . count($seriesData) . " series for producto {$producto->id}");
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Producto creado correctamente',
                'data' => $producto
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error store producto api: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar un producto específico.
     */
    public function show($id)
    {
        $producto = Producto::with(['categoria', 'marca', 'proveedor', 'almacen', 'inventarios.almacen', 'series'])->find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        if ($producto->imagen) {
            $producto->imagen_url = $this->generateCorrectStorageUrl($producto->imagen);
        } else {
            $producto->imagen_url = null;
        }

        // Si es un producto CVA, obtener stock detallado en tiempo real
        if ($producto->origen === 'CVA') {
            try {
                $cva = app(\App\Services\CVAService::class);
                $clave = $producto->cva_clave ?: $producto->codigo;
                // Limpiar clave de prefijo CVA- si lo tiene
                $clave = str_replace('CVA-', '', $clave);

                $details = $cva->getProductDetails($clave, false); // false para datos crudos de CVA
                if ($details) {
                    $producto->stock_desglose = $cva->parseBranchAvailability($details);
                    // Sincronizar stock total también
                    $totalStock = array_sum($producto->stock_desglose);
                    if ($totalStock > 0) {
                        $producto->stock = $totalStock;
                    }
                }
            } catch (\Exception $e) {
                \Log::error("Error al obtener stock detallado CVA: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'data' => $producto
        ]);
    }

    /**
     * Actualizar un producto existente.
     */
    public function update(Request $request, $id)
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }

        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'codigo' => 'nullable|string|unique:productos,codigo,' . $producto->id,
                'codigo_barras' => 'nullable|string|unique:productos,codigo_barras,' . $producto->id,
                'categoria_id' => 'required|exists:categorias,id',
                'marca_id' => 'required|exists:marcas,id',
                'proveedor_id' => 'nullable|exists:proveedores,id',
                'almacen_id' => 'nullable|exists:almacenes,id',
                'precio_compra' => 'required|numeric|min:0',
                'precio_venta' => 'required|numeric|min:0',
                'impuesto' => 'nullable|numeric|min:0',
                'unidad_medida' => 'required|string',
                'fecha_vencimiento' => 'nullable|date',
                'tipo_producto' => 'required|in:fisico,digital',
                'requiere_serie' => 'boolean',
                'imagen' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
                'estado' => 'required|in:activo,inactivo',

                // Nuevos campos
                'expires' => 'boolean',
                'comision_vendedor' => 'nullable|numeric|min:0|max:100',
                'stock_minimo_por_almacen' => 'nullable|array',
            ]);

            DB::beginTransaction();

            // Lock para evitar race conditions
            $producto = Producto::where('id', $producto->id)->lockForUpdate()->first();

            if ($request->hasFile('imagen')) {
                if ($producto->imagen) {
                    Storage::disk('public')->delete($producto->imagen);
                }
                // Try catch specific for image processing
                try {
                    $validated['imagen'] = $this->saveImageAsWebP($request->file('imagen'), 'productos');
                } catch (\Exception $e) {
                    Log::error("Error processing image: " . $e->getMessage());
                    // Fallback: save original if conversion fails
                    $path = $request->file('imagen')->store('productos', 'public');
                    $validated['imagen'] = $path;
                }
            }

            // Detectar cambios de precio
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
                    'notas' => 'Actualización desde API',
                    'user_id' => Auth::id() ?? 1,
                ]);
            }

            // Actualizar inventarios
            if (!empty($validated['stock_minimo_por_almacen'])) {
                foreach ($validated['stock_minimo_por_almacen'] as $almacenId => $stockMinimo) {
                    \App\Models\Inventario::updateOrCreate(
                        ['producto_id' => $producto->id, 'almacen_id' => $almacenId],
                        [
                            'stock_minimo' => $stockMinimo,
                            // No cambiamos cantidad aquí, eso es vía compras/ajustes
                        ]
                    );
                }
            }

            $producto->update($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado correctamente',
                'data' => $producto
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error("Validation Error Update Product: ", $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("General Error Update Product: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un producto.
     */
    public function destroy($id)
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }

        DB::beginTransaction();
        try {
            // Verificar dependencias si es necesario (ventas, compras)

            if ($producto->imagen) {
                Storage::disk('public')->delete($producto->imagen);
            }

            // Eliminar series
            $producto->series()->delete();
            // Eliminar inventarios
            $producto->inventarios()->delete();

            $producto->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Devuelve las series del producto disponibles para venta, filtradas opcionalmente por almacén.
     */
    public function series(Request $request, $id)
    {
        try {
            $producto = Producto::find($id);
            if (!$producto) {
                return response()->json(['message' => 'Producto no encontrado'], 404);
            }

            $almacenId = $request->get('almacen_id');

            // Query base para series en stock (disponibles para venta)
            $queryBase = ProductoSerie::where('producto_id', $producto->id)
                ->where('estado', 'en_stock')
                ->whereNull('deleted_at');

            // Si hay filtro de almacén, priorizar y verificar si hay stock
            if ($almacenId) {
                // Clonar query para consulta local
                $queryLocal = (clone $queryBase)->where('almacen_id', $almacenId);
                $countLocal = $queryLocal->count();

                if ($countLocal > 0) {
                    // Hay series en el almacén local, devolver SOLO esas
                    $seriesResult = $queryLocal->orderBy('numero_serie')->with('almacen:id,nombre')->get();
                } else {
                    // NO hay series en el almacén local, devolver series de OTROS almacenes
                    $seriesResult = $queryBase->where('almacen_id', '!=', $almacenId)
                        ->orderBy('numero_serie')
                        ->with('almacen:id,nombre')
                        ->get();
                }
            } else {
                // Sin filtro de almacén, devolver todas
                $seriesResult = $queryBase->orderBy('numero_serie')->with('almacen:id,nombre')->get();
            }

            // Formatear respuesta
            $seriesEnStock = $seriesResult->map(function ($serie) {
                return [
                    'id' => $serie->id,
                    'numero_serie' => $serie->numero_serie,
                    'estado' => $serie->estado,
                    'almacen_id' => $serie->almacen_id,
                    'almacen_nombre' => $serie->almacen ? $serie->almacen->nombre : 'Desconocido',
                    'created_at' => $serie->created_at,
                ];
            });

            return response()->json([
                'producto' => [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'codigo' => $producto->codigo,
                    'requiere_serie' => (bool) ($producto->requiere_serie ?? false) || ($producto->maneja_series ?? false),
                ],
                'series' => $seriesEnStock,
                'count' => $seriesEnStock->count(),
                'filtro_almacen' => $almacenId,
            ]);

        } catch (\Exception $e) {
            Log::error('Error en Api\ProductoController@series: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al cargar las series',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar URL de storage correcta independientemente de APP_URL
     * Si el path ya es una URL absoluta (externa), devolverla tal cual.
     */
    private function generateCorrectStorageUrl($path)
    {
        // Si ya es una URL absoluta (externa como CVA, etc.), devolverla sin modificar
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $scheme = request()->isSecure() ? 'https' : 'http';
        $host = request()->getHost();
        $port = request()->getPort();

        // No agregar puerto si es el puerto estándar
        $portString = (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) ? ':' . $port : '';

        return "{$scheme}://{$host}{$portString}/storage/{$path}";
    }
}


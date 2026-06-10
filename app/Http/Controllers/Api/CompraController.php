<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\Almacen;
use App\Models\Producto;
use App\Models\ProductoSerie;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Http\Controllers\Traits\ApiResponse;

class CompraController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly InventarioService $inventarioService
    ) {
    }

    /**
     * Listado de compras.
     */
    public function index(Request $request)
    {
        try {
            $query = Compra::with(['proveedor', 'almacen', 'createdBy']);

            if ($request->filled('q')) {
                $q = $request->query('q');
                $query->where(function($sub) use ($q) {
                    $sub->where('numero_compra', 'LIKE', "%$q%")
                        ->orWhereHas('proveedor', function($p) use ($q) {
                            $p->where('nombre_razon_social', 'LIKE', "%$q%");
                        });
                });
            }

            if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
                $tz = config('app.timezone');
                $desde = Carbon::createFromFormat('Y-m-d', $request->fecha_desde, $tz)->startOfDay();
                $hasta = Carbon::createFromFormat('Y-m-d', $request->fecha_hasta, $tz)->endOfDay();
                $query->whereBetween('created_at', [$desde, $hasta]);
            }

            $perPage = $request->query('per_page', 15);
            $paginator = $query->orderByDesc('created_at')->paginate((int) $perPage);

            return $this->success([
                'items' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error en Api\CompraController@index: ' . $e->getMessage());
            return $this->serverError('Error al obtener las compras', $e);
        }
    }

    /**
     * Detalle de una compra.
     */
    public function show($id)
    {
        try {
            Log::info("Consultando compra ID: $id");
            $compra = Compra::with([
                'proveedor',
                'almacen',
                'compraItems.series',
                'compraItems.comprable',
                'createdBy'
            ])->findOrFail($id);

            return $this->success($compra);
        } catch (\Exception $e) {
            Log::error("Error en show compra $id: " . $e->getMessage());
            return $this->notFound('Compra no encontrada');
        }
    }

    /**
     * Crear una nueva compra.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'proveedor_id' => 'required|exists:proveedores,id',
                'almacen_id' => 'required|exists:almacenes,id',
                'items' => 'required|array|min:1',
                'items.*.id' => 'required|exists:productos,id',
                'items.*.cantidad' => 'required|integer|min:1',
                'items.*.precio' => 'required|numeric|min:0',
                'items.*.series' => 'nullable|array',
                'notas' => 'nullable|string',
            ]);

            DB::beginTransaction();

            $almacenId = $validatedData['almacen_id'];
            $total = 0;
            foreach ($validatedData['items'] as $item) {
                $total += $item['cantidad'] * $item['precio'];
            }
            $totalConIva = $total * 1.16;

            // Obtener siguiente número
            $ultimo = Compra::orderByDesc('id')->first();
            $ultimoNum = 0;
            if ($ultimo && preg_match('/(\d+)$/', $ultimo->numero_compra, $m)) {
                $ultimoNum = (int) $m[1];
            }
            $numeroCompra = 'COM' . str_pad($ultimoNum + 1, 4, '0', STR_PAD_LEFT);

            $compra = Compra::create([
                'proveedor_id' => $validatedData['proveedor_id'],
                'almacen_id' => $almacenId,
                'numero_compra' => $numeroCompra,
                'fecha_compra' => now(),
                'total' => $totalConIva,
                'estado' => 'pendiente',
                'notas' => $validatedData['notas'] ?? null,
                'created_by' => auth()->id()
            ]);

            foreach ($validatedData['items'] as $itemData) {
                $producto = Producto::find($itemData['id']);
                
                $item = $compra->compraItems()->create([
                    'comprable_id' => $producto->id,
                    'comprable_type' => Producto::class,
                    'cantidad' => $itemData['cantidad'],
                    'precio' => $itemData['precio'],
                    'subtotal' => $itemData['cantidad'] * $itemData['precio'],
                    'descuento' => 0,
                    'descuento_monto' => 0,
                ]);

                // Ajustar inventario (Entrada)
                $this->inventarioService->entrada(
                    $producto,
                    $itemData['cantidad'],
                    [
                        'motivo' => "Compra $numeroCompra",
                        'almacen_id' => $almacenId,
                        'referencia' => $compra
                    ]
                );

                // Registrar series si aplica
                if ($producto->maneja_series && !empty($itemData['series'])) {
                    foreach ($itemData['series'] as $numeroSerie) {
                        $serie = ProductoSerie::create([
                            'producto_id' => $producto->id,
                            'almacen_id' => $almacenId,
                            'numero_serie' => $numeroSerie,
                            'estado' => 'en_stock',
                            'compra_id' => $compra->id,
                            'compra_item_id' => $item->id
                        ]);
                    }
                }
            }

            DB::commit();

            return $this->created($compra->load(['proveedor', 'compraItems.series', 'compraItems.comprable', 'almacen']), 'Compra registrada correctamente');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en Api\CompraController@store: ' . $e->getMessage());
            return $this->serverError('Error al registrar la compra', $e);
        }
    }

    /**
     * Siguiente número de compra.
     */
    public function siguienteNumero()
    {
        try {
            $ultimo = Compra::orderByDesc('id')->value('numero_compra');
            $max = 0;
            if ($ultimo && preg_match('/(\d+)$/', $ultimo, $m)) {
                $max = (int) $m[1];
            }
            $siguiente = $max + 1;
            $numero = 'COM' . str_pad($siguiente, 4, '0', STR_PAD_LEFT);

            return $this->success([
                'siguiente_numero' => $numero
            ]);
        } catch (\Exception $e) {
            return $this->serverError('Error al obtener número de compra', $e);
        }
    }
}

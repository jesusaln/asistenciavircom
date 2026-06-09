<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\ProductoSerie;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TempDebugController extends Controller
{
    public function testVentaSeries()
    {
        DB::beginTransaction();
        try {
            // 1. Setup Data
            $user = User::first();
            Auth::login($user);

            $almacen = Almacen::firstOrCreate(
                ['nombre' => 'Almacen Test'],
                ['estado' => 'activo', 'ubicacion' => 'Test']
            );

            $cliente = Cliente::firstOrCreate(
                ['email' => 'test@cliente.com'],
                ['nombre_razon_social' => 'Cliente Test', 'rfc' => 'XAXX010101000', 'telefono' => '1234567890']
            );

            // Create Product with Series
            $producto = Producto::create([
                'nombre' => 'Producto Test Series ' . uniqid(),
                'codigo' => 'TEST-' . uniqid(),
                'precio_compra' => 100,
                'precio_venta' => 200,
                'stock' => 10, // Initial stock
                'requiere_serie' => true,
                'estado' => 'activo',
                'categoria_id' => 1, // Assuming cat 1 exists
                'marca_id' => 1, // Assuming brand 1 exists
            ]);

            // Create 2 Series
            $serie1 = 'S-' . uniqid();
            $serie2 = 'S-' . uniqid();

            ProductoSerie::create([
                'producto_id' => $producto->id,
                'almacen_id' => $almacen->id,
                'numero_serie' => $serie1,
                'estado' => 'en_stock',
            ]);

            ProductoSerie::create([
                'producto_id' => $producto->id,
                'almacen_id' => $almacen->id,
                'numero_serie' => $serie2,
                'estado' => 'en_stock',
            ]);

            // 2. Simulate Request
            $requestData = [
                'cliente_id' => $cliente->id,
                'almacen_id' => $almacen->id,
                'metodo_pago' => 'efectivo',
                'productos' => [
                    [
                        'id' => $producto->id,
                        'cantidad' => 2,
                        'precio' => 200,
                        'series' => [$serie1, $serie2]
                    ]
                ]
            ];

            $request = new Request($requestData);
            $request->setMethod('POST');
            
            // Call VentaController store
            $controller = app(VentaController::class);
            
            // We need to mock the validation or ensure data passes validation
            // Since we call store() directly, it will validate $request
            
            // Note: store() returns a RedirectResponse or JsonResponse or the Venta object inside transaction?
            // In VentaController::store, it returns redirect()->route(...) on success.
            // But inside the transaction closure it returns $venta.
            // The method itself returns redirect.
            
            // We can't easily capture the return of store() if it redirects.
            // But we can check the DB after execution.
            
            try {
                $response = $controller->store($request);
            } catch (\Exception $e) {
                // If it redirects back with errors, we want to know
                if (session()->has('errors')) {
                    throw new \Exception('Validation Errors: ' . json_encode(session('errors')->all()));
                }
                throw $e;
            }

            // 3. Verify Results
            $venta = Venta::latest()->first();
            
            // Check Stock
            $producto->refresh();
            $stockReduced = $producto->stock == 8; // Started with 10, sold 2
            
            // Check Series Status
            $s1 = ProductoSerie::where('numero_serie', $serie1)->first();
            $s2 = ProductoSerie::where('numero_serie', $serie2)->first();
            
            $seriesSold = ($s1->estado === 'vendido') && ($s2->estado === 'vendido');
            $seriesLinked = ($s1->venta_id == $venta->id) && ($s2->venta_id == $venta->id);
            
            // Check VentaItemSeries
            $item = $venta->items->first();
            $itemSeriesCount = $item->series->count();
            
            $results = [
                'success' => $stockReduced && $seriesSold && $seriesLinked && ($itemSeriesCount == 2),
                'checks' => [
                    'stock_reduced_correctly' => $stockReduced,
                    'current_stock' => $producto->stock,
                    'expected_stock' => 8,
                    'series_marked_sold' => $seriesSold,
                    'series_linked_to_venta' => $seriesLinked,
                    'venta_item_series_count' => $itemSeriesCount,
                    'expected_series_count' => 2,
                ],
                'venta_id' => $venta->id,
                'debug_info' => [
                    'serie1_status' => $s1->estado,
                    'serie2_status' => $s2->estado,
                ]
            ];

            DB::rollBack(); // Always rollback test data
            
            return response()->json($results);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'validation_errors' => session('errors') ? session('errors')->all() : null
            ], 500);
        }
    }
}

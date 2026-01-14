<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\User;
use App\Models\Proveedor;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\ProductoSerie;
use App\Http\Controllers\CompraController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Enums\EstadoCompra;

class TestCompraFlujoCompleto extends Command
{
    protected $signature = 'inventario:test-compra-completo';
    protected $description = 'Prueba integral de flujo de compras: Normal, Series y Cancelación';

    public function handle()
    {
        $this->info('📦 Iniciando Prueba Integral de Compras...');

        try {
            DB::beginTransaction();

            $suffix = Str::random(5);
            $user = User::first() ?? User::factory()->create();
            $this->actingAs($user);

            // 1. Setup Environment
            $almacen = Almacen::create(['nombre' => "Almacen Compra $suffix", 'ubicacion' => 'Test Compra']);
            $proveedor = Proveedor::create([
                'nombre_razon_social' => "Proveedor Test $suffix",
                'rfc' => 'AAA010101AAA',
                'codigo_postal' => '12345',
                'regimen_fiscal' => '601',
                'tipo_persona' => 'fisica',
                'estado' => 'activo'
            ]);

            $categoria = Categoria::create(['nombre' => "Cat Compra $suffix", 'estado' => 'activo']);
            $marca = Marca::create(['nombre' => "Marca Compra $suffix", 'estado' => 'activo']);

            // ---------------------------------------------------------
            // ESCENARIO A: Producto Normal
            // ---------------------------------------------------------
            $this->info("\n👉 [A] Preparando Producto Normal...");
            $prodNormal = Producto::create([
                'nombre' => "Prod Compra Normal $suffix",
                'codigo' => "PCN-$suffix",
                'codigo_barras' => "CB-PCN-$suffix",
                'precio_compra' => 50,
                'precio_venta' => 100,
                'tipo_producto' => 'normal',
                'unidad_medida' => 'pza',
                'estado' => 'activo',
                'almacen_id' => $almacen->id,
                'categoria_id' => $categoria->id,
                'marca_id' => $marca->id,
            ]);
            // Initial stock 0

            // ---------------------------------------------------------
            // ESCENARIO B: Producto Serializado
            // ---------------------------------------------------------
            $this->info("👉 [B] Preparando Producto Serializado...");
            $prodSerie = Producto::create([
                'nombre' => "Prod Compra Serie $suffix",
                'codigo' => "PCS-$suffix",
                'codigo_barras' => "CB-PCS-$suffix",
                'precio_compra' => 200,
                'precio_venta' => 400,
                'tipo_producto' => 'normal',
                'unidad_medida' => 'pza',
                'estado' => 'activo',
                'requiere_serie' => true,
                'almacen_id' => $almacen->id,
                'categoria_id' => $categoria->id,
                'marca_id' => $marca->id,
            ]);
            // Initial stock 0

            // ---------------------------------------------------------
            // EJECUCIÓN: Crear Compra
            // ---------------------------------------------------------
            $this->info("\n🛒 Simulando Request de Compra...");

            // Prepare Request Data
            $requestData = [
                'proveedor_id' => $proveedor->id,
                'almacen_id' => $almacen->id,
                'fecha_compra' => now()->format('Y-m-d'),
                'metodo_pago' => 'efectivo',
                'productos' => [
                    [
                        'id' => $prodNormal->id,
                        'cantidad' => 10,
                        'precio' => 50,
                        'descuento' => 0,
                        'seriales' => []
                    ],
                    [
                        'id' => $prodSerie->id,
                        'cantidad' => 2,
                        'precio' => 200,
                        'descuento' => 0,
                        'seriales' => ["SN1-$suffix", "SN2-$suffix"]
                    ]
                ]
            ];

            // Instantiate Controller manually (or via app container)
            $controller = app(CompraController::class);

            // Create Mock Request
            $request = Request::create(route('compras.store'), 'POST', $requestData);
            $request->headers->set('Accept', 'application/json'); // Force JSON response logic
            $request->setUserResolver(fn() => $user);

            // Execute Store
            $response = $controller->store($request);

            // Extract Compra from response
            $content = $response->getData(); // Assuming JsonResponse
            if (!isset($content->success) || !$content->success) {
                throw new \Exception("Falló la creación de compra: " . json_encode($content));
            }

            $compraId = $content->compra->id;
            $this->info("✅ Compra Creada ID: {$compraId}");
            $compra = Compra::find($compraId);

            // ---------------------------------------------------------
            // VALIDACIÓN POST-COMPRA (Entrada)
            // ---------------------------------------------------------

            // Validate Normal Product (0 + 10 = 10)
            $stockNormal = \App\Models\Inventario::where('producto_id', $prodNormal->id)->where('almacen_id', $almacen->id)->value('cantidad');
            if ($stockNormal !== 10)
                throw new \Exception("Stock Normal Incorrecto tras compra: $stockNormal (!= 10)");
            $this->info("   ✔️ Stock Normal Aumentado (10)");

            // Validate Serialized Product (0 + 2 = 2)
            $stockSerie = \App\Models\Inventario::where('producto_id', $prodSerie->id)->where('almacen_id', $almacen->id)->value('cantidad');
            $seriesCount = ProductoSerie::where('producto_id', $prodSerie->id)->where('compra_id', $compraId)->where('estado', 'en_stock')->count();

            if ($stockSerie !== 2)
                throw new \Exception("Stock Serie Incorrecto tras compra: $stockSerie (!= 2)");
            if ($seriesCount !== 2)
                throw new \Exception("Conteo de Series Incorrecto: $seriesCount (!= 2)");

            $this->info("   ✔️ Stock Serie Aumentado (2) y Series en Stock");

            // ---------------------------------------------------------
            // ESCENARIO D: Cancelación
            // ---------------------------------------------------------
            $this->info("\n🚫 Probando Cancelación de Compra...");

            // Execute Destroy
            // Need to simulate request? Destroy usually accepts ID.
            // Check Controller signature: public function destroy($id)

            // The controller might redirect, so we need to handle that or catch exceptions if any logic fails before redirect.
            // But we are in a text command, calling the method directly.

            // We need to ensure the compra status is correct for cancellation
            if ($compra->estado !== EstadoCompra::Procesada->value) {
                $compra->update(['estado' => EstadoCompra::Procesada->value]); // Force if needed, though creation sets it
            }

            $responseDelete = $controller->destroy($compraId);

            // In case of RedirectResponse, we can't easily check 'success' message but we can check side effects (DB state)
            $this->info("   (Cancelación ejecutada)");

            // ---------------------------------------------------------
            // VALIDACIÓN POST-CANCELACIÓN (Reversión)
            // ---------------------------------------------------------

            // Validate Normal Product (10 - 10 = 0)
            $stockNormalFinal = \App\Models\Inventario::where('producto_id', $prodNormal->id)->where('almacen_id', $almacen->id)->value('cantidad');
            if ($stockNormalFinal !== 0)
                throw new \Exception("Reversión Normal Fallida: $stockNormalFinal (!= 0)");
            $this->info("   ✔️ Stock Normal Revertido (0)");

            // Validate Serialized Product (2 - 2 = 0)
            $stockSerieFinal = \App\Models\Inventario::where('producto_id', $prodSerie->id)->where('almacen_id', $almacen->id)->value('cantidad');
            $seriesFinalCount = ProductoSerie::where('producto_id', $prodSerie->id)->where('compra_id', $compraId)->count(); // Should be 0 if deleted

            // If soft deleted, use trashed()
            $seriesTrashedCount = ProductoSerie::withTrashed()->where('producto_id', $prodSerie->id)->where('compra_id', $compraId)->count();

            if ($stockSerieFinal !== 0)
                throw new \Exception("Reversión Serie Fallida: $stockSerieFinal (!= 0)");

            if ($seriesFinalCount !== 0)
                throw new \Exception("Series no fueron eliminadas físicamente (count: $seriesFinalCount)");

            // Check if they are soft deleted or hard deleted. 'eliminarTodasLasSeries' implies logic in Service.
            // Assuming soft delete or hard delete, regular count should be 0.

            $this->info("   ✔️ Stock Serie Revertido (0) y Series Eliminadas");

            $this->info("🏆 PRUEBA DE COMPRAS COMPLETA EXITOSA");
            DB::rollBack();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error en prueba: " . $e->getMessage());
            $this->line($e->getTraceAsString());
        }
    }

    protected function actingAs(User $user)
    {
        auth()->login($user);
        return $this;
    }
}

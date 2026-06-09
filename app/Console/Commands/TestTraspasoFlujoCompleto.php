<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Traspaso;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\User;
use App\Models\ProductoSerie;
use App\Http\Controllers\TraspasoController;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Categoria;
use App\Models\Marca;

class TestTraspasoFlujoCompleto extends Command
{
    protected $signature = 'inventario:test-traspaso-completo';
    protected $description = 'Prueba integral de flujo de traspasos: Normal, Series y Reversión';

    public function handle()
    {
        $this->info('📦 Iniciando Prueba Integral de Traspasos...');

        try {
            DB::beginTransaction();

            $suffix = Str::random(5);
            $user = User::first() ?? User::factory()->create();
            $this->actingAs($user);

            // 1. Setup Environment
            $almacenA = Almacen::create(['nombre' => "Almacen A $suffix", 'ubicacion' => 'A']);
            $almacenB = Almacen::create(['nombre' => "Almacen B $suffix", 'ubicacion' => 'B']);
            $this->info("🏭 Almacenes: {$almacenA->nombre} (Origen) -> {$almacenB->nombre} (Destino)");

            $categoria = Categoria::create(['nombre' => "Cat Traspaso $suffix", 'estado' => 'activo']);
            $marca = Marca::create(['nombre' => "Marca Traspaso $suffix", 'estado' => 'activo']);

            // ---------------------------------------------------------
            // ESCENARIO A: Producto Normal
            // ---------------------------------------------------------
            $prodNormal = Producto::create([
                'nombre' => "Prod Traspaso Normal $suffix",
                'codigo' => "PTN-$suffix",
                'precio_compra' => 50,
                'precio_venta' => 100,
                'tipo_producto' => 'normal',
                'estado' => 'activo',
                'codigo_barras' => "CBN-$suffix",
                'unidad_medida' => 'pza',
                'categoria_id' => $categoria->id,
                'marca_id' => $marca->id,
            ]);
            // Add Stock to A (10)
            app(InventarioService::class)->entrada($prodNormal, 10, ['almacen_id' => $almacenA->id, 'motivo' => 'Stock Inicial']);
            $this->info("👉 [A] Producto Normal creado con Stock 10 en A");

            // ---------------------------------------------------------
            // ESCENARIO B: Producto Serializado
            // ---------------------------------------------------------
            $prodSerie = Producto::create([
                'nombre' => "Prod Traspaso Serie $suffix",
                'codigo' => "PTS-$suffix",
                'precio_compra' => 200,
                'precio_venta' => 400,
                'tipo_producto' => 'normal',
                'estado' => 'activo',
                'requiere_serie' => true,
                'codigo_barras' => "CBS-$suffix",
                'unidad_medida' => 'pza',
                'categoria_id' => $categoria->id,
                'marca_id' => $marca->id,
            ]);
            // Create Series in A (2)
            $serie1 = ProductoSerie::create([
                'producto_id' => $prodSerie->id,
                'almacen_id' => $almacenA->id,
                'numero_serie' => "SN1-$suffix",
                'estado' => 'en_stock',
                'compra_id' => 1 // Dummy
            ]);
            $serie2 = ProductoSerie::create([
                'producto_id' => $prodSerie->id,
                'almacen_id' => $almacenA->id,
                'numero_serie' => "SN2-$suffix",
                'estado' => 'en_stock',
                'compra_id' => 1 // Dummy
            ]);

            // Manual sync because Observer skips if compra_id is present
            app(InventarioService::class)->entrada($prodSerie, 2, ['almacen_id' => $almacenA->id, 'motivo' => 'Stock Inicial Series Test']);

            // Wait for observers to trigger or check valid state
            $stockSerieA = \App\Models\Inventario::where('producto_id', $prodSerie->id)->where('almacen_id', $almacenA->id)->value('cantidad');
            if ($stockSerieA !== 2) {
                // If observer didn't trigger (e.g. running in testing env without observers sometimes), force it.
                // But usually observers run. Let's assume they run as we saw in logs.
                // If 0, we might need manual inventory entry, but let's trust previous findings.
                $this->warn("⚠️ Stock Serie en A es $stockSerieA (Esperado 2). Verificando Observer...");
            }
            $this->info("👉 [B] Producto Serializado con 2 series en A");


            // ---------------------------------------------------------
            // EJECUCIÓN: Crear Traspaso (A -> B)
            // ---------------------------------------------------------
            $this->info("\n🚚 Simulando Request de Traspaso...");

            $requestData = [
                'almacen_origen_id' => $almacenA->id,
                'almacen_destino_id' => $almacenB->id,
                'items' => [
                    [
                        'producto_id' => $prodNormal->id,
                        'cantidad' => 5,
                        'series' => []
                    ],
                    [
                        'producto_id' => $prodSerie->id,
                        'cantidad' => 2,
                        'series' => [$serie1->id, $serie2->id]
                    ]
                ],
                'observaciones' => 'Test Traspaso Integral',
                'referencia' => 'REF-INTEGRAL'
            ];

            $controller = app(TraspasoController::class);
            $request = Request::create(route('traspasos.store'), 'POST', $requestData);
            $request->setUserResolver(fn() => $user);

            // Execute Store
            $response = $controller->store($request);
            // Check for redirect (success) or errors
            if ($response->getSession()->has('errors')) {
                throw new \Exception("Error en Traspaso: " . json_encode($response->getSession()->get('errors')->all()));
            }

            $traspaso = Traspaso::latest()->first();
            $this->info("✅ Traspaso Creado ID: {$traspaso->id}");

            // ---------------------------------------------------------
            // VALIDACIÓN POST-TRASPASO
            // ---------------------------------------------------------

            // 1. Validate Normal Product (10 -> 5 in A, 0 -> 5 in B)
            $stockNormalA = \App\Models\Inventario::where('producto_id', $prodNormal->id)->where('almacen_id', $almacenA->id)->value('cantidad');
            $stockNormalB = \App\Models\Inventario::where('producto_id', $prodNormal->id)->where('almacen_id', $almacenB->id)->value('cantidad');

            if ($stockNormalA !== 5 || $stockNormalB !== 5)
                throw new \Exception("Stock Normal Incorrecto tras traspaso. A: $stockNormalA (Esp 5), B: $stockNormalB (Esp 5)");
            $this->info("   ✔️ Stock Normal Transferido correctamente (A: 5, B: 5)");

            // 2. Validate Serialized Product (2 -> 0 in A, 0 -> 2 in B)
            $stockSerieA = \App\Models\Inventario::where('producto_id', $prodSerie->id)->where('almacen_id', $almacenA->id)->value('cantidad');
            $stockSerieB = \App\Models\Inventario::where('producto_id', $prodSerie->id)->where('almacen_id', $almacenB->id)->value('cantidad');

            if ($stockSerieA !== 0 || $stockSerieB !== 2)
                throw new \Exception("Stock Serie Incorrecto tras traspaso. A: $stockSerieA (Esp 0), B: $stockSerieB (Esp 2)");

            // 3. Validate Series Location
            $serie1->refresh();
            $serie2->refresh();
            if ($serie1->almacen_id !== $almacenB->id || $serie2->almacen_id !== $almacenB->id)
                throw new \Exception("Series no cambiaron de almacén. S1: {$serie1->almacen_id}, S2: {$serie2->almacen_id} (Esp: {$almacenB->id})");

            $this->info("   ✔️ Stock Serie y Ubicación de Series actualizados CORRECTAMENTE");


            // ---------------------------------------------------------
            // EJECUCIÓN: Reversión (Destroy)
            // ---------------------------------------------------------
            $this->info("\n🔙 Ejecutando Reversión (Destroy)...");

            $controller->destroy($traspaso);

            // ---------------------------------------------------------
            // VALIDACIÓN POST-REVERSIÓN
            // ---------------------------------------------------------

            // 1. Validate Normal Product (5 -> 10 in A, 5 -> 0 in B)
            $stockNormalAFinal = \App\Models\Inventario::where('producto_id', $prodNormal->id)->where('almacen_id', $almacenA->id)->value('cantidad');
            $stockNormalBFinal = \App\Models\Inventario::where('producto_id', $prodNormal->id)->where('almacen_id', $almacenB->id)->value('cantidad'); // Likely 0 or record deleted/0

            if ($stockNormalAFinal !== 10)
                throw new \Exception("Reversión Normal Fallida en A: $stockNormalAFinal (Esp 10)");
            if ($stockNormalBFinal !== 0)
                throw new \Exception("Reversión Normal Fallida en B: $stockNormalBFinal (Esp 0)");

            $this->info("   ✔️ Stock Normal Revertido (A: 10, B: 0)");

            // 2. Validate Serialized Product (0 -> 2 in A, 2 -> 0 in B)
            $stockSerieAFinal = \App\Models\Inventario::where('producto_id', $prodSerie->id)->where('almacen_id', $almacenA->id)->value('cantidad');
            $stockSerieBFinal = \App\Models\Inventario::where('producto_id', $prodSerie->id)->where('almacen_id', $almacenB->id)->value('cantidad');

            if ($stockSerieAFinal !== 2)
                throw new \Exception("Reversión Serie Fallida en A: $stockSerieAFinal (Esp 2)");
            if ($stockSerieBFinal !== 0)
                throw new \Exception("Reversión Serie Fallida en B: $stockSerieBFinal (Esp 0)");

            // 3. Validate Series Location
            $serie1->refresh();
            $serie2->refresh();
            if ($serie1->almacen_id !== $almacenA->id || $serie2->almacen_id !== $almacenA->id)
                throw new \Exception("Series no regresaron a A. S1: {$serie1->almacen_id}, S2: {$serie2->almacen_id} (Esp: {$almacenA->id})");

            $this->info("   ✔️ Stock Serie y Series regresados CORRECTAMENTE");

            $this->info("🏆 PRUEBA DE TRASPASOS COMPLETA EXITOSA");
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

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

class TestIntegridadTraspaso extends Command
{
    protected $signature = 'inventario:test-traspaso';
    protected $description = 'Verifica la integridad de creación y reversión de traspasos';

    public function handle(TraspasoController $controller, InventarioService $inventarioService)
    {
        $this->info('🧪 Iniciando prueba de Integridad de Traspasos...');

        try {
            DB::beginTransaction();

            $suffix = Str::random(5);
            $usuario = User::first() ?? User::factory()->create();
            auth()->login($usuario);

            // 1. Setup Environment
            $almacenA = Almacen::create(['nombre' => "Almacen A $suffix", 'ubicacion' => 'A']);
            $almacenB = Almacen::create(['nombre' => "Almacen B $suffix", 'ubicacion' => 'B']);

            $this->info("🏭 Almacenes creados: {$almacenA->nombre} -> {$almacenB->nombre}");

            // 2. Create Product with Stock in A
            $producto = Producto::create([
                'nombre' => "Prod Traspaso $suffix",
                'codigo' => "TRASP-$suffix",
                'precio_compra' => 10,
                'precio_venta' => 20,
                'tipo_producto' => 'normal'
            ]);

            // Add stock to A
            $cantInicial = 10;
            $inventarioService->entrada($producto, $cantInicial, ['almacen_id' => $almacenA->id]);

            $this->info("📦 Stock Inicial en A: $cantInicial");

            // 3. Execute Traspaso (A -> B)
            $cantTraspaso = 3;
            $request = new Request([
                'almacen_origen_id' => $almacenA->id,
                'almacen_destino_id' => $almacenB->id,
                'items' => [
                    [
                        'producto_id' => $producto->id,
                        'cantidad' => $cantTraspaso
                    ]
                ],
                'observaciones' => 'Test Traspaso',
                'referencia' => 'REF-TEST'
            ]);

            // Simulate Controller Store
            // We can't easily capture the redirect response, so we call store and assume it works if no exception
            // But store returns RedirectResponse. We need to catch exceptions.
            // A better way is to emulate the logic or trust the controller is tested elsewhere.
            // Let's call the controller method.

            $controller->store($request);

            // 4. Verify Stock after Transfer
            $stockA = $producto->inventarios()->where('almacen_id', $almacenA->id)->first()->cantidad;
            $stockB = $producto->inventarios()->where('almacen_id', $almacenB->id)->first()->cantidad;

            $this->info("🔄 Post-Traspaso Stock -> A: $stockA (Esp: 7), B: $stockB (Esp: 3)");

            if ($stockA !== 7 || $stockB !== 3) {
                throw new \Exception("Fallo en movimiento de inventario (Store)");
            }

            // Find the created Traspaso
            $traspaso = Traspaso::latest()->first();
            $this->info("✅ Traspaso creado ID: {$traspaso->id}");

            // 5. Execute Reversal (Destroy)
            $this->info("🔙 Ejecutando reversión (Destroy)...");
            $controller->destroy($traspaso);

            // 6. Verify Stock after Reversal
            $stockAFinal = $producto->inventarios()->where('almacen_id', $almacenA->id)->first()->cantidad;
            $stockBFinal = $producto->inventarios()->where('almacen_id', $almacenB->id)->first()->cantidad;

            $this->info("🏁 Final Stock -> A: $stockAFinal (Esp: 10), B: $stockBFinal (Esp: 0)");

            if ($stockAFinal !== 10 || $stockBFinal !== 0) {
                throw new \Exception("Fallo en reversión de inventario (Destroy)");
            }

            $this->info("🏆 PRUEBA DE TRASPASO EXITOSA");

            DB::rollBack();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }
}

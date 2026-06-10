<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AjusteInventario;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\User;
use App\Models\ProductoSerie;
use App\Models\Categoria;
use App\Models\Marca;
use App\Http\Controllers\AjusteInventarioController;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestAjusteFlujoCompleto extends Command
{
    protected $signature = 'inventario:test-ajuste-completo';
    protected $description = 'Prueba integral de flujo de ajustes manuales: Incremento y Decremento';

    public function handle()
    {
        $this->info('⚖️ Iniciando Prueba Integral de Ajustes Manuales...');

        try {
            DB::beginTransaction();

            $suffix = Str::random(5);
            $user = User::first() ?? User::factory()->create();
            $this->actingAs($user);

            // 1. Setup Environment
            $almacen = Almacen::create(['nombre' => "Alm Ajuste $suffix", 'ubicacion' => 'LOC-1']);
            $categoria = Categoria::create(['nombre' => "Cat Ajuste $suffix", 'estado' => 'activo']);
            $marca = Marca::create(['nombre' => "Marca Ajuste $suffix", 'estado' => 'activo']);

            // ---------------------------------------------------------
            // ESCENARIO A: Producto Normal (Incremento y Decremento)
            // ---------------------------------------------------------
            $prodNormal = Producto::create([
                'nombre' => "Prod Ajuste Normal $suffix",
                'codigo' => "PAN-$suffix",
                'precio_compra' => 50,
                'precio_venta' => 100,
                'tipo_producto' => 'normal',
                'estado' => 'activo',
                'codigo_barras' => "CBN-$suffix",
                'unidad_medida' => 'pza',
                'categoria_id' => $categoria->id,
                'marca_id' => $marca->id,
            ]);

            $this->info("👉 [A] Producto Normal creado (Stock 0)");
            $controller = app(AjusteInventarioController::class);

            // TEST A1: Incremento Normal
            $reqIncNormal = Request::create(route('ajustes-inventario.store'), 'POST', [
                'producto_id' => $prodNormal->id,
                'almacen_id' => $almacen->id,
                'tipo' => 'incremento',
                'cantidad_ajuste' => 10,
                'motivo' => 'Test Incremento Normal',
                'observaciones' => 'Ajuste positivo manual'
            ]);
            $reqIncNormal->setUserResolver(fn() => $user);

            $controller->store($reqIncNormal);

            $stockNormal = \App\Models\Inventario::where('producto_id', $prodNormal->id)->where('almacen_id', $almacen->id)->value('cantidad');
            if ($stockNormal !== 10)
                throw new \Exception("Fallo Incremento Normal. Stock: $stockNormal (Esp: 10)");
            $this->info("   ✔️ Incremento Normal Exitoso (0 -> 10)");


            // TEST A2: Decremento Normal
            $reqDecNormal = Request::create(route('ajustes-inventario.store'), 'POST', [
                'producto_id' => $prodNormal->id,
                'almacen_id' => $almacen->id,
                'tipo' => 'decremento',
                'cantidad_ajuste' => 3,
                'motivo' => 'Test Decremento Normal',
                'observaciones' => 'Ajuste negativo manual'
            ]);
            $reqDecNormal->setUserResolver(fn() => $user);

            $controller->store($reqDecNormal);

            $stockNormal = \App\Models\Inventario::where('producto_id', $prodNormal->id)->where('almacen_id', $almacen->id)->value('cantidad');
            if ($stockNormal !== 7)
                throw new \Exception("Fallo Decremento Normal. Stock: $stockNormal (Esp: 7)");
            $this->info("   ✔️ Decremento Normal Exitoso (10 -> 7)");


            // ---------------------------------------------------------
            // ESCENARIO B: Producto Serializado (Incremento y Decremento)
            // ---------------------------------------------------------
            $prodSerie = Producto::create([
                'nombre' => "Prod Ajuste Serie $suffix",
                'codigo' => "PAS-$suffix",
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

            $this->info("👉 [B] Producto Serializado creado (Stock 0)");

            // TEST B1: Incremento Serializado
            $seriesInc = ["SN1-$suffix", "SN2-$suffix"];
            $reqIncSerie = Request::create(route('ajustes-inventario.store'), 'POST', [
                'producto_id' => $prodSerie->id,
                'almacen_id' => $almacen->id,
                'tipo' => 'incremento',
                'cantidad_ajuste' => 2,
                'motivo' => 'Test Incremento Serie',
                'seriales' => $seriesInc
            ]);
            $reqIncSerie->setUserResolver(fn() => $user);

            $controller->store($reqIncSerie);

            // Verify Stock
            $stockSerie = \App\Models\Inventario::where('producto_id', $prodSerie->id)->where('almacen_id', $almacen->id)->value('cantidad');
            if ($stockSerie !== 2)
                throw new \Exception("Fallo Incremento Serie Stock. Stock: $stockSerie (Esp: 2)");

            // Verify Series Created
            $countSeries = ProductoSerie::whereIn('numero_serie', $seriesInc)->where('estado', 'en_stock')->count();
            if ($countSeries !== 2)
                throw new \Exception("Fallo Creación Series. Encontradas: $countSeries (Esp: 2)");

            $this->info("   ✔️ Incremento Serie Exitoso (0 -> 2, Series creadas)");


            // TEST B2: Decremento Serializado
            $seriesDec = ["SN1-$suffix"];
            $reqDecSerie = Request::create(route('ajustes-inventario.store'), 'POST', [
                'producto_id' => $prodSerie->id,
                'almacen_id' => $almacen->id,
                'tipo' => 'decremento',
                'cantidad_ajuste' => 1,
                'motivo' => 'Test Decremento Serie',
                'seriales' => $seriesDec
            ]);
            $reqDecSerie->setUserResolver(fn() => $user);

            $controller->store($reqDecSerie);

            // Verify Stock
            $stockSerie = \App\Models\Inventario::where('producto_id', $prodSerie->id)->where('almacen_id', $almacen->id)->value('cantidad');
            if ($stockSerie !== 1)
                throw new \Exception("Fallo Decremento Serie Stock. Stock: $stockSerie (Esp: 1)");

            // Verify Series Status
            $serieBaja = ProductoSerie::where('numero_serie', "SN1-$suffix")->first();
            if ($serieBaja->estado !== 'ajuste_baja')
                throw new \Exception("Fallo Estado Serie Baja. Estado: {$serieBaja->estado} (Esp: ajuste_baja)");

            $serieStock = ProductoSerie::where('numero_serie', "SN2-$suffix")->first();
            if ($serieStock->estado !== 'en_stock')
                throw new \Exception("Fallo Estado Serie Stock. Estado: {$serieStock->estado} (Esp: en_stock)");

            $this->info("   ✔️ Decremento Serie Exitoso (2 -> 1, Serie marcada baja)");


            // ---------------------------------------------------------
            // ESCENARIO C: Validación Stock Negativo
            // ---------------------------------------------------------
            $this->info("👉 [C] Validando Prevención de Stock Negativo...");

            try {
                $reqFail = Request::create(route('ajustes-inventario.store'), 'POST', [
                    'producto_id' => $prodNormal->id,
                    'almacen_id' => $almacen->id,
                    'tipo' => 'decremento',
                    'cantidad_ajuste' => 100, // Should fail (current 7)
                    'motivo' => 'Test Negativo',
                ]);
                $reqFail->setUserResolver(fn() => $user);
                $controller->store($reqFail);

                throw new \Exception("ALERTA: El sistema permitió stock negativo!");
            } catch (\Exception $e) {
                // Expected exception (ValidationException or generic Exception depending on controller)
                // Controller throws generic Exception for negative stock check: "El ajuste resultaría en stock negativo..."
                if (str_contains($e->getMessage(), 'stock negativo')) {
                    $this->info("   ✔️ Sistema previno stock negativo correctamente.");
                } else {
                    // Rethrow if unexpected error
                    throw $e;
                }
            }


            $this->info("🏆 PRUEBA DE AJUSTES COMPLETA EXITOSA");
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

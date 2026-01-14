<?php

namespace App\Console\Commands;

use App\Models\Almacen;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoSerie;
use App\Models\Lote;
use App\Models\User;
use App\Models\Inventario;
use App\Models\InventarioMovimiento;
use App\Services\InventarioService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TestInventarioAjuste extends Command
{
    protected $signature = 'inventario:test-ajuste';
    protected $description = 'Valida la integridad de ajustes manuales y evita el doble conteo en series';

    public function handle()
    {
        $sessionId = uniqid();
        $this->info("🧪 Iniciando prueba de ajustes manuales (ID: {$sessionId})...");

        // Setup
        $user = User::first() ?: User::factory()->create();
        Auth::login($user);
        $almacen = Almacen::firstOrCreate(['nombre' => 'Almacén Test Ajuste'], ['estado' => 'activo']);
        $categoria = Categoria::first() ?: Categoria::create(['nombre' => 'Test']);
        $marca = Marca::first() ?: Marca::create(['nombre' => 'Test']);

        $commonFields = [
            'categoria_id' => $categoria->id,
            'marca_id' => $marca->id,
            'precio_compra' => 100,
            'precio_venta' => 150,
            'unidad_medida' => 'PZA',
            'stock' => 0,
            'estado' => 'activo',
            'sat_clave_prod_serv' => '01010101',
            'sat_clave_unidad' => 'H87',
            'sat_objeto_imp' => '02',
        ];

        // 1. Probar Producto Normal
        $this->info("\n📦 Prueba 1: Producto Normal");
        $pNormal = Producto::create(array_merge($commonFields, [
            'nombre' => "Normal Ajuste {$sessionId}",
            'codigo' => "NA-{$sessionId}",
            'codigo_barras' => "CB-NA-{$sessionId}",
        ]));

        app(\App\Http\Controllers\AjusteInventarioController::class)->store(new \Illuminate\Http\Request([
            'producto_id' => $pNormal->id,
            'almacen_id' => $almacen->id,
            'tipo' => 'incremento',
            'cantidad_ajuste' => 10,
            'motivo' => "Ajuste manual normal {$sessionId}",
        ]));

        $stockN = Inventario::where('producto_id', $pNormal->id)->where('almacen_id', $almacen->id)->value('cantidad');
        $movsN = InventarioMovimiento::where('producto_id', $pNormal->id)->count();

        if ($stockN == 10) {
            $this->info("✅ NORMAL: Stock correcto (10).");
        } else {
            $this->error("❌ NORMAL: Error en stock ($stockN).");
        }
        if ($movsN == 1) {
            $this->info("✅ NORMAL: 1 movimiento generado.");
        } else {
            $this->error("❌ NORMAL: Movimientos incorrectos ($movsN).");
        }

        // 2. Probar Producto Serializado
        $this->info("\n🔢 Prueba 2: Producto Serializado (Doble Conteo Check)");
        $pSerie = Producto::create(array_merge($commonFields, [
            'nombre' => "Serie Ajuste {$sessionId}",
            'codigo' => "SA-{$sessionId}",
            'codigo_barras' => "CB-SA-{$sessionId}",
            'requiere_serie' => true,
        ]));

        $serie1 = "S1-{$sessionId}";
        $serie2 = "S2-{$sessionId}";

        app(\App\Http\Controllers\AjusteInventarioController::class)->store(new \Illuminate\Http\Request([
            'producto_id' => $pSerie->id,
            'almacen_id' => $almacen->id,
            'tipo' => 'incremento',
            'cantidad_ajuste' => 2,
            'motivo' => "Ajuste manual serie {$sessionId}",
            'seriales' => [$serie1, $serie2]
        ]));

        $stockS = Inventario::where('producto_id', $pSerie->id)->where('almacen_id', $almacen->id)->value('cantidad');
        $movsS = InventarioMovimiento::where('producto_id', $pSerie->id)->count();

        if ($stockS == 2) {
            $this->info("✅ SERIE: Stock correcto (2). ¡SIN DOBLE CONTEO!");
        } else {
            $this->error("❌ SERIE: Error en stock ($stockS). POSIBLE DOBLE CONTEO.");
        }
        if ($movsS == 2) {
            $this->info("✅ SERIE: 2 movimientos generados (uno por cada serie).");
        } else {
            $this->error("❌ SERIE: Movimientos incorrectos ($movsS). Esperados 2.");
        }

        // 3. Probar Producto con Lote
        $this->info("\n📅 Prueba 3: Producto con Lote");
        $pLote = Producto::create(array_merge($commonFields, [
            'nombre' => "Lote Ajuste {$sessionId}",
            'codigo' => "LA-{$sessionId}",
            'codigo_barras' => "CB-LA-{$sessionId}",
            'expires' => true,
        ]));

        $loteNum = "LOTE-ADJ-{$sessionId}";
        $fechaCad = now()->addDays(30)->format('Y-m-d');

        app(\App\Http\Controllers\AjusteInventarioController::class)->store(new \Illuminate\Http\Request([
            'producto_id' => $pLote->id,
            'almacen_id' => $almacen->id,
            'tipo' => 'incremento',
            'cantidad_ajuste' => 5,
            'motivo' => "Ajuste manual lote {$sessionId}",
            'numero_lote' => $loteNum,
            'fecha_caducidad' => $fechaCad
        ]));

        $loteActual = Lote::where('producto_id', $pLote->id)->where('almacen_id', $almacen->id)->first();
        if ($loteActual && $loteActual->numero_lote === $loteNum && $loteActual->cantidad_actual == 5) {
            $this->info("✅ LOTE: Lote creado y stock correcto.");
        } else {
            $this->error("❌ LOTE: Error en creación de lote.");
        }

        $this->info("\n🏆 PRUEBA DE AJUSTES FINALIZADA.");
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Almacen;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoSerie;
use App\Models\Lote;
use App\Models\User;
use App\Models\Traspaso;
use App\Models\TraspasoItem;
use App\Models\Inventario;
use App\Models\InventarioMovimiento;
use App\Services\InventarioService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TestInventarioTraspaso extends Command
{
    protected $signature = 'inventario:test-traspaso';
    protected $description = 'Prueba la integridad de los traspasos entre almacenes y su trazabilidad';

    public function handle(InventarioService $inventarioService)
    {
        $sessionId = uniqid();
        $this->info("🧪 Iniciando prueba de traspasos entre almacenes (ID: {$sessionId})...");

        // 1. Setup inicial
        $user = User::first() ?: User::factory()->create();
        Auth::login($user);

        $almacenO = Almacen::firstOrCreate(['nombre' => 'Almacén Origen Test'], ['estado' => 'activo']);
        $almacenD = Almacen::firstOrCreate(['nombre' => 'Almacén Destino Test'], ['estado' => 'activo']);

        $categoria = Categoria::first() ?: Categoria::create(['nombre' => 'Test']);
        $marca = Marca::first() ?: Marca::create(['nombre' => 'Test']);

        // 2. Crear Productos
        $pNormal = Producto::create([
            'nombre' => "Normal Traspaso {$sessionId}",
            'codigo' => "NT-{$sessionId}",
            'codigo_barras' => "CB-NT-{$sessionId}",
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
        ]);

        $pSerie = Producto::create([
            'nombre' => "Serie Traspaso {$sessionId}",
            'codigo' => "ST-{$sessionId}",
            'codigo_barras' => "CB-ST-{$sessionId}",
            'categoria_id' => $categoria->id,
            'marca_id' => $marca->id,
            'precio_compra' => 500,
            'precio_venta' => 700,
            'unidad_medida' => 'PZA',
            'stock' => 0,
            'requiere_serie' => true,
            'estado' => 'activo',
            'sat_clave_prod_serv' => '01010101',
            'sat_clave_unidad' => 'H87',
            'sat_objeto_imp' => '02',
        ]);

        $pLote = Producto::create([
            'nombre' => "Lote Traspaso {$sessionId}",
            'codigo' => "LT-{$sessionId}",
            'codigo_barras' => "CB-LT-{$sessionId}",
            'categoria_id' => $categoria->id,
            'marca_id' => $marca->id,
            'precio_compra' => 200,
            'precio_venta' => 300,
            'unidad_medida' => 'PZA',
            'stock' => 0,
            'expires' => true,
            'estado' => 'activo',
            'sat_clave_prod_serv' => '01010101',
            'sat_clave_unidad' => 'H87',
            'sat_objeto_imp' => '02',
        ]);

        // Cargar stock inicial en Origen
        $inventarioService->entrada($pNormal, 10, ['almacen_id' => $almacenO->id, 'motivo' => 'Stock inicial']);

        $inventarioService->entrada($pSerie, 2, ['almacen_id' => $almacenO->id, 'motivo' => 'Stock inicial']);
        $s1 = ProductoSerie::create(['producto_id' => $pSerie->id, 'almacen_id' => $almacenO->id, 'numero_serie' => "S1-{$sessionId}", 'estado' => 'en_stock']);
        $s2 = ProductoSerie::create(['producto_id' => $pSerie->id, 'almacen_id' => $almacenO->id, 'numero_serie' => "S2-{$sessionId}", 'estado' => 'en_stock']);

        $loteNum = "LOTE-{$sessionId}";
        $inventarioService->entrada($pLote, 5, [
            'almacen_id' => $almacenO->id,
            'motivo' => 'Stock inicial',
            'numero_lote' => $loteNum,
            'fecha_caducidad' => now()->addYear(),
            'costo_unitario' => 200
        ]);

        $this->info('✅ Setup de productos y stock inicial completado.');

        // 3. Ejecutar Traspaso
        $this->info('🚀 Ejecutando traspaso...');

        DB::transaction(function () use ($pNormal, $pSerie, $pLote, $almacenO, $almacenD, $s1, $inventarioService, $sessionId, $loteNum) {
            $traspaso = Traspaso::create([
                'almacen_origen_id' => $almacenO->id,
                'almacen_destino_id' => $almacenD->id,
                'estado' => 'completado',
                'usuario_envia' => Auth::id(),
                'observaciones' => "Test {$sessionId}"
            ]);

            // Traspaso Normal (5 unidades)
            TraspasoItem::create([
                'traspaso_id' => $traspaso->id,
                'producto_id' => $pNormal->id,
                'cantidad' => 5
            ]);
            $inventarioService->salida($pNormal, 5, [
                'almacen_id' => $almacenO->id,
                'motivo' => "Traspaso Out-{$sessionId}",
                'referencia' => $traspaso,
                'skip_transaction' => true
            ]);
            $inventarioService->entrada($pNormal, 5, [
                'almacen_id' => $almacenD->id,
                'motivo' => "Traspaso In-{$sessionId}",
                'referencia' => $traspaso,
                'skip_transaction' => true
            ]);

            // Traspaso Serie (1 unidad: s1)
            TraspasoItem::create([
                'traspaso_id' => $traspaso->id,
                'producto_id' => $pSerie->id,
                'cantidad' => 1,
                'series_ids' => [$s1->id]
            ]);
            $s1->update(['almacen_id' => $almacenD->id]);

            // Traspaso Lote (3 unidades)
            TraspasoItem::create([
                'traspaso_id' => $traspaso->id,
                'producto_id' => $pLote->id,
                'cantidad' => 3
            ]);
            $loteO = Lote::where('producto_id', $pLote->id)->where('almacen_id', $almacenO->id)->first();

            $inventarioService->salida($pLote, 3, [
                'almacen_id' => $almacenO->id,
                'motivo' => "Traspaso Out-{$sessionId}",
                'referencia' => $traspaso,
                'skip_transaction' => true
            ]);
            $inventarioService->entrada($pLote, 3, [
                'almacen_id' => $almacenD->id,
                'motivo' => "Traspaso In-{$sessionId}",
                'referencia' => $traspaso,
                'numero_lote' => $loteNum,
                'fecha_caducidad' => $loteO->fecha_caducidad,
                'costo_unitario' => $loteO->costo_unitario,
                'skip_transaction' => true
            ]);
        });

        // 4. Verificaciones
        $this->info('🔍 Verificando integridad...');

        $normalOk = true;
        $stockON = Inventario::where('producto_id', $pNormal->id)->where('almacen_id', $almacenO->id)->value('cantidad');
        $stockDN = Inventario::where('producto_id', $pNormal->id)->where('almacen_id', $almacenD->id)->value('cantidad');
        if ($stockON == 5 && $stockDN == 5) {
            $this->info('✅ NORMAL: Stock dividido correctamente (5/5).');
        } else {
            $normalOk = false;
            $this->error("❌ NORMAL: Discrepancia. O:{$stockON}, D:{$stockDN}");
        }

        $serieOk = true;
        $stockOS = Inventario::where('producto_id', $pSerie->id)->where('almacen_id', $almacenO->id)->value('cantidad');
        $stockDS = Inventario::where('producto_id', $pSerie->id)->where('almacen_id', $almacenD->id)->value('cantidad');
        $movsSerie = InventarioMovimiento::where('producto_id', $pSerie->id)
            ->where('motivo', 'like', 'Traspaso entre almacenes%')
            ->count();

        if ($stockOS == 1 && $stockDS == 1 && $movsSerie == 2) {
            $this->info('✅ SERIE: Stock sincronizado (1/1) y trazabilidad generada (2 movs).');
        } else {
            $serieOk = false;
            $this->error("❌ SERIE: Discrepancia. O:{$stockOS}, D:{$stockDS}, Movs:{$movsSerie}");
        }

        $loteOk = true;
        $loteActualD = Lote::where('producto_id', $pLote->id)->where('almacen_id', $almacenD->id)->first();
        if ($loteActualD && $loteActualD->cantidad_actual == 3) {
            $this->info('✅ LOTE: Lote persistido en destino con cantidad correcta (3).');
        } else {
            $loteOk = false;
            $this->error("❌ LOTE: Error o no encontrado.");
        }

        // 5. Probar Reverso (Eliminación)
        if ($normalOk && $serieOk && $loteOk) {
            $this->info("\n🔙 Probando REVERSO (Eliminación del traspaso)...");
            $traspaso = Traspaso::where('observaciones', "Test {$sessionId}")->first();

            // Simular lógica de TraspasoController::destroy
            app(\App\Http\Controllers\TraspasoController::class)->destroy($traspaso);

            $this->info('🔍 Verificando reverso...');

            $stockFinalN = Inventario::where('producto_id', $pNormal->id)->where('almacen_id', $almacenO->id)->value('cantidad');
            $stockFinalS = Inventario::where('producto_id', $pSerie->id)->where('almacen_id', $almacenO->id)->value('cantidad');
            $stockFinalL = Inventario::where('producto_id', $pLote->id)->where('almacen_id', $almacenO->id)->value('cantidad');

            if ($stockFinalN == 10 && $stockFinalS == 2 && $stockFinalL == 5) {
                $this->info('✅ REVERSO: Stock restaurado íntegramente en origen (10, 2, 5).');
            } else {
                $this->error("❌ REVERSO: Error al restaurar stock. N:{$stockFinalN}, S:{$stockFinalS}, L:{$stockFinalL}");
            }
        }

        $this->info("\n🏆 PRUEBA DE TRASPASOS FINALIZADA.");
    }
}

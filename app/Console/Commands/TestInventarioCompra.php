<?php

namespace App\Console\Commands;

use App\Models\Almacen;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoSerie;
use App\Models\Lote;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Compra;
use App\Models\InventarioMovimiento;
use App\Models\ProductoPrecioHistorial;
use App\Services\InventarioService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TestInventarioCompra extends Command
{
    protected $signature = 'inventario:test-compra';
    protected $description = 'Prueba la integridad del incremento de inventario al registrar una compra';

    public function handle(InventarioService $inventarioService)
    {
        $sessionId = uniqid();
        $this->info("🧪 Iniciando prueba de incremento de inventario (ID: {$sessionId})...");

        // 1. Setup inicial
        $user = User::first() ?: User::factory()->create();
        Auth::login($user);
        $this->info("👤 Autenticado como: {$user->name}");

        $almacen = Almacen::firstOrCreate(['nombre' => 'Almacén de Pruebas Compra'], [
            'ubicacion' => 'Ubicación de Pruebas',
            'estado' => 'activo'
        ]);

        $proveedor = Proveedor::firstOrCreate(['rfc' => 'PRU010101TR7'], [
            'nombre_razon_social' => 'Proveedor de Pruebas',
            'activo' => true
        ]);

        $categoria = Categoria::first() ?: Categoria::create(['nombre' => 'Test']);
        $marca = Marca::first() ?: Marca::create(['nombre' => 'Test']);

        // 2. Crear Productos
        $pNormal = Producto::create([
            'nombre' => 'Prod Normal Compra',
            'codigo' => 'NORM-COMPRA-' . time(),
            'codigo_barras' => '12345678',
            'categoria_id' => $categoria->id,
            'marca_id' => $marca->id,
            'precio_compra' => 100,
            'precio_venta' => 120,
            'precio_distribuidor' => 110,
            'unidad_medida' => 'PZA',
            'stock' => 0, // Iniciar en 0 para ver incremento limpio
            'estado' => 'activo',
            'sat_clave_prod_serv' => '01010101',
            'sat_clave_unidad' => 'H87',
            'sat_objeto_imp' => '01',
        ]);

        $pSerie = Producto::create([
            'nombre' => 'Prod Serie Compra',
            'codigo' => 'SERIE-COMPRA-' . time(),
            'codigo_barras' => '87654321',
            'categoria_id' => $categoria->id,
            'marca_id' => $marca->id,
            'precio_compra' => 500,
            'precio_venta' => 600,
            'precio_distribuidor' => 550,
            'unidad_medida' => 'PZA',
            'stock' => 0,
            'requiere_serie' => true,
            'estado' => 'activo',
            'sat_clave_prod_serv' => '01010101',
            'sat_clave_unidad' => 'H87',
            'sat_objeto_imp' => '01',
        ]);

        $pLote = Producto::create([
            'nombre' => 'Prod Lote Compra',
            'codigo' => 'LOTE-COMPRA-' . time(),
            'codigo_barras' => '55667788',
            'categoria_id' => $categoria->id,
            'marca_id' => $marca->id,
            'precio_compra' => 200,
            'precio_venta' => 300,
            'precio_distribuidor' => 250,
            'unidad_medida' => 'PZA',
            'stock' => 0, // Iniciar en 0
            'expires' => true,
            'estado' => 'activo',
            'sat_clave_prod_serv' => '01010101',
            'sat_clave_unidad' => 'H87',
            'sat_objeto_imp' => '01',
        ]);

        $this->info('✅ Productos de prueba creados.');

        // 3. Simular Registro de Compra
        DB::transaction(function () use ($pNormal, $pSerie, $pLote, $almacen, $proveedor, $inventarioService, $sessionId) {
            $compra = Compra::create([
                'numero_compra' => 'TEST-C-' . time(),
                'proveedor_id' => $proveedor->id,
                'almacen_id' => $almacen->id,
                'total' => 3000,
                'estado' => 'procesada',
                'fecha_compra' => now(),
                'user_id' => Auth::id(),
                'inventario_procesado' => true,
            ]);

            // Item 1: Producto Normal (Cantidad 10)
            $inventarioService->entrada($pNormal, 10, [
                'motivo' => "Nueva compra (Test-{$sessionId})",
                'almacen_id' => $almacen->id,
                'referencia' => $compra
            ]);

            // Item 2: Producto Serie (Cantidad 2)
            $seriales = ["SN-{$sessionId}-1", "SN-{$sessionId}-2"];
            $inventarioService->entrada($pSerie, 2, [
                'motivo' => "Nueva compra (Test Serie-{$sessionId})",
                'almacen_id' => $almacen->id,
                'referencia' => $compra
            ]);
            foreach ($seriales as $serie) {
                ProductoSerie::create([
                    'producto_id' => $pSerie->id,
                    'compra_id' => $compra->id,
                    'almacen_id' => $almacen->id,
                    'numero_serie' => $serie,
                    'estado' => 'en_stock',
                ]);
            }

            // Item 3: Producto Lote (Cantidad 5)
            $inventarioService->entrada($pLote, 5, [
                'motivo' => "Nueva compra (Test Lote-{$sessionId})",
                'almacen_id' => $almacen->id,
                'referencia' => $compra,
                'numero_lote' => "LOTE-{$sessionId}",
                'fecha_caducidad' => now()->addYear(),
                'costo_unitario' => 250
            ]);

            // Bonus: Simular actualización de precio histórico (como hace CompraController)
            ProductoPrecioHistorial::create([
                'producto_id' => $pNormal->id,
                'precio_compra_anterior' => 0,
                'precio_compra_nuevo' => 100,
                'precio_venta_anterior' => null,
                'precio_venta_nuevo' => 120,
                'tipo_cambio' => 'compra',
                'notas' => "Test {$sessionId}",
                'user_id' => Auth::id(),
            ]);
        });

        $this->info('⚙️ Registro de compra simulado.');

        // 4. Verificaciones
        $this->info('🔍 Verificando resultados...');

        // Normal
        $pNormal->refresh();
        if ($pNormal->stock == 10) {
            $this->info('✅ STOCK NORMAL: Correcto (10)');
        } else {
            $this->error("❌ STOCK NORMAL: Error. Esperado 10, obtenido {$pNormal->stock}");
        }

        // Serie
        $pSerie->refresh();
        $seriesCount = ProductoSerie::where('producto_id', $pSerie->id)->where('estado', 'en_stock')->count();
        if ($pSerie->stock == 2 && $seriesCount == 2) {
            $this->info('✅ STOCK & SERIES: Correcto (2)');
        } else {
            $this->error("❌ STOCK & SERIES: Error. Stock: {$pSerie->stock}, Series: {$seriesCount}");
        }

        // Lote
        $pLote->refresh();
        $loteActual = Lote::where('producto_id', $pLote->id)->first();
        if ($pLote->stock == 5 && $loteActual && $loteActual->cantidad_actual == 5) {
            $this->info('✅ STOCK & LOTE: Correcto (Stock: 5, Lote: 5)');
        } else {
            $this->error("❌ STOCK & LOTE: Error. Stock: {$pLote->stock}, Lote: " . ($loteActual ? $loteActual->cantidad_actual : 'N/A'));
        }

        // Traceability
        $movimientos = InventarioMovimiento::where('motivo', 'like', "%{$sessionId}%")->get();
        $movimientosCount = $movimientos->count();

        foreach ($movimientos as $mov) {
            $this->info("📍 Movimiento: {$mov->motivo} | Producto: {$mov->producto_nombre} | Cant: {$mov->cantidad}");
        }

        if ($movimientosCount == 3) {
            $this->info("✅ MOVIMIENTOS: Trazabilidad unitaria correcta (3 registros)");
        } else {
            $this->error("❌ MOVIMIENTOS: Error. Se esperaban 3 registros, se encontraron {$movimientosCount}.");
        }

        // Price History
        $historial = ProductoPrecioHistorial::where('notas', "Test {$sessionId}")->exists();
        if ($historial) {
            $this->info('✅ HISTORIAL PRECIOS: Registro creado correctamente.');
        } else {
            $this->error('❌ HISTORIAL PRECIOS: No se encontró el registro de cambio de precio.');
        }

        $this->info("\n🏆 PRUEBA FINALIZADA.");
    }
}

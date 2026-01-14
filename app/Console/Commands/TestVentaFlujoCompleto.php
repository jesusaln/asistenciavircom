<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Venta;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\ProductoSerie;
use App\Models\KitItem;
use App\Http\Controllers\VentaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Enums\EstadoVenta;

class TestVentaFlujoCompleto extends Command
{
    protected $signature = 'inventario:test-venta-completo';
    protected $description = 'Prueba integral de flujo de ventas: Normal, Kits, Series y Cancelación';

    public function handle()
    {
        $this->info('🛍️  Iniciando Prueba Integral de Ventas...');

        try {
            DB::beginTransaction();

            $suffix = Str::random(5);
            $user = User::first() ?? User::factory()->create();
            $this->actingAs($user);

            // 1. Setup Environment
            $almacen = Almacen::create(['nombre' => "Almacen Venta $suffix", 'ubicacion' => 'Test Venta']);
            $cliente = Cliente::create([
                'nombre_razon_social' => "Cliente Test $suffix",
                'rfc' => 'XAXX' . rand(100000, 999999) . '000',
                'email' => "test$suffix@example.com",
                'codigo_postal' => '12345',
                'regimen_fiscal' => '601',
                'tipo_persona' => 'fisica'
            ]);

            $categoria = Categoria::create(['nombre' => "Cat $suffix", 'estado' => 'activo']);
            $marca = Marca::create(['nombre' => "Marca $suffix", 'estado' => 'activo']);

            $inventarioService = app(\App\Services\InventarioService::class);
            $ventaController = app(VentaController::class);

            // ---------------------------------------------------------
            // ESCENARIO A: Producto Normal
            // ---------------------------------------------------------
            $this->info("\n👉 [A] Probando Producto Normal...");
            $prodNormal = Producto::create([
                'nombre' => "Prod Normal $suffix",
                'codigo' => "PN-$suffix",
                'codigo_barras' => "CB-PN-$suffix",
                'precio_compra' => 50,
                'precio_venta' => 100,
                'tipo_producto' => 'normal',
                'unidad_medida' => 'pza',
                'estado' => 'activo',
                'almacen_id' => $almacen->id, // Default
                'categoria_id' => $categoria->id,
                'marca_id' => $marca->id,
            ]);
            $inventarioService->entrada($prodNormal, 10, ['almacen_id' => $almacen->id]);

            // ---------------------------------------------------------
            // ESCENARIO B: Producto Serializado
            // ---------------------------------------------------------
            $this->info("👉 [B] Probando Producto Serializado...");
            $prodSerie = Producto::create([
                'nombre' => "Prod Serie $suffix",
                'codigo' => "PS-$suffix",
                'codigo_barras' => "CB-PS-$suffix",
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
            // Create series via Inventory Service (simulated entrada with series logic usually happens in purchase, doing manual here)
            // But for integrity, let's use the standard flow: Create series in 'en_stock'
            $serie1 = ProductoSerie::create([
                'producto_id' => $prodSerie->id,
                'almacen_id' => $almacen->id,
                'numero_serie' => "SN1-$suffix",
                'estado' => 'en_stock'
            ]);
            // Observer should trigger inventory update

            // ---------------------------------------------------------
            // ESCENARIO C: Kit (Mixto)
            // ---------------------------------------------------------
            $this->info("👉 [C] Probando Kit Virtual...");
            $prodComponente = Producto::create([
                'nombre' => "Componente Kit $suffix",
                'codigo' => "CK-$suffix",
                'codigo_barras' => "CB-CK-$suffix",
                'precio_compra' => 10,
                'precio_venta' => 20,
                'tipo_producto' => 'normal',
                'unidad_medida' => 'pza',
                'estado' => 'activo',
                'almacen_id' => $almacen->id,
                'categoria_id' => $categoria->id,
                'marca_id' => $marca->id,
            ]);
            $inventarioService->entrada($prodComponente, 20, ['almacen_id' => $almacen->id]);

            $kit = Producto::create([
                'nombre' => "Kit Venta $suffix",
                'codigo' => "KITV-$suffix",
                'codigo_barras' => "CB-KITV-$suffix",
                'precio_compra' => 0,
                'precio_venta' => 150,
                'tipo_producto' => 'kit',
                'unidad_medida' => 'pza',
                'estado' => 'activo',
                'almacen_id' => $almacen->id,
                'categoria_id' => $categoria->id,
                'marca_id' => $marca->id,
            ]);
            KitItem::create(['kit_id' => $kit->id, 'item_id' => $prodComponente->id, 'item_type' => 'producto', 'cantidad' => 2]);

            // ---------------------------------------------------------
            // EJECUCIÓN: Crear Venta
            // ---------------------------------------------------------
            $request = new Request([
                'cliente_id' => $cliente->id,
                'almacen_id' => $almacen->id,
                'metodo_pago' => 'efectivo',
                'productos' => [
                    [
                        'id' => $prodNormal->id,
                        'cantidad' => 2,
                        'precio' => 100,
                        'series' => []
                    ],
                    [
                        'id' => $prodSerie->id,
                        'cantidad' => 1,
                        'precio' => 400,
                        'series' => ["SN1-$suffix"]
                    ],
                    [
                        'id' => $kit->id,
                        'cantidad' => 1,
                        'precio' => 150,
                        'componentes_series' => [] // No serialized components
                    ]
                ]
            ]);

            // Simulation: Calling store logic directly via Service (bypassing Controller Request validation for simplicity or use Controller?)
            // Using Controller store method is better integration test, but requires handling Redirects/JSON.
            // Let's use VentaCreationService directly to capture the object easily

            $ventaService = app(\App\Services\Ventas\VentaCreationService::class);
            $venta = $ventaService->createVenta($request->all());

            $this->info("✅ Venta Creada ID: {$venta->id}");

            // ---------------------------------------------------------
            // VALIDACIÓN POST-VENTA
            // ---------------------------------------------------------

            // Validate Normal Product (10 - 2 = 8)
            $stockNormal = \App\Models\Inventario::where('producto_id', $prodNormal->id)->where('almacen_id', $almacen->id)->value('cantidad');
            if ($stockNormal !== 8)
                throw new \Exception("Stock Normal Incorrecto: $stockNormal (!= 8)");
            $this->info("   ✔️ Stock Normal OK (8)");

            // Validate Serialized Product (1 - 1 = 0)
            $stockSerie = \App\Models\Inventario::where('producto_id', $prodSerie->id)->where('almacen_id', $almacen->id)->value('cantidad');
            $serieEstado = $serie1->fresh()->estado;
            if ($stockSerie !== 0)
                throw new \Exception("Stock Serie Incorrecto: $stockSerie (!= 0)");
            if ($serieEstado !== 'vendido')
                throw new \Exception("Estado Serie Incorrecto: $serieEstado (!= vendido)");
            $this->info("   ✔️ Stock Serie OK (0) y Estado Vendido");

            // Validate Kit Component (20 - (1*2) = 18)
            $stockComp = \App\Models\Inventario::where('producto_id', $prodComponente->id)->where('almacen_id', $almacen->id)->value('cantidad');
            if ($stockComp !== 18)
                throw new \Exception("Stock Componente Kit Incorrecto: $stockComp (!= 18)");
            $this->info("   ✔️ Stock Componente Kit OK (18)");

            // ---------------------------------------------------------
            // ESCENARIO D: Cancelación
            // ---------------------------------------------------------
            $this->info("\n🚫 Probando Cancelación...");

            // Invoking Cancellation Service
            $cancelService = app(\App\Services\Ventas\VentaCancellationService::class);
            $cancelService->cancelVenta($venta, "Test Cancelación");

            // ---------------------------------------------------------
            // VALIDACIÓN POST-CANCELACIÓN
            // ---------------------------------------------------------

            // Normal: 8 + 2 = 10
            $stockNormalFinal = \App\Models\Inventario::where('producto_id', $prodNormal->id)->where('almacen_id', $almacen->id)->value('cantidad');
            if ($stockNormalFinal !== 10)
                throw new \Exception("Restauración Normal Fallida: $stockNormalFinal (!= 10)");
            $this->info("   ✔️ Stock Normal Restaurado (10)");

            // Serie: 0 + 1 = 1, Estado 'en_stock'
            $stockSerieFinal = \App\Models\Inventario::where('producto_id', $prodSerie->id)->where('almacen_id', $almacen->id)->value('cantidad');
            $serieEstadoFinal = $serie1->fresh()->estado;
            if ($stockSerieFinal !== 1)
                throw new \Exception("Restauración Serie Fallida: $stockSerieFinal (!= 1)");
            if ($serieEstadoFinal !== 'en_stock')
                throw new \Exception("Estado Serie Restaurado Fallido: $serieEstadoFinal");
            $this->info("   ✔️ Stock Serie Restaurado (1) y Estado En Stock");

            // Kit Component: 18 + 2 = 20
            $stockCompFinal = \App\Models\Inventario::where('producto_id', $prodComponente->id)->where('almacen_id', $almacen->id)->value('cantidad');
            if ($stockCompFinal !== 20)
                throw new \Exception("Restauración Componente Kit Fallida: $stockCompFinal (!= 20)");
            $this->info("   ✔️ Stock Componente Kit Restaurado (20)");

            $this->info("🏆 PRUEBA DE VENTAS COMPLETA EXITOSA");
            DB::rollBack();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error en prueba: " . $e->getMessage());
            $this->line($e->getTraceAsString());
        }
    }

    /**
     * Helper to act as user
     */
    protected function actingAs(User $user)
    {
        auth()->login($user);
        return $this;
    }
}

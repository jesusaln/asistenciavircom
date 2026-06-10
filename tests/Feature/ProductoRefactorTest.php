<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Almacen;
use App\Models\User;
use App\Models\Empresa;
use App\Models\Inventario;
use App\Services\InventarioService;
use App\Support\EmpresaResolver;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;

class ProductoRefactorTest extends TestCase
{
    use DatabaseTransactions;

    protected Empresa $empresa;
    protected User $user;
    protected Categoria $categoria;
    protected Marca $marca;
    protected Almacen $almacen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::factory()->create();
        EmpresaResolver::setContext($this->empresa->id);

        $this->user = User::factory()->create(['empresa_id' => $this->empresa->id]);
        $this->actingAs($this->user);

        $this->categoria = Categoria::create(['nombre' => 'Test Cat', 'empresa_id' => $this->empresa->id]);
        $this->marca = Marca::create(['nombre' => 'Test Marca', 'empresa_id' => $this->empresa->id]);
        $this->almacen = Almacen::withoutEvents(fn() => Almacen::factory()->create(['empresa_id' => $this->empresa->id, 'estado' => 'activo']));
    }

    /** @test */
    public function it_can_create_a_product_via_form()
    {
        $this->withoutMiddleware();
        Storage::fake('public');

        $payload = [
            'nombre' => 'Producto de Prueba',
            'descripcion' => 'Descripción del producto',
            'codigo' => 'PROD-TEST-001',
            'codigo_barras' => '1234567890123',
            'categoria_id' => $this->categoria->id,
            'marca_id' => $this->marca->id,
            'precio_compra' => 100,
            'precio_venta' => 150,
            'unidad_medida' => 'PZA',
            'tipo_producto' => 'fisico',
            'estado' => 'activo',
            'incluye_iva' => true,
            'stock_minimo_por_almacen' => [
                $this->almacen->id => 5
            ]
        ];

        $response = $this->post(route('productos.store'), $payload);

        $response->assertRedirect(route('productos.index'));
        $this->assertDatabaseHas('productos', [
            'nombre' => 'Producto de Prueba',
            'codigo' => 'PROD-TEST-001',
            'empresa_id' => $this->empresa->id
        ]);
    }

    /** @test */
    public function it_can_update_a_product()
    {
        $this->withoutMiddleware();
        $producto = Producto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Original',
            'estado' => 'activo'
        ]);

        $payload = [
            'nombre' => 'Updated Name',
            'descripcion' => 'New Desc',
            'codigo' => $producto->codigo,
            'codigo_barras' => $producto->codigo_barras,
            'categoria_id' => $producto->categoria_id,
            'marca_id' => $producto->marca_id,
            'precio_compra' => 200,
            'precio_venta' => 300,
            'unidad_medida' => $producto->unidad_medida,
            'tipo_producto' => $producto->tipo_producto,
            'estado' => 'activo',
        ];

        $response = $this->putJson(route('productos.update', $producto->id), $payload);

        $response->assertStatus(200);
        $producto->refresh();
        $this->assertEquals('Updated Name', $producto->nombre);
    }

    /** @test */
    public function it_can_adjust_inventory()
    {
        $producto = Producto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'stock' => 0
        ]);

        $service = app(InventarioService::class);
        dump("Service Class: " . get_class($service));
        
        // Entrada
        $service->entrada($producto, 10, [
            'almacen_id' => $this->almacen->id,
            'motivo' => 'Test Entrada',
            'user_id' => $this->user->id
        ]);

        $producto->refresh();
        $this->assertEquals(10, $producto->stock);

        // Salida
        $service->salida($producto, 4, [
            'almacen_id' => $this->almacen->id,
            'motivo' => 'Test Salida',
            'user_id' => $this->user->id
        ]);

        $producto->refresh();
        $this->assertEquals(6, $producto->stock);
    }

    /** @test */
    public function it_prevents_deletion_of_active_product()
    {
        $this->withoutMiddleware();
        $producto = Producto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'estado' => 'activo'
        ]);

        $response = $this->deleteJson(route('productos.destroy', $producto->id));

        $response->assertStatus(422);
        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'deleted_at' => null]);
    }

    /** @test */
    public function it_can_adjust_kit_inventory_components()
    {
        $this->withoutMiddleware();

        // 1. Create components with initial stock in the warehouse
        $componente1 = Producto::factory()->create(['empresa_id' => $this->empresa->id, 'stock' => 10, 'tipo_producto' => 'fisico']);
        $componente2 = Producto::factory()->create(['empresa_id' => $this->empresa->id, 'stock' => 20, 'tipo_producto' => 'fisico']);

        // Initialize warehouse stock
        Inventario::updateOrCreate(
            ['producto_id' => $componente1->id, 'almacen_id' => $this->almacen->id],
            ['cantidad' => 10, 'empresa_id' => $this->empresa->id]
        );
        Inventario::updateOrCreate(
            ['producto_id' => $componente2->id, 'almacen_id' => $this->almacen->id],
            ['cantidad' => 20, 'empresa_id' => $this->empresa->id]
        );

        // 2. Create Kit
        $kit = Producto::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Kit Test',
            'codigo' => 'KIT-001',
            'tipo_producto' => 'kit',
            'stock' => 0,
            'expires' => false,
            'requiere_serie' => false,
            'categoria_id' => $this->categoria->id,
            'marca_id' => $this->marca->id,
            'unidad_medida' => 'PZA',
            'precio_venta' => 1000,
            'estado' => 'activo',
        ]);

        // 3. Define kit components (2 of comp1, 3 of comp2)
        \App\Models\KitItem::create([
            'kit_producto_id' => $kit->id,
            'producto_id' => $componente1->id, 
            'item_id' => $componente1->id,
            'item_type' => 'producto',
            'cantidad' => 2,
            'empresa_id' => $this->empresa->id
        ]);
        \App\Models\KitItem::create([
            'kit_producto_id' => $kit->id,
            'producto_id' => $componente2->id, 
            'item_id' => $componente2->id,
            'item_type' => 'producto',
            'cantidad' => 3,
            'empresa_id' => $this->empresa->id
        ]);

        $service = app(InventarioService::class);

        // Access protected method for testing
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('ajustar');
        $method->setAccessible(true);
        $method->invoke($service, $kit, 'salida', 2, [
            'almacen_id' => $this->almacen->id,
            'motivo' => 'Venta de Kit'
        ]);

        // Verify warehouse stock
        $this->assertEquals(6, Inventario::where('producto_id', $componente1->id)->where('almacen_id', $this->almacen->id)->value('cantidad'));
        $this->assertEquals(14, Inventario::where('producto_id', $componente2->id)->where('almacen_id', $this->almacen->id)->value('cantidad'));
    }
}

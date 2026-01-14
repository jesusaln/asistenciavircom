<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\Categoria;
use App\Models\Marca;
use Spatie\Permission\Models\Role;

use Tests\TestCase;

class KitTest extends TestCase
{
    

    protected $user;
    protected $almacen;
    protected $categoria;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $this->user = User::firstOrCreate(
            ['email' => 'jesuslopeznoriega@hotmail.com'],
            [
                'name' => 'Jesus Lopez',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $this->user->assignRole($adminRole);

        $this->almacen = Almacen::factory()->create(['estado' => 'activo']);
        $this->categoria = Categoria::factory()->create();
        // Ensure we have at least one Marca for the controller to use
        if (class_exists(Marca::class) && Marca::count() === 0) {
             Marca::factory()->create();
        }
    }

    public function test_can_create_kit_with_correct_cost_calculation()
    {
        // Create components with specific purchase price
        $componente1 = Producto::factory()->create([
            'precio_compra' => 100,
            'tipo_producto' => 'producto',
            'estado' => 'activo'
        ]);
        
        $componente2 = Producto::factory()->create([
            'precio_compra' => 50,
            'tipo_producto' => 'producto',
            'estado' => 'activo'
        ]);

        $response = $this->actingAs($this->user)->postJson('/kits', [
            'nombre' => 'Kit Test',
            'precio_venta' => 500,
            'categoria_id' => $this->categoria->id,
            'componentes' => [
                [
                    'item_type' => 'producto',
                    'item_id' => $componente1->id,
                    'cantidad' => 2, // Cost: 2 * 100 = 200
                    'precio_unitario' => 150 // Selling price breakdown
                ],
                [
                    'item_type' => 'producto',
                    'item_id' => $componente2->id,
                    'cantidad' => 1, // Cost: 1 * 50 = 50
                    'precio_unitario' => 100
                ]
            ]
        ]);

        $response->assertStatus(201);
        
        $kit = Producto::where('tipo_producto', 'kit')->first();
        $this->assertNotNull($kit);
        
        // Total Cost should be 200 + 50 = 250
        $this->assertEquals(250, $kit->precio_compra);
    }

    public function test_show_kit_passes_estimated_cost()
    {
        $componente = Producto::factory()->create([
            'precio_compra' => 100,
            'tipo_producto' => 'producto'
        ]);

        $kit = Producto::factory()->create([
            'tipo_producto' => 'kit',
            'precio_compra' => 100 // Initial cost
        ]);

        // Attach component
        \App\Models\KitItem::create([
            'kit_id' => $kit->id,
            'item_type' => 'producto',
            'item_id' => $componente->id,
            'cantidad' => 1
        ]);

        $response = $this->actingAs($this->user)->get("/kits/{$kit->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Kits/Show')
            ->has('costoEstimado')
            ->where('costoEstimado', 100)
        );
    }
}

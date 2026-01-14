<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\User;
use App\Enums\EstadoCompra;

use Spatie\Permission\Models\Role;
use Laravel\Sanctum\Sanctum;

class CompraTest extends TestCase
{
    

    protected $proveedor;
    protected $producto1;
    protected $producto2;

    protected function setUp(): void
    {
        parent::setUp();

        // Autenticar usuario
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        // Verificar email para pasar middleware 'verified'
        $user->forceFill(['email_verified_at' => now()])->save();
        // Asignar rol requerido por middleware de rutas
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user->assignRole($role);
        Sanctum::actingAs($user, ['*']);
        $this->actingAs($user);

        // Crear categoria y marca para productos
        $categoria = Categoria::create([
            'nombre' => 'Categoria Test',
            'descripcion' => 'Descripción categoria test',
            'activo' => true,
        ]);

        $marca = Marca::create([
            'nombre' => 'Marca Test',
            'descripcion' => 'Descripción marca test',
            'activo' => true,
        ]);

        // Crear datos de prueba básicos sin dependencias complejas
        $this->proveedor = Proveedor::create([
            'nombre_razon_social' => 'Proveedor Test S.A.',
            'tipo_persona' => 'moral',
            'rfc' => 'TES123456789',
            'regimen_fiscal' => '601',
            'uso_cfdi' => 'G01',
            'email' => 'proveedor@test.com',
            'telefono' => '555-123-4567',
            'calle' => 'Calle Test',
            'numero_exterior' => '123',
            'colonia' => 'Colonia Test',
            'codigo_postal' => '12345',
            'municipio' => 'Municipio Test',
            'estado' => 'Estado Test',
            'pais' => 'México',
            'activo' => true,
        ]);

        // Crear productos sin dependencias de categoria/marca/almacen para simplificar
        $this->producto1 = Producto::create([
            'nombre' => 'Producto Test 1',
            'descripcion' => 'Descripción del producto test 1',
            'codigo' => 'TEST001',
            'codigo_barras' => '1234567890123',
            'categoria_id' => $categoria->id,
            'marca_id' => $marca->id,
            'proveedor_id' => $this->proveedor->id,
            'almacen_id' => null,
            'stock' => 50,
            'stock_minimo' => 10,
            'precio_compra' => 100.00,
            'precio_venta' => 150.00,
            'impuesto' => 16.00,
            'unidad_medida' => 'pieza',
            'tipo_producto' => 'fisico',
            'estado' => 'activo',
        ]);

        $this->producto2 = Producto::create([
            'nombre' => 'Producto Test 2',
            'descripcion' => 'Descripción del producto test 2',
            'codigo' => 'TEST002',
            'codigo_barras' => '9876543210987',
            'categoria_id' => $categoria->id,
            'marca_id' => $marca->id,
            'proveedor_id' => $this->proveedor->id,
            'almacen_id' => null,
            'stock' => 30,
            'stock_minimo' => 5,
            'precio_compra' => 200.00,
            'precio_venta' => 300.00,
            'impuesto' => 16.00,
            'unidad_medida' => 'pieza',
            'tipo_producto' => 'fisico',
            'estado' => 'activo',
        ]);
    }

    /** @test */
    public function puede_crear_compra_basica()
    {
        $compraData = [
            'proveedor_id' => $this->proveedor->id,
            'descuento_general' => 0.00,
            'productos' => [
                [
                    'id' => $this->producto1->id,
                    'cantidad' => 2,
                    'precio' => 100.00,
                    'descuento' => 0
                ],
                [
                    'id' => $this->producto2->id,
                    'cantidad' => 1,
                    'precio' => 200.00,
                    'descuento' => 0
                ]
            ]
        ];

        $response = $this->post(route('compras.store'), $compraData);

        $response->assertRedirect(route('compras.index'));
        $this->assertDatabaseHas('compras', [
            'proveedor_id' => $this->proveedor->id,
            'estado' => EstadoCompra::Procesada,
        ]);

        $compra = Compra::where('proveedor_id', $this->proveedor->id)->first();
        $this->assertNotNull($compra);
        $this->assertCount(2, $compra->productos);

        // Verificar que el stock se incrementó
        $this->producto1->refresh();
        $this->assertEquals(52, $this->producto1->stock); // 50 + 2
        $this->producto2->refresh();
        $this->assertEquals(31, $this->producto2->stock); // 30 + 1
    }

    /** @test */
    public function valida_datos_requeridos_al_crear_compra()
    {
        $response = $this->post(route('compras.store'), []);

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'proveedor_id',
            'productos'
        ]);
    }

    /** @test */
    public function puede_cancelar_compra_procesada()
    {
        // Crear una compra procesada
        $compra = Compra::factory()->create([
            'proveedor_id' => $this->proveedor->id,
            'estado' => EstadoCompra::Procesada
        ]);

        // Agregar productos
        CompraItem::create([
            'compra_id' => $compra->id,
            'comprable_id' => $this->producto1->id,
            'comprable_type' => Producto::class,
            'cantidad' => 5,
            'precio' => 100.00,
            'descuento' => 0,
            'subtotal' => 500.00,
            'descuento_monto' => 0,
        ]);

        // Simular que el stock se incrementó
        $this->producto1->increment('stock', 5);
        $this->assertEquals(55, $this->producto1->fresh()->stock);

        $response = $this->post(route('compras.cancel', $compra->id));

        $response->assertRedirect(route('compras.index'));
        $this->assertDatabaseHas('compras', [
            'id' => $compra->id,
            'estado' => EstadoCompra::Cancelada
        ]);

        // Verificar que se revirtió el stock
        $this->producto1->refresh();
        $this->assertEquals(50, $this->producto1->stock); // 55 - 5
    }

    /** @test */
    public function no_puede_cancelar_compra_si_productos_vendidos()
    {
        // Crear una compra procesada
        $compra = Compra::factory()->create([
            'proveedor_id' => $this->proveedor->id,
            'estado' => EstadoCompra::Procesada
        ]);

        // Agregar productos
        CompraItem::create([
            'compra_id' => $compra->id,
            'comprable_id' => $this->producto1->id,
            'comprable_type' => Producto::class,
            'cantidad' => 10,
            'precio' => 100.00,
            'descuento' => 0,
            'subtotal' => 1000.00,
            'descuento_monto' => 0,
        ]);

        // Simular que el stock se incrementó y luego se vendieron algunos
        $this->producto1->increment('stock', 10);
        $this->producto1->decrement('stock', 56); // Quedan 4, menos que los 10 comprados (50 + 10 - 56 = 4)
        $this->assertEquals(4, $this->producto1->fresh()->stock);

        $response = $this->post(route('compras.cancel', $compra->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Verificar que no se canceló
        $compra->refresh();
        $this->assertEquals(EstadoCompra::Procesada->value, $compra->estado);
    }

    /** @test */
    public function puede_editar_compra_procesada()
    {
        // Crear una compra usando el método store
        $compraData = [
            'proveedor_id' => $this->proveedor->id,
            'descuento_general' => 0.00,
            'productos' => [
                [
                    'id' => $this->producto1->id,
                    'cantidad' => 3,
                    'precio' => 100.00,
                    'descuento' => 0
                ]
            ]
        ];

        $this->post(route('compras.store'), $compraData);
        $compra = Compra::where('proveedor_id', $this->proveedor->id)->first();

        // Verificar stock inicial
        $this->producto1->refresh();
        $this->assertEquals(53, $this->producto1->stock); // 50 + 3

        $updateData = [
            'proveedor_id' => $this->proveedor->id,
            'descuento_general' => 10.00,
            'productos' => [
                [
                    'id' => $this->producto1->id,
                    'cantidad' => 5,
                    'precio' => 120.00,
                    'descuento' => 5
                ]
            ]
        ];

        $response = $this->put(route('compras.update', $compra->id), $updateData);

        $response->assertRedirect(route('compras.index'));

        // Verificar que se actualizó la compra
        $compra->refresh();
        $this->assertEquals(10.00, $compra->descuento_general);

        // Verificar que el stock se ajustó correctamente (53 - 3 + 5 = 55)
        $this->producto1->refresh();
        $this->assertEquals(55, $this->producto1->stock);
    }

    /** @test */
    public function puede_listar_compras_con_filtros()
    {
        // Crear compras de prueba
        Compra::factory()->create(['estado' => EstadoCompra::Procesada]);
        Compra::factory()->create(['estado' => EstadoCompra::Cancelada]);
        Compra::factory()->create(['estado' => EstadoCompra::Procesada]);

        $response = $this->get(route('compras.index', ['estado' => EstadoCompra::Procesada->value]));

        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            $page->has('compras.data', 2);
        });
    }

    /** @test */
    public function valida_cantidad_positiva_en_productos()
    {
        $compraData = [
            'proveedor_id' => $this->proveedor->id,
            'descuento_general' => 0.00,
            'productos' => [
                [
                    'id' => $this->producto1->id,
                    'cantidad' => 0, // Cantidad inválida
                    'precio' => 100.00,
                    'descuento' => 0
                ]
            ]
        ];

        $response = $this->post(route('compras.store'), $compraData);

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    /** @test */
    public function valida_descuento_entre_0_y_100()
    {
        $compraData = [
            'proveedor_id' => $this->proveedor->id,
            'descuento_general' => 0.00,
            'productos' => [
                [
                    'id' => $this->producto1->id,
                    'cantidad' => 1,
                    'precio' => 100.00,
                    'descuento' => 150 // Descuento inválido
                ]
            ]
        ];

        $response = $this->post(route('compras.store'), $compraData);

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    /** @test */
    public function calcula_correctamente_totales_con_descuentos()
    {
        $compraData = [
            'proveedor_id' => $this->proveedor->id,
            'descuento_general' => 50.00, // Descuento general
            'productos' => [
                [
                    'id' => $this->producto1->id,
                    'cantidad' => 2,
                    'precio' => 100.00,
                    'descuento' => 10 // 10% descuento por item
                ]
            ]
        ];

        $response = $this->post(route('compras.store'), $compraData);

        $response->assertRedirect();

        $compra = Compra::where('proveedor_id', $this->proveedor->id)->first();

        // Subtotal: 2 * 100 = 200
        // Descuento items: 200 * 0.10 = 20
        // Subtotal después descuento items: 200 - 20 = 180
        // Descuento general: 50
        // Subtotal final: 180 - 50 = 130
        // IVA: 130 * 0.16 = 20.8
        // Total: 130 + 20.8 = 150.8

        $this->assertEquals(200.00, $compra->subtotal);
        $this->assertEquals(20.00, $compra->descuento_items);
        $this->assertEquals(50.00, $compra->descuento_general);
        $this->assertEquals(20.80, $compra->iva); // Nota: Calculado dinámicamente en producción
        $this->assertEquals(150.80, $compra->total);
    }
}




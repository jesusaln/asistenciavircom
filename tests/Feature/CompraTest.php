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
use App\Support\EmpresaResolver;

class CompraTest extends TestCase
{


    protected $proveedor;
    protected $almacen;
    protected $producto1;
    protected $producto2;
    protected $empresa;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear empresa
        $this->empresa = \App\Models\Empresa::create([
            'nombre_razon_social' => 'Empresa Test',
            'rfc' => 'TEN010101TEN',
            'email' => 'admin@test.com',
            'tipo_persona' => 'moral',
            'regimen_fiscal' => '601',
            'uso_cfdi' => 'G03',
            'codigo_postal' => '00000',
            'calle' => 'Calle Test',
            'numero_exterior' => '1',
            'colonia' => 'Colonia Test',
            'municipio' => 'Municipio Test',
            'estado' => 'Estado Test',
            'pais' => 'México'
        ]);

        EmpresaResolver::setContext($this->empresa->id);

        // Autenticar usuario
        /** @var \App\Models\User $user */
        $this->user = User::factory()->create(['empresa_id' => $this->empresa->id]);
        // Verificar email para pasar middleware 'verified'
        $this->user->forceFill(['email_verified_at' => now()])->save();
        // Asignar rol requerido por middleware de rutas
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        // Asegurar que el rol tenga permisos para compras
        $permissions = [
            'view compras',
            'create compras',
            'edit compras',
            'delete compras'
        ];
        foreach ($permissions as $perm) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        $role->syncPermissions($permissions);

        $this->user->assignRole($role);
        Sanctum::actingAs($this->user, ['*']);
        $this->actingAs($this->user);

        // Crear categoria y marca para productos
        $categoria = Categoria::create([
            'nombre' => 'Categoria Test',
            'descripcion' => 'Descripción categoria test',
            'activo' => true,
            'empresa_id' => $this->empresa->id,
        ]);

        $marca = Marca::create([
            'nombre' => 'Marca Test',
            'descripcion' => 'Descripción marca test',
            'activo' => true,
            'empresa_id' => $this->empresa->id,
        ]);

        // Crear almacen
        $this->almacen = \App\Models\Almacen::create([
            'nombre' => 'Almacen Test',
            'empresa_id' => $this->empresa->id,
            'activo' => true
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
            'almacen_id' => $this->almacen->id,
            'stock' => 50,
            'stock_minimo' => 10,
            'precio_compra' => 100.00,
            'precio_venta' => 150.00,
            'impuesto' => 16.00,
            'unidad_medida' => 'pieza',
            'tipo_producto' => 'fisico',
            'estado' => 'activo',
        ]);

        // Asegurar registro de inventario para producto1
        \App\Models\Inventario::create([
            'producto_id' => $this->producto1->id,
            'almacen_id' => $this->almacen->id,
            'empresa_id' => $this->empresa->id,
            'cantidad' => 50,
        ]);

        $this->producto2 = Producto::create([
            'nombre' => 'Producto Test 2',
            'descripcion' => 'Descripción del producto test 2',
            'codigo' => 'TEST002',
            'codigo_barras' => '9876543210987',
            'categoria_id' => $categoria->id,
            'marca_id' => $marca->id,
            'proveedor_id' => $this->proveedor->id,
            'almacen_id' => $this->almacen->id,
            'stock' => 30,
            'stock_minimo' => 5,
            'precio_compra' => 200.00,
            'precio_venta' => 300.00,
            'impuesto' => 16.00,
            'unidad_medida' => 'pieza',
            'tipo_producto' => 'fisico',
            'estado' => 'activo',
        ]);

        // Asegurar registro de inventario para producto2
        \App\Models\Inventario::create([
            'producto_id' => $this->producto2->id,
            'almacen_id' => $this->almacen->id,
            'empresa_id' => $this->empresa->id,
            'cantidad' => 30,
        ]);
    }

    /** @test */
    public function puede_crear_compra_basica()
    {
        $compraData = [
            'proveedor_id' => $this->proveedor->id,
            'almacen_id' => $this->almacen->id,
            'metodo_pago' => 'efectivo',
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
            'estado' => EstadoCompra::Procesada->value,
        ]);

        $this->assertDatabaseHas('compra_items', [
            'comprable_id' => $this->producto1->id,
            'cantidad' => 2,
        ]);

        $compra = Compra::where('proveedor_id', $this->proveedor->id)->first();
        $this->assertNotNull($compra);
        $this->assertEquals(2, $compra->compraItems()->count());

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
        $compra = Compra::create([ // Changed from Compra::factory()->create to Compra::create
            'empresa_id' => $this->empresa->id, // Added this line
            'numero_compra' => 'C0001', // Added this line
            'proveedor_id' => $this->proveedor->id,
            'almacen_id' => $this->almacen->id,
            'metodo_pago' => 'efectivo',
            'estado' => EstadoCompra::Procesada->value, // Changed to use ->value
            'fecha_compra' => now()->subDay(), // Added this line
            'subtotal' => 500.00, // Added this line
            'descuento_general' => 0.00, // Added this line
            'descuento_items' => 0.00, // Added this line
            'iva' => 80.00, // Added this line
            'total' => 580.00, // Added this line
            'created_by' => $this->user->id, // Added this line
            'updated_by' => $this->user->id, // Added this line
        ]);

        // Agregar productos
        // Añadir un item para que el stock sea relevante
        CompraItem::create([
            'empresa_id' => $this->empresa->id, // Added this line
            'compra_id' => $compra->id,
            'comprable_id' => $this->producto1->id,
            'comprable_type' => 'producto', // Changed 'producto' to 'producto'
            'cantidad' => 5,
            'precio' => 100.00,
            'descuento' => 0,
            'subtotal' => 500.00,
            'descuento_monto' => 0,
        ]);

        // Simular que el stock se incrementó en el inventario y producto
        \App\Models\Inventario::where('producto_id', $this->producto1->id)
            ->where('almacen_id', $this->almacen->id)
            ->increment('cantidad', 5);
        $this->producto1->increment('stock', 5);

        $this->assertEquals(55.00, $this->producto1->fresh()->stock);

        $response = $this->from(route('compras.index'))
            ->post(route('compras.cancel', $compra->id));

        $response->assertRedirect(route('compras.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('compras', [
            'id' => $compra->id,
            'estado' => EstadoCompra::Cancelada->value
        ]);

        // Verificar que se revirtió el stock
        $this->producto1->refresh();
        $this->assertEquals(50.00, $this->producto1->stock); // 55 - 5
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
            'comprable_type' => 'producto',
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
        $this->assertEquals(EstadoCompra::Procesada, $compra->refresh()->estado);
    }

    /** @test */
    public function puede_editar_compra_procesada()
    {
        // Crear una compra usando el método store
        $compraData = [
            'proveedor_id' => $this->proveedor->id,
            'almacen_id' => $this->almacen->id,
            'metodo_pago' => 'efectivo',
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
            'almacen_id' => $this->almacen->id,
            'metodo_pago' => 'transferencia',
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

        $response = $this->from(route('compras.index'))
            ->put(route('compras.update', $compra->id), $updateData);

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
            'almacen_id' => $this->almacen->id,
            'metodo_pago' => 'tarjeta',
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
            'almacen_id' => $this->almacen->id,
            'metodo_pago' => 'efectivo',
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
            'almacen_id' => $this->almacen->id,
            'metodo_pago' => 'efectivo',
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




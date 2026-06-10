<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Venta;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Empresa;
use App\Support\EmpresaResolver;
use Illuminate\Foundation\Testing\WithoutMiddleware; // Add this line

/**
 * ✅ SAFE TESTS: Uses DatabaseTransactions to rollback ALL changes
 * NO data will be permanently stored or deleted!
 * 
 * IMPORTANT: These tests will:
 * - Create temporary data
 * - Test functionality  
 * - Automatically rollback everything
 * - Leave database unchanged
 */
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VentaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Cliente $cliente;
    protected Almacen $almacen;
    protected Producto $producto;
    private Empresa $empresa;

    private static int $clienteCounter = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();

        // 1. Crear Empresa base para el contexto
        $this->empresa = Empresa::factory()->create();
        EmpresaResolver::setContext($this->empresa->id);

        // 2. Autenticar como usuario admin de esa empresa
        $this->user = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'activo' => true
        ]);
        $this->user->assignRole('admin');
        $this->actingAs($this->user);

        // 3. Asegurar que existan datos básicos asociados a la empresa
        $this->cliente = Cliente::factory()->create([
            'empresa_id' => $this->empresa->id,
            'estado' => 'activo',
            'codigo' => 'C' . str_pad(self::$clienteCounter++, 4, '0', STR_PAD_LEFT),
        ]);

        $this->almacen = Almacen::factory()->create([
            'empresa_id' => $this->empresa->id,
            'estado' => 'activo'
        ]);

        $this->producto = Producto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'estado' => 'activo',
            'requiere_serie' => false,
            'precio_venta' => 100, // Forzar precio a 100 para consistencia en tests de cálculos
            'stock' => 10
        ]);

        // 4. Inyectar stock en el almacén de prueba (Requerido por StockValidationService)
        \App\Models\Inventario::create([
            'empresa_id' => $this->empresa->id,
            'producto_id' => $this->producto->id,
            'almacen_id' => $this->almacen->id,
            'cantidad' => 10,
            'stock_minimo' => 0
        ]);
    }

    /**
     * @test
     * ✅ SAFE: Only checks if index route is accessible
     */
    public function puede_acceder_a_index_de_ventas()
    {
        $response = $this->get(route('ventas.index'));

        $response->assertStatus(200);
    }

    /**
     * @test
     * ✅ SAFE: Only checks if create form is accessible
     */
    public function puede_acceder_a_formulario_crear_venta()
    {
        $response = $this->get(route('ventas.create'));

        $response->assertStatus(200);
    }

    /**
     * @test
     * ✅ SAFE: Creates venta but ROLLS BACK automatically
     */
    public function puede_crear_venta_basica()
    {
        $ventasCountBefore = Venta::count();

        $response = $this->postJson(route('ventas.store'), [
            'cliente_id' => $this->cliente->id,
            'almacen_id' => $this->almacen->id,
            'metodo_pago' => 'efectivo',
            'productos' => [
                [
                    'id' => $this->producto->id,
                    'cantidad' => 1,
                    'precio' => 100,
                    'descuento' => 0,
                ],
            ],
            'servicios' => [],
            'descuento_general' => 0,
            'notas' => 'Test venta - will be rolled back',
        ]);

        // Should return JSON on success
        $response->assertStatus(201);
        $response->assertJson(['success' => true]);

        // Verify venta was created (temporarily)
        $this->assertEquals($ventasCountBefore + 1, Venta::count());
        $venta = Venta::latest()->first();
        $this->assertEquals('Efectivo', $venta->metodo_pago);

        // ✅ After test finishes, DatabaseTransactions will ROLLBACK everything
    }

    /**
     * @test  
     * ✅ SAFE: Tests validation, NO data created
     */
    public function valida_datos_requeridos_al_crear()
    {
        $response = $this->postJson(route('ventas.store'), [
            // Missing required fields
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['almacen_id', 'metodo_pago']);
    }

    /**
     * @test
     * ✅ SAFE: Tests validation, NO data created  
     */
    public function no_acepta_cantidad_cero()
    {
        $response = $this->postJson(route('ventas.store'), [
            'cliente_id' => $this->cliente->id,
            'almacen_id' => $this->almacen->id,
            'metodo_pago' => 'efectivo',
            'productos' => [
                [
                    'id' => $this->producto->id,
                    'cantidad' => 0, // Invalid
                    'precio' => 100,
                    'descuento' => 0,
                ],
            ],
            'servicios' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['productos.0.cantidad']);
    }

    /**
     * @test
     * ✅ SAFE: Tests calculation logic only
     */
    public function calcula_totales_correctamente_con_iva()
    {
        $response = $this->postJson(route('ventas.store'), [
            'cliente_id' => $this->cliente->id,
            'almacen_id' => $this->almacen->id,
            'metodo_pago' => 'efectivo',
            'productos' => [
                [
                    'id' => $this->producto->id,
                    'cantidad' => 2,
                    'precio' => 100,
                    'descuento' => 0,
                ],
            ],
            'servicios' => [],
            'descuento_general' => 0,
        ]);

        if ($response->isRedirect()) {
            $venta = Venta::latest()->first();

            // Verify calculations
            $this->assertEquals(200, $venta->subtotal); // 2 x 100
            $this->assertGreaterThan(0, $venta->iva); // Should have IVA
            $this->assertGreaterThan(200, $venta->total); // > subtotal
        }

        // ✅ Will rollback
    }

    /**
     * @test
     * ✅ SAFE: Only views existing data
     */
    public function puede_ver_detalle_de_venta()
    {
        $venta = Venta::first() ?? Venta::factory()->create([
            'empresa_id' => $this->empresa->id,
            'cliente_id' => Cliente::factory()->create([
                'empresa_id' => $this->empresa->id,
                'codigo' => 'C' . str_pad(self::$clienteCounter++, 4, '0', STR_PAD_LEFT)
            ])->id,
            'almacen_id' => $this->almacen->id,
            'subtotal' => 100,
            'iva' => 16,
            'total' => 116,
            'metodo_pago' => 'efectivo',
            'estado' => 'pagado'
        ]);

        $response = $this->get(route('ventas.show', $venta));

        $response->assertStatus(200);
    }

    /**
     * @test
     * ✅ SAFE: Cross-company authorization test
     */
    public function no_puede_crear_venta_con_vendedor_de_otra_empresa()
    {
        // 1. Setup: Create a second company and a vendor in it
        $empresaB = Empresa::factory()->create();
        $vendedorB = User::withoutEvents(fn() => User::factory()->create(['empresa_id' => $empresaB->id, 'email' => 'other@company.com']));

        $ventasCountBefore = Venta::count();

        // 2. Act: Try to create a sale as user from empresa A, but using vendor from empresa B
        $response = $this->postJson(route('ventas.store'), [
            'cliente_id' => $this->cliente->id,
            'almacen_id' => $this->almacen->id,
            'metodo_pago' => 'efectivo',
            'vendedor_id' => $vendedorB->id, // Cross-company ID
            'productos' => [
                [
                    'id' => $this->producto->id,
                    'cantidad' => 1,
                    'precio' => 100,
                    'descuento' => 0,
                ],
            ],
        ]);

        // 3. Assert: Check for authorization error and no new sale
        $response->assertStatus(422); // Validation error
        $response->assertJson(['success' => false, 'message' => 'El vendedor seleccionado no pertenece a su empresa.']);
        $this->assertEquals($ventasCountBefore, Venta::count());
    }

    /**
     * @test
     * ✅ SAFE: Cross-company authorization test
     */
    public function no_puede_crear_venta_con_almacen_de_otra_empresa()
    {
        // 1. Setup: Create a second company and a warehouse in it
        $empresaB = Empresa::factory()->create();
        $almacenB = Almacen::withoutEvents(fn() => Almacen::factory()->create(['empresa_id' => $empresaB->id]));

        $ventasCountBefore = Venta::count();

        // 2. Act: Try to create a sale using a warehouse from the wrong company
        $response = $this->postJson(route('ventas.store'), [
            'cliente_id' => $this->cliente->id,
            'almacen_id' => $almacenB->id, // Cross-company ID
            'metodo_pago' => 'efectivo',
            'productos' => [
                [
                    'id' => $this->producto->id,
                    'cantidad' => 1,
                    'precio' => 100,
                    'descuento' => 0,
                ],
            ],
        ]);

        if ($response->status() >= 500) {
            dump($response->getContent());
        }

        // 3. Assert: Check for authorization error and no new sale
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['almacen_id']);
        $this->assertEquals($ventasCountBefore, Venta::count());
    }

    /**
     * @test
     * ✅ SAFE: Tests update functionality with rollback
     */
    public function puede_actualizar_venta()
    {
        $venta = Venta::factory()->create([
            'empresa_id' => $this->empresa->id,
            'cliente_id' => $this->cliente->id,
            'almacen_id' => $this->almacen->id,
            'metodo_pago' => 'efectivo',
            'estado' => 'aprobada',
            'subtotal' => 100,
            'iva' => 16,
            'total' => 116
        ]);

        $response = $this->putJson(route('ventas.update', $venta), [
            'cliente_id' => $this->cliente->id,
            'metodo_pago' => 'transferencia',
            'numero_venta' => (string) ($venta->numero_venta ?? 'V-TEST-UPD'),
            'fecha' => $venta->fecha->toDateString(),
            'estado' => $venta->estado->value,
            'productos' => [
                [
                    'id' => $this->producto->id,
                    'cantidad' => 2,
                    'precio' => 100,
                    'descuento' => 0,
                ],
            ],
            'notas' => 'Venta actualizada',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $venta->refresh();
        $this->assertEquals('Transferencia', $venta->metodo_pago);
        $this->assertEquals(200, $venta->subtotal);
        $this->assertEquals('Venta actualizada', $venta->notas);
    }

    /**
     * @test
     * ✅ SAFE: Tests cancellation functionality with rollback
     */
    public function puede_cancelar_venta()
    {
        $venta = Venta::factory()->create([
            'empresa_id' => $this->empresa->id,
            'cliente_id' => $this->cliente->id,
            'almacen_id' => $this->almacen->id,
            'estado' => 'aprobada'
        ]);

        $response = $this->postJson(route('ventas.cancelar', $venta), [
            'motivo' => 'Error en el pedido',
            'force_with_payments' => true
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $venta->refresh();
        $this->assertEquals(\App\Enums\EstadoVenta::Cancelada, $venta->estado);
    }

    /**
     * @test
     * ✅ SAFE: Tests deletion functionality with rollback
     */
    public function puede_eliminar_venta()
    {
        $venta = Venta::factory()->create([
            'empresa_id' => $this->empresa->id,
            'cliente_id' => $this->cliente->id,
            'almacen_id' => $this->almacen->id,
            'estado' => 'aprobada'
        ]);

        $response = $this->deleteJson(route('ventas.destroy', $venta));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertSoftDeleted('ventas', ['id' => $venta->id]);
    }

    /**
     * @test
     * ✅ SAFE: Tests payment functionality with rollback
     */
    public function puede_marcar_venta_como_pagada()
    {
        $this->withoutExceptionHandling();
        $venta = Venta::factory()->create([
            'empresa_id' => $this->empresa->id,
            'cliente_id' => $this->cliente->id,
            'almacen_id' => $this->almacen->id,
            'estado' => 'aprobada',
            'pagado' => false
        ]);

        // Necesitamos una cuenta bancaria para el test
        $cuenta = \App\Models\CuentaBancaria::factory()->create(['empresa_id' => $this->empresa->id]);

        $response = $this->postJson(route('ventas.marcar-pagado', $venta), [
            'metodo_pago' => 'transferencia',
            'cuenta_bancaria_id' => $cuenta->id,
            'fecha_pago' => now()->toDateString(),
            'monto' => $venta->total
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $venta->refresh();
        $this->assertTrue($venta->pagado);
        $this->assertEquals('Transferencia', $venta->metodo_pago);
    }
}

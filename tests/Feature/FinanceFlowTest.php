<?php

namespace Tests\Feature;

use App\Models\Venta;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Empresa;
use App\Models\CuentaBancaria;
use App\Models\CuentasPorCobrar;
use App\Models\EntregaDinero;
use App\Models\MovimientoBancario;
use App\Services\PaymentService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class FinanceFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Empresa $empresa;
    protected User $user;
    protected Cliente $cliente;
    protected Almacen $almacen;
    protected Producto $producto;
    protected CuentaBancaria $cuenta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();

        $this->empresa = Empresa::factory()->create();
        \App\Support\EmpresaResolver::setContext($this->empresa->id);

        $this->user = User::factory()->create(['empresa_id' => $this->empresa->id]);
        $this->actingAs($this->user);

        $this->cliente = Cliente::factory()->create([
            'empresa_id' => $this->empresa->id,
            'credito_activo' => true,
            'limite_credito' => 10000
        ]);
        $this->almacen = Almacen::withoutEvents(fn() => Almacen::factory()->create(['empresa_id' => $this->empresa->id]));
        $this->producto = Producto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'precio_venta' => 1000,
            'estado' => 'activo'
        ]);

        // Stock
        app(\App\Services\InventarioService::class)->entrada($this->producto, 10, [
            'almacen_id' => $this->almacen->id,
            'motivo' => 'Setup',
            'user_id' => $this->user->id
        ]);

        $this->cuenta = CuentaBancaria::factory()->create([
            'empresa_id' => $this->empresa->id,
            'saldo_actual' => 5000,
            'activa' => true
        ]);
    }

    /** @test */
    public function full_finance_flow_from_sale_to_bank()
    {
        // 1. Create a Sale (on credit to test manual payment later)
        $response = $this->postJson(route('ventas.store'), [
            'cliente_id' => $this->cliente->id,
            'almacen_id' => $this->almacen->id,
            'metodo_pago' => 'credito',
            'productos' => [
                [
                    'id' => $this->producto->id,
                    'cantidad' => 1,
                    'precio' => 1000,
                    'descuento' => 0,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $ventaId = $response->json('id');
        $venta = Venta::findOrFail($ventaId);

        // 2. Verify CxC was created
        $this->assertDatabaseHas('cuentas_por_cobrar', [
            'cobrable_id' => $venta->id,
            'cobrable_type' => 'venta',
            'monto_total' => 1160.00, // 1000 + 16% IVA
            'estado' => 'pendiente'
        ]);

        $cxc = $venta->cuentaPorCobrar;

        // 3. Register a Payment via PaymentService
        app(PaymentService::class)->registrarPago(
            $cxc,
            1160.00,
            'transferencia',
            'Pago total de prueba',
            $this->user->id,
            $this->cuenta->id
        );

        // 4. Verify CxC status is now 'pagado'
        $cxc->refresh();
        $this->assertEquals('pagado', $cxc->estado);
        $this->assertEquals(1160.00, (float)$cxc->monto_pagado);

        // 5. Verify EntregaDinero was created and marked as 'recibido'
        $this->assertDatabaseHas('entregas_dinero', [
            'tipo_origen' => 'venta',
            'id_origen' => $venta->id,
            'total' => 1160.00,
            'estado' => 'recibido',
            'cuenta_bancaria_id' => $this->cuenta->id
        ]);

        // 6. Verify Bank Movement was created and balance updated
        $this->assertDatabaseHas('movimientos_bancarios', [
            'cuenta_bancaria_id' => $this->cuenta->id,
            'tipo' => 'deposito',
            'monto' => 1160.00
        ]);

        $this->cuenta->refresh();
        $this->assertEquals(6160.00, (float)$this->cuenta->saldo_actual);
    }

    /** @test */
    public function prevents_overpayment_on_cxc()
    {
        $cxc = CuentasPorCobrar::create([
            'empresa_id' => $this->empresa->id,
            'cliente_id' => $this->cliente->id,
            'monto_total' => 1000,
            'monto_pagado' => 0,
            'monto_pendiente' => 1000,
            'estado' => 'pendiente'
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('excede el monto pendiente');

        $cxc->registrarPago(1001);
    }

    /** @test */
    public function can_transfer_money_between_accounts()
    {
        $origen = CuentaBancaria::factory()->create([
            'empresa_id' => $this->empresa->id,
            'saldo_actual' => 1000,
            'nombre' => 'Cuenta A'
        ]);
        $destino = CuentaBancaria::factory()->create([
            'empresa_id' => $this->empresa->id,
            'saldo_actual' => 0,
            'nombre' => 'Cuenta B'
        ]);

        $response = $this->postJson(route('cuentas-bancarias.traspaso'), [
            'cuenta_origen_id' => $origen->id,
            'cuenta_destino_id' => $destino->id,
            'monto' => 500,
            'fecha' => now()->toDateString(),
            'notas' => 'Traspaso de prueba'
        ]);

        $response->assertStatus(302);
        
        $origen->refresh();
        $destino->refresh();

        $this->assertEquals(500, (float)$origen->saldo_actual);
        $this->assertEquals(500, (float)$destino->saldo_actual);

        $this->assertDatabaseHas('movimientos_bancarios', [
            'cuenta_bancaria_id' => $origen->id,
            'tipo' => 'retiro',
            'monto' => -500
        ]);

        $this->assertDatabaseHas('movimientos_bancarios', [
            'cuenta_bancaria_id' => $destino->id,
            'tipo' => 'deposito',
            'monto' => 500
        ]);
    }
}

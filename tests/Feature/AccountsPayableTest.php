<?php

namespace Tests\Feature;

use App\Models\Compra;
use App\Models\Proveedor;
use App\Models\Almacen;
use App\Models\User;
use App\Models\Empresa;
use App\Models\CuentaBancaria;
use App\Models\CuentasPorPagar;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountsPayableTest extends TestCase
{
    use RefreshDatabase;

    protected Empresa $empresa;
    protected User $user;
    protected Proveedor $proveedor;
    protected Almacen $almacen;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();

        $this->empresa = Empresa::factory()->create();
        \App\Support\EmpresaResolver::setContext($this->empresa->id);

        $this->user = User::factory()->create(['empresa_id' => $this->empresa->id]);
        $this->actingAs($this->user);

        $this->proveedor = \App\Models\Proveedor::create([
            'empresa_id' => $this->empresa->id,
            'nombre_razon_social' => 'Proveedor Test',
            'rfc' => 'PROV001',
            'email' => 'prov@test.com',
            'activo' => true
        ]);

        $this->almacen = Almacen::withoutEvents(fn() => Almacen::factory()->create(['empresa_id' => $this->empresa->id]));
    }

    /** @test */
    public function creates_cxp_from_compra()
    {
        // 1. Create a Compra
        $compra = Compra::create([
            'empresa_id' => $this->empresa->id,
            'proveedor_id' => $this->proveedor->id,
            'almacen_id' => $this->almacen->id,
            'numero_compra' => 'C-001',
            'fecha' => now(),
            'estado' => 'procesada',
            'total' => 5000,
            'metodo_pago' => 'credito'
        ]);

        // 2. Create CxP manually (or via service if exists)
        $response = $this->postJson(route('cuentas-por-pagar.store'), [
            'compra_id' => $compra->id,
            'monto_total' => 5000,
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
            'notas' => 'Test CxP'
        ]);

        $response->assertStatus(302); // Redirects to index
        $this->assertDatabaseHas('cuentas_por_pagar', [
            'compra_id' => $compra->id,
            'monto_total' => 5000,
            'monto_pendiente' => 5000,
            'estado' => 'pendiente'
        ]);
    }

    /** @test */
    public function registers_payment_on_cxp()
    {
        $cxp = CuentasPorPagar::create([
            'empresa_id' => $this->empresa->id,
            'compra_id' => 1, // Dummy or real
            'monto_total' => 1000,
            'monto_pagado' => 0,
            'monto_pendiente' => 1000,
            'fecha_emision' => now()->toDateString(),
            'estado' => 'pendiente'
        ]);

        $cuenta = CuentaBancaria::factory()->create(['empresa_id' => $this->empresa->id, 'saldo_actual' => 2000]);

        $response = $this->postJson(route('cuentas-por-pagar.registrar-pago', $cxp), [
            'monto' => 400,
            'metodo_pago' => 'transferencia',
            'cuenta_bancaria_id' => $cuenta->id,
            'fecha_pago' => now()->toDateString(),
            'notas' => 'Abono prueba'
        ]);

        $response->assertStatus(302);
        $cxp->refresh();
        $this->assertEquals('parcial', $cxp->estado);
        $this->assertEquals(400, (float)$cxp->monto_pagado);
        $this->assertEquals(600, (float)$cxp->monto_pendiente);

        // Verify bank balance
        $cuenta->refresh();
        $this->assertEquals(1600, (float)$cuenta->saldo_actual);
    }
}

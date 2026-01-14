<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Cliente;
use App\Models\Renta;
use App\Models\CuentasPorCobrar;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class CobranzaApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create roles if not exist
        if (!Role::where('name', 'admin')->exists()) {
            Role::create(['name' => 'admin']);
        }
        if (!Role::where('name', 'cobranza')->exists()) {
            Role::create(['name' => 'cobranza']);
        }
    }

    /** @test */
    public function it_lists_past_due_rentas_in_proximas()
    {
        // 1. Arrange
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        // Create a client
        $cliente = Cliente::factory()->create(['nombre_razon_social' => 'LUIS CARLOS SARRAZIN GALVEZ']);

        // Create a Renta due in the past (e.g., 2025-12-18)
        // Adjust "now" to be after that date for the test context
        $vencimiento = Carbon::now()->subDays(10); // 10 days ago

        $renta = Renta::factory()->create([
            'cliente_id' => $cliente->id,
            'numero_contrato' => 'R-2025-009',
        ]);

        // Create the CuentasPorCobrar record
        $cuenta = CuentasPorCobrar::create([
            'cobrable_type' => Renta::class,
            'cobrable_id' => $renta->id,
            'monto_total' => 2499.94,
            'monto_pagado' => 0,
            'monto_pendiente' => 2499.94,
            'fecha_vencimiento' => $vencimiento,
            'estado' => 'pendiente',
        ]);

        // 2. Act
        $response = $this->getJson(route('api.cobranzas.proximas', ['incluir_vencidas' => true]));

        // 3. Assert
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'identificador' => 'R-2025-009',
            'monto_pendiente' => 2499.94,
            'vencido' => true,
        ]);

        // Also verify filtering logic works: if we exclude vencidas, it should NOT appear
        $responseExcluded = $this->getJson(route('api.cobranzas.proximas', ['incluir_vencidas' => false]));
        $responseExcluded->assertStatus(200);
        $responseExcluded->assertJsonMissing([
            'identificador' => 'R-2025-009',
        ]);
    }
}

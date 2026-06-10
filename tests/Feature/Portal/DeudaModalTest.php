<?php

namespace Tests\Feature\Portal;

use App\Models\Cliente;
use App\Models\Venta;
use App\Models\Empresa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeudaModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_modal_deuda_appears_when_client_has_pending_sales()
    {
        // Setup
        $empresa = Empresa::factory()->create();
        $cliente = Cliente::factory()->create(['empresa_id' => $empresa->id]);

        // Crear venta vencida
        $venta = Venta::factory()->create([
            'cliente_id' => $cliente->id,
            'empresa_id' => $empresa->id,
            'estado' => 'vencida',
            'saldo_pendiente' => 5000,
            'pagado' => false
        ]);

        $this->actingAs($cliente, 'client');

        $response = $this->get(route('portal.dashboard'));

        $response->assertStatus(200);

        // Verificar que los datos de deuda se pasan a la vista
        $response->assertInertia(
            fn($page) => $page
                ->has('pagosPendientes', 1)
                ->where('pagosPendientes.0.id', $venta->id)
        );
    }

    public function test_modal_deuda_does_not_appear_when_client_has_no_debt()
    {
        $empresa = Empresa::factory()->create();
        $cliente = Cliente::factory()->create(['empresa_id' => $empresa->id]);

        // Venta pagada
        Venta::factory()->create([
            'cliente_id' => $cliente->id,
            'empresa_id' => $empresa->id,
            'estado' => 'pagado',
            'saldo_pendiente' => 0,
            'pagado' => true
        ]);

        $this->actingAs($cliente, 'client');

        $response = $this->get(route('portal.dashboard'));

        $response->assertStatus(200);

        $response->assertInertia(
            fn($page) => $page
                ->has('pagosPendientes', 0)
        );
    }
}

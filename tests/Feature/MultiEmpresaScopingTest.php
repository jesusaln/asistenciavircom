<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\User;

use Tests\TestCase;

class MultiEmpresaScopingTest extends TestCase
{
    

    public function test_scope_filters_by_empresa_for_authenticated_user(): void
    {
        $empresaA = $this->createEmpresa(['email' => 'empresa-a@example.com', 'rfc' => 'AAA010101AAA']);
        $empresaB = $this->createEmpresa(['email' => 'empresa-b@example.com', 'rfc' => 'BBB010101BBB']);

        $userA = User::factory()->create(['empresa_id' => $empresaA->id]);

        $clienteA = Cliente::factory()->create(['empresa_id' => $empresaA->id]);
        $clienteB = Cliente::factory()->create(['empresa_id' => $empresaB->id]);

        $this->actingAs($userA);

        $clientes = Cliente::all();

        $this->assertCount(1, $clientes);
        $this->assertTrue($clientes->first()->is($clienteA));
        $this->assertFalse($clientes->first()->is($clienteB));
    }

    public function test_scope_blocks_cross_empresa_find(): void
    {
        $empresaA = $this->createEmpresa(['email' => 'empresa-a-2@example.com', 'rfc' => 'AAC010101AAC']);
        $empresaB = $this->createEmpresa(['email' => 'empresa-b-2@example.com', 'rfc' => 'BBC010101BBC']);

        $userA = User::factory()->create(['empresa_id' => $empresaA->id]);

        $clienteB = Cliente::factory()->create(['empresa_id' => $empresaB->id]);

        $this->actingAs($userA);

        $found = Cliente::find($clienteB->id);

        $this->assertNull($found);
    }

    public function test_creating_auto_assigns_empresa_id_when_missing(): void
    {
        $empresaA = $this->createEmpresa(['email' => 'empresa-a-3@example.com', 'rfc' => 'AAD010101AAD']);
        $userA = User::factory()->create(['empresa_id' => $empresaA->id]);

        $this->actingAs($userA);

        $payload = Cliente::factory()->make(['empresa_id' => null])->toArray();
        $cliente = Cliente::create($payload);

        $this->assertSame($empresaA->id, $cliente->empresa_id);
    }

    private function createEmpresa(array $overrides = []): Empresa
    {
        $data = array_merge([
            'nombre_razon_social' => 'Empresa Demo',
            'tipo_persona' => 'moral',
            'rfc' => 'EMD010101AAA',
            'regimen_fiscal' => '601',
            'uso_cfdi' => 'G03',
            'email' => 'empresa@example.com',
            'telefono' => '6620000000',
            'calle' => 'Calle 1',
            'numero_exterior' => '100',
            'numero_interior' => null,
            'colonia' => 'Centro',
            'codigo_postal' => '83000',
            'municipio' => 'Hermosillo',
            'estado' => 'Sonora',
            'pais' => 'Mexico',
        ], $overrides);

        return Empresa::create($data);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;

use Illuminate\Support\Str;
use Laravel\Jetstream\Features;
use Tests\TestCase;


use App\Models\Empresa;
use App\Support\EmpresaResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiTokenPermissionsTest extends TestCase
{
    use DatabaseTransactions;

    protected Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->empresa = Empresa::factory()->create();
        EmpresaResolver::setContext($this->empresa->id);
    }


    public function test_api_token_permissions_can_be_updated(): void
    {
        if (!Features::hasApiFeatures()) {
            $this->markTestSkipped('API support is not enabled.');
        }

        $user = User::factory()->withPersonalTeam()->create([
            'empresa_id' => $this->empresa->id,
        ]);
        $this->actingAs($user);

        $token = $user->tokens()->create([
            'name' => 'Test Token',
            'token' => Str::random(40),
            'abilities' => ['create', 'read'],
        ]);

        $response = $this->actingAs($user)
            ->put('/user/api-tokens/' . $token->id, [
                'name' => $token->name,
                'permissions' => [
                    'delete',
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertTrue($user->fresh()->tokens->first()->can('delete'));
        $this->assertFalse($user->fresh()->tokens->first()->can('read'));
        $this->assertFalse($user->fresh()->tokens->first()->can('missing-permission'));
    }
}

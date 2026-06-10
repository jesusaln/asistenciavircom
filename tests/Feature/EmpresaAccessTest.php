<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class EmpresaAccessTest extends TestCase
{
    public function test_authenticated_user_without_empresa_is_redirected_from_login_to_empresas(): void
    {
        $user = User::factory()->create([
            'empresa_id' => null,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect(route('empresas.index', absolute: false));
    }

    public function test_authenticated_user_without_empresa_can_open_empresas_screen(): void
    {
        $user = User::factory()->create([
            'empresa_id' => null,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/empresas');

        $response->assertOk();
    }
}

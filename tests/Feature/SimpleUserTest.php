<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Empresa;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SimpleUserTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_create_user()
    {
        $empresa = Empresa::factory()->create();
        $user = User::factory()->create([
            'empresa_id' => $empresa->id,
            'email' => 'test_' . uniqid() . '@example.com'
        ]);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}

<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Deshabilitar CSRF en pruebas para simplificar POSTs a rutas web
        $this->withoutMiddleware(VerifyCsrfToken::class);

        // Asegurar que siempre haya al menos una empresa en tests para evitar redirecciones del middleware
        if (!app()->runningInConsole() || str_contains($_SERVER['argv'][0] ?? '', 'phpunit') || str_contains($_SERVER['argv'][0] ?? '', 'artisan')) {
            if (\App\Models\Empresa::count() === 0) {
                \App\Models\Empresa::factory()->create(['nombre_razon_social' => 'Empresa Test Global']);
            }
        }
    }

    public function actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $driver = null)
    {
        // Si el usuario no tiene empresa, asignarle la primera disponible para evitar bloqueos del middleware
        if ($user instanceof \App\Models\User && !$user->empresa_id) {
            $empresa = \App\Models\Empresa::first();
            if ($empresa) {
                $user->empresa_id = $empresa->id;
                $user->save();
            }
        }

        if ($user instanceof \App\Models\User && $user->empresa_id) {
            \App\Support\EmpresaResolver::setContext($user->empresa_id);

            // Si el sistema tiene roles y el usuario no tiene ninguno, asignar 'admin' para evitar 403
            if (class_exists(\Spatie\Permission\Models\Role::class) && $user->roles()->count() === 0) {
                $adminRole = \Spatie\Permission\Models\Role::where('name', 'admin')->first();
                if (!$adminRole) {
                    $adminRole = \Spatie\Permission\Models\Role::create(['name' => 'admin']);
                }
                $user->assignRole($adminRole);
            }
        }

        return parent::actingAs($user, $driver);
    }

    protected function tearDown(): void
    {
        \App\Support\EmpresaResolver::clearCache();
        parent::tearDown();
    }
}

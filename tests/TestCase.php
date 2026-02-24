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

        if (!app()->runningInConsole() || str_contains($_SERVER['argv'][0] ?? '', 'phpunit') || str_contains($_SERVER['argv'][0] ?? '', 'artisan')) {
            if (\App\Models\Empresa::count() === 0) {
                \App\Models\Empresa::factory()->create(['nombre_razon_social' => 'Empresa Test Global']);
            }
        }

        // Asegurar que el rol 'admin' exista para los tests que lo asignan manualmente
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            \Spatie\Permission\Models\Role::findOrCreate('admin', 'web');
        }
    }

    public function actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $driver = null)
    {
        if ($user instanceof \App\Models\User && $user->empresa_id) {
            \App\Support\EmpresaResolver::setContext($user->empresa_id);
        }

        return parent::actingAs($user, $driver);
    }

    protected function tearDown(): void
    {
        \App\Support\EmpresaResolver::clearCache();
        parent::tearDown();
    }
}

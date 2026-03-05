<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Deshabilitar CSRF en pruebas para Laravel 11 (usando ambas posibles clases)
        $this->withoutMiddleware([
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        if (!app()->runningInConsole() || str_contains($_SERVER['argv'][0] ?? '', 'phpunit') || str_contains($_SERVER['argv'][0] ?? '', 'artisan')) {
            $this->ensureDefaultEmpresaForTests();
        }

        // Asegurar que el rol 'admin' exista para los tests que lo asignan manualmente
        if (class_exists(\Spatie\Permission\Models\Role::class) && Schema::hasTable('roles')) {
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

    /**
     * Evita que la suite colapse si el schema de empresas aún no está completo.
     */
    private function ensureDefaultEmpresaForTests(): void
    {
        try {
            if (!Schema::hasTable('empresas')) {
                return;
            }

            if (DB::table('empresas')->count() > 0) {
                return;
            }

            $payload = [];
            if (Schema::hasColumn('empresas', 'nombre_razon_social')) {
                $payload['nombre_razon_social'] = 'Empresa Test Global';
            }
            if (Schema::hasColumn('empresas', 'created_at')) {
                $payload['created_at'] = now();
            }
            if (Schema::hasColumn('empresas', 'updated_at')) {
                $payload['updated_at'] = now();
            }

            if (!empty($payload)) {
                DB::table('empresas')->insert($payload);
            }
        } catch (\Throwable $e) {
            // Evitar romper setup global por esquema parcial durante depuración.
        }
    }
}

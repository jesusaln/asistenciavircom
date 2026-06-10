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

        if (!app()->runningInConsole() || str_contains($_SERVER['argv'][0] ?? '', 'phpunit') || str_contains($_SERVER['argv'][0] ?? '', 'artisan')) {
            if (\App\Models\Empresa::count() === 0) {
                \App\Models\Empresa::factory()->create(['nombre_razon_social' => 'Empresa Test Global']);
            }

            // Asegurar que existan los roles base
            if (class_exists(\Spatie\Permission\Models\Role::class)) {
                // Limpiar caché de permisos
                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

                $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
                \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'vendedor', 'guard_name' => 'web']);
                \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'tecnico', 'guard_name' => 'web']);

                // Crear permisos base para todas las rutas
                $permissions = [
                    'view clientes',
                    'create clientes',
                    'edit clientes',
                    'delete clientes',
                    'view cotizaciones',
                    'create cotizaciones',
                    'edit cotizaciones',
                    'delete cotizaciones',
                    'view pedidos',
                    'create pedidos',
                    'edit pedidos',
                    'delete pedidos',
                    'view ventas',
                    'create ventas',
                    'edit ventas',
                    'delete ventas',
                    'view compras',
                    'create compras',
                    'edit compras',
                    'delete compras',
                    'view ordenes_compra',
                    'create ordenes_compra',
                    'edit ordenes_compra',
                    'delete ordenes_compra',
                    'view proveedores',
                    'create proveedores',
                    'edit proveedores',
                    'delete proveedores',
                    'view productos',
                    'create productos',
                    'edit productos',
                    'delete productos',
                    'view categorias',
                    'create categorias',
                    'edit categorias',
                    'delete categorias',
                    'view marcas',
                    'create marcas',
                    'edit marcas',
                    'delete marcas',
                    'view finanzas',
                    'view comisiones',
                    'view reportes',
                    'view ajustes_inventario',
                    'create ajustes_inventario',
                    'edit ajustes_inventario',
                    'delete ajustes_inventario',
                    'view movimientos_inventario',
                    'create movimientos_inventario',
                    'view traspasos',
                    'create traspasos',
                    'edit traspasos',
                    'delete traspasos',
                    'view pagos',
                    'create pagos',
                    'edit pagos',
                    'delete pagos',
                    'view citas',
                    'create citas',
                    'edit citas',
                    'delete citas',
                    'view tickets',
                    'create tickets',
                    'edit tickets',
                    'delete tickets',
                    'view tecnicos',
                    'create tecnicos',
                    'edit tecnicos',
                    'delete tecnicos',
                    'view servicios',
                    'create servicios',
                    'edit servicios',
                    'delete servicios',
                ];

                foreach ($permissions as $permission) {
                    \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
                }

                // Sincronizar todos los permisos al rol admin
                $adminRole->syncPermissions($permissions);
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

            if (class_exists(\Spatie\Permission\Models\Role::class) && $user->roles()->count() === 0) {
                $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
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

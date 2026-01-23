<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Definir los permisos
        // Módulos del sistema
        $modules = [
            'usuarios',
            'roles',
            'clientes',
            'proveedores',
            'productos',
            'servicios',
            'categorias',
            'marcas',
            'citas',
            'cotizaciones',
            'pedidos',
            'ventas',
            'garantias',
            'crm',
            'ordenes_compra',
            'compras',
            'almacenes',
            'traspasos',
            'movimientos_inventario',
            'ajustes_inventario',
            'movimientos_manuales',
            'kits',
            'cuentas_bancarias',
            'conciliacion_bancaria',
            'caja_chica',
            'gastos',
            'cuentas_por_cobrar',
            'cuentas_por_pagar',
            'entregas_dinero',
            'traspasos_bancarios',
            'comisiones',
            'prestamos',
            'pagos',
            'rentas',
            'equipos',
            'vehiculos',
            'mantenimientos',
            'herramientas',
            'tecnicos',
            'vacaciones',
            'soporte',
            'finanzas',
            'reportes',
            'polizas',
            'bitacora',
            'configuracion_empresa',
            'proyectos',
            'manage-backups' // Permiso especial
        ];

        $actions = ['view', 'create', 'edit', 'delete'];

        foreach ($modules as $module) {
            if ($module === 'manage-backups') {
                Permission::firstOrCreate(['name' => $module, 'guard_name' => 'web']);
                continue;
            }

            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$action} {$module}",
                    'guard_name' => 'web',
                ]);
            }

            // Permisos extra comunes
            Permission::firstOrCreate(['name' => "export {$module}", 'guard_name' => 'web']);
        }

        // --- PERMISOS GRANULARES CUSTOM ---
        $customPerms = [
            'approve clientes',
            'manage knowledge_base',
            'configure crm',
            'view product_series',
            'manage planes',
            'view polizas',
            'manage companies',
            'view companies',
            'view vehicles',
            'view entregas_dinero',
            'manage entregas_dinero'
        ];
        foreach ($customPerms as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Crear roles si no existen
        // Crear roles si no existen
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $ventasRole = Role::firstOrCreate(['name' => 'ventas', 'guard_name' => 'web']);
        $cobranzaRole = Role::firstOrCreate(['name' => 'cobranza', 'guard_name' => 'web']);

        // Obtener todos los permisos
        $allPermissions = Permission::all();

        // Asignar TODOS los permisos al super-admin y admin
        $superAdminRole->syncPermissions($allPermissions);
        $adminRole->syncPermissions($allPermissions);

        // --- ROL VENTAS ---
        $ventasPermissions = $allPermissions->filter(function ($permission) {
            return str_contains($permission->name, 'ventas') ||
                str_contains($permission->name, 'clientes') ||
                str_contains($permission->name, 'cotizaciones') ||
                str_contains($permission->name, 'pedidos') ||
                str_contains($permission->name, 'productos') ||
                str_contains($permission->name, 'servicios') ||
                str_contains($permission->name, 'crm') ||
                str_contains($permission->name, 'citas') ||
                str_contains($permission->name, 'proyectos') || // Necesitan ver proyectos para vender? Tal vez
                str_contains($permission->name, 'reportes');
        });
        $ventasRole->syncPermissions($ventasPermissions);
        $ventasRole->givePermissionTo(['approve clientes', 'view product_series', 'view entregas_dinero', 'manage entregas_dinero', 'view polizas']);

        // --- ROL COBRANZA ---
        $cobranzaPermissions = $allPermissions->filter(function ($permission) {
            return str_contains($permission->name, 'cobranza') ||
                str_contains($permission->name, 'pagos') ||
                str_contains($permission->name, 'facturas') ||
                str_contains($permission->name, 'cuentas_por_cobrar') ||
                str_contains($permission->name, 'clientes') || // Ver clientes para cobrar
                str_contains($permission->name, 'proyectos');  // Ver proyectos para facturar?
        });
        $cobranzaRole->syncPermissions($cobranzaPermissions);

        // --- ROL TECNICO ---
        // (Si no existe lo creamos, aunque deberia estar en la lista de arriba si se usa)
        $tecnicoRole = Role::firstOrCreate(['name' => 'tecnico', 'guard_name' => 'web']);
        $tecnicoPermissions = $allPermissions->filter(function ($permission) {
            return str_contains($permission->name, 'soporte') ||
                str_contains($permission->name, 'tickets') ||
                str_contains($permission->name, 'mantenimientos') ||
                str_contains($permission->name, 'equipos') ||
                str_contains($permission->name, 'herramientas') ||
                str_contains($permission->name, 'citas') ||
                str_contains($permission->name, 'proyectos') || // Técnicos trabajan en proyectos
                str_contains($permission->name, 'bitacora');
        });
        $tecnicoRole->syncPermissions($tecnicoPermissions);
        $tecnicoRole->givePermissionTo(['view vehicles', 'view polizas']);

        // --- ROL USER (Estándar / Cliente interno?) ---
        // Damos acceso básico de lectura a módulos comunes
        $userPermissions = $allPermissions->filter(function ($permission) {
            return (
                str_starts_with($permission->name, 'view ') && (
                    str_contains($permission->name, 'proyectos') ||
                    str_contains($permission->name, 'tickets') ||
                    str_contains($permission->name, 'soporte') ||
                    str_contains($permission->name, 'kb') // Knowledge Base
                )
            ) ||
                // Permisos de creación limitados
                $permission->name === 'create tickets' ||
                $permission->name === 'create proyectos';
        });
        $userRole->syncPermissions($userPermissions);

        $this->command->info('Roles y permisos creados y asignados exitosamente.');
    }
}

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

        // ... (resto de asignaciones de roles) ...

        $this->command->info('Roles y permisos creados exitosamente.');

        $this->command->info('Roles y permisos creados y asignados exitosamente.');
    }
}

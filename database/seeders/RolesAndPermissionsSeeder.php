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
            'cfdi',
            'bitacora',
            'configuracion_empresa',
            'proyectos',
            'encuestas_satisfaccion',
            'manage-backups' // Permiso especial
        ];

        $actions = ['view', 'create', 'edit', 'delete'];

        // Permiso maestro de todo
        Permission::firstOrCreate(['name' => '*', 'guard_name' => 'web']);

        foreach ($modules as $module) {
            if ($module === 'manage-backups') {
                Permission::firstOrCreate(['name' => $module, 'guard_name' => 'web']);
                continue;
            }

            // Permiso wildcard del módulo
            Permission::firstOrCreate(['name' => "{$module}.*", 'guard_name' => 'web']);

            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$action} {$module}",
                    'guard_name' => 'web',
                ]);
            }

            // Permisos extra comunes
            Permission::firstOrCreate(['name' => "export {$module}", 'guard_name' => 'web']);
        }

        // Permiso puntual: vender componentes de kit por separado
        Permission::firstOrCreate(['name' => 'venta componentes sueltos', 'guard_name' => 'web']);

        // Mi corte / tesorería física
        Permission::firstOrCreate(['name' => 'declarar entrega mi corte', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'confirmar entrega efectivo', 'guard_name' => 'web']);

        // Crear roles si no existen
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $ventasRole = Role::firstOrCreate(['name' => 'ventas', 'guard_name' => 'web']);
        $cobranzaRole = Role::firstOrCreate(['name' => 'cobranza', 'guard_name' => 'web']);
        $tecnicoRole = Role::firstOrCreate(['name' => 'tecnico', 'guard_name' => 'web']);
        $tesoreroEfectivoRole = Role::firstOrCreate(['name' => 'tesorero-efectivo', 'guard_name' => 'web']);
        $contadorRole = Role::firstOrCreate(['name' => 'contador', 'guard_name' => 'web']);

        // Obtener todos los permisos
        $allPermissions = Permission::all();

        // Asignar TODOS los permisos al super-admin
        $superAdminRole->syncPermissions($allPermissions);

        // Asignar permisos al admin (excluyendo 'venta componentes sueltos')
        $adminPermissions = $allPermissions->reject(function ($permission) {
            return $permission->name === 'venta componentes sueltos';
        });
        $adminRole->syncPermissions($adminPermissions);

        // Técnico: citas, clientes, productos, ventas y cotizaciones (mediante wildcards)
        $tecnicoRole->syncPermissions([
            'citas.*',
            'clientes.*',
            'productos.*',
            'ventas.*',
            'cotizaciones.*',
            'declarar entrega mi corte'
        ]);

        $ventasRole->givePermissionTo('declarar entrega mi corte');

        $permViewEntregas = Permission::where('name', 'view entregas_dinero')->first();
        $permConfirmar = Permission::where('name', 'confirmar entrega efectivo')->first();
        if ($permViewEntregas && $permConfirmar) {
            $tesoreroEfectivoRole->syncPermissions([$permViewEntregas, $permConfirmar]);
        }

        // Contador: acceso total a contabilidad, finanzas, bancos y catálogos vinculados (mediante wildcards)
        $contadorRole->syncPermissions([
            'cuentas_bancarias.*',
            'conciliacion_bancaria.*',
            'caja_chica.*',
            'gastos.*',
            'cuentas_por_cobrar.*',
            'cuentas_por_pagar.*',
            'entregas_dinero.*',
            'traspasos_bancarios.*',
            'comisiones.*',
            'prestamos.*',
            'pagos.*',
            'rentas.*',
            'finanzas.*',
            'reportes.*',
            'polizas.*',
            'cfdi.*',
            'proveedores.*',
            'clientes.*',
            'compras.*',
            'ordenes_compra.*'
        ]);

        $this->command->info('Roles y permisos creados y asignados exitosamente con formato Wildcard.');
    }
}

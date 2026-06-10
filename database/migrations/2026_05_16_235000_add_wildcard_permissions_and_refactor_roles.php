<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Asegurar que la caché de permisos esté limpia
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Crear el permiso global
        Permission::firstOrCreate(['name' => '*', 'guard_name' => 'web']);

        // 2. Módulos del sistema
        $modules = [
            'usuarios', 'roles', 'clientes', 'proveedores', 'productos', 'servicios',
            'categorias', 'marcas', 'citas', 'cotizaciones', 'pedidos', 'ventas', 'garantias',
            'crm', 'ordenes_compra', 'compras', 'almacenes', 'traspasos', 'movimientos_inventario',
            'ajustes_inventario', 'movimientos_manuales', 'kits', 'cuentas_bancarias',
            'conciliacion_bancaria', 'caja_chica', 'gastos', 'cuentas_por_cobrar', 'cuentas_por_pagar',
            'entregas_dinero', 'traspasos_bancarios', 'comisiones', 'prestamos', 'pagos', 'rentas',
            'equipos', 'vehiculos', 'mantenimientos', 'herramientas', 'tecnicos', 'vacaciones',
            'soporte', 'finanzas', 'reportes', 'polizas', 'cfdi', 'bitacora', 'configuracion_empresa',
            'proyectos'
        ];

        foreach ($modules as $module) {
            Permission::firstOrCreate(['name' => "{$module}.*", 'guard_name' => 'web']);
        }

        // 3. Asignar todos al super-admin
        $superAdminRole = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdminRole) {
            $superAdminRole->syncPermissions(Permission::all());
        }

        // 4. Refactorizar Técnico a wildcards
        Permission::firstOrCreate(['name' => 'declarar entrega mi corte', 'guard_name' => 'web']);
        $tecnicoRole = Role::where('name', 'tecnico')->where('guard_name', 'web')->first();
        if ($tecnicoRole) {
            $tecnicoRole->syncPermissions([
                'citas.*',
                'clientes.*',
                'productos.*',
                'ventas.*',
                'cotizaciones.*',
                'declarar entrega mi corte'
            ]);
        }

        // 5. Refactorizar Contador a wildcards
        $contadorRole = Role::where('name', 'contador')->where('guard_name', 'web')->first();
        if ($contadorRole) {
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
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};

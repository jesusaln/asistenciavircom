<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Permite a técnicos ver, crear, editar y eliminar cotizaciones (misma API / app móvil).
     */
    public function up(): void
    {
        $role = Role::where('name', 'tecnico')->where('guard_name', 'web')->first();
        if (! $role) {
            return;
        }

        $names = [];
        foreach (['view', 'create', 'edit', 'delete', 'export'] as $action) {
            $names[] = "{$action} cotizaciones";
        }

        $permissions = Permission::whereIn('name', $names)->where('guard_name', 'web')->get();
        foreach ($permissions as $permission) {
            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }
    }

    public function down(): void
    {
        $role = Role::where('name', 'tecnico')->where('guard_name', 'web')->first();
        if (! $role) {
            return;
        }

        $names = [];
        foreach (['view', 'create', 'edit', 'delete', 'export'] as $action) {
            $names[] = "{$action} cotizaciones";
        }

        $permissions = Permission::whereIn('name', $names)->where('guard_name', 'web')->get();
        foreach ($permissions as $permission) {
            if ($role->hasPermissionTo($permission)) {
                $role->revokePermissionTo($permission);
            }
        }
    }
};

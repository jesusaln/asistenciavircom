<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EncuestaCrearPermisos extends Command
{
    protected $signature = 'encuesta:crear-permisos';
    protected $description = 'Crea el permiso "view encuestas" y lo asigna al rol admin';

    public function handle(): int
    {
        $permiso = Permission::firstOrCreate([
            'name' => 'view encuestas',
            'guard_name' => 'web',
        ]);
        $this->info("✓ Permiso 'view encuestas' listo (id={$permiso->id})");

        // Asignar al rol admin
        $rolAdmin = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($rolAdmin && ! $rolAdmin->hasPermissionTo($permiso)) {
            $rolAdmin->givePermissionTo($permiso);
            $this->info("✓ Asignado al rol 'admin'");
        }

        // También super-admin y cualquier rol con permisos de marketing
        $rolesAsignar = ['super-admin', 'marketing', 'gerente', 'supervisor'];
        foreach ($rolesAsignar as $rolNombre) {
            $rol = Role::where('name', $rolNombre)->where('guard_name', 'web')->first();
            if ($rol && ! $rol->hasPermissionTo($permiso)) {
                $rol->givePermissionTo($permiso);
                $this->info("✓ Asignado al rol '{$rolNombre}'");
            }
        }

        return self::SUCCESS;
    }
}
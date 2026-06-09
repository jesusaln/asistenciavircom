<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GrantSupportAccessToRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:grant-support-access-role {role : Nombre del rol}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otorga el permiso "view soporte" a un rol completo.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $roleName = (string) $this->argument('role');

        $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

        if (!$role) {
            $this->error("No se encontró el rol {$roleName}.");
            return self::FAILURE;
        }

        $permission = Permission::firstOrCreate([
            'name' => 'view soporte',
            'guard_name' => 'web',
        ]);

        if ($role->hasPermissionTo($permission->name)) {
            $this->info("El rol {$roleName} ya tiene el permiso '{$permission->name}'.");
            return self::SUCCESS;
        }

        $role->givePermissionTo($permission);

        $this->info("Permiso '{$permission->name}' asignado correctamente al rol {$roleName}.");

        return self::SUCCESS;
    }
}

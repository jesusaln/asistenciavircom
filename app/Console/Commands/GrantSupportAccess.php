<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class GrantSupportAccess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:grant-support-access {email : Correo del usuario}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otorga el permiso "view soporte" a un usuario por correo.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = (string) $this->argument('email');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("No se encontró un usuario con el correo {$email}.");
            return self::FAILURE;
        }

        $permission = Permission::firstOrCreate([
            'name' => 'view soporte',
            'guard_name' => 'web',
        ]);

        if ($user->hasPermissionTo($permission->name)) {
            $this->info("El usuario {$email} ya tiene el permiso '{$permission->name}'.");
            return self::SUCCESS;
        }

        $user->givePermissionTo($permission);

        $this->info("Permiso '{$permission->name}' asignado correctamente a {$email}.");

        return self::SUCCESS;
    }
}

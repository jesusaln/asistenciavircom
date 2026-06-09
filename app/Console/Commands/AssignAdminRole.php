<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AssignAdminRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:assign-admin-role {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Asignar rol admin a un usuario por email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Usuario con email {$email} no encontrado.");
            return;
        }

        $adminRole = Role::where('name', 'admin')->first();

        if (!$adminRole) {
            $this->error("Rol 'admin' no encontrado. Asegúrate de que los roles estén creados.");
            return;
        }

        if ($user->hasRole('admin')) {
            $this->info("El usuario {$email} ya tiene el rol admin.");
            return;
        }

        $user->assignRole('admin');

        $this->info("Rol admin asignado exitosamente a {$email}.");
    }
}

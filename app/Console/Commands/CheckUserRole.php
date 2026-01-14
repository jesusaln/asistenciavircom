<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;

class CheckUserRole extends Command
{
    protected $signature = 'user:check-role {email}';
    protected $description = 'Check if user has admin role and assign if missing';

    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("Usuario con email {$email} no encontrado");
            return 1;
        }
        
        $this->info("Usuario encontrado: {$user->name} (ID: {$user->id})");
        $this->info("Roles actuales: " . $user->getRoleNames()->implode(', '));
        $this->info("¿Tiene rol admin?: " . ($user->hasRole('admin') ? 'SÍ' : 'NO'));
        
        if (!$user->hasRole('admin')) {
            // Asignar rol admin
            $adminRole = Role::firstOrCreate(['name' => 'admin']);
            $user->assignRole($adminRole);
            $this->info("✅ Rol 'admin' asignado exitosamente");
            
            // Verificar de nuevo
            $user->refresh();
            $this->info("Verificación: ¿Tiene rol admin ahora?: " . ($user->hasRole('admin') ? 'SÍ' : 'NO'));
        } else {
            $this->info("✅ El usuario ya tiene el rol admin");
        }
        
        return 0;
    }
}

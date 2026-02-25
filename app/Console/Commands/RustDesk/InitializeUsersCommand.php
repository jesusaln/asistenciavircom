<?php

namespace App\Console\Commands\RustDesk;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\EmpresaConfiguracion;
use Illuminate\Support\Str;

class InitializeUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rustdesk:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inicializa la configuración de RustDesk y asigna alias a todos los usuarios.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando inicialización de RustDesk...');

        // 1. Configuración Global de la Empresa
        $config = EmpresaConfiguracion::first();
        if ($config) {
            $config->update([
                'rustdesk_server_address' => 'remoto.climasdeldesierto.com',
                'rustdesk_relay_server' => 'remoto.climasdeldesierto.com',
                'rustdesk_public_key' => 'nWZn0wE7Gq6meimntlv0G8usBkxDjoR0+OTgUh76WEU=',
                'rustdesk_api_url' => 'http://localhost:21114',
            ]);
            $this->info('✅ Configuración global de la empresa actualizada.');
        } else {
            $this->error('❌ No se encontró la configuración de la empresa.');
        }

        // 2. Inicialización de Usuarios
        $users = User::all();
        $bar = $this->output->createProgressBar(count($users));

        foreach ($users as $user) {
            if (!$user->rustdesk_alias) {
                // Crear un alias amigable basado en el nombre
                $alias = Str::slug($user->name, ' ');
                $user->rustdesk_alias = ucwords($alias);
                $user->save();
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('✅ Todos los usuarios han sido inicializados con un alias de RustDesk.');

        $this->info('---');
        $this->info('Próximo paso sugerido: Registrar los IDs numéricos de RustDesk manualmente en el perfil de cada empleado.');
    }
}

<?php

namespace App\Console\Commands;

use App\Services\GoogleDriveService;
use Illuminate\Console\Command;

class GoogleDriveAuth extends Command
{
    protected $signature = 'google-drive:auth {--code= : Código de autorización de Google}';
    protected $description = 'Autoriza la aplicación para acceder a Google Drive';

    public function handle()
    {
        $code = $this->option('code');

        if (!$code) {
            // Mostrar URL de autorización
            $this->info('=== Configuración de Google Drive ===');
            $this->newLine();
            
            $this->info('Paso 1: Ve a Google Cloud Console');
            $this->line('   https://console.cloud.google.com/apis/credentials');
            $this->newLine();
            
            $this->info('Paso 2: Crea credenciales OAuth 2.0 (tipo: Aplicación de escritorio)');
            $this->newLine();
            
            $this->info('Paso 3: Agrega al .env:');
            $this->line('   GOOGLE_DRIVE_CLIENT_ID=tu_client_id');
            $this->line('   GOOGLE_DRIVE_CLIENT_SECRET=tu_client_secret');
            $this->newLine();
            
            if (!config('services.google_drive.client_id')) {
                $this->error('❌ GOOGLE_DRIVE_CLIENT_ID no está configurado en .env');
                return 1;
            }

            $authUrl = GoogleDriveService::getAuthUrl();
            
            $this->info('Paso 4: Abre esta URL en tu navegador:');
            $this->newLine();
            $this->line($authUrl);
            $this->newLine();
            
            $this->info('Paso 5: Inicia sesión y autoriza la aplicación');
            $this->newLine();
            
            $this->info('Paso 6: Copia el código y ejecuta:');
            $this->line('   php artisan google-drive:auth --code=TU_CODIGO');
            
            return 0;
        }

        // Intercambiar código por tokens
        $this->info('Intercambiando código por tokens...');
        
        $result = GoogleDriveService::exchangeCodeForTokens($code);

        if (!$result['success']) {
            $this->error('❌ Error al obtener tokens:');
            $this->line($result['error']);
            return 1;
        }

        $this->newLine();
        $this->info('✅ ¡Autorización exitosa!');
        $this->newLine();
        
        $this->info('Agrega esto a tu .env:');
        $this->newLine();
        $this->line('GOOGLE_DRIVE_REFRESH_TOKEN=' . $result['refresh_token']);
        $this->newLine();

        $this->warn('⚠️  Guarda el refresh_token de forma segura. No lo compartas.');

        return 0;
    }
}

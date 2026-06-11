<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MeliService;

class MeliAuth extends Command
{
    protected $signature = 'meli:auth
                            {--code= : Código de autorización de MercadoLibre}
                            {--redirect= : Redirect URI configurada en la app de ML}';

    protected $description = 'Autenticar con MercadoLibre (OAuth)';

    public function handle(MeliService $meli)
    {
        $code = $this->option('code');
        $redirect = $this->option('redirect');

        if (!$code) {
            $url = $meli->getAuthUrl($redirect ?? route('meli.callback'));
            $this->info('Abre esta URL en tu navegador:');
            $this->line($url);
            $this->newLine();
            $this->warn('Después de autorizar, copia el código de la URL y ejecuta:');
            $this->line('  php artisan meli:auth --code=TU_CODIGO --redirect=' . ($redirect ?? route('meli.callback')));
            return;
        }

        $result = $meli->authenticate($code, $redirect);

        if (isset($result['success'])) {
            $this->info('✅ Autenticación exitosa! User ID: ' . ($result['user_id'] ?? 'N/A'));
        } else {
            $this->error('❌ ' . ($result['error'] ?? 'Error desconocido'));
        }
    }
}

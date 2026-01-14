<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Services\WhatsAppConfigService;
use Illuminate\Console\Command;

class ShowWhatsAppConfig extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'whatsapp:show-config {--empresa_id= : ID específico de empresa} {--show-sensitive : Mostrar información sensible (solo desarrollo)}';

    /**
     * The console command description.
     */
    protected $description = 'Mostrar configuración segura de WhatsApp (sin exponer información sensible)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $empresaId = $this->option('empresa_id');
        $showSensitive = $this->option('show-sensitive');

        $this->info('🔐 Mostrando configuración segura de WhatsApp...');
        $this->newLine();

        try {
            // Obtener configuración segura
            $config = WhatsAppConfigService::getConfig($empresaId);
            $sources = WhatsAppConfigService::getConfigSources();

            $this->line("🏢 Empresa: <fg=cyan>" . (Empresa::find($empresaId) ? Empresa::find($empresaId)->nombre_razon_social : 'Empresa por defecto') . "</>");
            $this->newLine();

            // Información segura (no sensible)
            $this->line('📋 <fg=yellow>CONFIGURACIÓN GENERAL</>');
            $this->line("Graph Version: <fg=green>{$config['graph_version']}</>");
            $this->line("Idioma por defecto: <fg=green>{$config['default_language']}</>");
            $this->line("Timeout: <fg=green>{$config['request_timeout']}s</>");
            $this->line("Plantilla por defecto: <fg=green>{$config['default_template']}</>");
            $this->newLine();

            // Información parcialmente oculta
            $this->line('🔒 <fg=yellow>CONFIGURACIÓN SENSIBLE</>');
            $this->line("Business Account ID: <fg=green>" . $this->maskValue($config['business_account_id']) . "</>");
            $this->line("Phone Number ID: <fg=green>" . $this->maskValue($config['phone_number_id']) . "</>");

            if ($showSensitive && app()->environment('local')) {
                $this->line("Access Token: <fg=yellow>" . substr($config['access_token'], 0, 20) . "...</>");
                $this->line("Token Length: <fg=green>" . strlen($config['access_token']) . " caracteres</>");
            } else {
                $this->line("Access Token: <fg=green>" . $this->maskToken($config['access_token']) . "</>");
            }

            $this->newLine();

            // Fuentes de configuración
            $this->line('🔍 <fg=yellow>FUENTES DE CONFIGURACIÓN</>');
            $this->line("Archivo .env: <fg=green>{$sources['env_file']}</>");
            $this->line("Base de datos: <fg=green>{$sources['database']}</>");
            $this->line("Archivo desarrollo: <fg=green>{$sources['dev_file']}</>");
            $this->line("Caché habilitado: <fg=green>" . ($sources['cache_enabled'] ? 'Sí' : 'No') . "</>");
            $this->line("TTL del caché: <fg=green>{$sources['cache_ttl']} segundos</>");
            $this->newLine();

            // Estado de seguridad
            $this->line('🛡️ <fg=yellow>ESTADO DE SEGURIDAD</>');
            $this->line("Tokens encriptados: <fg=green>" . (config('whatsapp.security.encrypt_tokens') ? 'Sí' : 'No') . "</>");
            $this->line("Validación de token: <fg=green>" . (config('whatsapp.security.validate_token_on_use') ? 'Sí' : 'No') . "</>");
            $this->line("Rate limiting: <fg=green>" . (config('whatsapp.rate_limiting.enabled') ? 'Sí' : 'No') . "</>");
            $this->newLine();

            // Recomendaciones
            if (!app()->environment('production')) {
                $this->warn('⚠️  Entorno de desarrollo detectado');
                $this->line('💡 Usa variables de entorno (.env) para configuración sensible');
                $this->line('🔒 En producción, configura todo en la base de datos');
            }

            $this->info('✅ Configuración cargada exitosamente');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error al cargar configuración: ' . $e->getMessage());

            if (strpos($e->getMessage(), 'incompleta') !== false) {
                $this->line('');
                $this->warn('💡 COMPLETA LA CONFIGURACIÓN:');
                $this->line('   1. Copia .env.example a .env');
                $this->line('   2. Configura variables WHATSAPP_*');
                $this->line('   3. O configura en la base de datos');
            }

            return 1;
        }
    }

    /**
     * Enmascarar valores sensibles para mostrar
     */
    private function maskValue(string $value): string
    {
        if (strlen($value) <= 8) {
            return '****' . substr($value, -4);
        }

        return substr($value, 0, 4) . '****' . substr($value, -4);
    }

    /**
     * Enmascarar token de acceso
     */
    private function maskToken(string $token): string
    {
        if (strlen($token) <= 10) {
            return '****';
        }

        return substr($token, 0, 4) . '****' . substr($token, -4) . ' (' . strlen($token) . ' chars)';
    }
}

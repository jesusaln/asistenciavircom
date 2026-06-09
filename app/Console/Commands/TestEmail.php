<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\EmpresaConfiguracion;

class TestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-email {email? : Email de destino para la prueba}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar configuración de envío de emails SMTP (Laravel vs PHP nativo)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $emailDestino = $this->argument('email') ?: 'test@example.com';

        $this->info('=== PRUEBA DE CONFIGURACIÓN SMTP ===');
        $this->info("Email de destino: {$emailDestino}");

        // Mostrar configuración PHP nativa vs Laravel
        $this->info('📋 CONFIGURACIÓN ACTUAL:');
        $this->line('PHP nativo (php.ini):');
        $this->line("  SMTP: " . ini_get('SMTP'));
        $this->line("  Puerto: " . ini_get('smtp_port'));
        $this->line("  Sendmail From: " . ini_get('sendmail_from'));
        $this->line("  Sendmail Path: " . (ini_get('sendmail_path') ?: 'no configurado'));

        try {
            // Obtener configuración de Laravel
            $configuracion = EmpresaConfiguracion::getConfig();

            $this->line('');
            $this->line('Laravel (Base de datos):');
            $this->line("  Host: {$configuracion->smtp_host}");
            $this->line("  Puerto: {$configuracion->smtp_port}");
            $this->line("  Usuario: {$configuracion->smtp_username}");
            $this->line("  Encriptación: {$configuracion->smtp_encryption}");
            $this->line("  De: {$configuracion->email_from_address} ({$configuracion->email_from_name})");

            // Verificar si las configuraciones coinciden
            $phpSmtp = ini_get('SMTP');
            $phpPort = ini_get('smtp_port');
            $laravelHost = $configuracion->smtp_host;
            $laravelPort = $configuracion->smtp_port;

            if ($phpSmtp === $laravelHost && (int)$phpPort === (int)$laravelPort) {
                $this->warn('⚠️  ATENCIÓN: Las configuraciones SMTP de PHP y Laravel COINCIDEN');
                $this->warn('   Esto puede causar conflictos. Laravel debería usar configuración diferente.');
            } else {
                $this->info('✅ Las configuraciones SMTP son diferentes (correcto)');
            }

            // Configurar mail dinámicamente - FORZAR uso de configuración de empresa
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => $configuracion->smtp_host,
                'mail.mailers.smtp.port' => $configuracion->smtp_port,
                'mail.mailers.smtp.username' => $configuracion->smtp_username,
                'mail.mailers.smtp.password' => $configuracion->smtp_password,
                'mail.mailers.smtp.encryption' => $configuracion->smtp_encryption,
                'mail.mailers.smtp.timeout' => 30,
                'mail.from.address' => $configuracion->email_from_address,
                'mail.from.name' => $configuracion->email_from_name,
            ]);

            // Limpiar cualquier configuración previa
            \Illuminate\Support\Facades\Mail::purge('smtp');

            $this->info('');
            $this->info('🚀 Enviando email de prueba usando configuración de EMPRESA (Hostinger)...');

            // Enviar email simple de prueba
            Mail::raw('Este es un email de prueba desde el sistema CDD usando configuración de EMPRESA (Hostinger).', function ($message) use ($emailDestino, $configuracion) {
                $message->to($emailDestino)
                        ->subject('Prueba de Email - CDD Sistema de Gestión (Hostinger)');

                if ($configuracion->email_reply_to) {
                    $message->replyTo($configuracion->email_reply_to);
                }
            });

            Log::info('Email de prueba enviado exitosamente via CONFIGURACIÓN DE EMPRESA', [
                'email_destino' => $emailDestino,
                'configuracion_empresa' => [
                    'host' => $configuracion->smtp_host,
                    'port' => $configuracion->smtp_port,
                    'username' => $configuracion->smtp_username,
                    'encryption' => $configuracion->smtp_encryption,
                    'from_address' => $configuracion->email_from_address,
                ],
                'php_native_smtp_config' => [
                    'smtp' => ini_get('SMTP'),
                    'port' => ini_get('smtp_port'),
                    'sendmail_from' => ini_get('sendmail_from'),
                    'sendmail_path' => ini_get('sendmail_path')
                ],
                'laravel_config_aplicada' => config('mail.mailers.smtp')
            ]);

            $this->info('✅ Email enviado exitosamente via LARAVEL!');
            $this->info('Revisa la bandeja de entrada y carpeta de spam.');
            $this->info('También revisa los logs de Laravel para más detalles.');

            // Verificar si realmente se usó la configuración de Laravel
            $this->info('');
            $this->info('🔍 Verificación: Si el email NO llega, puede que esté usando SMTP de PHP nativo');
            $this->info('   Revisa los logs de Laravel para confirmar qué configuración se usó realmente.');

        } catch (\Exception $e) {
            $this->error('❌ Error al enviar email:');
            $this->error($e->getMessage());

            Log::error('Error en prueba de email SMTP', [
                'email_destino' => $emailDestino,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'php_smtp_config' => [
                    'smtp' => ini_get('SMTP'),
                    'port' => ini_get('smtp_port'),
                    'sendmail_from' => ini_get('sendmail_from'),
                    'sendmail_path' => ini_get('sendmail_path')
                ]
            ]);

            // Diagnóstico específico
            $errorMessage = strtolower($e->getMessage());
            if (strpos($errorMessage, 'authentication failed') !== false) {
                $this->warn('💡 Posible problema: Credenciales SMTP incorrectas');
                $this->warn('   Verifica que el usuario/contraseña de Hostinger sean correctos');
            } elseif (strpos($errorMessage, 'connection refused') !== false) {
                $this->warn('💡 Posible problema: Servidor SMTP no accesible o puerto bloqueado');
                $this->warn('   Verifica que smtp.hostinger.com:587 esté accesible');
            } elseif (strpos($errorMessage, 'timeout') !== false) {
                $this->warn('💡 Posible problema: Timeout de conexión');
                $this->warn('   Puede que el servidor esté bloqueado por firewall');
            } elseif (strpos($errorMessage, 'ssl') !== false) {
                $this->warn('💡 Posible problema: Configuración SSL/TLS');
                $this->warn('   Intenta cambiar encryption de "tls" a "ssl" o null');
            } elseif (strpos($errorMessage, 'relay') !== false) {
                $this->warn('💡 Posible problema: Relay access denied');
                $this->warn('   El servidor SMTP no permite envío desde esta IP');
            }

            $this->info('');
            $this->info('🔄 SOLUCIONES PROPUESTAS:');
            $this->info('   1. Cambiar a configuración de Ionos (ya probada):');
            $this->info('      UPDATE empresa_configuracion SET smtp_host="smtp.ionos.mx", smtp_port=465, smtp_encryption="ssl" WHERE id=1;');
            $this->info('   2. Verificar credenciales de Hostinger en el panel de control');
            $this->info('   3. Crear nueva cuenta de email en Hostinger específicamente para SMTP');
            $this->info('');
            $this->info('💡 RECOMENDACIÓN: Usar la configuración de Ionos que ya funciona en PHP nativo');

            return 1;
        }

        return 0;
    }
}

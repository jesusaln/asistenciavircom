<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Support\EmpresaResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ConfigurarWhatsApp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:configurar
                            {--phone_number_id= : Phone Number ID de WhatsApp}
                            {--business_account_id= : Business Account ID}
                            {--phone= : Número de teléfono en formato E.164}
                            {--access_token= : Access Token de WhatsApp API}
                            {--app_secret= : App Secret (opcional)}
                            {--empresa_id= : ID específico de empresa (opcional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configurar credenciales de WhatsApp Business API para la empresa';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phoneNumberId = $this->option('phone_number_id');
        $businessAccountId = $this->option('business_account_id');
        $phone = $this->option('phone');
        $accessToken = $this->option('access_token');
        $appSecret = $this->option('app_secret');
        $empresaId = $this->option('empresa_id');

        $this->info('🚀 Configurando WhatsApp Business API...');
        $this->line("Phone Number ID: {$phoneNumberId}");
        $this->line("Business Account ID: {$businessAccountId}");
        $this->line("Número de teléfono: {$phone}");

        try {
            // Obtener empresa
            if ($empresaId) {
                $empresa = Empresa::findOrFail($empresaId);
            } else {
                $empresaId = $empresaId ?: EmpresaResolver::resolveId();
                $empresa = $empresaId ? Empresa::find($empresaId) : null;

                if (!$empresa) {
                    $this->info('No se encontró ninguna empresa. Creando empresa básica...');

                    // Crear empresa básica
                    $empresa = Empresa::create([
                        'nombre_razon_social' => 'Climas del Desierto',
                        'tipo_persona' => 'moral',
                        'tipo_identificacion' => 'rfc',
                        'identificacion' => 'LONJ880321KMA',
                        'rfc' => 'LONJ880321KMA',
                        'regimen_fiscal' => '626', // Régimen simplificado de confianza
                        'uso_cfdi' => 'G03', // Gastos en general
                        'email' => 'contacto@climasdeldesierto.com',
                        'telefono' => '+526621234567',
                        'calle' => 'Avenida Principal',
                        'numero_exterior' => '123',
                        'colonia' => 'Centro',
                        'codigo_postal' => '83000',
                        'municipio' => 'Hermosillo',
                        'ciudad' => 'Hermosillo',
                        'estado' => 'Sonora',
                        'pais' => 'México',
                    ]);

                    $this->info("✅ Empresa creada: {$empresa->nombre_razon_social}");
                }
            }

            $this->info("Configurando empresa: {$empresa->nombre_razon_social}");

            // Actualizar configuración de WhatsApp
            $empresa->update([
                'whatsapp_enabled' => true,
                'whatsapp_business_account_id' => $businessAccountId,
                'whatsapp_phone_number_id' => $phoneNumberId,
                'whatsapp_sender_phone' => $phone,
                'whatsapp_access_token' => $accessToken,
                'whatsapp_app_secret' => $appSecret,
                'whatsapp_webhook_verify_token' => 'vircom_whatsapp_' . time() . '_' . str()->random(10),
                'whatsapp_default_language' => 'es_MX',
                'whatsapp_template_payment_reminder' => 'payment_reminder',
            ]);

            $this->info('✅ Configuración de WhatsApp guardada exitosamente');
            $this->line("Empresa: {$empresa->nombre_razon_social}");
            $this->line("WhatsApp habilitado: Sí");
            $this->line("Token de verificación webhook: {$empresa->whatsapp_webhook_verify_token}");

            // Mostrar información importante
            $this->newLine();
            $this->warn('📋 Información importante para configurar el webhook en Meta:');
            $this->line("URL del webhook: https://sudominio.com/api/webhooks/whatsapp");
            $this->line("Token de verificación: {$empresa->whatsapp_webhook_verify_token}");
            $this->line("Eventos a suscribir: messages, message_template_status_update");

            $this->newLine();
            $this->info('🎯 Próximos pasos:');
            $this->line('1. Configure el webhook en Meta Business Manager');
            $this->line('2. Cree y apruebe la plantilla "payment_reminder" en Meta');
            $this->line('3. Ejecute: php artisan whatsapp:enviar-recordatorios --dias=3');

            Log::info('Configuración de WhatsApp aplicada vía comando', [
                'empresa_id' => $empresa->id,
                'phone_number_id' => $phoneNumberId,
                'business_account_id' => $businessAccountId,
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error('Error al configurar WhatsApp: ' . $e->getMessage());
            Log::error('Error en configuración de WhatsApp vía comando', [
                'error' => $e->getMessage(),
                'empresa_id' => $empresaId,
            ]);
            return 1;
        }
    }
}

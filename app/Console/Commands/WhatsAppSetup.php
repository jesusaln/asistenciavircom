<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use Illuminate\Console\Command;

class WhatsAppSetup extends Command
{
    protected $signature = 'whatsapp:setup
                            {--empresa_id= : ID de la empresa a configurar}
                            {--phone-number-id= : WhatsApp Phone Number ID}
                            {--business-account-id= : WhatsApp Business Account ID}
                            {--access-token= : Access Token de WhatsApp}
                            {--sender-phone= : Número de teléfono del remitente}
                            {--template-reminder= : Nombre de la plantilla de recordatorio de pago}
                            {--template-maintenance= : Nombre de la plantilla de mantenimiento}';

    protected $description = 'Configurar credenciales de WhatsApp Business API para una empresa';

    public function handle(): int
    {
        $empresaId = $this->option('empresa_id');

        $this->newLine();
        $this->info('⚙️  WhatsApp Business - Configuración');
        $this->line('═══════════════════════════════════════════');
        $this->newLine();

        // Resolver empresa
        if (!$empresaId) {
            $empresas = Empresa::all();
            if ($empresas->isEmpty()) {
                $this->error('❌ No hay empresas registradas.');
                return 1;
            }

            $this->line('<fg=yellow>Empresas disponibles:</>');
            $rows = $empresas->map(fn($e) => [
                $e->id,
                $e->nombre_razon_social,
                $e->whatsapp_enabled ? '✅' : '❌',
            ]);
            $this->table(['ID', 'Empresa', 'WA Activo'], $rows);
            $empresaId = $this->ask('¿ID de la empresa a configurar?', $empresas->first()->id);
        }

        $empresa = Empresa::findOrFail($empresaId);
        $this->line("🏢 Configurando: <fg=cyan>{$empresa->nombre_razon_social}</>");
        $this->newLine();

        // Recoger datos
        $phoneNumberId = $this->option('phone-number-id')
            ?: $this->ask(
                'Phone Number ID (Meta Business > WhatsApp > Phone Numbers)',
                $empresa->whatsapp_phone_number_id ?: ''
            );

        $businessAccountId = $this->option('business-account-id')
            ?: $this->ask(
                'Business Account ID (Meta Business > WhatsApp > Overview)',
                $empresa->whatsapp_business_account_id ?: ''
            );

        $accessToken = $this->option('access-token')
            ?: $this->ask(
                'Access Token (Facebook Developers > System User > Token)',
                $empresa->whatsapp_access_token ? '***configurado***' : ''
            );

        $senderPhone = $this->option('sender-phone')
            ?: $this->ask(
                'Número de teléfono remitente (ej: +526622036840)',
                $empresa->whatsapp_sender_phone ?: ''
            );

        $templateReminder = $this->option('template-reminder')
            ?: $this->ask(
                'Plantilla de recordatorio de pago',
                $empresa->whatsapp_template_payment_reminder ?: 'recordatorio_de_pago'
            );

        $templateMaintenance = $this->option('template-maintenance')
            ?: $this->ask(
                'Plantilla de mantenimiento',
                $empresa->whatsapp_template_maintenance ?: 'recordatorio_mantenimiento'
            );

        // Resumen
        $this->newLine();
        $this->line('📋 <fg=yellow>Resumen de Configuración:</>');
        $this->line("   Phone Number ID: <fg=cyan>{$phoneNumberId}</>");
        $this->line("   Business Account ID: <fg=cyan>{$businessAccountId}</>");
        $this->line("   Access Token: <fg=cyan>" . (strlen($accessToken) > 20 ? substr($accessToken, 0, 20) . '...' : $accessToken) . "</>");
        $this->line("   Teléfono Remitente: <fg=cyan>{$senderPhone}</>");
        $this->line("   Template Pago: <fg=cyan>{$templateReminder}</>");
        $this->line("   Template Mant: <fg=cyan>{$templateMaintenance}</>");
        $this->newLine();

        if (!$this->confirm('¿Guardar esta configuración?', true)) {
            $this->line('Configuración cancelada.');
            return 0;
        }

        // Guardar
        $updateData = [
            'whatsapp_enabled' => true,
            'whatsapp_default_language' => 'es_MX',
        ];

        if ($phoneNumberId && $phoneNumberId !== '') {
            $updateData['whatsapp_phone_number_id'] = $phoneNumberId;
        }
        if ($businessAccountId && $businessAccountId !== '') {
            $updateData['whatsapp_business_account_id'] = $businessAccountId;
        }
        if ($accessToken && $accessToken !== '***configurado***') {
            $updateData['whatsapp_access_token'] = $accessToken;
        }
        if ($senderPhone && $senderPhone !== '') {
            $updateData['whatsapp_sender_phone'] = $senderPhone;
        }
        if ($templateReminder && $templateReminder !== '') {
            $updateData['whatsapp_template_payment_reminder'] = $templateReminder;
        }
        if ($templateMaintenance && $templateMaintenance !== '') {
            $updateData['whatsapp_template_maintenance'] = $templateMaintenance;
        }

        $empresa->update($updateData);

        $this->newLine();
        $this->info('✅ ¡Configuración guardada exitosamente!');
        $this->newLine();
        $this->line('Próximos pasos:');
        $this->line("  1. Diagnóstico: <fg=green>php artisan whatsapp:diagnose-connection --empresa_id={$empresa->id}</>");
        $this->line("  2. Prueba: <fg=green>php artisan whatsapp:send-test 6622036840 --template={$templateReminder} --empresa_id={$empresa->id}</>");

        return 0;
    }
}

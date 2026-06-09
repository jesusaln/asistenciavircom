<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppService;
use App\Support\EmpresaResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WhatsAppSendTest extends Command
{
    protected $signature = 'whatsapp:send-test
                            {telefono : Número de teléfono destino (ej: 6622036840)}
                            {--template= : Nombre de la plantilla a enviar}
                            {--params= : Parámetros separados por coma (ej: "Juan,Minisplit,2950")}
                            {--empresa_id= : ID de empresa (por defecto usa la primera con WhatsApp habilitado)}
                            {--text= : Enviar mensaje de texto libre (requiere sesión activa de 24h)}
                            {--dry-run : Solo simular, no enviar realmente}
                            {--list-templates : Listar plantillas disponibles en Meta}';

    protected $description = 'Enviar un mensaje de prueba por WhatsApp Business API';

    public function handle(): int
    {
        $telefono = $this->argument('telefono');
        $empresaId = $this->option('empresa_id');
        $dryRun = $this->option('dry-run');
        $listTemplates = $this->option('list-templates');

        $this->newLine();
        $this->info('📱 WhatsApp Business - Envío de Prueba');
        $this->line('═══════════════════════════════════════════');
        $this->newLine();

        // 1. Resolver empresa
        $empresa = $this->resolveEmpresa($empresaId);
        if (!$empresa) return 1;

        // Establecer contexto de empresa
        EmpresaResolver::setContext($empresa->id);

        $this->line("🏢 Empresa: <fg=cyan>{$empresa->nombre_razon_social}</> (ID: {$empresa->id})");
        $this->newLine();

        // 2. Verificar configuración
        if (!$this->verificarConfiguracion($empresa)) return 1;

        // 3. Listar plantillas si se pide
        if ($listTemplates) {
            return $this->listarPlantillas($empresa);
        }

        // 4. Formatear teléfono
        $formattedPhone = WhatsAppService::formatPhoneToE164($telefono);
        $this->line("📞 Teléfono: <fg=yellow>{$telefono}</> → <fg=green>{$formattedPhone}</>");
        $this->newLine();

        // 5. Determinar tipo de envío
        if ($this->option('text')) {
            return $this->enviarTextoLibre($empresa, $formattedPhone, $dryRun);
        }

        return $this->enviarPlantilla($empresa, $formattedPhone, $dryRun);
    }

    private function resolveEmpresa(?string $empresaId): ?Empresa
    {
        if ($empresaId) {
            $empresa = Empresa::find($empresaId);
            if (!$empresa) {
                $this->error("❌ Empresa con ID {$empresaId} no encontrada.");
                return null;
            }
            return $empresa;
        }

        // Buscar la primera empresa con WhatsApp habilitado
        $empresa = Empresa::where('whatsapp_enabled', true)
            ->whereNotNull('whatsapp_phone_number_id')
            ->whereNotNull('whatsapp_access_token')
            ->first();

        if (!$empresa) {
            // Fallback: mostrar empresas disponibles
            $empresas = Empresa::all();
            if ($empresas->isEmpty()) {
                $this->error('❌ No hay empresas registradas en el sistema.');
                return null;
            }

            $this->warn('⚠️  No se encontró empresa con WhatsApp completamente configurado.');
            $this->newLine();
            $this->line('<fg=yellow>Empresas disponibles:</>');

            $rows = $empresas->map(fn($e) => [
                $e->id,
                $e->nombre_razon_social,
                $e->whatsapp_enabled ? '✅' : '❌',
                $e->whatsapp_phone_number_id ?: '❌ FALTA',
                $e->whatsapp_access_token ? '✅ Configurado' : '❌ FALTA',
            ]);

            $this->table(
                ['ID', 'Empresa', 'WA Activo', 'Phone ID', 'Token'],
                $rows
            );

            $this->newLine();
            $this->line('Para configurar WhatsApp, necesitas:');
            $this->line('  1. <fg=cyan>Phone Number ID</> - Meta Business > WhatsApp > Phone Numbers');
            $this->line('  2. <fg=cyan>Business Account ID</> - Misma sección');
            $this->line('  3. <fg=cyan>Access Token</> - Permanente desde Facebook Developers');
            $this->newLine();
            $this->line('Configurar con: <fg=green>php artisan whatsapp:setup --empresa_id=ID</>');

            // Si hay al menos una empresa con whatsapp_enabled, usarla con advertencia
            $fallback = Empresa::where('whatsapp_enabled', true)->first();
            if ($fallback) {
                $this->newLine();
                $this->warn("Usando empresa ID {$fallback->id} ({$fallback->nombre_razon_social}) como fallback.");
                return $fallback;
            }

            return null;
        }

        return $empresa;
    }

    private function verificarConfiguracion(Empresa $empresa): bool
    {
        $checks = [
            ['WhatsApp Habilitado', $empresa->whatsapp_enabled, 'whatsapp_enabled = true en la tabla empresas'],
            ['Phone Number ID', !empty($empresa->whatsapp_phone_number_id), 'Obtenerlo en Meta Business > WhatsApp > Phone Numbers'],
            ['Access Token', !empty($empresa->whatsapp_access_token), 'Generar token permanente en Facebook Developers'],
        ];

        $this->line('📋 <fg=yellow>Verificación de Configuración</>');

        $allOk = true;
        foreach ($checks as [$label, $ok, $help]) {
            $icon = $ok ? '<fg=green>✅</>' : '<fg=red>❌</>';
            $this->line("   {$icon} {$label}");
            if (!$ok) {
                $this->line("      <fg=gray>→ {$help}</>");
                $allOk = false;
            }
        }

        $this->newLine();

        if (!$allOk) {
            $this->error('❌ Configuración de WhatsApp incompleta.');
            $this->line('   Usa <fg=green>php artisan whatsapp:setup --empresa_id=' . $empresa->id . '</> para configurar.');
            $this->newLine();
            return false;
        }

        return true;
    }

    private function enviarPlantilla(Empresa $empresa, string $phone, bool $dryRun): int
    {
        $templateName = $this->option('template');
        $paramsStr = $this->option('params');

        if (!$templateName) {
            // Mostrar plantillas conocidas de la configuración
            $this->line('📑 <fg=yellow>Plantillas configuradas:</>');

            $templates = array_filter([
                'payment_reminder' => $empresa->whatsapp_template_payment_reminder,
                'maintenance' => $empresa->whatsapp_template_maintenance,
            ]);

            if (empty($templates)) {
                $this->warn('   No hay plantillas configuradas en la empresa.');
                $this->line('   Especifica una con: --template=nombre_plantilla');
                return 1;
            }

            foreach ($templates as $key => $name) {
                $this->line("   • <fg=cyan>{$key}</>: {$name}");
            }

            $templateName = $this->ask('¿Qué plantilla deseas usar?', array_values($templates)[0] ?? '');
        }

        // Parsear parámetros
        $params = $paramsStr ? explode(',', $paramsStr) : [];
        $params = array_map('trim', $params);

        $language = $empresa->whatsapp_default_language ?: 'es_MX';

        // Resumen del envío
        $this->newLine();
        $this->line('📤 <fg=yellow>Resumen del Envío:</>');
        $this->line("   Plantilla: <fg=cyan>{$templateName}</>");
        $this->line("   Destino: <fg=green>{$phone}</>");
        $this->line("   Idioma: <fg=gray>{$language}</>");
        $this->line("   Parámetros: <fg=gray>" . (empty($params) ? 'ninguno' : implode(', ', $params)) . "</>");
        $this->newLine();

        if ($dryRun) {
            $this->info('🧪 [DRY RUN] Mensaje NO enviado. Todo se ve correcto.');
            return 0;
        }

        if (!$this->confirm('¿Enviar este mensaje?', true)) {
            $this->line('Envío cancelado.');
            return 0;
        }

        try {
            $whatsapp = WhatsAppService::fromEmpresa($empresa);

            // Registrar en la base de datos
            $log = WhatsAppMessage::create([
                'empresa_id' => $empresa->id,
                'to' => $phone,
                'template_name' => $templateName,
                'template_params' => $params,
                'status' => WhatsAppMessage::STATUS_QUEUED,
            ]);

            $this->line('⏳ Enviando...');

            $response = $whatsapp->sendTemplate($phone, $templateName, $language, $params);

            $messageId = $response['messages'][0]['id'] ?? null;
            $log->markAsSent($messageId, $response);

            $this->newLine();
            $this->info('✅ ¡Mensaje enviado exitosamente!');
            $this->line("   Message ID: <fg=green>{$messageId}</>");
            $this->line("   Log ID: <fg=gray>{$log->id}</>");

            Log::info('WhatsApp test message sent', [
                'empresa_id' => $empresa->id,
                'to' => $phone,
                'template' => $templateName,
                'message_id' => $messageId,
            ]);

            return 0;

        } catch (\Exception $e) {
            // Marcar como fallido si existe el log
            if (isset($log)) {
                $log->markAsFailed($e->getCode() ?: 'SEND_ERROR', ['error' => $e->getMessage()]);
            }

            $this->newLine();
            $this->error('❌ Error al enviar: ' . $e->getMessage());

            // Ayuda contextual
            if (str_contains($e->getMessage(), 'access token')) {
                $this->newLine();
                $this->warn('💡 El token de acceso puede estar expirado o ser inválido.');
                $this->line('   Genera uno nuevo en: https://developers.facebook.com');
            } elseif (str_contains($e->getMessage(), 'template')) {
                $this->newLine();
                $this->warn('💡 La plantilla puede no existir o no estar aprobada en Meta.');
                $this->line('   Verifica en: Meta Business Manager > WhatsApp > Message Templates');
            } elseif (str_contains($e->getMessage(), 'phone')) {
                $this->newLine();
                $this->warn('💡 El número puede ser inválido o no tener WhatsApp.');
                $this->line("   Número formateado: {$phone}");
            }

            return 1;
        }
    }

    private function enviarTextoLibre(Empresa $empresa, string $phone, bool $dryRun): int
    {
        $text = $this->option('text');

        $this->line('📤 <fg=yellow>Envío de Texto Libre:</>');
        $this->line("   Destino: <fg=green>{$phone}</>");
        $this->line("   Mensaje: <fg=gray>{$text}</>");
        $this->newLine();
        $this->warn('⚠️  Los mensajes de texto libre solo funcionan dentro de la ventana de 24h');
        $this->line('   (el destinatario debe haberte enviado un mensaje primero).');
        $this->newLine();

        if ($dryRun) {
            $this->info('🧪 [DRY RUN] Mensaje NO enviado.');
            return 0;
        }

        if (!$this->confirm('¿Enviar este mensaje?', true)) {
            $this->line('Envío cancelado.');
            return 0;
        }

        try {
            $whatsapp = WhatsAppService::fromEmpresa($empresa);

            $log = WhatsAppMessage::create([
                'empresa_id' => $empresa->id,
                'to' => $phone,
                'template_name' => '__text_message__',
                'template_params' => ['text' => $text],
                'status' => WhatsAppMessage::STATUS_QUEUED,
            ]);

            $this->line('⏳ Enviando...');

            $response = $whatsapp->sendTextMessage($phone, $text);
            $messageId = $response['messages'][0]['id'] ?? null;
            $log->markAsSent($messageId, $response);

            $this->newLine();
            $this->info('✅ ¡Mensaje de texto enviado!');
            $this->line("   Message ID: <fg=green>{$messageId}</>");

            return 0;

        } catch (\Exception $e) {
            if (isset($log)) {
                $log->markAsFailed($e->getCode() ?: 'SEND_ERROR', ['error' => $e->getMessage()]);
            }

            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }

    private function listarPlantillas(Empresa $empresa): int
    {
        $this->line('📑 <fg=yellow>Listando plantillas de WhatsApp...</>');

        try {
            $whatsapp = WhatsAppService::fromEmpresa($empresa);
            $templates = $whatsapp->listTemplates();

            if (empty($templates)) {
                $this->warn('No se encontraron plantillas.');
                return 0;
            }

            $rows = array_map(fn($t) => [
                $t['name'] ?? '-',
                $t['status'] ?? '-',
                $t['category'] ?? '-',
                $t['language'] ?? '-',
            ], $templates);

            $this->table(['Nombre', 'Estado', 'Categoría', 'Idioma'], $rows);
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error al listar plantillas: ' . $e->getMessage());
            return 1;
        }
    }
}

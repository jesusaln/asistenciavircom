<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Support\EmpresaResolver;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class ListWhatsAppTemplates extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'whatsapp:list-templates {--empresa_id= : ID específico de empresa}';

    /**
     * The console command description.
     */
    protected $description = 'Listar plantillas de WhatsApp disponibles en Meta Business';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $empresaId = $this->option('empresa_id');

        $this->info('📱 Listando plantillas de WhatsApp...');
        $this->newLine();

        try {
            // Obtener empresa
            if ($empresaId) {
                $empresa = Empresa::findOrFail($empresaId);
            } else {
                $empresaId = $empresaId ?: EmpresaResolver::resolveId();
                $empresa = $empresaId ? Empresa::find($empresaId) : null;

                if (!$empresa) {
                    $this->error('❌ No se encontró ninguna empresa');
                    return 1;
                }
            }

            $this->line("🏢 Empresa: <fg=cyan>{$empresa->nombre_razon_social}</>");
            $this->newLine();

            if (!$empresa->whatsapp_enabled) {
                $this->warn('⚠️  WhatsApp no está habilitado para esta empresa');
                return 0;
            }

            // Crear servicio WhatsApp
            $whatsappService = WhatsAppService::fromEmpresa($empresa);

            // Listar plantillas
            $this->listarPlantillas($whatsappService, $empresa);

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());

            if (strpos($e->getMessage(), 'Template name does not exist') !== false) {
                $this->line('');
                $this->warn('💡 La plantilla configurada no existe:');
                $this->line('   Plantilla actual: ' . $empresa->whatsapp_template_payment_reminder);
                $this->line('');
                $this->warn('🔧 SOLUCIONES:');
                $this->line('   1. Crear la plantilla en Meta Business Manager');
                $this->line('   2. Cambiar a una plantilla existente');
                $this->line('   3. Usar el nombre correcto de la plantilla');
            }

            return 1;
        }
    }

    private function listarPlantillas(WhatsAppService $whatsappService, Empresa $empresa): void
    {
        $this->line('📋 <fg=yellow>PLANTILLAS DISPONIBLES</>');

        try {
            $templates = $whatsappService->listTemplates();

            if (empty($templates)) {
                $this->warn('⚠️  No se encontraron plantillas');
                $this->line('');
                $this->line('💡 Crea una plantilla en Meta Business Manager:');
                $this->line('   1. Ve a Meta Business Manager');
                $this->line('   2. WhatsApp Business Account');
                $this->line('   3. Message Templates');
                $this->line('   4. "Create Template"');
                return;
            }

            $this->line('Encontradas: <fg=cyan>' . count($templates) . '</> plantillas');
            $this->newLine();

            foreach ($templates as $template) {
                $name = $template['name'];
                $status = $template['status'] ?? 'unknown';
                $category = $template['category'] ?? 'unknown';

                $statusIcon = match ($status) {
                    'APPROVED' => '✅',
                    'PENDING' => '⏳',
                    'REJECTED' => '❌',
                    default => '❓'
                };

                $this->line("📱 {$name}");
                $this->line("   Estado: {$statusIcon} {$status}");
                $this->line("   Categoría: {$category}");

                // Mostrar idiomas disponibles
                if (isset($template['language'])) {
                    $this->line("   Idioma: {$template['language']}");
                }

                $this->line(str_repeat('-', 50));
            }

            // Verificar plantilla configurada
            $currentTemplate = $empresa->whatsapp_template_payment_reminder;
            $templateExists = false;

            foreach ($templates as $template) {
                if ($template['name'] === $currentTemplate) {
                    $templateExists = true;
                    break;
                }
            }

            $this->newLine();
            if ($templateExists) {
                $this->line('<fg=green>✅ Plantilla configurada existe: ' . $currentTemplate . '</>');
            } else {
                $this->line('<fg=red>❌ Plantilla configurada NO existe: ' . $currentTemplate . '</>');
                $this->line('');
                $this->warn('💡 OPCIONES:');
                $this->line('   1. Crear la plantilla "' . $currentTemplate . '" en Meta Business');
                $this->line('   2. Cambiar configuración a una plantilla existente');
            }

        } catch (\Exception $e) {
            $this->error('❌ Error al conectar con WhatsApp API: ' . $e->getMessage());

            if (strpos($e->getMessage(), 'Error validating access token') !== false) {
                $this->line('');
                $this->warn('🔑 El token de acceso podría estar expirado');
                $this->line('💡 Ejecuta: php artisan whatsapp:validate-token');
            }
        }
    }
}

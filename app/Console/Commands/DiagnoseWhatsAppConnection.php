<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Support\EmpresaResolver;
use App\Services\WhatsAppService;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DiagnoseWhatsAppConnection extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'whatsapp:diagnose-connection {--empresa_id= : ID específico de empresa}';

    /**
     * The console command description.
     */
    protected $description = 'Diagnosticar problemas de conexión con WhatsApp Business API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $empresaId = $this->option('empresa_id');

        $this->info('🔍 Diagnosticando conexión con WhatsApp Business API...');
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

            // Verificar configuración básica
            $this->verificarConfiguracion($empresa);

            // Probar conexión directa con la API
            $this->probarConexionAPI($empresa);

            // Verificar plantilla
            $this->verificarPlantilla($empresa);

            $this->newLine();
            $this->info('✅ Diagnóstico completado');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error durante el diagnóstico: ' . $e->getMessage());
            Log::error('Error en diagnóstico de WhatsApp', [
                'error' => $e->getMessage(),
                'empresa_id' => $empresaId,
            ]);
            return 1;
        }
    }

    private function verificarConfiguracion(Empresa $empresa): void
    {
        $this->line('📋 <fg=yellow>VERIFICACIÓN DE CONFIGURACIÓN</>');

        $config = [
            'Business Account ID' => $empresa->whatsapp_business_account_id,
            'Phone Number ID' => $empresa->whatsapp_phone_number_id,
            'Número de teléfono' => $empresa->whatsapp_sender_phone,
            'Plantilla' => $empresa->whatsapp_template_payment_reminder,
        ];

        foreach ($config as $label => $value) {
            $status = !empty($value) ? '<fg=green>✅</>' : '<fg=red>❌</>';
            $this->line("{$label}: {$status} " . ($value ?: 'No configurado'));
        }

        $this->newLine();

        // Verificar si hay errores tipográficos comunes
        if (strpos($empresa->whatsapp_template_payment_reminder, 'recordarorio') !== false) {
            $this->warn('⚠️  Posible error tipográfico en el nombre de la plantilla');
            $this->line('   Se encontró "recordarorio" en lugar de "recordatorio"');
        }
    }

    private function probarConexionAPI(Empresa $empresa): void
    {
        $this->line('🌐 <fg=yellow>PRUEBA DE CONEXIÓN API</>');

        try {
            // Crear cliente HTTP directo para pruebas
            $client = new Client([
                'base_uri' => 'https://graph.facebook.com/v20.0/',
                'timeout' => 30,
                'headers' => [
                    'Authorization' => 'Bearer ' . $empresa->whatsapp_access_token,
                    'Content-Type' => 'application/json',
                ],
            ]);

            // Probar obtener información del número de teléfono
            $this->line('Probando Phone Number ID: ' . $empresa->whatsapp_phone_number_id);

            $response = $client->get($empresa->whatsapp_phone_number_id);
            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['id'])) {
                $this->line('<fg=green>✅ Phone Number ID válido</>');
                $this->line('   Nombre: ' . ($data['display_phone_number'] ?? 'No disponible'));
                $this->line('   Estado: ' . ($data['account_mode'] ?? 'No disponible'));
            } else {
                $this->line('<fg=red>❌ Phone Number ID inválido o sin permisos</>');
            }

        } catch (\Exception $e) {
            $this->line('<fg=red>❌ Error de conexión con la API</>');
            $this->line('   Error: ' . $e->getMessage());

            if (strpos($e->getMessage(), 'Unknown path components') !== false) {
                $this->line('');
                $this->warn('💡 POSIBLES CAUSAS:');
                $this->line('   • El Phone Number ID no es válido');
                $this->line('   • El número de teléfono no está asociado correctamente');
                $this->line('   • No tienes permisos para acceder a este número');
                $this->line('');
                $this->warn('💡 SOLUCIONES:');
                $this->line('   • Verifica el Phone Number ID en Meta Business');
                $this->line('   • Asegúrate de que el número esté conectado a tu WABA');
                $this->line('   • Revisa los permisos en Facebook Developers');
            }
        }

        $this->newLine();
    }

    private function verificarPlantilla(Empresa $empresa): void
    {
        $this->line('📱 <fg=yellow>VERIFICACIÓN DE PLANTILLA</>');

        if (empty($empresa->whatsapp_template_payment_reminder)) {
            $this->line('<fg=red>❌ No hay plantilla configurada</>');
            return;
        }

        $this->line('Plantilla configurada: <fg=cyan>' . $empresa->whatsapp_template_payment_reminder . '</>');

        try {
            // Crear servicio WhatsApp para verificar plantilla
            $whatsappService = WhatsAppService::fromEmpresa($empresa);

            // Intentar obtener información de la plantilla (si existe el método)
            // Nota: Este método podría no existir en la implementación actual
            $this->line('<fg=yellow>ℹ️  Para verificar la plantilla completamente:</>');
            $this->line('   1. Ve a Meta Business Manager');
            $this->line('   2. Busca tu WhatsApp Business Account');
            $this->line('   3. Verifica que la plantilla esté creada y aprobada');
            $this->line('   4. El nombre debe coincidir exactamente');

        } catch (\Exception $e) {
            $this->line('<fg=red>❌ Error al verificar plantilla: ' . $e->getMessage() . '</>');
        }

        $this->newLine();
    }
}

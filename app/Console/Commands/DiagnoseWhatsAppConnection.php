<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Support\EmpresaResolver;
use App\Services\WhatsAppService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
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
            $accessToken = $this->resolveAccessToken($empresa);

            // Crear cliente HTTP directo para pruebas
            $client = new Client([
                'base_uri' => 'https://graph.facebook.com/v20.0/',
                'timeout' => 30,
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
            ]);

            $this->line('Probando Phone Number ID: ' . $empresa->whatsapp_phone_number_id);

            $response = $client->get($empresa->whatsapp_phone_number_id . '?fields=id,display_phone_number,verified_name,account_mode,quality_rating');
            $data = json_decode($response->getBody()->getContents(), true) ?? [];

            if (isset($data['id'])) {
                $this->line('<fg=green>✅ Phone Number ID válido</>');
                $this->line('   Display Number: ' . ($data['display_phone_number'] ?? 'No disponible'));
                $this->line('   Verified Name: ' . ($data['verified_name'] ?? 'No disponible'));
                $this->line('   Modo: ' . ($data['account_mode'] ?? 'No disponible'));
                $this->line('   Quality Rating: ' . ($data['quality_rating'] ?? 'No disponible'));
            } else {
                $this->line('<fg=red>❌ Phone Number ID inválido o sin permisos</>');
            }

            if ($empresa->whatsapp_business_account_id) {
                $this->line('');
                $this->line('Probando WhatsApp Business Account ID: ' . $empresa->whatsapp_business_account_id);

                $wabaResponse = $client->get($empresa->whatsapp_business_account_id . '?fields=id,name,currency,timezone_id,message_template_namespace');
                $wabaData = json_decode($wabaResponse->getBody()->getContents(), true) ?? [];

                if (isset($wabaData['id'])) {
                    $this->line('<fg=green>✅ WABA accesible</>');
                    $this->line('   Nombre: ' . ($wabaData['name'] ?? 'No disponible'));
                    $this->line('   Moneda: ' . ($wabaData['currency'] ?? 'No disponible'));
                    $this->line('   Timezone ID: ' . ($wabaData['timezone_id'] ?? 'No disponible'));
                } else {
                    $this->line('<fg=red>❌ WABA no accesible con este token</>');
                }
            }

        } catch (RequestException $e) {
            $body = $e->getResponse() ? (string) $e->getResponse()->getBody() : '';
            $this->line('<fg=red>❌ Error de conexión con la API</>');
            $this->line('   Error: ' . $e->getMessage());
            if ($body !== '') {
                $this->line('   Response: ' . $body);
            }

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
        } catch (\Exception $e) {
            $this->line('<fg=red>❌ Error de conexión con la API</>');
            $this->line('   Error: ' . $e->getMessage());
            $this->line('   Trace: ' . $e->getTraceAsString());
        }

        $this->newLine();
    }

    private function verificarPlantilla(Empresa $empresa): void
    {
        $this->line('📱 <fg=yellow>VERIFICACIÓN DE PLANTILLAS</>');

        $configuredTemplate = $empresa->whatsapp_template_payment_reminder ?: 'No configurada';
        $this->line('Plantilla configurada: <fg=cyan>' . $configuredTemplate . '</>');

        try {
            $whatsappService = WhatsAppService::fromEmpresa($empresa);
            $templates = $whatsappService->listTemplates();

            if (empty($templates)) {
                $this->line('<fg=yellow>⚠️  No se encontraron plantillas visibles con este token</>');
                $this->newLine();
                return;
            }

            $this->line('Plantillas encontradas: <fg=green>' . count($templates) . '</>');

            $configuredExists = false;
            foreach ($templates as $template) {
                $name = $template['name'] ?? 'sin_nombre';
                $status = $template['status'] ?? 'unknown';
                $category = $template['category'] ?? 'unknown';

                if ($empresa->whatsapp_template_payment_reminder && $name === $empresa->whatsapp_template_payment_reminder) {
                    $configuredExists = true;
                }

                $this->line("   • {$name} [{$status}] ({$category})");
            }

            if ($empresa->whatsapp_template_payment_reminder) {
                if ($configuredExists) {
                    $this->line('<fg=green>✅ La plantilla configurada existe en Meta</>');
                } else {
                    $this->line('<fg=red>❌ La plantilla configurada no aparece en Meta</>');
                }
            } else {
                $this->line('<fg=yellow>ℹ️  No hay plantilla configurada en empresa; para pruebas puedes usar hello_world</>');
            }

        } catch (\Exception $e) {
            $this->line('<fg=red>❌ Error al verificar plantilla: ' . $e->getMessage() . '</>');
        }

        $this->newLine();
    }

    private function resolveAccessToken(Empresa $empresa): string
    {
        $token = $empresa->whatsapp_access_token;

        if (!is_string($token) || trim($token) === '') {
            throw new \RuntimeException('No hay access token configurado');
        }

        // El modelo ya usa cast encrypted. Si por compatibilidad histórica se guardó sin cast,
        // intentamos decrypt solamente cuando parece un payload cifrado de Laravel.
        if (str_starts_with($token, 'eyJpdiI6')) {
            try {
                return decrypt($token);
            } catch (\Throwable) {
                // Si falla, devolver el valor tal cual para diagnosticar con el mensaje real de Meta.
            }
        }

        return $token;
    }
}

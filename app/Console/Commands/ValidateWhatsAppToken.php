<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Support\EmpresaResolver;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class ValidateWhatsAppToken extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'whatsapp:validate-token {--empresa_id= : ID específico de empresa}';

    /**
     * The console command description.
     */
    protected $description = 'Validar token de acceso de WhatsApp Business API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $empresaId = $this->option('empresa_id');

        $this->info('🔐 Validando token de WhatsApp Business API...');
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

            // Validar configuración básica
            if (!$empresa->whatsapp_phone_number_id || !$empresa->whatsapp_access_token) {
                $this->error('❌ Configuración incompleta');
                $this->line('Phone Number ID y Access Token son requeridos');
                return 1;
            }

            // Probar token
            $this->probarToken($empresa);

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error durante la validación: ' . $e->getMessage());
            return 1;
        }
    }

    private function probarToken(Empresa $empresa): void
    {
        $this->line('🔍 <fg=yellow>PROBANDO TOKEN DE ACCESO</>');

        try {
            // Obtener token (desencriptar si es necesario)
            $accessToken = $empresa->whatsapp_access_token;
            try {
                $accessToken = decrypt($accessToken);
                $this->line('🔓 Token desencriptado exitosamente');
            } catch (\Exception $e) {
                $this->line('🔒 Usando token sin encriptar');
            }

            // Crear cliente HTTP
            $client = new Client([
                'base_uri' => 'https://graph.facebook.com/v20.0/',
                'timeout' => 30,
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
            ]);

            // Probar con Phone Number ID
            $this->line('📞 Consultando Phone Number ID: ' . $empresa->whatsapp_phone_number_id);

            $response = $client->get($empresa->whatsapp_phone_number_id . '?fields=id,display_phone_number,account_mode');
            $data = json_decode($response->getBody()->getContents(), true);

            $this->line('<fg=green>✅ TOKEN VÁLIDO</>');
            $this->line('   ID: ' . $data['id']);
            $this->line('   Número: ' . ($data['display_phone_number'] ?? 'No disponible'));
            $this->line('   Modo: ' . ($data['account_mode'] ?? 'No disponible'));

            // Probar acceso a Business Account
            if ($empresa->whatsapp_business_account_id) {
                $this->line('');
                $this->line('🏢 Probando Business Account ID: ' . $empresa->whatsapp_business_account_id);

                $response = $client->get($empresa->whatsapp_business_account_id . '?fields=id,name');
                $businessData = json_decode($response->getBody()->getContents(), true);

                $this->line('<fg=green>✅ BUSINESS ACCOUNT ACCESIBLE</>');
                $this->line('   Nombre: ' . ($businessData['name'] ?? 'No disponible'));
            }

        } catch (\Exception $e) {
            $this->line('<fg=red>❌ TOKEN INVÁLIDO O EXPIRADO</>');
            $this->line('   Error: ' . $e->getMessage());

            if (strpos($e->getMessage(), 'Error validating access token') !== false) {
                $this->line('');
                $this->warn('💡 SOLUCIONES:');
                $this->line('   1. Genera un nuevo token en Meta Business');
                $this->line('   2. Usa System User token (larga duración)');
                $this->line('   3. Ve a: http://127.0.0.1:8000/empresa/configuracion');
                $this->line('   4. Actualiza el Access Token');
            }
        }

        $this->newLine();
    }
}

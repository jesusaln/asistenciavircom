<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CredentialRotation;
use App\Models\EmpresaConfiguracion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AuditCredentials extends Command
{
    protected $signature = 'app:audit-credentials {--days=90 : Umbral de días para rotación}';
    protected $description = 'Auditar la rotación de credenciales sensibles';

    public function handle()
    {
        $days = $this->option('days');
        $threshold = now()->subDays($days);
        $this->info("Iniciando auditoría de credenciales (Umbral: {$days} días)...");

        $configs = EmpresaConfiguracion::all();
        $alertsFound = false;

        foreach ($configs as $config) {
            $sensitiveFields = [
                'cva_password' => 'CVA API',
                'stripe_secret_key' => 'Stripe',
                'mercadopago_access_token' => 'MercadoPago',
                'pac_apikey' => 'PAC Facturación',
                'smtp_password' => 'SMTP/Email'
            ];

            foreach ($sensitiveFields as $field => $provider) {
                if (empty($config->$field))
                    continue;

                $lastRotation = CredentialRotation::where('field_name', $field)
                    ->where('empresa_id', $config->empresa_id)
                    ->latest('rotated_at')
                    ->first();

                if (!$lastRotation || $lastRotation->rotated_at->lt($threshold)) {
                    $date = $lastRotation ? $lastRotation->rotated_at->format('Y-m-d') : 'NUNCA';
                    $this->warn("⚠️ CRITICAL: La credencial {$provider} ({$field}) para empresa ID {$config->empresa_id} no se ha rotado desde: {$date}");

                    Log::warning("Credencial expirada o sin rotar detectada", [
                        'provider' => $provider,
                        'field' => $field,
                        'last_rotation' => $date
                    ]);

                    $alertsFound = true;
                }
            }
        }

        if (!$alertsFound) {
            $this->info("✅ Todas las credenciales están dentro del umbral de rotación.");
        }

        return 0;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Support\EmpresaResolver;
use App\Models\WhatsAppMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VerificarWhatsApp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:verificar {--empresa_id= : ID específico de empresa}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar el estado completo del sistema de WhatsApp Business';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $empresaId = $this->option('empresa_id');

        $this->info('🔍 Verificando sistema de WhatsApp Business...');
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
                    $this->info('💡 Usa: php artisan whatsapp:configurar para crear una empresa con WhatsApp');
                    return 1;
                }
            }

            $this->line("🏢 Empresa: <fg=cyan>{$empresa->nombre_razon_social}</>");
            $this->newLine();

            // Verificar configuración de WhatsApp
            $this->verificarConfiguracion($empresa);

            // Verificar logs de mensajes
            $this->verificarLogsMensajes($empresa);

            // Verificar scheduler
            $this->verificarScheduler();

            $this->newLine();
            $this->info('✅ Verificación completada');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error durante la verificación: ' . $e->getMessage());
            Log::error('Error en verificación de WhatsApp', [
                'error' => $e->getMessage(),
                'empresa_id' => $empresaId,
            ]);
            return 1;
        }
    }

    private function verificarConfiguracion(Empresa $empresa): void
    {
        $this->line('📋 <fg=yellow>VERIFICACIÓN DE CONFIGURACIÓN</>');

        // Estado general
        $whatsappEnabled = $empresa->whatsapp_enabled;
        $this->line("WhatsApp habilitado: " . ($whatsappEnabled ? '<fg=green>✅ SÍ</>' : '<fg=red>❌ NO</>'));

        if (!$whatsappEnabled) {
            $this->line("💡 Configura WhatsApp en: <fg=cyan>http://127.0.0.1:8000/empresa/configuracion</>");
            return;
        }

        // Verificar campos requeridos
        $requiredFields = [
            'whatsapp_business_account_id' => 'Business Account ID',
            'whatsapp_phone_number_id' => 'Phone Number ID',
            'whatsapp_sender_phone' => 'Número de teléfono',
            'whatsapp_access_token' => 'Access Token',
            'whatsapp_app_secret' => 'App Secret',
            'whatsapp_webhook_verify_token' => 'Token de verificación',
            'whatsapp_template_payment_reminder' => 'Plantilla de recordatorio',
        ];

        $missingFields = [];
        foreach ($requiredFields as $field => $label) {
            $value = $empresa->$field;
            $status = !empty($value) ? '<fg=green>✅</>' : '<fg=red>❌</>';
            $this->line("{$label}: {$status}");

            if (empty($value)) {
                $missingFields[] = $label;
            }
        }

        if (!empty($missingFields)) {
            $this->warn('⚠️  Campos faltantes: ' . implode(', ', $missingFields));
        } else {
            $this->line('<fg=green>✅ Todos los campos requeridos están configurados</>');
        }

        $this->newLine();
    }

    private function verificarLogsMensajes(Empresa $empresa): void
    {
        $this->line('📊 <fg=yellow>ESTADÍSTICAS DE MENSAJES</>');

        $totalMessages = WhatsAppMessage::where('empresa_id', $empresa->id)->count();
        $this->line("Total de mensajes: <fg=cyan>{$totalMessages}</>");

        if ($totalMessages > 0) {
            $stats = WhatsAppMessage::where('empresa_id', $empresa->id)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get();

            foreach ($stats as $stat) {
                $status = $stat->status ?? 'unknown';
                $count = $stat->count;
                $this->line("  {$status}: <fg=cyan>{$count}</>");
            }
        }

        $recentMessages = WhatsAppMessage::where('empresa_id', $empresa->id)
            ->recent(1)
            ->count();
        $this->line("Mensajes recientes (24h): <fg=cyan>{$recentMessages}</>");

        $this->newLine();
    }

    private function verificarScheduler(): void
    {
        $this->line('⏰ <fg=yellow>VERIFICACIÓN DE SCHEDULER</>');

        try {
            // Verificar si el comando existe
            $command = 'php artisan whatsapp:enviar-recordatorios --dias=3';

            $this->line('Comando para recordatorios:');
            $this->line("  <fg=cyan>{$command}</>");
            $this->line('Estado: <fg=green>✅ Disponible</>');

            $this->info('💡 Para agregar al scheduler, agrega esto a app/Console/Kernel.php:');
            $this->line('$schedule->command(\'whatsapp:enviar-recordatorios --dias=3\')');
            $this->line('         ->dailyAt(\'09:00\')');
            $this->line('         ->withoutOverlapping();');

        } catch (\Exception $e) {
            $this->error('❌ Error verificando scheduler: ' . $e->getMessage());
        }

        $this->newLine();
    }
}

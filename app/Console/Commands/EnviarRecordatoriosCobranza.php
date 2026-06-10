<?php

namespace App\Console\Commands;

use App\Models\CuentasPorCobrar;
use App\Models\RecordatorioCobranza;
use App\Models\EmpresaConfiguracion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class EnviarRecordatoriosCobranza extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cobranza:enviar-recordatorios {--dry-run : Ejecutar sin enviar emails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar recordatorios automáticos de pago para cuentas por cobrar vencidas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando envío de recordatorios de cobranza...');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('⚠️ Modo de prueba activado - no se enviarán emails reales');
        }

        // Obtener cuentas que necesitan recordatorio (Polimórfico)
        $cuentasNecesitanRecordatorio = CuentasPorCobrar::with(['cobrable'])
            ->pendientes()
            ->get()
            ->filter(function ($cuenta) {
                if (!$cuenta->cobrable) return false;
                
                // Cargar cliente dinámicamente según el tipo
                $cliente = $cuenta->cobrable->cliente ?? null;
                
                if (!$cliente || !$cliente->email) {
                    return false;
                }

                // Lógica Estricta para Rentas: Si es Renta, siempre notificar el día de pago
                if ($cuenta->cobrable_type === \App\Models\Renta::class) {
                     // Si hoy es el día de vencimiento o ya pasó, notificar
                     if (now()->greaterThanOrEqualTo($cuenta->fecha_vencimiento)) {
                         // Verificar si ya se envió HOY para evitar spam
                         return !$cuenta->recordatorios()
                             ->whereDate('fecha_envio', now())
                             ->exists();
                     }
                }

                return $cuenta->necesitaRecordatorio();
            });

        $this->info("📋 Se encontraron {$cuentasNecesitanRecordatorio->count()} cuentas que necesitan recordatorio");

        if ($cuentasNecesitanRecordatorio->isEmpty()) {
            $this->info('✅ No hay cuentas que necesiten recordatorios en este momento');
            return 0;
        }

        // ✅ FIX: Verificar rate limit antes de empezar
        $rateLimiter = app(\App\Services\EmailRateLimiterService::class);
        $stats = $rateLimiter->getStats();
        
        $this->info("📧 Estado del rate limiter:");
        $this->line("   • Correos enviados (ráfaga): {$stats['burst']['current']}/{$stats['burst']['limit']}");
        $this->line("   • Correos enviados (día): {$stats['daily']['current']}/{$stats['daily']['limit']}");
        
        if (!$rateLimiter->canSendEmail()) {
            $this->warn("⚠️ Límite de correos alcanzado. Espere {$stats['burst']['reset_in_minutes']} minutos.");
            return 1;
        }

        $totalEnviados = 0;
        $totalErrores = 0;
        $saltadosPorLimite = 0;

        foreach ($cuentasNecesitanRecordatorio as $cuenta) {
            // ✅ FIX: Verificar rate limit antes de cada envío
            if (!$rateLimiter->canSendEmail()) {
                $saltadosPorLimite++;
                $tipo = class_basename($cuenta->cobrable_type);
                $this->warn("⏸️ Saltado {$tipo} por límite de correos alcanzado");
                continue;
            }
            
            try {
                $this->processCuenta($cuenta, $dryRun);
                
                // ✅ FIX: Registrar envío en rate limiter después de éxito
                if (!$dryRun) {
                    $rateLimiter->recordEmailSent();
                }
                
                $totalEnviados++;
                $tipo = class_basename($cuenta->cobrable_type);
                $id = $cuenta->cobrable instanceof \App\Models\Venta ? $cuenta->cobrable->numero_venta : $cuenta->cobrable->numero_contrato;
                $this->line("✅ Recordatorio enviado para {$tipo} #{$id}");
            } catch (\Exception $e) {
                $totalErrores++;
                $this->error("❌ Error procesando cuenta {$cuenta->id}: {$e->getMessage()}");
                Log::error('Error enviando recordatorio de cobranza', [
                    'cuenta_id' => $cuenta->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $this->newLine();
        $this->info("📊 Resumen del proceso:");
        $this->line("   ✅ Recordatorios enviados: {$totalEnviados}");
        $this->line("   ❌ Errores: {$totalErrores}");
        if ($saltadosPorLimite > 0) {
            $this->line("   ⏸️ Saltados por límite: {$saltadosPorLimite}");
        }
        $this->line("   📋 Total procesados: {$cuentasNecesitanRecordatorio->count()}");

        return $totalErrores > 0 ? 1 : 0;
    }

    private function processCuenta(CuentasPorCobrar $cuenta, bool $dryRun): void
    {
        $recordatorio = $cuenta->programarRecordatorio();

        // Para rentas, forzar creación si es necesario (lógica estricta)
        if (!$recordatorio && $cuenta->cobrable_type === \App\Models\Renta::class) {
             // Crear recordatorio forzado
             $recordatorio = $cuenta->recordatorios()->create([
                'tipo_recordatorio' => 'urgente_renta',
                'fecha_envio' => now(),
                'fecha_proximo_recordatorio' => now()->addDay(),
                'enviado' => false,
                'numero_intento' => $cuenta->recordatorios()->count() + 1,
            ]);
        }

        if (!$recordatorio) {
            throw new \Exception('No se pudo programar recordatorio para esta cuenta');
        }

        if ($dryRun) {
            $tipo = class_basename($cuenta->cobrable_type);
            $this->warn("   [DRY RUN] Se programaría recordatorio para {$tipo}");
            $recordatorio->marcarComoEnviado('Simulado en dry-run');
            return;
        }

        $cobrable = $cuenta->cobrable;
        $cliente = $cobrable->cliente;
        $configuracion = EmpresaConfiguracion::getConfig($cuenta->empresa_id);

        // Configurar SMTP
        config([
            'mail.mailers.smtp.host' => $configuracion->smtp_host,
            'mail.mailers.smtp.port' => $configuracion->smtp_port,
            'mail.mailers.smtp.username' => $configuracion->smtp_username,
            'mail.mailers.smtp.password' => $configuracion->smtp_password,
            'mail.mailers.smtp.encryption' => $configuracion->smtp_encryption === 'null' ? null : $configuracion->smtp_encryption,
            'mail.from.address' => $configuracion->email_from_address,
            'mail.from.name' => $configuracion->email_from_name,
        ]);

        \Illuminate\Support\Facades\Mail::purge('smtp');
        app()->forgetInstance('mail.manager');

        if ($cobrable instanceof \App\Models\Venta) {
            $this->enviarEmailVenta($cuenta, $cobrable, $cliente, $configuracion, $recordatorio);
        } elseif ($cobrable instanceof \App\Models\Renta) {
            $this->enviarEmailRenta($cuenta, $cobrable, $cliente, $configuracion, $recordatorio);
        }

        $recordatorio->marcarComoEnviado();
        
        Log::info("Recordatorio de cobranza enviado exitosamente", [
            'cuenta_id' => $cuenta->id,
            'cliente_email' => $cliente->email,
        ]);
    }

    private function enviarEmailVenta($cuenta, $venta, $cliente, $configuracion, $recordatorio)
    {
        $pdf = Pdf::loadView('venta_pdf', [
            'venta' => $venta,
            'configuracion' => $configuracion,
        ]);
        $pdf->setPaper('letter', 'portrait');

        $datosEmail = [
            'cuenta' => $cuenta,
            'cliente' => $cliente,
            'configuracion' => $configuracion,
            'recordatorio' => $recordatorio,
        ];

        Mail::send('emails.recordatorio_pago', $datosEmail, function ($message) use ($venta, $cliente, $configuracion, $pdf) {
            $message->to($cliente->email)
                ->subject("🚨 Recordatorio de Pago - Venta #{$venta->numero_venta} - {$configuracion->nombre_empresa}")
                ->attachData($pdf->output(), "venta-{$venta->numero_venta}.pdf", ['mime' => 'application/pdf']);

            if ($configuracion->email_reply_to) {
                $message->replyTo($configuracion->email_reply_to);
            }
        });
    }

    private function enviarEmailRenta($cuenta, $renta, $cliente, $configuracion, $recordatorio)
    {
        // Para rentas, usamos un email más directo y urgente
        // Podríamos adjuntar el contrato si lo tuviéramos, por ahora solo el aviso textual
        
        $datosEmail = [
            'cuenta' => $cuenta,
            'cliente' => $cliente,
            'configuracion' => $configuracion,
            'recordatorio' => $recordatorio,
            'esRenta' => true,
            'renta' => $renta
        ];

        // Reutilizamos la misma vista pero con una variable 'esRenta' para cambiar el texto
        Mail::send('emails.recordatorio_pago', $datosEmail, function ($message) use ($renta, $cliente, $configuracion) {
            $message->to($cliente->email)
                ->subject("🔴 URGENTE: Pago de Renta Vencido - Contrato #{$renta->numero_contrato} - {$configuracion->nombre_empresa}");

            if ($configuracion->email_reply_to) {
                $message->replyTo($configuracion->email_reply_to);
            }
        });
    }
}

<?php

namespace App\Console\Commands;

use App\Models\PolizaServicio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PolizaAlertasVencimiento extends Command
{
    protected $signature = 'polizas:alertas-vencimiento';
    protected $description = 'Envía alertas de pólizas próximas a vencer y resetea consumos mensuales';

    public function handle()
    {
        $this->info('Iniciando proceso de alertas de pólizas...');

        $this->enviarAlertasVencimiento();
        $this->verificarResetMensual();

        $this->info('Proceso completado.');
    }

    /**
     * Enviar alertas para pólizas próximas a vencer.
     */
    protected function enviarAlertasVencimiento(): void
    {
        $this->info('Verificando pólizas próximas a vencer...');

        $polizas = PolizaServicio::activa()
            ->whereNotNull('fecha_fin')
            ->where('alerta_vencimiento_enviada', false)
            ->get()
            ->filter(fn($p) => $p->debeEnviarAlertaVencimiento());

        if ($polizas->isEmpty()) {
            $this->info('  No hay pólizas que requieran alerta de vencimiento.');
            return;
        }

        $bar = $this->output->createProgressBar($polizas->count());
        $bar->start();

        foreach ($polizas as $poliza) {
            try {
                $this->enviarAlertaVencimiento($poliza);

                $poliza->update(['alerta_vencimiento_enviada' => true]);

                Log::info("Alerta de vencimiento enviada para póliza {$poliza->folio}");
            } catch (\Exception $e) {
                Log::error("Error enviando alerta para póliza {$poliza->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("  {$polizas->count()} alertas de vencimiento enviadas.");
    }

    /**
     * Enviar una alerta individual de vencimiento.
     */
    protected function enviarAlertaVencimiento(PolizaServicio $poliza): void
    {
        $cliente = $poliza->cliente;
        $diasRestantes = $poliza->dias_para_vencer;

        // Envío por Email si hay correo configurado
        if ($cliente && $cliente->email) {
            // Crear Mailable dedicado para esto
            // Mail::to($cliente->email)->send(new PolizaProximaVencer($poliza));

            Log::info("📧 Alerta de vencimiento", [
                'poliza' => $poliza->folio,
                'cliente' => $cliente->nombre_razon_social,
                'email' => $cliente->email,
                'dias_restantes' => $diasRestantes,
                'fecha_vencimiento' => $poliza->fecha_fin->format('d/m/Y'),
            ]);
        }

        // Envío por WhatsApp si hay celular configurado
        if ($cliente && ($cliente->celular || $cliente->telefono)) {
            $telefono = $cliente->celular ?: $cliente->telefono;

            // Integrar con servicio de WhatsApp
            Log::info("📱 WhatsApp: Alerta de vencimiento", [
                'poliza' => $poliza->folio,
                'telefono' => $telefono,
                'mensaje' => "Su póliza de servicio {$poliza->nombre} vence en {$diasRestantes} días ({$poliza->fecha_fin->format('d/m/Y')}). Contacte con nosotros para renovar.",
            ]);
        }
    }

    /**
     * Verificar y resetear consumos mensuales si corresponde.
     */
    protected function verificarResetMensual(): void
    {
        $this->info('Verificando reset de consumos mensuales...');

        $hoy = now();

        // Inicio del mes actual según el día de cobro
        $inicioMesActual = $hoy->copy()->startOfMonth();

        // Obtener pólizas activas que necesitan reset
        // CORRECCIÓN: La lógica anterior con OR era incorrecta
        $polizas = PolizaServicio::activa()
            ->where(function ($query) use ($hoy, $inicioMesActual) {
                // Nunca se ha reseteado
                $query->whereNull('ultimo_reset_consumo_at')
                    // O el último reset fue ANTES del inicio del mes actual
                    ->orWhere('ultimo_reset_consumo_at', '<', $inicioMesActual);
            })
            ->where('dia_cobro', '<=', $hoy->day) // Solo resetear en o después del día de cobro
            ->get();

        if ($polizas->isEmpty()) {
            $this->info('  No hay consumos que resetear este período.');
            return;
        }

        $contador = 0;
        foreach ($polizas as $poliza) {
            $horasAntes = $poliza->horas_consumidas_mes;

            $poliza->resetearConsumoMensual();

            // También resetear flag de alerta de exceso
            $poliza->update(['ultima_alerta_exceso_at' => null]);

            if ($horasAntes > 0) {
                Log::info("Reset mensual de póliza {$poliza->folio}: {$horasAntes} horas consumidas reseteadas a 0");
            }

            $contador++;
        }

        $this->info("  {$contador} pólizas reseteadas para el nuevo período de facturación.");
    }
}

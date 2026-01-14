<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\CuentasPorCobrar;
use App\Models\PagoPrestamo;
use App\Jobs\SendWhatsAppTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnviarRecordatoriosWhatsApp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:enviar-recordatorios
                            {--dias=1 : Días antes del vencimiento para enviar recordatorio}
                            {--empresa_id= : ID específico de empresa (opcional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar recordatorios de pago por WhatsApp a clientes con cuentas por cobrar o préstamos próximos a vencer';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $diasAnticipacion = (int) $this->option('dias');
        $empresaId = $this->option('empresa_id');

        $this->info("Enviando recordatorios de WhatsApp {$diasAnticipacion} días antes del vencimiento...");

        // Obtener empresas con WhatsApp habilitado
        $empresasQuery = Empresa::where('whatsapp_enabled', true)
                               ->whereNotNull('whatsapp_template_payment_reminder');

        if ($empresaId) {
            $empresasQuery->where('id', $empresaId);
        }

        $empresas = $empresasQuery->get();

        if ($empresas->isEmpty()) {
            $this->warn('No se encontraron empresas con WhatsApp habilitado');
            return 0;
        }

        $totalEnviados = 0;

        foreach ($empresas as $empresa) {
            $this->info("Procesando empresa: {$empresa->nombre_razon_social}");

            try {
                // =====================================================
                // SECCIÓN 1: CUENTAS POR COBRAR
                // =====================================================
                $fechaRecordatorio = now()->addDays($diasAnticipacion);

                $cuentasPorCobrar = CuentasPorCobrar::where('empresa_id', $empresa->id)
                    ->where('fecha_vencimiento', '<=', $fechaRecordatorio)
                    ->where('fecha_vencimiento', '>', now())
                    ->where('estado', 'pendiente')
                    ->whereHas('cliente', function ($query) {
                        $query->where('whatsapp_optin', true)
                              ->whereNotNull('whatsapp_consent_date');
                    })
                    ->with(['cliente'])
                    ->get();

                if ($cuentasPorCobrar->isNotEmpty()) {
                    $this->line("  [Cuentas por Cobrar] Encontradas {$cuentasPorCobrar->count()} cuentas");

                    foreach ($cuentasPorCobrar as $cuenta) {
                        if ($this->verificarLimiteMensajes($empresa->id, $cuenta->cliente->telefono)) {
                            $this->line("  Saltando {$cuenta->cliente->nombre_razon_social} - límite alcanzado");
                            continue;
                        }

                        $templateParams = [
                            $cuenta->cliente->nombre_razon_social,
                            '$' . number_format($cuenta->monto_total, 2),
                            $cuenta->fecha_vencimiento->format('d/m/Y'),
                        ];

                        SendWhatsAppTemplate::dispatch(
                            $empresa->id,
                            $cuenta->cliente->telefono,
                            $empresa->whatsapp_template_payment_reminder,
                            $empresa->whatsapp_default_language,
                            $templateParams,
                            [
                                'tipo' => 'recordatorio_pago',
                                'cuenta_id' => $cuenta->id,
                                'dias_anticipacion' => $diasAnticipacion,
                            ]
                        );

                        $totalEnviados++;
                        $this->line("  ✓ Recordatorio CxC para {$cuenta->cliente->nombre_razon_social}");
                    }
                }

                // =====================================================
                // SECCIÓN 2: PAGOS DE PRÉSTAMOS
                // =====================================================
                $fechaManana = now()->addDays($diasAnticipacion)->toDateString();

                $pagosPrestamo = PagoPrestamo::with(['prestamo.cliente'])
                    ->whereHas('prestamo', function ($query) {
                        $query->where('estado', 'activo');
                    })
                    ->whereDate('fecha_programada', $fechaManana)
                    ->whereIn('estado', ['pendiente', 'parcial'])
                    ->whereHas('prestamo.cliente', function ($query) {
                        $query->where('whatsapp_optin', true)
                              ->whereNotNull('whatsapp_consent_date')
                              ->whereNotNull('telefono');
                    })
                    ->get();

                if ($pagosPrestamo->isNotEmpty()) {
                    $this->line("  [Préstamos] Encontrados {$pagosPrestamo->count()} pagos de préstamo");

                    foreach ($pagosPrestamo as $pago) {
                        $cliente = $pago->prestamo->cliente;

                        if ($this->verificarLimiteMensajes($empresa->id, $cliente->telefono)) {
                            $this->line("  Saltando {$cliente->nombre_razon_social} - límite alcanzado");
                            continue;
                        }

                        $montoPendiente = $pago->monto_programado - ($pago->monto_pagado ?? 0);
                        $templateParams = [
                            $cliente->nombre_razon_social,
                            '$' . number_format($montoPendiente, 2),
                            $pago->fecha_programada->format('d/m/Y'),
                        ];

                        SendWhatsAppTemplate::dispatch(
                            $empresa->id,
                            $cliente->telefono,
                            $empresa->whatsapp_template_payment_reminder,
                            $empresa->whatsapp_default_language,
                            $templateParams,
                            [
                                'tipo' => 'recordatorio_prestamo',
                                'pago_id' => $pago->id,
                                'prestamo_id' => $pago->prestamo_id,
                                'dias_anticipacion' => $diasAnticipacion,
                            ]
                        );

                        $totalEnviados++;
                        $this->line("  ✓ Recordatorio préstamo #{$pago->numero_pago} para {$cliente->nombre_razon_social}");
                    }
                }

                if ($cuentasPorCobrar->isEmpty() && $pagosPrestamo->isEmpty()) {
                    $this->line("  No hay pagos próximos a vencer para esta empresa");
                }

            } catch (\Exception $e) {
                $this->error("Error procesando empresa {$empresa->nombre_razon_social}: " . $e->getMessage());
                Log::error('Error en envío de recordatorios WhatsApp', [
                    'empresa_id' => $empresa->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Proceso completado. Total de recordatorios programados: {$totalEnviados}");

        return 0;
    }

    /**
     * Verificar si se ha alcanzado el límite de mensajes diarios para un teléfono
     */
    private function verificarLimiteMensajes(int $empresaId, string $telefono): bool
    {
        $mensajesHoy = \App\Models\WhatsAppMessage::where('empresa_id', $empresaId)
            ->where('to', $telefono)
            ->whereDate('created_at', today())
            ->count();

        $limiteMensajes = 3;
        return $mensajesHoy >= $limiteMensajes;
    }
}

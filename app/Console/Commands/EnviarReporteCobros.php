<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EmpresaConfiguracion;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EnviarReporteCobros extends Command
{
    protected $signature = 'cobranza:enviar-reporte';
    protected $description = 'Envía el reporte diario de cobranza al email configurado';

    public function handle(): int
    {
        $configuracion = EmpresaConfiguracion::getConfig();
        
        if (!$configuracion->cobros_reporte_automatico) {
            $this->info('El reporte automático de cobros está deshabilitado.');
            return self::SUCCESS;
        }

        if (empty($configuracion->email_cobros)) {
            $this->warn('No hay email de cobros configurado.');
            return self::SUCCESS;
        }

        try {
            // Configurar SMTP desde BD
            config([
                'mail.mailers.smtp.host' => $configuracion->smtp_host,
                'mail.mailers.smtp.port' => $configuracion->smtp_port,
                'mail.mailers.smtp.username' => $configuracion->smtp_username,
                'mail.mailers.smtp.password' => $configuracion->smtp_password,
                'mail.mailers.smtp.encryption' => $configuracion->smtp_encryption,
                'mail.from.address' => $configuracion->email_from_address,
                'mail.from.name' => $configuracion->email_from_name,
            ]);

            app()->forgetInstance('mail.manager');
            Mail::purge('smtp');

            $diasAnticipacion = $configuracion->cobros_dias_anticipacion ?? 0;
            $fechaLimite = now()->addDays($diasAnticipacion);

            // Obtener cuentas por cobrar
            $cuentasPorCobrar = \App\Models\CuentasPorCobrar::with('venta.cliente')
                ->whereIn('estado', ['pendiente', 'vencida', 'parcial'])
                // ->where('fecha_vencimiento', '<=', $fechaLimite) // Mostrar todo
                ->orderBy('fecha_vencimiento')
                ->get()
                ->map(function ($cuenta) {
                    $diasAtraso = round(now()->diffInDays($cuenta->fecha_vencimiento, false));
                    $cliente = $cuenta->venta->cliente ?? null;
                    return [
                        'tipo' => 'Cuenta por Cobrar',
                        'cliente' => $cliente->nombre_razon_social ?? 'Sin nombre',
                        'telefono' => $cliente->telefono ?? 'Sin teléfono',
                        'monto' => $cuenta->saldo_pendiente,
                        'dias_atraso' => $diasAtraso < 0 ? abs($diasAtraso) : 0,
                        'dias_para_vencer' => $diasAtraso > 0 ? $diasAtraso : 0,
                        'concepto' => $cuenta->concepto ?? 'N/A',
                        'estado_deuda' => $diasAtraso < 0 ? 'vencida' : 'proxima',
                    ];
                });

            // Obtener pagos de préstamos
            $pagosAtrasados = \App\Models\PagoPrestamo::with('prestamo.cliente')
                ->whereIn('estado', ['pendiente', 'parcial', 'atrasado'])
                // ->where('fecha_programada', '<=', $fechaLimite) // Mostrar todo
                ->orderBy('fecha_programada')
                ->get()
                ->map(function ($pago) {
                    $diasAtraso = round(now()->diffInDays($pago->fecha_programada, false));
                    $cliente = $pago->prestamo->cliente ?? null;
                    return [
                        'tipo' => 'Préstamo',
                        'cliente' => $cliente->nombre_razon_social ?? 'Sin nombre',
                        'telefono' => $cliente->telefono ?? 'Sin teléfono',
                        'monto' => $pago->monto_programado - $pago->monto_pagado,
                        'dias_atraso' => $diasAtraso < 0 ? abs($diasAtraso) : 0,
                        'dias_para_vencer' => $diasAtraso > 0 ? $diasAtraso : 0,
                        'concepto' => "Préstamo #{$pago->prestamo_id} - Pago #{$pago->numero_pago}",
                        'estado_deuda' => $diasAtraso < 0 ? 'vencida' : 'proxima',
                    ];
                });

            $deudores = $cuentasPorCobrar->concat($pagosAtrasados)->sortByDesc('dias_atraso')->values();

            if ($deudores->isEmpty()) {
                $this->info('No hay deudores para reportar.');
                Log::info('Reporte de cobros: sin deudores para reportar');
                return self::SUCCESS;
            }

            // Construir HTML
            $html = $this->construirHtmlReporte($deudores, $configuracion);

            // Enviar email
            Mail::html($html, function ($message) use ($configuracion) {
                $message->to($configuracion->email_cobros)
                    ->subject('Reporte de Cobranza - ' . $configuracion->nombre_empresa . ' - ' . now()->format('d/m/Y'));
            });

            Log::info('Reporte automático de cobros enviado', [
                'email' => $configuracion->email_cobros,
                'total_deudores' => $deudores->count(),
            ]);

            $this->info("Reporte enviado a {$configuracion->email_cobros} con {$deudores->count()} deudores.");
            return self::SUCCESS;

        } catch (\Throwable $e) {
            Log::error('Error en reporte automático de cobros', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function construirHtmlReporte($deudores, $configuracion): string
    {
        $totalDeuda = $deudores->sum('monto');
        $fecha = now()->format('d/m/Y H:i');
        
        $rows = '';
        foreach ($deudores as $deudor) {
            $estadoDeuda = $deudor['estado_deuda'] ?? 'vencida';
            $diasTexto = $estadoDeuda === 'vencida' 
                ? "<span style='color: #dc2626;'>{$deudor['dias_atraso']} días atrasado</span>"
                : "<span style='color: #f59e0b;'>Vence en {$deudor['dias_para_vencer']} días</span>";
            
            $rows .= "<tr>
                <td style='padding: 12px; border-bottom: 1px solid #e5e7eb;'><strong>{$deudor['cliente']}</strong></td>
                <td style='padding: 12px; border-bottom: 1px solid #e5e7eb;'>
                    <a href='tel:{$deudor['telefono']}' style='color: #2563eb; text-decoration: none;'>{$deudor['telefono']}</a>
                </td>
                <td style='padding: 12px; border-bottom: 1px solid #e5e7eb;'>\$" . number_format($deudor['monto'], 2, '.', ',') . "</td>
                <td style='padding: 12px; border-bottom: 1px solid #e5e7eb;'>{$diasTexto}</td>
                <td style='padding: 12px; border-bottom: 1px solid #e5e7eb;'>{$deudor['tipo']}</td>
            </tr>";
        }

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Reporte de Cobranza</title>
        </head>
        <body style='font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f3f4f6;'>
            <div style='max-width: 800px; margin: 0 auto; background-color: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
                <div style='background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); padding: 30px; text-align: center;'>
                    <h1 style='color: white; margin: 0; font-size: 24px;'>📊 Reporte de Cobranza</h1>
                    <p style='color: rgba(255,255,255,0.8); margin: 10px 0 0 0;'>{$configuracion->nombre_empresa}</p>
                </div>
                
                <div style='padding: 30px;'>
                    <div style='display: flex; justify-content: space-between; margin-bottom: 20px; background: #f9fafb; padding: 15px; border-radius: 8px;'>
                        <div>
                            <strong>Fecha:</strong> {$fecha}
                        </div>
                        <div>
                            <strong>Total Deudores:</strong> {$deudores->count()}
                        </div>
                        <div>
                            <strong>Deuda Total:</strong> <span style='color: #dc2626; font-weight: bold;'>\$" . number_format($totalDeuda, 2, '.', ',') . "</span>
                        </div>
                    </div>

                    <table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
                        <thead>
                            <tr style='background-color: #1e40af; color: white;'>
                                <th style='padding: 12px; text-align: left;'>Cliente</th>
                                <th style='padding: 12px; text-align: left;'>Teléfono</th>
                                <th style='padding: 12px; text-align: left;'>Monto</th>
                                <th style='padding: 12px; text-align: left;'>Atraso</th>
                                <th style='padding: 12px; text-align: left;'>Tipo</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$rows}
                        </tbody>
                    </table>

                    <div style='margin-top: 30px; padding: 20px; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;'>
                        <strong>💡 Tip:</strong> Haz clic en los números de teléfono para llamar directamente desde tu dispositivo.
                    </div>
                </div>

                <div style='background: #f3f4f6; padding: 20px; text-align: center; font-size: 12px; color: #6b7280;'>
                    Este reporte fue generado automáticamente por el sistema CDD.<br>
                    {$configuracion->nombre_empresa} - {$configuracion->telefono}
                </div>
            </div>
        </body>
        </html>";
    }
}

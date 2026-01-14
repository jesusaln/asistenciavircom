<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EmpresaConfiguracion;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EnviarReportePagos extends Command
{
    protected $signature = 'pagos:enviar-reporte';
    protected $description = 'Envía el reporte diario de cuentas por pagar al email configurado';

    public function handle(): int
    {
        $configuracion = EmpresaConfiguracion::getConfig();
        
        if (!$configuracion->pagos_reporte_automatico) {
            $this->info('El reporte automático de pagos está deshabilitado.');
            return self::SUCCESS;
        }

        if (empty($configuracion->email_pagos)) {
            $this->warn('No hay email de pagos configurado.');
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

            $diasAnticipacion = $configuracion->pagos_dias_anticipacion ?? 0;
            $fechaLimite = now()->addDays($diasAnticipacion);

            $pagosPendientes = \App\Models\CuentasPorPagar::with('compra.proveedor')
                ->whereIn('estado', ['pendiente', 'vencido', 'parcial'])
                // ->where('fecha_vencimiento', '<=', $fechaLimite) // Mostrar todo
                ->orderBy('fecha_vencimiento')
                ->get()
                ->map(function ($cuenta) {
                    $diasAtraso = round(now()->diffInDays($cuenta->fecha_vencimiento, false));
                    $proveedor = $cuenta->compra->proveedor ?? null;
                    
                    return [
                        'proveedor' => $proveedor->nombre_razon_social ?? 'Proveedor Desconocido',
                        'telefono' => $proveedor->telefono ?? 'Sin teléfono',
                        'monto' => $cuenta->monto_pendiente,
                        'dias_atraso' => $diasAtraso < 0 ? abs($diasAtraso) : 0,
                        'dias_para_vencer' => $diasAtraso > 0 ? $diasAtraso : 0,
                        'concepto' => $cuenta->notas ?? ($cuenta->compra ? "Compra #{$cuenta->compra->numero_compra}" : 'Cuenta por Pagar'),
                        'estado_deuda' => $diasAtraso < 0 ? 'vencida' : 'proxima',
                    ];
                })
                ->sortByDesc('dias_atraso')
                ->values();

            if ($pagosPendientes->isEmpty()) {
                $this->info('No hay pagos pendientes para reportar.');
                Log::info('Reporte de pagos: sin cuentas pendientes para reportar');
                return self::SUCCESS;
            }

            // Construir HTML
            $html = $this->construirHtmlReporte($pagosPendientes, $configuracion);

            // Enviar email
            Mail::html($html, function ($message) use ($configuracion) {
                $message->to($configuracion->email_pagos)
                    ->subject('Reporte de Pagos a Proveedores - ' . $configuracion->nombre_empresa . ' - ' . now()->format('d/m/Y'));
            });

            Log::info('Reporte automático de pagos enviado', [
                'email' => $configuracion->email_pagos,
                'total_pendientes' => $pagosPendientes->count(),
            ]);

            $this->info("Reporte enviado a {$configuracion->email_pagos} con {$pagosPendientes->count()} cuentas.");
            return self::SUCCESS;

        } catch (\Throwable $e) {
            Log::error('Error en reporte automático de pagos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function construirHtmlReporte($pagos, $configuracion): string
    {
        $totalDeuda = $pagos->sum('monto');
        $fecha = now()->format('d/m/Y H:i');
        
        $rows = '';
        foreach ($pagos as $pago) {
            $estadoDeuda = $pago['estado_deuda'] ?? 'vencida';
            $diasTexto = $estadoDeuda === 'vencida' 
                ? "<span style='color: #dc2626;'>{$pago['dias_atraso']} días atrasado</span>"
                : "<span style='color: #f59e0b;'>Vence en {$pago['dias_para_vencer']} días</span>";
            
            $rows .= "<tr>
                <td style='padding: 12px; border-bottom: 1px solid #e5e7eb;'>
                    <strong>{$pago['proveedor']}</strong><br>
                    <span style='font-size: 12px; color: #6b7280;'>{$pago['concepto']}</span>
                </td>
                <td style='padding: 12px; border-bottom: 1px solid #e5e7eb;'>\$" . number_format($pago['monto'], 2, '.', ',') . "</td>
                <td style='padding: 12px; border-bottom: 1px solid #e5e7eb;'>{$diasTexto}</td>
            </tr>";
        }

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Reporte de Pagos a Proveedores</title>
        </head>
        <body style='font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f3f4f6;'>
            <div style='max-width: 800px; margin: 0 auto; background-color: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
                <div style='background: linear-gradient(135deg, #ea580c 0%, #f97316 100%); padding: 30px; text-align: center;'>
                    <h1 style='color: white; margin: 0; font-size: 24px;'>📉 Cuentas por Pagar</h1>
                    <p style='color: rgba(255,255,255,0.8); margin: 10px 0 0 0;'>{$configuracion->nombre_empresa}</p>
                </div>
                
                <div style='padding: 30px;'>
                    <div style='display: flex; justify-content: space-between; margin-bottom: 20px; background: #fff7ed; padding: 15px; border-radius: 8px; border: 1px solid #ffedd5;'>
                        <div>
                            <strong>Fecha:</strong> {$fecha}
                        </div>
                        <div>
                            <strong>Total Pendientes:</strong> {$pagos->count()}
                        </div>
                        <div>
                            <strong>Total a Pagar:</strong> <span style='color: #ea580c; font-weight: bold;'>\$" . number_format($totalDeuda, 2, '.', ',') . "</span>
                        </div>
                    </div>

                    <table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
                        <thead>
                            <tr style='background-color: #ea580c; color: white;'>
                                <th style='padding: 12px; text-align: left;'>Proveedor / Concepto</th>
                                <th style='padding: 12px; text-align: left;'>Monto</th>
                                <th style='padding: 12px; text-align: left;'>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$rows}
                        </tbody>
                    </table>

                    <div style='margin-top: 30px; padding: 20px; background: #eff6ff; border-radius: 8px; border-left: 4px solid #3b82f6;'>
                        <strong>💡 Información:</strong> Este reporte incluye cuentas vencidas y próximas a vencer según su configuración de anticipación.
                    </div>
                </div>

                <div style='background: #f3f4f6; padding: 20px; text-align: center; font-size: 12px; color: #6b7280;'>
                    Este reporte fue generado automáticamente por el sistema CDD.<br>
                    {$configuracion->nombre_empresa}
                </div>
            </div>
        </body>
        </html>";
    }
}

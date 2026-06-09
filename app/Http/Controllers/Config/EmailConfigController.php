<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponse;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Controller para gestión de configuración de email
 * 
 * Maneja: SMTP, pruebas de email, reportes automáticos
 */
class EmailConfigController extends Controller
{
    use ApiResponse;

    /**
     * Actualizar configuración SMTP
     */
    public function update(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_encryption' => 'nullable|string|in:tls,ssl',
            'email_from_address' => 'nullable|email|max:255',
            'email_from_name' => 'nullable|string|max:255',
            'email_reply_to' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            Log::error('Validación fallida en configuracion correo', ['errors' => $validator->errors()]);
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $configuracion = EmpresaConfiguracion::getConfig();
        $configuracion->update($validator->validated());
        EmpresaConfiguracion::clearCache();

        return redirect()->back()->with('success', 'Configuración de correo actualizada correctamente.');
    }

    /**
     * Enviar correo de prueba
     */
    public function testEmail(Request $request)
    {
        $data = $request->validate([
            'email_destino' => ['required', 'email'],
        ]);

        try {
            $configuracion = EmpresaConfiguracion::getConfig();

            Log::info('Configuración obtenida de BD para prueba de correo:', [
                'smtp_host' => $configuracion->smtp_host,
                'smtp_port' => $configuracion->smtp_port,
                'smtp_username' => $configuracion->smtp_username,
                'smtp_encryption' => $configuracion->smtp_encryption,
            ]);

            if (empty($configuracion->smtp_host) || empty($configuracion->smtp_port)) {
                throw new \RuntimeException('Configuración SMTP incompleta. Verifica host y puerto.');
            }

            $this->configurarSmtp($configuracion);

            Log::info('Configuración de Laravel Mail aplicada desde BD');

            Mail::raw('Prueba de SMTP OK - ' . now()->format('Y-m-d H:i:s'), function ($m) use ($data) {
                $m->to($data['email_destino'])->subject('Prueba SMTP - CDD');
            });

            Log::info('Correo de prueba enviado exitosamente');

            return back()->with('success', 'Correo de prueba enviado correctamente.');
        } catch (\Throwable $e) {
            Log::error('Fallo al enviar correo de prueba', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $mensaje = $this->formatearErrorSmtp($e);

            throw ValidationException::withMessages([
                'smtp' => $mensaje,
            ]);
        }
    }

    /**
     * Enviar reporte de deudores por email
     */
    public function enviarReporteCobros(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'test_mode' => 'nullable|boolean',
        ]);

        $isTestMode = $request->boolean('test_mode', true);

        try {
            $configuracion = EmpresaConfiguracion::getConfig();
            $this->configurarSmtp($configuracion);

            if ($isTestMode) {
                $deudores = $this->getDatosEjemploCobros();
            } else {
                $deudores = $this->getDeudoresReales($configuracion);
                if ($deudores->isEmpty()) {
                    return back()->with('success', 'No hay deudores pendientes para reportar.');
                }
            }

            $html = $this->construirHtmlReporteCobros($deudores, $configuracion, $isTestMode);

            Mail::html($html, function ($message) use ($request, $configuracion) {
                $message->to($request->email)
                    ->subject('Reporte de Cobranza - ' . $configuracion->nombre_empresa . ' - ' . now()->format('d/m/Y'));
            });

            Log::info('Reporte de cobros enviado', ['email' => $request->email, 'total_deudores' => $deudores->count()]);

            return back()->with('success', 'Reporte enviado a ' . $request->email . ' con ' . $deudores->count() . ' deudores.');

        } catch (\Throwable $e) {
            Log::error('Error al enviar reporte de cobros', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['email' => $this->formatearErrorSmtp($e)]);
        }
    }

    /**
     * Enviar reporte de pagos pendientes a proveedores
     */
    public function enviarReportePagos(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'test_mode' => 'nullable|boolean',
        ]);

        $isTestMode = $request->boolean('test_mode', false);
        $configuracion = EmpresaConfiguracion::getConfig();

        try {
            $this->configurarSmtp($configuracion);

            if ($isTestMode) {
                $pagosPendientes = $this->getDatosEjemploPagos();
            } else {
                $pagosPendientes = $this->getPagosPendientesReales($configuracion);
                if ($pagosPendientes->isEmpty()) {
                    return back()->with('success', 'No hay pagos pendientes para reportar.');
                }
            }

            $html = $this->construirHtmlReportePagos($pagosPendientes, $configuracion, $isTestMode);

            Mail::html($html, function ($message) use ($request, $configuracion, $isTestMode) {
                $asunto = $isTestMode ? '[PRUEBA] ' : '';
                $asunto .= 'Reporte de Pagos a Proveedores - ' . $configuracion->nombre_empresa;
                $message->to($request->email)->subject($asunto);
            });

            return back()->with('success', $isTestMode ? 'Reporte de prueba enviado correctamente' : 'Reporte de pagos enviado correctamente');

        } catch (\Exception $e) {
            Log::error('Error enviando reporte de pagos: ' . $e->getMessage());
            return back()->withErrors(['email' => 'Error al enviar el correo: ' . $e->getMessage()]);
        }
    }

    /**
     * Configurar SMTP dinámicamente desde la configuración de BD
     */
    private function configurarSmtp($configuracion): void
    {
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
    }

    /**
     * Formatear error SMTP para mensaje amigable al usuario
     */
    private function formatearErrorSmtp(\Throwable $e): string
    {
        $msg = $e->getMessage();

        if (str_contains($msg, '553 5.7.1') && str_contains($msg, 'Sender address rejected')) {
            return 'Error de Hostinger: Verifica que DKIM/SPF estén configurados correctamente.';
        }

        if (str_contains($msg, 'Expected response code "235"') || str_contains($msg, '535 5.7.8')) {
            return 'Autenticación SMTP fallida (535). Revisa usuario/contraseña y el cifrado/puerto.';
        }

        $line = strtok($msg, "\n");
        return mb_strimwidth($line ?: 'Error SMTP desconocido.', 0, 300, '…');
    }

    private function getDatosEjemploCobros(): \Illuminate\Support\Collection
    {
        return collect([
            ['tipo' => 'Cuenta por Cobrar', 'cliente' => 'Juan Pérez (EJEMPLO)', 'telefono' => '662-123-4567', 'monto' => 5000.00, 'dias_atraso' => 15, 'dias_para_vencer' => 0, 'estado_deuda' => 'vencida'],
            ['tipo' => 'Préstamo', 'cliente' => 'María López (EJEMPLO)', 'telefono' => '662-987-6543', 'monto' => 3500.50, 'dias_atraso' => 7, 'dias_para_vencer' => 0, 'estado_deuda' => 'vencida'],
        ]);
    }

    private function getDatosEjemploPagos(): \Illuminate\Support\Collection
    {
        return collect([
            ['proveedor' => 'Proveedor Ejemplo S.A.', 'telefono' => '555-123-4567', 'monto' => 15000.50, 'dias_atraso' => 5, 'dias_para_vencer' => 0, 'concepto' => 'Compra #1234', 'estado_deuda' => 'vencida'],
            ['proveedor' => 'Servicios Logísticos MX', 'telefono' => '555-987-6543', 'monto' => 8200.00, 'dias_atraso' => 0, 'dias_para_vencer' => 3, 'concepto' => 'Flete F-998', 'estado_deuda' => 'proxima'],
        ]);
    }

    private function getDeudoresReales($configuracion): \Illuminate\Support\Collection
    {
        $cxc = \App\Models\CuentasPorCobrar::with('venta.cliente')
            ->whereIn('estado', ['pendiente', 'vencida', 'parcial'])
            ->orderBy('fecha_vencimiento')
            ->get()
            ->map(fn($c) => [
                'tipo' => 'Cuenta por Cobrar',
                'cliente' => $c->venta->cliente->nombre_razon_social ?? 'Sin nombre',
                'telefono' => $c->venta->cliente->telefono ?? 'Sin teléfono',
                'monto' => $c->saldo_pendiente,
                'dias_atraso' => max(0, -round(now()->diffInDays($c->fecha_vencimiento, false))),
                'dias_para_vencer' => max(0, round(now()->diffInDays($c->fecha_vencimiento, false))),
                'estado_deuda' => now()->gt($c->fecha_vencimiento) ? 'vencida' : 'proxima',
            ]);

        return $cxc->sortByDesc('dias_atraso')->values();
    }

    private function getPagosPendientesReales($configuracion): \Illuminate\Support\Collection
    {
        return \App\Models\CuentasPorPagar::with('compra.proveedor')
            ->whereIn('estado', ['pendiente', 'vencido', 'parcial'])
            ->orderBy('fecha_vencimiento')
            ->get()
            ->map(fn($c) => [
                'proveedor' => $c->compra->proveedor->nombre_razon_social ?? 'Desconocido',
                'telefono' => $c->compra->proveedor->telefono ?? 'Sin teléfono',
                'monto' => $c->monto_pendiente,
                'dias_atraso' => max(0, -round(now()->diffInDays($c->fecha_vencimiento, false))),
                'dias_para_vencer' => max(0, round(now()->diffInDays($c->fecha_vencimiento, false))),
                'concepto' => $c->notas ?? "Compra #{$c->compra?->numero_compra}",
                'estado_deuda' => now()->gt($c->fecha_vencimiento) ? 'vencida' : 'proxima',
            ])
            ->sortByDesc('dias_atraso')
            ->values();
    }

    private function formatMoney($amount): string
    {
        return number_format($amount, 2, '.', ',');
    }

    private function construirHtmlReporteCobros($deudores, $config, bool $isTest = false): string
    {
        $total = $deudores->sum('monto');
        $fecha = now()->format('d/m/Y H:i');
        $test = $isTest ? "<div style='background:#fef3c7;border:2px dashed #f59e0b;padding:15px;margin-bottom:20px;border-radius:8px;text-align:center'><strong style='color:#b45309'>⚠️ REPORTE DE PRUEBA</strong></div>" : "";
        $rows = $deudores->map(fn($d) => "<tr><td style='padding:12px;border-bottom:1px solid #e5e7eb'><strong>{$d['cliente']}</strong></td><td style='padding:12px;border-bottom:1px solid #e5e7eb'>{$d['telefono']}</td><td style='padding:12px;border-bottom:1px solid #e5e7eb'>\${$this->formatMoney($d['monto'])}</td><td style='padding:12px;border-bottom:1px solid #e5e7eb'>{$d['dias_atraso']} días</td></tr>")->implode('');

        return "<!DOCTYPE html><html><body style='font-family:Arial;padding:20px;background:#f3f4f6'><div style='max-width:800px;margin:0 auto;background:white;border-radius:12px;box-shadow:0 4px 6px rgba(0,0,0,0.1)'><div style='background:linear-gradient(135deg,#1e40af,#3b82f6);padding:30px;text-align:center'><h1 style='color:white;margin:0'>📊 Reporte de Cobranza</h1><p style='color:rgba(255,255,255,0.8);margin:10px 0 0'>{$config->nombre_empresa}</p></div><div style='padding:30px'>{$test}<div style='background:#f9fafb;padding:15px;border-radius:8px;margin-bottom:20px'><strong>Fecha:</strong> {$fecha} | <strong>Total:</strong> {$deudores->count()} deudores | <strong>Deuda:</strong> <span style='color:#dc2626'>\${$this->formatMoney($total)}</span></div><table style='width:100%;border-collapse:collapse'><thead><tr style='background:#1e40af;color:white'><th style='padding:12px;text-align:left'>Cliente</th><th style='padding:12px;text-align:left'>Teléfono</th><th style='padding:12px;text-align:left'>Monto</th><th style='padding:12px;text-align:left'>Atraso</th></tr></thead><tbody>{$rows}</tbody></table></div></div></body></html>";
    }

    private function construirHtmlReportePagos($pagos, $config, bool $isTest = false): string
    {
        $total = $pagos->sum('monto');
        $fecha = now()->format('d/m/Y H:i');
        $test = $isTest ? "<div style='background:#f3f4f6;border:2px dashed #9ca3af;padding:15px;text-align:center;margin-bottom:20px;border-radius:8px'><strong>MODO PRUEBA</strong></div>" : "";
        $rows = $pagos->map(fn($p) => "<tr><td style='padding:12px;border-bottom:1px solid #e5e7eb'><strong>{$p['proveedor']}</strong><br><span style='font-size:12px;color:#6b7280'>{$p['concepto']}</span></td><td style='padding:12px;border-bottom:1px solid #e5e7eb'>\${$this->formatMoney($p['monto'])}</td><td style='padding:12px;border-bottom:1px solid #e5e7eb'>{$p['dias_atraso']} días</td></tr>")->implode('');

        return "<!DOCTYPE html><html><body style='font-family:Arial;padding:20px;background:#f3f4f6'><div style='max-width:800px;margin:0 auto;background:white;border-radius:12px;box-shadow:0 4px 6px rgba(0,0,0,0.1)'><div style='background:linear-gradient(135deg,#ea580c,#f97316);padding:30px;text-align:center'><h1 style='color:white;margin:0'>📉 Cuentas por Pagar</h1><p style='color:rgba(255,255,255,0.8);margin:10px 0 0'>{$config->nombre_empresa}</p></div><div style='padding:30px'>{$test}<div style='background:#fff7ed;padding:15px;border-radius:8px;border:1px solid #ffedd5;margin-bottom:20px'><strong>Fecha:</strong> {$fecha} | <strong>Total:</strong> {$pagos->count()} pendientes | <strong>A Pagar:</strong> <span style='color:#ea580c'>\${$this->formatMoney($total)}</span></div><table style='width:100%;border-collapse:collapse'><thead><tr style='background:#ea580c;color:white'><th style='padding:12px;text-align:left'>Proveedor</th><th style='padding:12px;text-align:left'>Monto</th><th style='padding:12px;text-align:left'>Estado</th></tr></thead><tbody>{$rows}</tbody></table></div></div></body></html>";
    }
}

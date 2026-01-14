<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class CotizacionDocumentoController extends Controller
{
    public function __construct(
        private readonly \App\Services\PdfGeneratorService $pdfService
    ) {
    }
    /**
     * Enviar cotización por email
     */
    public function enviarEmail(Request $request, $id)
    {
        $data = $request->validate([
            'email_destino' => ['nullable', 'email'],
        ]);

        try {
            $cotizacion = Cotizacion::with(['cliente', 'items.cotizable'])->findOrFail($id);

            $emailDestino = $data['email_destino'] ?? $cotizacion->cliente->email;
            if (!$emailDestino) {
                throw ValidationException::withMessages([
                    'email' => 'El cliente no tiene email configurado y no se proporcionó un email de destino',
                ]);
            }

            Log::info('Iniciando envío de cotización por email', [
                'cotizacion_id' => $cotizacion->id,
                'numero_cotizacion' => $cotizacion->numero_cotizacion,
                'cliente_id' => $cotizacion->cliente->id,
                'cliente_email' => $emailDestino,
                'cliente_nombre' => $cotizacion->cliente->nombre_razon_social,
            ]);

            $configuracion = \App\Models\EmpresaConfiguracion::getConfig();

            Log::info('Configuración SMTP obtenida', [
                'smtp_host' => $configuracion->smtp_host,
                'smtp_port' => $configuracion->smtp_port,
                'smtp_username' => $configuracion->smtp_username ? substr($configuracion->smtp_username, 0, 10) . '...' : 'no configurado',
                'smtp_encryption' => $configuracion->smtp_encryption,
                'email_from_address' => $configuracion->email_from_address,
                'email_from_name' => $configuracion->email_from_name,
            ]);

            Log::info('Generando PDF de cotización');

            // Generate PDF using Service
            $pdf = $this->pdfService->loadView('cotizacion_pdf', [
                'cotizacion' => $cotizacion
            ]);

            Log::info('PDF generado exitosamente', [
                'pdf_size' => strlen($pdf->output()) . ' bytes'
            ]);

            $datosEmail = [
                'cotizacion' => $cotizacion,
                'cliente' => $cotizacion->cliente,
                'configuracion' => $configuracion,
                'fecha_envio' => now()->format('d/m/Y H:i:s'),
                'numero_cotizacion_formateado' => $cotizacion->numero_cotizacion,
            ];

            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => $configuracion->smtp_host,
                'mail.mailers.smtp.port' => $configuracion->smtp_port,
                'mail.mailers.smtp.username' => $configuracion->smtp_username,
                'mail.mailers.smtp.password' => $configuracion->smtp_password,
                'mail.mailers.smtp.encryption' => $configuracion->smtp_encryption,
                'mail.mailers.smtp.timeout' => 30,
                'mail.from.address' => $configuracion->email_from_address,
                'mail.from.name' => $configuracion->email_from_name,
            ]);

            \Illuminate\Support\Facades\Mail::purge('smtp');

            Log::info('Configuración SMTP aplicada y forzada', [
                'config_aplicada' => config('mail.mailers.smtp'),
                'config_default' => config('mail.default'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
            ]);

            $rateLimiter = app(\App\Services\EmailRateLimiterService::class);
            if (!$rateLimiter->canSendEmail()) {
                $stats = $rateLimiter->getStats();
                $waitMinutes = $stats['burst']['reset_in_minutes'];
                throw new \Exception("Límite de correos alcanzado. Espere {$waitMinutes} minutos antes de enviar más correos. (Límite: {$stats['burst']['limit']} cada {$stats['burst']['window_minutes']} min, {$stats['daily']['limit']} por día)");
            }

            Mail::send('emails.cotizacion', $datosEmail, function ($message) use ($cotizacion, $pdf, $configuracion, $emailDestino) {
                $message->to($emailDestino)
                    ->subject("Cotización #{$cotizacion->numero_cotizacion} - {$configuracion->nombre_empresa}")
                    ->attachData($pdf->output(), "cotizacion-{$cotizacion->numero_cotizacion}.pdf", [
                        'mime' => 'application/pdf',
                    ]);

                if ($configuracion->email_reply_to) {
                    $message->replyTo($configuracion->email_reply_to);
                }

                if ($configuracion->email_from_address) {
                    $message->bcc($configuracion->email_from_address);
                }

                Log::info('Email preparado para envío', [
                    'to' => $emailDestino,
                    'bcc' => $configuracion->email_from_address,
                    'subject' => "Cotización #{$cotizacion->numero_cotizacion} - {$configuracion->nombre_empresa}",
                    'attachment_name' => "cotizacion-{$cotizacion->numero_cotizacion}.pdf",
                    'reply_to' => $configuracion->email_reply_to ?? 'no configurado',
                ]);
            });

            $rateLimiter->recordEmailSent();

            Log::info('Email enviado exitosamente via Mail::send DESDE INTERFAZ WEB', [
                'cotizacion_id' => $cotizacion->id,
                'cliente_email' => $emailDestino,
                'numero_cotizacion' => $cotizacion->numero_cotizacion,
                'configuracion_smtp' => [
                    'host' => $configuracion->smtp_host,
                    'port' => $configuracion->smtp_port,
                    'encryption' => $configuracion->smtp_encryption,
                ],
                'bcc_enviado' => $configuracion->email_from_address,
                'timestamp' => now()->toISOString(),
                'contexto' => 'web_interface',
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
            ]);

            $cotizacion->update([
                'email_enviado' => true,
                'email_enviado_fecha' => now(),
                'email_enviado_por' => Auth::id(),
            ]);

            Log::info('Cotización actualizada con envío de email registrado', [
                'cotizacion_id' => $cotizacion->id,
                'email_enviado' => true,
                'email_enviado_fecha' => now()->format('Y-m-d H:i:s'),
                'email_enviado_por' => Auth::id(),
            ]);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cotización enviada por email correctamente. Si no llega, revisa la carpeta de spam.',
                    'cotizacion' => [
                        'id' => $cotizacion->id,
                        'email_enviado' => true,
                        'email_enviado_fecha' => $cotizacion->email_enviado_fecha?->format('d/m/Y H:i'),
                        'estado' => $cotizacion->estado->value
                    ]
                ]);
            }

            return redirect()->back()->with('success', 'Cotización enviada por email correctamente. Si no llega, revisa la carpeta de spam.');
        } catch (\Exception $e) {
            Log::error('Error al enviar PDF de cotización por email', [
                'cotizacion_id' => $id,
                'cliente_email' => $emailDestino ?? 'no disponible',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = $e->getMessage();
            $mensaje = 'Error al enviar cotización por email';

            if (strpos($errorMessage, 'authentication failed') !== false) {
                $mensaje = 'Error de autenticación SMTP. Verifica usuario/contraseña.';
            } elseif (strpos($errorMessage, 'Connection refused') !== false) {
                $mensaje = 'No se pudo conectar al servidor SMTP. Verifica host/puerto.';
            } elseif (strpos($errorMessage, 'timeout') !== false) {
                $mensaje = 'Timeout de conexión. Servidor no responde.';
            } elseif (strpos($errorMessage, 'View') !== false) {
                $mensaje = 'Error en plantilla de email. Verifica archivos de vistas.';
            }

            throw ValidationException::withMessages([
                'email' => $mensaje . ' | Detalle: ' . $errorMessage,
            ]);
        }
    }

    /**
     * Generar PDF de cotización usando plantilla Blade
     */
    public function generarPDF($id)
    {
        try {
            $cotizacion = Cotizacion::with(['cliente', 'items.cotizable'])->findOrFail($id);

            $pdf = $this->pdfService->loadView('cotizacion_pdf', [
                'cotizacion' => $cotizacion
            ]);

            $modo = request()->query('modo', 'inline');
            $filename = "cotizacion-{$cotizacion->numero_cotizacion}.pdf";

            if ($modo === 'download') {
                return $this->pdfService->download($pdf, $filename);
            }

            // Stream Logic
            $this->pdfService->stream($pdf, $filename);

        } catch (\Exception $e) {
            Log::error('Error al generar PDF de cotización', [
                'cotizacion_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()->back()->with('error', 'Error al generar el PDF de la cotización');
        }
    }

    /**
     * Generar ticket térmico de cotización (80mm)
     */
    public function generarTicket($id)
    {
        try {
            $cotizacion = Cotizacion::with(['cliente', 'items.cotizable'])->findOrFail($id);

            // 80mm paper logic handled by service if 4th param array
            $pdf = $this->pdfService->loadView('cotizacion_ticket', [
                'cotizacion' => $cotizacion
            ], [0, 0, 226.77, 1000]);

            return $this->pdfService->download($pdf, "ticket-cotizacion-{$cotizacion->numero_cotizacion}.pdf");
        } catch (\Exception $e) {
            Log::error('Error al generar ticket térmico de cotización', [
                'cotizacion_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()->back()->with('error', 'Error al generar el ticket térmico de la cotización');
        }
    }
}

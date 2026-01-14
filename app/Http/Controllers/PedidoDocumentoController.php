<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Servicio;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class PedidoDocumentoController extends Controller
{
    public function __construct(
        private readonly \App\Services\PdfGeneratorService $pdfService
    ) {
    }
    /**
     * Enviar pedido por email
     */
    public function enviarEmail(Request $request, $id)
    {
        $data = $request->validate([
            'email_destino' => ['nullable', 'email'],
        ]);

        try {
            $pedido = Pedido::with([
                'cliente',
                'items' => function ($q) {
                    $q->whereIn('pedible_type', [Producto::class, Servicio::class]);
                },
                'items.pedible'
            ])->findOrFail($id);

            $emailDestino = $data['email_destino'] ?? $pedido->cliente->email;
            if (!$emailDestino) {
                throw ValidationException::withMessages([
                    'email' => 'El cliente no tiene email configurado y no se proporcionó un email de destino',
                ]);
            }

            $configuracion = \App\Models\EmpresaConfiguracion::getConfig();

            $configuracion = \App\Models\EmpresaConfiguracion::getConfig();

            // Use Service for PDF generation
            $pdf = $this->pdfService->loadView('pedido_pdf', [
                'pedido' => $pedido
            ]);

            $datosEmail = [
                'pedido' => $pedido,
                'cliente' => $pedido->cliente,
                'configuracion' => $configuracion,
            ];

            config([
                'mail.mailers.smtp.host' => $configuracion->smtp_host,
                'mail.mailers.smtp.port' => $configuracion->smtp_port,
                'mail.mailers.smtp.username' => $configuracion->smtp_username,
                'mail.mailers.smtp.password' => $configuracion->smtp_password,
                'mail.mailers.smtp.encryption' => $configuracion->smtp_encryption,
                'mail.from.address' => $configuracion->email_from_address,
                'mail.from.name' => $configuracion->email_from_name,
            ]);

            $rateLimiter = app(\App\Services\EmailRateLimiterService::class);
            if (!$rateLimiter->canSendEmail()) {
                $stats = $rateLimiter->getStats();
                $waitMinutes = $stats['burst']['reset_in_minutes'];
                throw new \Exception("Límite de correos alcanzado. Espere {$waitMinutes} minutos.");
            }

            Mail::send('emails.pedido', $datosEmail, function ($message) use ($pedido, $pdf, $configuracion, $emailDestino) {
                $message->to($emailDestino)
                    ->subject("Pedido #{$pedido->numero_pedido} - {$configuracion->nombre_empresa}")
                    ->attachData($pdf->output(), "pedido-{$pedido->numero_pedido}.pdf", [
                        'mime' => 'application/pdf',
                    ]);

                if ($configuracion->email_reply_to) {
                    $message->replyTo($configuracion->email_reply_to);
                }
            });

            $rateLimiter->recordEmailSent();

            Log::info('PDF de pedido enviado por email', [
                'pedido_id' => $pedido->id,
                'cliente_email' => $emailDestino,
                'numero_pedido' => $pedido->numero_pedido,
                'configuracion_smtp' => [
                    'host' => $configuracion->smtp_host,
                    'port' => $configuracion->smtp_port,
                    'encryption' => $configuracion->smtp_encryption,
                ]
            ]);

            $pedido->update([
                'email_enviado' => true,
                'email_enviado_fecha' => now(),
                'email_enviado_por' => Auth::id(),
            ]);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pedido enviado por email correctamente',
                    'pedido' => [
                        'id' => $pedido->id,
                        'email_enviado' => true,
                        'email_enviado_fecha' => now()->format('d/m/Y H:i'),
                        'estado' => $pedido->estado->value
                    ]
                ]);
            }

            return redirect()->back()->with('success', 'Pedido enviado por email correctamente');
        } catch (\Exception $e) {
            Log::error('Error al enviar PDF de pedido por email', [
                'pedido_id' => $id,
                'cliente_email' => $emailDestino ?? 'no disponible',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = $e->getMessage();
            $mensaje = 'Error al enviar pedido por email';

            if (strpos($errorMessage, 'authentication failed') !== false) {
                $mensaje = 'Error de autenticación SMTP. Verifica usuario/contraseña.';
            } elseif (strpos($errorMessage, 'Connection refused') !== false) {
                $mensaje = 'No se pudo conectar al servidor SMTP. Verifica host/puerto.';
            } elseif (strpos($errorMessage, 'timeout') !== false) {
                $mensaje = 'Timeout de conexión. Servidor no responde.';
            } elseif (strpos($errorMessage, 'View') !== false) {
                $mensaje = 'Error en plantilla de email. Verifica archivos de vistas.';
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => $mensaje,
                    'details' => app()->environment('local') ? $errorMessage : null
                ], 500);
            }

            throw ValidationException::withMessages([
                'email' => $mensaje . ' | Detalle: ' . $errorMessage,
            ]);
        }
    }

    /**
     * Generar PDF de pedido usando plantilla Blade
     */
    public function generarPDF($id)
    {
        try {
            $pedido = Pedido::with(['cliente', 'items.pedible'])->findOrFail($id);

            $pdf = $this->pdfService->loadView('pedido_pdf', [
                'pedido' => $pedido
            ]);

            $modo = request()->query('modo', 'inline');
            $filename = "pedido-{$pedido->numero_pedido}.pdf";

            if ($modo === 'download') {
                return $this->pdfService->download($pdf, $filename);
            }

            // Stream
            $this->pdfService->stream($pdf, $filename);

        } catch (\Exception $e) {
        } catch (\Exception $e) {
            Log::error('Error al generar PDF de pedido', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()->back()->with('error', 'Error al generar el PDF del pedido');
        }
    }

    /**
     * Generar ticket térmico de pedido (80mm)
     */
    public function generarTicket($id)
    {
        try {
            $pedido = Pedido::with(['cliente', 'items.pedible'])->findOrFail($id);

            // Ticket layout 80mm
            $pdf = $this->pdfService->loadView('pedido_ticket', [
                'pedido' => $pedido
            ], [0, 0, 226.77, 1000]);

            return $this->pdfService->download($pdf, "ticket-pedido-{$pedido->numero_pedido}.pdf");
        } catch (\Exception $e) {
            Log::error('Error al generar ticket térmico de pedido', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()->back()->with('error', 'Error al generar el ticket térmico del pedido');
        }
    }
}

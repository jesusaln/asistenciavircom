<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class VentaDocumentoController extends Controller
{
    public function __construct(
        private readonly \App\Services\PdfGeneratorService $pdfService
    ) {
    }
    /**
     * Send sale by email.
     */
    public function enviarEmail(Request $request, $id)
    {
        try {
            $venta = Venta::with(['cliente', 'almacen', 'items.ventable', 'items.series', 'vendedor'])
                ->findOrFail($id);

            if (!$venta->cliente || !$venta->cliente->email) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'El cliente no tiene un email registrado'
                    ], 400);
                }
                return redirect()->back()->with('error', 'El cliente no tiene un email registrado');
            }

            $rateLimiter = app(\App\Services\EmailRateLimiterService::class);
            if (!$rateLimiter->canSendEmail()) {
                $stats = $rateLimiter->getStats();
                $waitMinutes = $stats['burst']['reset_in_minutes'];
                $errorMsg = "Límite de correos alcanzado. Espere {$waitMinutes} minutos.";
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'error' => $errorMsg], 429);
                }
                return redirect()->back()->with('error', $errorMsg);
            }

            Mail::to($venta->cliente->email)->send(new \App\Mail\VentaMail($venta));

            $rateLimiter->recordEmailSent();

            Log::info('Email de venta enviado', [
                'venta_id' => $venta->id,
                'numero_venta' => $venta->numero_venta,
                'cliente_email' => $venta->cliente->email,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Comprobante enviado exitosamente a ' . $venta->cliente->email
                ]);
            }

            return redirect()->back()->with('success', 'Comprobante enviado exitosamente a ' . $venta->cliente->email);
        } catch (\Exception $e) {
            Log::error('Error enviando email de venta: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al enviar el email: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Error al enviar el email: ' . $e->getMessage());
        }
    }

    /**
     * Generate PDF.
     */
    public function generarPDF(Request $request, $id)
    {
        try {
            $venta = Venta::with([
                'cliente',
                'almacen',
                'items.series.productoSerie',
                'vendedor'
            ])
                ->with([
                    'items.ventable' => function ($morphTo) {
                        $morphTo->morphWith([
                            \App\Models\Producto::class => ['kitItems.item'],
                        ]);
                    }
                ])
                ->findOrFail($id);

            Log::info('Generando PDF para venta', [
                'id' => $venta->id,
                'numero_venta' => $venta->numero_venta,
                'created_at' => $venta->created_at,
                'fecha' => $venta->fecha,
                'estado' => $venta->estado,
                'pagado' => $venta->pagado,
            ]);

            $validarDatos = config('ventas.pdf.validar_datos_completos', true);

            if ($validarDatos) {
                if (config('ventas.pdf.validar_cliente', true) && !$venta->cliente) {
                    throw new \Exception('La venta no tiene un cliente asignado');
                }

                if (config('ventas.pdf.validar_items', true) && $venta->items->isEmpty()) {
                    throw new \Exception('La venta no tiene productos');
                }

                if (config('ventas.pdf.validar_total_minimo', true) && $venta->total <= 0) {
                    throw new \Exception('El total de la venta debe ser mayor a cero');
                }

                if (empty($venta->numero_venta)) {
                    throw new \Exception('La venta no tiene número de venta asignado');
                }

                if (!$venta->fecha) {
                    throw new \Exception('La venta no tiene fecha asignada');
                }

                if (config('ventas.pdf.validar_almacen', true) && !$venta->almacen) {
                    throw new \Exception('La venta no tiene almacén asignado');
                }

                foreach ($venta->items as $item) {
                    if (!$item->ventable) {
                        throw new \Exception("Item {$item->id} no tiene producto/servicio asociado");
                    }

                    if ($item->precio === null || $item->precio < 0) {
                        throw new \Exception("Item {$item->id} tiene un precio inválido");
                    }

                    if ($item->cantidad <= 0) {
                        throw new \Exception("Item {$item->id} tiene una cantidad inválida");
                    }
                }
            }

            $piePagina = \App\Models\EmpresaConfiguracion::getPiePagina('ventas');

            // Load view using Service
            // Note: We need to pass 'piePagina' additionally
            $pdf = $this->pdfService->loadView('ventas.pdf', [
                'venta' => $venta,
                'piePagina' => $piePagina
            ]);

            $modo = $request->query('modo', 'inline');
            $filename = 'venta-' . $venta->numero_venta . '.pdf';

            if ($modo === 'download') {
                return $this->pdfService->download($pdf, $filename);
            }

            // Stream
            $this->pdfService->stream($pdf, $filename);
        } catch (\Exception $e) {
            Log::error('Error generando PDF de venta: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json(['error' => 'Error generando PDF: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generate ticket.
     */
    public function generarTicket(Request $request, $id)
    {
        try {
            $venta = Venta::with(['cliente', 'almacen', 'items.ventable', 'items.series', 'vendedor'])
                ->findOrFail($id);

            Log::info('Generando ticket para venta', [
                'id' => $venta->id,
                'numero_venta' => $venta->numero_venta,
            ]);

            // Ticket layout 80mm usually (width 226.77pt)
            // Existing code: [0, 0, 226.77, 841.89] (A4 height? or specific roll height)
            $pdf = $this->pdfService->loadView('ventas.ticket', compact('venta'), [0, 0, 226.77, 841.89]);

            return $this->pdfService->download($pdf, 'ticket-' . $venta->numero_venta . '.pdf');
        } catch (\Exception $e) {
            Log::error('Error generando ticket de venta: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json(['error' => 'Error generando ticket: ' . $e->getMessage()], 500);
        }
    }
}

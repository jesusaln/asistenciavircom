<?php

namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Enums\EstadoVenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PortalPaymentController extends Controller
{
    /**
     * Crear preferencia de pago en MercadoPago para una Venta
     */
    public function createMercadoPago(Request $request)
    {
        $validated = $request->validate([
            'venta_id' => 'required|exists:ventas,id',
        ]);

        $cliente = Auth::guard('client')->user();
        $venta = Venta::where('cliente_id', $cliente->id)->findOrFail($validated['venta_id']);

        if ($venta->estado === EstadoVenta::Pagado || $venta->pagado) {
            return response()->json([
                'success' => false,
                'message' => 'Esta venta ya fue pagada.',
            ], 400);
        }

        try {
            // Configurar MercadoPago
            $accessToken = config('services.mercadopago.access_token');
            $apiUrl = 'https://api.mercadopago.com';

            // Items
            $items = [
                [
                    'title' => "Pago de Nota de Venta #" . ($venta->numero_venta ?? $venta->id),
                    'quantity' => 1,
                    'unit_price' => (float) $venta->total,
                    'currency_id' => 'MXN',
                ]
            ];

            $preferenceData = [
                'items' => $items,
                'back_urls' => [
                    'success' => route('portal.dashboard', ['pago_status' => 'success']),
                    'failure' => route('portal.dashboard', ['pago_status' => 'failure']),
                    'pending' => route('portal.dashboard', ['pago_status' => 'pending']),
                ],
                'auto_return' => 'approved',
                'payer' => [
                    'name' => $cliente->nombre_razon_social,
                    'email' => $cliente->email,
                ],
                'external_reference' => 'VENTA_' . $venta->id,
                'notification_url' => route('portal.pagos.mercadopago.webhook'),
            ];

            $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->post("$apiUrl/checkout/preferences", $preferenceData);

            if ($response->successful()) {
                $preference = $response->json();
                return response()->json([
                    'success' => true,
                    'init_point' => $preference['init_point'],
                    'sandbox_init_point' => $preference['sandbox_init_point'],
                ]);
            }

            throw new \Exception('Error al crear preferencia en MP: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Portal MP error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar pasarela: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function webhookMercadoPago(Request $request)
    {
        // Lógica básica de webhook
        $type = $request->input('type');
        $data = $request->input('data');

        Log::info('Portal MP Webhook', $request->except(['key', 'token', 'secret', 'access_token']));

        if ($type === 'payment' && isset($data['id'])) {
            try {
                $accessToken = config('services.mercadopago.access_token');
                $apiUrl = 'https://api.mercadopago.com';

                $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
                    ->get("$apiUrl/v1/payments/{$data['id']}");

                if ($response->successful()) {
                    $payment = $response->json();
                    $ref = $payment['external_reference'] ?? '';

                    if (str_starts_with($ref, 'VENTA_')) {
                        $ventaId = str_replace('VENTA_', '', $ref);
                        $venta = Venta::find($ventaId);

                        if ($venta && !$venta->pagado) {
                            $venta->estado = EstadoVenta::Pagado;
                            $venta->pagado = true;
                            $venta->fecha_pago = now();
                            $venta->metodo_pago = 'mercadopago'; // o tarjeta
                            $venta->notas_pago = "Pagado vía MercadoPago ID: " . $payment['id'];
                            $venta->save();

                            // Actualizar saldo crédito si aplica? No, esto es pago directo.
                            // Pero si había CXC
                            if ($venta->cuentaPorCobrar) {
                                $venta->cuentaPorCobrar->update([
                                    'monto_pendiente' => 0,
                                    'estado' => 'pagado'
                                ]);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error processing Portal MP webhook: ' . $e->getMessage());
            }
        }
        return response()->json(['status' => 'ok']);
    }
}

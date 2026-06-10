<?php

namespace App\Http\Controllers;

use App\Models\PedidoOnline;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PaymentController extends Controller
{
    /**
     * Crear preferencia de pago en MercadoPago
     */
    public function createMercadoPago(Request $request)
    {
        $validated = $request->validate([
            'pedido_id' => 'required|exists:pedidos_online,id',
        ]);

        $pedido = PedidoOnline::findOrFail($validated['pedido_id']);

        // Verificar que el pedido esté pendiente
        if ($pedido->estado !== 'pendiente') {
            return response()->json([
                'success' => false,
                'message' => 'Este pedido ya fue procesado',
            ], 400);
        }

        try {
            // Configurar MercadoPago
            $accessToken = config('services.mercadopago.access_token');
            $isSandbox = config('services.mercadopago.sandbox', true);
            $apiUrl = 'https://api.mercadopago.com';

            // Items del pedido
            $items = [];
            foreach ($pedido->items as $item) {
                $items[] = [
                    'title' => $item['nombre'],
                    'quantity' => (int) $item['cantidad'],
                    'unit_price' => (float) $item['precio'],
                    'currency_id' => 'MXN',
                ];
            }

            // Agregar costo de envío si aplica
            if ($pedido->costo_envio > 0) {
                $items[] = [
                    'title' => 'Costo de envío',
                    'quantity' => 1,
                    'unit_price' => (float) $pedido->costo_envio,
                    'currency_id' => 'MXN',
                ];
            }

            $preferenceData = [
                'items' => $items,
                'back_urls' => [
                    'success' => route('pago.mercadopago.exito', ['pedido' => $pedido->numero_pedido]),
                    'failure' => route('pago.mercadopago.error', ['pedido' => $pedido->numero_pedido]),
                    'pending' => route('pago.mercadopago.pendiente', ['pedido' => $pedido->numero_pedido]),
                ],
                'auto_return' => 'approved',
                'payer' => [
                    'name' => $pedido->nombre,
                    'email' => $pedido->email,
                ],
                'external_reference' => $pedido->numero_pedido,
                'notification_url' => route('pago.mercadopago.webhook'),
            ];

            $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->post("$apiUrl/checkout/preferences", $preferenceData);

            if ($response->successful()) {
                $preference = $response->json();
                return response()->json([
                    'success' => true,
                    'preference_id' => $preference['id'],
                    'init_point' => $preference['init_point'],
                    'sandbox_init_point' => $preference['sandbox_init_point'],
                ]);
            }

            throw new \Exception('Error al crear preferencia en MP: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('MercadoPago error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pago: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Webhook de MercadoPago
     */
    public function mercadoPagoWebhook(Request $request)
    {
        Log::info('MercadoPago Webhook received', $request->except(['key', 'token', 'secret', 'access_token']));

        $type = $request->input('type');
        $data = $request->input('data');

        if ($type === 'payment' && isset($data['id'])) {
            try {
                $accessToken = config('services.mercadopago.access_token');
                $apiUrl = 'https://api.mercadopago.com';

                $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
                    ->get("$apiUrl/v1/payments/{$data['id']}");

                if ($response->successful()) {
                    $payment = $response->json();
                    $pedido = PedidoOnline::where('numero_pedido', $payment['external_reference'] ?? null)->first();

                    if ($pedido && ($payment['status'] === 'approved' || $payment['status'] === 'authorized')) {
                        $pedido->marcarComoPagado(
                            (string) $payment['id'],
                            $payment['status'],
                            [
                                'payment_type' => $payment['payment_type_id'] ?? null,
                                'payment_method' => $payment['payment_method_id'] ?? null,
                                'transaction_amount' => $payment['transaction_amount'] ?? 0,
                            ]
                        );

                        Log::info("Pedido {$pedido->numero_pedido} marcado como pagado vía webhook");
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error processing MercadoPago webhook: ' . $e->getMessage());
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Página de éxito de MercadoPago
     */
    public function mercadoPagoExito(Request $request)
    {
        $numeroPedido = $request->query('pedido');
        $paymentId = $request->query('payment_id');
        $status = $request->query('status');

        if ($numeroPedido && $paymentId && $status === 'approved') {
            $pedido = PedidoOnline::where('numero_pedido', $numeroPedido)->first();
            if ($pedido && $pedido->estado === 'pendiente') {
                $paymentData = $request->only([
                    'collection_id',
                    'collection_status',
                    'payment_id',
                    'status',
                    'external_reference',
                    'payment_type',
                    'merchant_order_id',
                    'preference_id',
                    'site_id',
                    'processing_mode',
                    'merchant_account_id'
                ]);
                $pedido->marcarComoPagado($paymentId, $status, $paymentData);
            }
        }

        return redirect()->route('tienda.pedido', $numeroPedido);
    }

    /**
     * Página de pendiente de MercadoPago
     */
    public function mercadoPagoPendiente(Request $request)
    {
        $numeroPedido = $request->query('pedido');
        return redirect()->route('tienda.pedido', $numeroPedido)
            ->with('info', 'Tu pago está pendiente de confirmación');
    }

    /**
     * Página de error de MercadoPago
     */
    public function mercadoPagoError(Request $request)
    {
        $numeroPedido = $request->query('pedido');
        return redirect()->route('tienda.pedido', $numeroPedido)
            ->with('error', 'Hubo un problema con tu pago. Por favor intenta de nuevo.');
    }

    /**
     * Crear orden en PayPal
     */
    public function createPayPal(Request $request)
    {
        $validated = $request->validate([
            'pedido_id' => 'required|exists:pedidos_online,id',
        ]);

        $pedido = PedidoOnline::findOrFail($validated['pedido_id']);

        if ($pedido->estado !== 'pendiente') {
            return response()->json([
                'success' => false,
                'message' => 'Este pedido ya fue procesado',
            ], 400);
        }

        try {
            // Configurar PayPal
            $clientId = config('services.paypal.client_id');
            $clientSecret = config('services.paypal.client_secret');
            $mode = config('services.paypal.mode', 'sandbox');

            $baseUrl = $mode === 'live'
                ? 'https://api-m.paypal.com'
                : 'https://api-m.sandbox.paypal.com';

            // Obtener access token
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "$baseUrl/v1/oauth2/token");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, "$clientId:$clientSecret");
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

            $response = curl_exec($ch);
            $tokenData = json_decode($response);
            curl_close($ch);

            if (!isset($tokenData->access_token)) {
                throw new \Exception('No se pudo obtener token de PayPal');
            }

            $accessToken = $tokenData->access_token;

            // Crear orden
            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => $pedido->numero_pedido,
                        'amount' => [
                            'currency_code' => 'MXN',
                            'value' => number_format((float) $pedido->total, 2, '.', ''),
                        ],
                        'description' => "Pedido {$pedido->numero_pedido}",
                    ],
                ],
                'application_context' => [
                    'return_url' => route('tienda.pedido', $pedido->numero_pedido),
                    'cancel_url' => route('tienda.checkout'),
                ],
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "$baseUrl/v2/checkout/orders");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                "Authorization: Bearer $accessToken",
            ]);

            $response = curl_exec($ch);
            $order = json_decode($response);
            curl_close($ch);

            if (!isset($order->id)) {
                throw new \Exception('No se pudo crear orden en PayPal');
            }

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
            ]);

        } catch (\Exception $e) {
            Log::error('PayPal error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pago: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Capturar pago de PayPal
     */
    public function capturePayPal(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|string',
            'pedido_id' => 'required|exists:pedidos_online,id',
        ]);

        $pedido = PedidoOnline::findOrFail($validated['pedido_id']);

        try {
            $clientId = config('services.paypal.client_id');
            $clientSecret = config('services.paypal.client_secret');
            $mode = config('services.paypal.mode', 'sandbox');

            $baseUrl = $mode === 'live'
                ? 'https://api-m.paypal.com'
                : 'https://api-m.sandbox.paypal.com';

            // Obtener access token
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "$baseUrl/v1/oauth2/token");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, "$clientId:$clientSecret");

            $response = curl_exec($ch);
            $tokenData = json_decode($response);
            curl_close($ch);

            $accessToken = $tokenData->access_token;

            // Capturar orden
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "$baseUrl/v2/checkout/orders/{$validated['order_id']}/capture");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, '');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                "Authorization: Bearer $accessToken",
            ]);

            $response = curl_exec($ch);
            $capture = json_decode($response);
            curl_close($ch);

            if (isset($capture->status) && $capture->status === 'COMPLETED') {
                $pedido->marcarComoPagado(
                    $validated['order_id'],
                    'approved',
                    ['paypal_response' => $capture]
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Pago completado exitosamente',
                    'redirect' => route('tienda.pedido', $pedido->numero_pedido),
                ]);
            }

            throw new \Exception('El pago no fue completado');

        } catch (\Exception $e) {
            Log::error('PayPal capture error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al capturar el pago: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Webhook de PayPal
     */
    public function paypalWebhook(Request $request)
    {
        Log::info('PayPal Webhook received', $request->except(['key', 'token', 'secret']));

        // Procesar webhook de PayPal
        // Aquí se manejarían las notificaciones de PayPal

        return response()->json(['status' => 'ok']);
    }
}

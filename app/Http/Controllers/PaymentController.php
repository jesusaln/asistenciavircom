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
            // Configurar MercadoPago SDK
            \MercadoPago\SDK::setAccessToken(config('services.mercadopago.access_token'));

            // Crear preferencia
            $preference = new \MercadoPago\Preference();

            // Items del pedido
            $items = [];
            foreach ($pedido->items as $item) {
                $mpItem = new \MercadoPago\Item();
                $mpItem->title = $item['nombre'];
                $mpItem->quantity = $item['cantidad'];
                $mpItem->unit_price = floatval($item['precio']);
                $items[] = $mpItem;
            }

            // Agregar costo de envío si aplica
            if ($pedido->costo_envio > 0) {
                $envioItem = new \MercadoPago\Item();
                $envioItem->title = 'Costo de envío';
                $envioItem->quantity = 1;
                $envioItem->unit_price = floatval($pedido->costo_envio);
                $items[] = $envioItem;
            }

            $preference->items = $items;

            // URLs de retorno
            $preference->back_urls = [
                'success' => route('pago.mercadopago.exito', ['pedido' => $pedido->numero_pedido]),
                'failure' => route('pago.mercadopago.error', ['pedido' => $pedido->numero_pedido]),
                'pending' => route('pago.mercadopago.pendiente', ['pedido' => $pedido->numero_pedido]),
            ];
            $preference->auto_return = 'approved';

            // Datos del comprador
            $preference->payer = [
                'name' => $pedido->nombre,
                'email' => $pedido->email,
            ];

            // Referencia externa
            $preference->external_reference = $pedido->numero_pedido;

            // Webhook para notificaciones
            $preference->notification_url = route('pago.mercadopago.webhook');

            $preference->save();

            return response()->json([
                'success' => true,
                'preference_id' => $preference->id,
                'init_point' => $preference->init_point,
                'sandbox_init_point' => $preference->sandbox_init_point,
            ]);

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
        Log::info('MercadoPago Webhook received', $request->all());

        $type = $request->input('type');
        $data = $request->input('data');

        if ($type === 'payment') {
            try {
                \MercadoPago\SDK::setAccessToken(config('services.mercadopago.access_token'));

                $payment = \MercadoPago\Payment::find_by_id($data['id']);

                if ($payment) {
                    $pedido = PedidoOnline::where('numero_pedido', $payment->external_reference)->first();

                    if ($pedido && $payment->status === 'approved') {
                        $pedido->marcarComoPagado(
                            $payment->id,
                            $payment->status,
                            [
                                'payment_type' => $payment->payment_type_id,
                                'payment_method' => $payment->payment_method_id,
                                'transaction_amount' => $payment->transaction_amount,
                            ]
                        );

                        Log::info("Pedido {$pedido->numero_pedido} marcado como pagado");
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
                $pedido->marcarComoPagado($paymentId, $status, $request->all());
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
                            'value' => number_format($pedido->total, 2, '.', ''),
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
        Log::info('PayPal Webhook received', $request->all());

        // Procesar webhook de PayPal
        // Aquí se manejarían las notificaciones de PayPal

        return response()->json(['status' => 'ok']);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\PolizaServicio;
use App\Models\Venta;
use App\Services\PaymentService;
use App\Support\EmpresaResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PolizaPaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    // =========================================================================
    // PAYPAL
    // =========================================================================

    /**
     * Crear orden de PayPal para pagar póliza
     */
    public function createPayPalOrder(Request $request)
    {
        $validated = $request->validate([
            'poliza_id' => 'required|exists:polizas_servicio,id',
        ]);

        $poliza = PolizaServicio::with('cliente')->findOrFail($validated['poliza_id']);

        // Verificar que la póliza esté pendiente de pago
        if ($poliza->estado !== 'pendiente_pago') {
            return response()->json([
                'success' => false,
                'message' => 'Esta póliza ya fue pagada o no requiere pago.',
            ], 400);
        }

        // Calcular monto
        $amount = $poliza->calcularMontoActual();
        $description = "Póliza: {$poliza->nombre}";

        $result = $this->paymentService->createPayPalOrder($poliza, $amount, $description);

        if ($result) {
            return response()->json([
                'success' => true,
                'order_id' => $result['order_id'],
                'approve_url' => $result['approve_url'],
                'mode' => $result['mode'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Error al crear orden de pago en PayPal',
        ], 500);
    }

    /**
     * Capturar pago de PayPal
     */
    public function capturePayPalOrder(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|string',
            'poliza_id' => 'required|exists:polizas_servicio,id',
        ]);

        $poliza = PolizaServicio::findOrFail($validated['poliza_id']);

        $result = $this->paymentService->capturePayPalOrder($validated['order_id']);

        if ($result && $result['status'] === 'COMPLETED') {
            $this->paymentService->markPolizaAsPaid(
                $poliza,
                $result['id'],
                'paypal',
                $result
            );

            return response()->json([
                'success' => true,
                'message' => 'Pago completado exitosamente',
                'redirect' => route('contratacion.exito', ['slug' => $poliza->planPoliza?->slug ?? 'servicio']),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Error al capturar el pago de PayPal',
        ], 500);
    }

    /**
     * Webhook de PayPal
     */
    public function paypalWebhook(Request $request)
    {
        Log::info('PayPal Poliza Webhook received', $request->all());

        $eventType = $request->input('event_type');
        $resource = $request->input('resource');

        if ($eventType === 'CHECKOUT.ORDER.APPROVED' || $eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            $externalRef = $resource['purchase_units'][0]['reference_id'] ?? null;

            if ($externalRef && str_starts_with($externalRef, 'POLIZA-')) {
                $polizaId = (int) str_replace('POLIZA-', '', $externalRef);
                $poliza = PolizaServicio::find($polizaId);

                if ($poliza && $poliza->estado === 'pendiente_pago') {
                    $this->paymentService->markPolizaAsPaid(
                        $poliza,
                        $resource['id'] ?? 'webhook',
                        'paypal',
                        $resource
                    );
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    // =========================================================================
    // MERCADOPAGO
    // =========================================================================

    /**
     * Crear preferencia de MercadoPago
     */
    public function createMercadoPagoPreference(Request $request)
    {
        $validated = $request->validate([
            'poliza_id' => 'required|exists:polizas_servicio,id',
        ]);

        $poliza = PolizaServicio::with('cliente')->findOrFail($validated['poliza_id']);

        if ($poliza->estado !== 'pendiente_pago') {
            return response()->json([
                'success' => false,
                'message' => 'Esta póliza ya fue pagada o no requiere pago.',
            ], 400);
        }

        $amount = $poliza->calcularMontoActual();
        $description = "Póliza: {$poliza->nombre}";

        $result = $this->paymentService->createMercadoPagoPreference($poliza, $amount, $description);

        if ($result) {
            return response()->json([
                'success' => true,
                'preference_id' => $result['preference_id'],
                'init_point' => $result['init_point'],
                'sandbox_init_point' => $result['sandbox_init_point'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Error al crear preferencia de pago en MercadoPago',
        ], 500);
    }

    /**
     * Webhook de MercadoPago
     */
    public function mercadoPagoWebhook(Request $request)
    {
        Log::info('MercadoPago Poliza Webhook received', $request->all());

        $type = $request->input('type');
        $data = $request->input('data');

        if ($type === 'payment' && isset($data['id'])) {
            $payment = $this->paymentService->getMercadoPagoPayment($data['id']);

            if ($payment && $payment['status'] === 'approved') {
                $externalRef = $payment['external_reference'] ?? '';

                if (str_starts_with($externalRef, 'POLIZA-')) {
                    $polizaId = (int) str_replace('POLIZA-', '', $externalRef);
                    $poliza = PolizaServicio::find($polizaId);

                    if ($poliza && $poliza->estado === 'pendiente_pago') {
                        $this->paymentService->markPolizaAsPaid(
                            $poliza,
                            $payment['id'],
                            'mercadopago',
                            $payment
                        );
                    }
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    // =========================================================================
    // STRIPE (Tarjeta de Crédito)
    // =========================================================================

    /**
     * Crear Payment Intent de Stripe
     */
    public function createStripePaymentIntent(Request $request)
    {
        $validated = $request->validate([
            'poliza_id' => 'required|exists:polizas_servicio,id',
        ]);

        $poliza = PolizaServicio::with('cliente')->findOrFail($validated['poliza_id']);

        if ($poliza->estado !== 'pendiente_pago') {
            return response()->json([
                'success' => false,
                'message' => 'Esta póliza ya fue pagada o no requiere pago.',
            ], 400);
        }

        $amount = $poliza->calcularMontoActual();
        $description = "Póliza: {$poliza->nombre}";

        $result = $this->paymentService->createStripePaymentIntent($poliza, $amount, $description);

        if ($result) {
            return response()->json([
                'success' => true,
                'client_secret' => $result['client_secret'],
                'payment_intent_id' => $result['payment_intent_id'],
                'public_key' => config('payments.stripe.public_key'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Error al crear intención de pago en Stripe',
        ], 500);
    }

    /**
     * Crear Checkout Session de Stripe (alternativa más simple)
     */
    public function createStripeCheckoutSession(Request $request)
    {
        $validated = $request->validate([
            'poliza_id' => 'required|exists:polizas_servicio,id',
        ]);

        $poliza = PolizaServicio::with('cliente')->findOrFail($validated['poliza_id']);

        if ($poliza->estado !== 'pendiente_pago') {
            return response()->json([
                'success' => false,
                'message' => 'Esta póliza ya fue pagada o no requiere pago.',
            ], 400);
        }

        $amount = $poliza->calcularMontoActual();
        $description = "Póliza: {$poliza->nombre}";

        $result = $this->paymentService->createStripeCheckoutSession($poliza, $amount, $description);

        if ($result) {
            return response()->json([
                'success' => true,
                'session_id' => $result['session_id'],
                'checkout_url' => $result['checkout_url'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Error al crear sesión de checkout en Stripe',
        ], 500);
    }

    /**
     * Confirmar pago de Stripe
     */
    public function confirmStripePayment(Request $request)
    {
        $validated = $request->validate([
            'payment_intent_id' => 'required|string',
            'poliza_id' => 'required|exists:polizas_servicio,id',
        ]);

        $poliza = PolizaServicio::findOrFail($validated['poliza_id']);

        $result = $this->paymentService->confirmStripePayment($validated['payment_intent_id']);

        if ($result && $result['status'] === 'succeeded') {
            $this->paymentService->markPolizaAsPaid(
                $poliza,
                $result['id'],
                'tarjeta',
                $result
            );

            return response()->json([
                'success' => true,
                'message' => 'Pago con tarjeta completado exitosamente',
                'redirect' => route('contratacion.exito', ['slug' => $poliza->planPoliza?->slug ?? 'servicio']),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'El pago no fue completado',
            'status' => $result['status'] ?? 'unknown',
        ], 400);
    }

    /**
     * Webhook de Stripe
     */
    public function stripeWebhook(Request $request)
    {
        Log::info('Stripe Poliza Webhook received', $request->all());

        $payload = $request->getContent();
        $event = json_decode($payload, true);

        if ($event['type'] === 'checkout.session.completed' || $event['type'] === 'payment_intent.succeeded') {
            $session = $event['data']['object'];
            $polizaId = $session['metadata']['poliza_id'] ?? null;

            if ($polizaId) {
                $poliza = PolizaServicio::find($polizaId);

                if ($poliza && $poliza->estado === 'pendiente_pago') {
                    $this->paymentService->markPolizaAsPaid(
                        $poliza,
                        $session['id'] ?? $session['payment_intent'] ?? 'webhook',
                        'tarjeta',
                        $session
                    );
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    // =========================================================================
    // PÁGINAS DE RESULTADO
    // =========================================================================

    /**
     * Página de éxito después del pago
     */
    public function success(Request $request)
    {
        $polizaId = $request->query('poliza_id');
        $sessionId = $request->query('session_id'); // Para Stripe
        $paymentId = $request->query('payment_id'); // Para MercadoPago
        $pending = $request->query('pending');

        $poliza = null;
        if ($polizaId) {
            $poliza = PolizaServicio::with(['cliente', 'planPoliza'])->find($polizaId);
        }

        // Si viene de Stripe, verificar la sesión
        if ($sessionId && $poliza) {
            $session = $this->paymentService->getStripeCheckoutSession($sessionId);
            if ($session && $session['payment_status'] === 'paid') {
                $this->paymentService->markPolizaAsPaid(
                    $poliza,
                    $session['payment_intent'] ?? $sessionId,
                    'tarjeta',
                    $session
                );
            }
        }

        // Si viene de MercadoPago con ID de pago
        if ($paymentId && $poliza) {
            $payment = $this->paymentService->getMercadoPagoPayment($paymentId);
            if ($payment && $payment['status'] === 'approved') {
                $this->paymentService->markPolizaAsPaid(
                    $poliza,
                    $paymentId,
                    'mercadopago',
                    $payment
                );
            }
        }

        $empresaId = EmpresaResolver::resolveId();
        $empresa = \App\Models\Empresa::find($empresaId);

        $venta = Venta::whereHas('items', function ($q) use ($poliza) {
            $q->where('ventable_type', PolizaServicio::class)
                ->where('ventable_id', $poliza->id);
        })->first();

        return Inertia::render('Contratacion/Exito', [
            'empresa' => $empresa,
            'poliza' => $poliza,
            'venta' => $venta,
            'pending' => (bool) $pending,
            'message' => $pending
                ? 'Tu pago está siendo procesado. Te notificaremos cuando se confirme.'
                : '¡Pago completado exitosamente!',
        ]);
    }

    /**
     * Página de cancelación
     */
    public function cancel(Request $request)
    {
        $polizaId = $request->query('poliza_id');
        $poliza = null;

        if ($polizaId) {
            $poliza = PolizaServicio::with('planPoliza')->find($polizaId);
        }

        return Inertia::render('Contratacion/Cancelado', [
            'poliza' => $poliza,
            'message' => 'El pago fue cancelado. Puedes intentar de nuevo cuando quieras.',
        ]);
    }

    // =========================================================================
    // UTILIDADES
    // =========================================================================

    /**
     * Obtener pasarelas de pago disponibles
     */
    public function getAvailableGateways()
    {
        return response()->json([
            'gateways' => $this->paymentService->getAvailableGateways(),
            'currency' => config('payments.currency', 'MXN'),
        ]);
    }
}

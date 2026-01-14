<?php

namespace App\Services;

use App\Models\PolizaServicio;
use App\Models\Venta;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PaymentService
{
    /**
     * APIs en modo sandbox/test para desarrollo
     */
    protected array $config;

    public function __construct()
    {
        $this->config = config('payments');
        $this->loadDynamicConfig();
    }

    /**
     * Cargar credenciales desde la base de datos si existen
     */
    protected function loadDynamicConfig()
    {
        try {
            $dbConfig = \App\Models\EmpresaConfiguracion::getConfig();

            // PayPal
            if ($dbConfig->paypal_client_id) {
                $this->config['paypal']['client_id'] = $dbConfig->paypal_client_id;
                // Si hay secret en DB, usarlo. Si no, mantener el de env (por seguridad hidden)
                // Nota: Los secrets están ocultos en $hidden del modelo, acceder directo al atributo si se necesita
                if ($dbConfig->paypal_client_secret) {
                    $this->config['paypal']['client_secret'] = $dbConfig->paypal_client_secret;
                }
                $this->config['paypal']['mode'] = $dbConfig->paypal_sandbox ? 'sandbox' : 'live';
            }

            // MercadoPago
            if ($dbConfig->mercadopago_access_token) {
                $this->config['mercadopago']['access_token'] = $dbConfig->mercadopago_access_token;
                if ($dbConfig->mercadopago_public_key) {
                    $this->config['mercadopago']['public_key'] = $dbConfig->mercadopago_public_key;
                }
                $this->config['mercadopago']['sandbox'] = (bool) $dbConfig->mercadopago_sandbox;
            }

            // Stripe
            if ($dbConfig->stripe_secret_key) {
                $this->config['stripe']['secret_key'] = $dbConfig->stripe_secret_key;
                if ($dbConfig->stripe_public_key) {
                    $this->config['stripe']['public_key'] = $dbConfig->stripe_public_key;
                }
                if ($dbConfig->stripe_webhook_secret) {
                    $this->config['stripe']['webhook_secret'] = $dbConfig->stripe_webhook_secret;
                }
                // Stripe sandbox no cambia URLs, solo las keys (sk_test_ vs sk_live_)
            }

            // Habilitar/Deshabilitar según config general
            $this->config['enabled_methods']['paypal'] = (bool) $this->config['paypal']['client_id'];
            $this->config['enabled_methods']['mercadopago'] = (bool) $this->config['mercadopago']['access_token'];
            $this->config['enabled_methods']['stripe'] = (bool) $this->config['stripe']['secret_key'];

        } catch (\Exception $e) {
            Log::warning('No se pudo cargar config de pagos dinámica: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // PAYPAL API
    // =========================================================================

    /**
     * Obtener Access Token de PayPal
     */
    public function getPayPalAccessToken(): ?string
    {
        $config = $this->config['paypal'];
        $mode = $config['mode'];
        $baseUrl = $config[$mode]['api_url'];

        try {
            $response = Http::withBasicAuth($config['client_id'], $config['client_secret'])
                ->asForm()
                ->post("$baseUrl/v1/oauth2/token", [
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->successful()) {
                return $response->json()['access_token'];
            }

            Log::error('PayPal token error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('PayPal token exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear orden de PayPal para póliza
     */
    public function createPayPalOrder(PolizaServicio $poliza, float $amount, string $description): ?array
    {
        $accessToken = $this->getPayPalAccessToken();
        if (!$accessToken) {
            return null;
        }

        $config = $this->config['paypal'];
        $mode = $config['mode'];
        $baseUrl = $config[$mode]['api_url'];
        $currency = $this->config['currency'] ?? 'MXN';

        try {
            $response = Http::withToken($accessToken)
                ->post("$baseUrl/v2/checkout/orders", [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [
                        [
                            'reference_id' => 'POLIZA-' . $poliza->id,
                            'description' => $description,
                            'amount' => [
                                'currency_code' => $currency,
                                'value' => number_format($amount, 2, '.', ''),
                            ],
                        ],
                    ],
                    'application_context' => [
                        'return_url' => url($this->config['success_url']) . '?poliza_id=' . $poliza->id,
                        'cancel_url' => url($this->config['cancel_url']) . '?poliza_id=' . $poliza->id,
                        'brand_name' => config('app.name'),
                        'shipping_preference' => 'NO_SHIPPING',
                        'user_action' => 'PAY_NOW',
                    ],
                ]);

            if ($response->successful()) {
                $order = $response->json();
                $approveUrl = collect($order['links'])->firstWhere('rel', 'approve')['href'] ?? null;

                return [
                    'order_id' => $order['id'],
                    'status' => $order['status'],
                    'approve_url' => $approveUrl,
                    'mode' => $mode,
                ];
            }

            Log::error('PayPal create order error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('PayPal create order exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Capturar pago de PayPal
     */
    public function capturePayPalOrder(string $orderId): ?array
    {
        $accessToken = $this->getPayPalAccessToken();
        if (!$accessToken) {
            return null;
        }

        $config = $this->config['paypal'];
        $mode = $config['mode'];
        $baseUrl = $config[$mode]['api_url'];

        try {
            $response = Http::withToken($accessToken)
                ->withBody('', 'application/json')
                ->post("$baseUrl/v2/checkout/orders/{$orderId}/capture");

            if ($response->successful()) {
                $capture = $response->json();
                return [
                    'id' => $capture['id'],
                    'status' => $capture['status'],
                    'payer' => $capture['payer'] ?? null,
                    'purchase_units' => $capture['purchase_units'] ?? [],
                ];
            }

            Log::error('PayPal capture error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('PayPal capture exception: ' . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    // MERCADOPAGO API
    // =========================================================================

    /**
     * Crear preferencia de pago en MercadoPago
     */
    public function createMercadoPagoPreference(PolizaServicio $poliza, float $amount, string $description): ?array
    {
        $config = $this->config['mercadopago'];

        // Construir URLs de retorno
        $baseUrl = config('app.url');
        $successUrl = $baseUrl . '/pago/poliza/exito?poliza_id=' . $poliza->id;
        $pendingUrl = $baseUrl . '/pago/poliza/exito?poliza_id=' . $poliza->id . '&pending=1';
        $failureUrl = $baseUrl . '/pago/poliza/cancelado?poliza_id=' . $poliza->id;

        try {
            $preferenceData = [
                'items' => [
                    [
                        'id' => 'POLIZA-' . $poliza->id,
                        'title' => $description,
                        'quantity' => 1,
                        'unit_price' => $amount,
                        'currency_id' => $this->config['currency'] ?? 'MXN',
                    ],
                ],
                'payer' => [
                    'name' => $poliza->cliente->nombre_comercial ?? $poliza->cliente->razon_social ?? 'Cliente',
                    'email' => $poliza->cliente->email ?? 'cliente@temp.com',
                ],
                'back_urls' => [
                    'success' => $successUrl,
                    'pending' => $pendingUrl,
                    'failure' => $failureUrl,
                ],
                'external_reference' => 'POLIZA-' . $poliza->id,
                'statement_descriptor' => substr(config('app.name', 'Pago'), 0, 22),
            ];

            // Solo agregar auto_return si estamos en producción (no localhost)
            if (!str_contains($baseUrl, 'localhost') && !str_contains($baseUrl, '127.0.0.1')) {
                $preferenceData['auto_return'] = 'approved';
            }

            // Agregar webhook solo si hay ruta disponible
            try {
                $preferenceData['notification_url'] = route('pago.poliza.mercadopago.webhook');
            } catch (\Exception $e) {
                // Sin webhook
            }

            $response = Http::withToken($config['access_token'])
                ->post($config['api_url'] . '/checkout/preferences', $preferenceData);

            if ($response->successful()) {
                $preference = $response->json();
                $isSandbox = $this->config['mercadopago']['sandbox'] ?? true;

                return [
                    'preference_id' => $preference['id'],
                    'init_point' => $isSandbox ? $preference['sandbox_init_point'] : $preference['init_point'],
                    'sandbox_init_point' => $preference['sandbox_init_point'],
                ];
            }

            Log::error('MercadoPago preference error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('MercadoPago preference exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Verificar estado de pago en MercadoPago
     */
    public function getMercadoPagoPayment(string $paymentId): ?array
    {
        $config = $this->config['mercadopago'];

        try {
            $response = Http::withToken($config['access_token'])
                ->get($config['api_url'] . '/v1/payments/' . $paymentId);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('MercadoPago get payment exception: ' . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    // STRIPE API (Tarjeta de Crédito)
    // =========================================================================

    /**
     * Crear Payment Intent en Stripe
     */
    public function createStripePaymentIntent(PolizaServicio $poliza, float $amount, string $description): ?array
    {
        $config = $this->config['stripe'];

        try {
            // Stripe usa centavos para MXN
            $amountCents = (int) round($amount * 100);

            $response = Http::withBasicAuth($config['secret_key'], '')
                ->asForm()
                ->post($config['api_url'] . '/v1/payment_intents', [
                    'amount' => $amountCents,
                    'currency' => strtolower($this->config['currency'] ?? 'mxn'),
                    'description' => $description,
                    'metadata[poliza_id]' => $poliza->id,
                    'metadata[cliente_id]' => $poliza->cliente_id,
                    'automatic_payment_methods[enabled]' => 'true',
                ]);

            if ($response->successful()) {
                $intent = $response->json();
                return [
                    'client_secret' => $intent['client_secret'],
                    'payment_intent_id' => $intent['id'],
                    'status' => $intent['status'],
                ];
            }

            Log::error('Stripe payment intent error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Stripe payment intent exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear Checkout Session en Stripe (alternativa más simple)
     */
    public function createStripeCheckoutSession(PolizaServicio $poliza, float $amount, string $description): ?array
    {
        $config = $this->config['stripe'];

        try {
            $amountCents = (int) round($amount * 100);

            $response = Http::withBasicAuth($config['secret_key'], '')
                ->asForm()
                ->post($config['api_url'] . '/v1/checkout/sessions', [
                    'mode' => 'payment',
                    'success_url' => url($this->config['success_url']) . '?poliza_id=' . $poliza->id . '&session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => url($this->config['cancel_url']) . '?poliza_id=' . $poliza->id,
                    'line_items[0][price_data][currency]' => strtolower($this->config['currency'] ?? 'mxn'),
                    'line_items[0][price_data][product_data][name]' => $description,
                    'line_items[0][price_data][unit_amount]' => $amountCents,
                    'line_items[0][quantity]' => 1,
                    'metadata[poliza_id]' => $poliza->id,
                    'customer_email' => $poliza->cliente->email,
                ]);

            if ($response->successful()) {
                $session = $response->json();
                return [
                    'session_id' => $session['id'],
                    'checkout_url' => $session['url'],
                    'status' => $session['status'],
                ];
            }

            Log::error('Stripe checkout session error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Stripe checkout session exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Confirmar Payment Intent de Stripe
     */
    public function confirmStripePayment(string $paymentIntentId): ?array
    {
        $config = $this->config['stripe'];

        try {
            $response = Http::withBasicAuth($config['secret_key'], '')
                ->get($config['api_url'] . '/v1/payment_intents/' . $paymentIntentId);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Stripe confirm payment exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Verificar sesión de checkout de Stripe
     */
    public function getStripeCheckoutSession(string $sessionId): ?array
    {
        $config = $this->config['stripe'];

        try {
            $response = Http::withBasicAuth($config['secret_key'], '')
                ->get($config['api_url'] . '/v1/checkout/sessions/' . $sessionId);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Stripe get session exception: ' . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Marcar póliza como pagada
     */
    public function markPolizaAsPaid(PolizaServicio $poliza, string $transactionId, string $method, array $metadata = []): void
    {
        $poliza->update([
            'estado' => 'activa',
        ]);

        // Buscar y actualizar la venta asociada
        $venta = Venta::whereHas('items', function ($q) use ($poliza) {
            $q->where('ventable_type', PolizaServicio::class)
                ->where('ventable_id', $poliza->id);
        })->first();

        if ($venta) {
            $venta->update([
                'estado' => 'pagado',
                'metodo_pago' => $method,
                'notas' => $venta->notas . "\nPago recibido ID: {$transactionId}",
            ]);

            // Liquidar la Cuenta por Cobrar si existe
            if ($venta->cuentaPorCobrar) {
                try {
                    $venta->cuentaPorCobrar->registrarPago((float) $venta->total, "Pago recibido via {$method} - ID: {$transactionId}");
                } catch (\Exception $e) {
                    Log::error("Error liquidando CXC tras pago online: " . $e->getMessage());
                }
            }

            // AUTOMATIZACIÓN DE BANCOS:
            // Registrar movimiento en el banco automáticamente dependiendo del método
            try {
                $config = \App\Models\EmpresaConfiguracion::getConfig($poliza->empresa_id);
                $cuentaId = null;

                if ($method === 'paypal') {
                    $cuentaId = $config->cuenta_id_paypal;
                } elseif ($method === 'mercadopago') {
                    $cuentaId = $config->cuenta_id_mercadopago;
                } elseif ($method === 'tarjeta' || $method === 'stripe') {
                    $cuentaId = $config->cuenta_id_stripe;
                }

                if ($cuentaId) {
                    $cuenta = \App\Models\CuentaBancaria::find($cuentaId);
                    if ($cuenta) {
                        $cuenta->registrarMovimiento(
                            'deposito',
                            (float) $venta->total,
                            "Pago automático Web ({$method}): Folio Venta {$venta->folio} - Poliza {$poliza->id}",
                            'venta'
                        );
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error registrando movimiento bancario automático: " . $e->getMessage());
            }
        }

        Log::info("Póliza {$poliza->id} marcada como pagada via {$method}. Transaction: {$transactionId}");
    }

    /**
     * Registrar un pago manual para una Cuenta por Cobrar.
     * Unifica: CuentasPorCobrar + EntregaDinero + Movimiento Bancario.
     */
    public function registrarPago(
        \App\Models\CuentasPorCobrar $cuenta,
        float $monto,
        string $metodoPago,
        ?string $notas = null,
        ?int $userId = null,
        ?int $cuentaBancariaId = null,
        $fechaPago = null
    ): void {
        \Illuminate\Support\Facades\DB::transaction(function () use ($cuenta, $monto, $metodoPago, $notas, $userId, $cuentaBancariaId, $fechaPago) {
            // 1. Registrar el pago en la cuenta (actualiza montos y estado)
            $cuenta->registrarPago($monto, $notas);

            // 2. Crear Entrega de Dinero para auditoría y flujo de caja
            $fecha = $fechaPago ? ($fechaPago instanceof \Carbon\Carbon ? $fechaPago->format('Y-m-d') : $fechaPago) : now()->format('Y-m-d');

            // Determinar tipo de origen para la entrega
            $tipoOrigen = 'venta';
            if ($cuenta->cobrable_type && str_contains(strtolower($cuenta->cobrable_type), 'renta')) {
                $tipoOrigen = 'renta';
            }

            \App\Services\EntregaDineroService::crearAutoPorMetodo(
                $tipoOrigen,
                $cuenta->cobrable_id,
                $monto,
                $metodoPago,
                $fecha,
                $userId ?: \Illuminate\Support\Facades\Auth::id(),
                $notas,
                null, // Recibido por
                $cuentaBancariaId
            );
        });
    }

    /**
     * Registrar pago total de contado (usado al crear ventas no-crédito).
     */
    public function registrarPagoContado(
        Venta $venta,
        string $metodoPago,
        ?string $notas = null,
        ?int $cuentaBancariaId = null
    ): void {
        if (!$venta->cuentaPorCobrar) {
            // Si por alguna razón no tiene CxC (ej. error en transacción previa), crearla o ignorar
            Log::warning("Intento de registrarPagoContado en venta #{$venta->id} sin CuentaPorCobrar.");
            return;
        }

        $this->registrarPago(
            $venta->cuentaPorCobrar,
            (float) $venta->total,
            $metodoPago,
            $notas ?: "Pago de contado - Venta #{$venta->numero_venta}",
            $venta->vendedor_id ?: \Illuminate\Support\Facades\Auth::id(),
            $cuentaBancariaId
        );
    }

    /**
     * Verificar si las pasarelas están configuradas
     */
    public function getAvailableGateways(): array
    {
        return [
            'paypal' => !empty($this->config['paypal']['client_id']),
            'mercadopago' => !empty($this->config['mercadopago']['access_token']),
            'stripe' => !empty($this->config['stripe']['secret_key']),
        ];
    }
}

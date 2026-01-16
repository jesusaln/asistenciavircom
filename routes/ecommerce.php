<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PolizaPaymentController;

// =====================================================
// RUTAS TIENDA ONLINE Y PAGOS
// =====================================================

// Autenticación de clientes
Route::get('/tienda/login', [SocialAuthController::class, 'showLogin'])->name('tienda.login');
Route::get('/auth/google', [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);
Route::get('/auth/microsoft', [SocialAuthController::class, 'redirectToMicrosoft'])->name('auth.microsoft');
Route::get('/auth/microsoft/callback', [SocialAuthController::class, 'handleMicrosoftCallback']);
Route::post('/tienda/logout', [SocialAuthController::class, 'logout'])->name('tienda.logout');
Route::get('/tienda/mi-cuenta', [SocialAuthController::class, 'miCuenta'])->name('tienda.mi-cuenta');
Route::get('/api/tienda/cliente', [SocialAuthController::class, 'me'])->name('tienda.cliente.me');

// Carrito y Checkout de Tienda
Route::get('/carrito', [CheckoutController::class, 'carrito'])->name('tienda.carrito');
Route::post('/carrito/validar', [CheckoutController::class, 'validateCart'])->name('tienda.carrito.validar');
Route::get('/checkout', [CheckoutController::class, 'show'])->name('tienda.checkout');
Route::post('/checkout/procesar', [CheckoutController::class, 'procesar'])->name('tienda.checkout.procesar');
Route::get('/pedido/{numero}', [CheckoutController::class, 'pedido'])->name('tienda.pedido');

// Pasarela de Pagos (Tienda)
Route::post('/pago/mercadopago/crear', [PaymentController::class, 'createMercadoPago'])->name('pago.mercadopago.crear');
Route::post('/pago/mercadopago/webhook', [PaymentController::class, 'mercadoPagoWebhook'])->name('pago.mercadopago.webhook');
Route::get('/pago/mercadopago/exito', [PaymentController::class, 'mercadoPagoExito'])->name('pago.mercadopago.exito');
Route::get('/pago/mercadopago/pendiente', [PaymentController::class, 'mercadoPagoPendiente'])->name('pago.mercadopago.pendiente');
Route::get('/pago/mercadopago/error', [PaymentController::class, 'mercadoPagoError'])->name('pago.mercadopago.error');

Route::post('/pago/paypal/crear', [PaymentController::class, 'createPayPal'])->name('pago.paypal.crear');
Route::post('/pago/paypal/capturar', [PaymentController::class, 'capturePayPal'])->name('pago.paypal.capturar');
Route::post('/pago/paypal/webhook', [PaymentController::class, 'paypalWebhook'])->name('pago.paypal.webhook');

// Pasarela de Pagos (Pólizas)
Route::prefix('pago/poliza')->name('pago.poliza.')->group(function () {
    // PayPal
    Route::post('/paypal/crear', [PolizaPaymentController::class, 'createPayPalOrder'])->name('paypal.crear');
    Route::post('/paypal/capturar', [PolizaPaymentController::class, 'capturePayPalOrder'])->name('paypal.capturar');
    Route::post('/paypal/webhook', [PolizaPaymentController::class, 'paypalWebhook'])
        ->name('paypal.webhook')->withoutMiddleware([\App\Http\Middleware\CustomVerifyCsrfToken::class, \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

    // MercadoPago
    Route::post('/mercadopago/crear', [PolizaPaymentController::class, 'createMercadoPagoPreference'])->name('mercadopago.crear');
    Route::post('/mercadopago/webhook', [PolizaPaymentController::class, 'mercadoPagoWebhook'])
        ->name('mercadopago.webhook')->withoutMiddleware([\App\Http\Middleware\CustomVerifyCsrfToken::class, \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

    // Stripe
    Route::post('/stripe/intent', [PolizaPaymentController::class, 'createStripePaymentIntent'])->name('stripe.intent');
    Route::post('/stripe/checkout', [PolizaPaymentController::class, 'createStripeCheckoutSession'])->name('stripe.checkout');
    Route::post('/stripe/confirmar', [PolizaPaymentController::class, 'confirmStripePayment'])->name('stripe.confirmar');
    Route::post('/stripe/webhook', [PolizaPaymentController::class, 'stripeWebhook'])
        ->name('stripe.webhook')->withoutMiddleware([\App\Http\Middleware\CustomVerifyCsrfToken::class, \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

    Route::get('/pasarelas', [PolizaPaymentController::class, 'getAvailableGateways'])->name('pasarelas');

    // Crédito
    Route::post('/credito/pagar', [PolizaPaymentController::class, 'payWithCredit'])
        ->middleware('auth:client')
        ->name('credito.pagar');
});

// Resultados de Pago Pólizas
Route::get('/contratacion/exito', [PolizaPaymentController::class, 'success'])->name('pago.poliza.exito');
Route::get('/contratacion/cancelado', [PolizaPaymentController::class, 'cancel'])->name('pago.poliza.cancelado');

// Integración Grupo CVA (Proxy)
Route::get('/api/tienda/cva/catalogo', [App\Http\Controllers\Tienda\CVAProxyController::class, 'index'])->name('tienda.cva.catalogo');
Route::get('/api/tienda/cva/sugerencias', [App\Http\Controllers\Tienda\CVAProxyController::class, 'suggestions'])->name('tienda.cva.sugerencias');
Route::post('/api/tienda/cva/shipping', [App\Http\Controllers\Tienda\CVAProxyController::class, 'calculateShipping'])->name('tienda.cva.shipping');
Route::get('/api/tienda/cva/producto/{clave}', [App\Http\Controllers\Tienda\CVAProxyController::class, 'show'])->name('tienda.cva.producto');
Route::post('/api/tienda/cva/importar', [App\Http\Controllers\Tienda\CVAProxyController::class, 'import'])->name('tienda.cva.importar');
Route::post('/api/tienda/cva/sync-local', [App\Http\Controllers\Tienda\CVAProxyController::class, 'syncToLocal'])->name('tienda.cva.sync-local');
Route::post('/api/tienda/cva/sync-categorias', [App\Http\Controllers\Tienda\CVAProxyController::class, 'syncCategories'])->name('tienda.cva.sync-categorias');
Route::post('/api/tienda/cva/sync-marcas', [App\Http\Controllers\Tienda\CVAProxyController::class, 'syncBrands'])->name('tienda.cva.sync-marcas');

// Ruta de PRUEBA para Pagos (Solo dev)
Route::get('/test/pagos', function () {
    if (app()->environment('production'))
        abort(404);
    $poliza = \App\Models\PolizaServicio::with('cliente')->where('estado', 'pendiente_pago')->latest()->first();
    return \Inertia\Inertia::render('Contratacion/TestPagos', ['poliza' => $poliza, 'gateways' => config('payments')]);
})->name('pago.test');

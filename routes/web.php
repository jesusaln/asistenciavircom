<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Rutas de broadcasting para Presence Channels.
Broadcast::routes([
    'middleware' => [
        'auth:sanctum',
        config('jetstream.auth_session'),
        'verified',
    ],
]);

Route::get('/img-proxy', [App\Http\Controllers\ImageProxyController::class, 'proxy'])->name('img.proxy');

// Rutas Públicas y Utilidades
require __DIR__ . '/public.php';

// E-commerce y Pagos
require __DIR__ . '/ecommerce.php';

// Portal de Clientes
require __DIR__ . '/portal.php';

// Panel de Administración (Protegido)
require __DIR__ . '/admin.php';

// Newsletter Tracking
Route::get('/nt/o/{token}', [App\Http\Controllers\Api\NewsletterTrackingController::class, 'open'])->name('newsletter.track.open');
Route::get('/nt/c/{token}', [App\Http\Controllers\Api\NewsletterTrackingController::class, 'click'])->name('newsletter.track.click');

// iCal Feed
Route::get('/calendar/feed', [App\Http\Controllers\CalendarFeedController::class, 'index'])->name('calendar.feed');


// Promo Seguridad Enero
Route::get('/promo', [App\Http\Controllers\PromoController::class, 'index'])->name('promo');
Route::post('/promo/lead', [App\Http\Controllers\PromoController::class, 'storeLead'])->name('promo.lead');
Route::post('/chat/message', [App\Http\Controllers\ChatController::class, 'message'])->name('chat.message');

Route::get('/crear-poliza-test', function () {
    $cliente = App\Models\Cliente::firstOrCreate(
        ['email' => 'test@asistenciavircom.com'],
        ['nombre_comercial' => 'Cliente Pruebas', 'razon_social' => 'Test SA DE CV', 'rfc' => 'XAXX010101000']
    );

    $poliza = App\Models\PolizaServicio::create([
        'cliente_id' => $cliente->id,
        'empresa_id' => 1,
        'nombre' => 'Póliza Test Producción $20',
        'monto_mensual' => 20,
        'monto_anual' => 240,
        'frecuencia_pago' => 'mensual',
        'fecha_inicio' => now(),
        'fecha_fin' => now()->addYear(),
        'dia_pago' => now()->day,
        'estado' => 'pendiente_pago'
    ]);

    return "Póliza creada: ID {$poliza->id} - Monto: \${$poliza->monto_mensual}";
});

// --- LANDINGS SEO DINÁMICAS ( Catch-all root ) ---
// IMPORTANTE: Se coloca al final de TODO para no interferir con el admin o portal
Route::get('/{slug}', [\App\Http\Controllers\PublicSeoLandingController::class, 'show'])->name('public.seo.landing');

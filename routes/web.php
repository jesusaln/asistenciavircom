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


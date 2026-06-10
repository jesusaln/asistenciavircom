<?php

use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Rutas de broadcasting para Presence Channels.
// Deben usar el mismo stack de autenticacion que el panel Inertia/Jetstream.
Broadcast::routes([
    'middleware' => [
        'auth:sanctum',
        config('jetstream.auth_session'),
        'verified',
        \App\Http\Middleware\EnsureTwoFactorEnabled::class,
    ],
]);

Route::get('/img-proxy', [App\Http\Controllers\ImageProxyController::class, 'proxy'])->name('img.proxy');

// Comprobante de préstamo: PDF con URL firmada (compartir por WhatsApp sin sesión).
// Firma relativa: valida path + query sin depender del host/esquema (evita 403 si APP_URL ≠ URL pública).
Route::get('/comprobante-prestamo/{historial}', [\App\Http\Controllers\PagoPrestamoController::class, 'descargarComprobantePdf'])
    ->name('pagos.comprobante.descarga')
    ->middleware(ValidateSignature::relative())
    ->whereNumber('historial');

// Comprobante de venta público para compartir por WhatsApp
Route::get('/share/venta/{token}/pdf', [\App\Http\Controllers\VentaDocumentoController::class, 'generarPDFPublico'])
    ->name('ventas.public.pdf');

Route::get('/share/venta/{token}/pdf-base64', [\App\Http\Controllers\VentaDocumentoController::class, 'generarPDFPublicoBase64'])
    ->name('ventas.public.pdf-base64');

Route::get('/descargar-apk', function () {
    $path = storage_path('app/public/climas-debug.apk');
    if (!file_exists($path)) {
        abort(404, 'APK no encontrado en el servidor.');
    }
    return response()->download($path, 'climas-debug.apk', [
        'Content-Type' => 'application/vnd.android.package-archive',
    ]);
});

// Rutas Públicas y Utilidades
require __DIR__.'/public.php';

// E-commerce y Pagos
require __DIR__.'/ecommerce.php';

// Portal de Clientes
require __DIR__.'/portal.php';

// Panel de Administración (Protegido)
require __DIR__.'/admin.php';

// 🚀 Puente de Datos IA (Protegido por Token Secreto)
Route::post('/trading/bulk-save-experience', [\App\Http\Controllers\TradingController::class, 'bulkSaveExperience'])->name('trading.bulk-save-experience');
Route::get('/trading/get-weights', [\App\Http\Controllers\TradingController::class, 'getWeights'])->name('trading.get-weights');
Route::get('/trading/get-history', [\App\Http\Controllers\TradingController::class, 'getHistory'])->name('trading.get-history');
Route::post('/trading/sync', [\App\Http\Controllers\TradingController::class, 'sync'])->name('trading.sync');
Route::get('/trading/poll-orders', [\App\Http\Controllers\TradingController::class, 'pollOrders'])->name('trading.poll-orders');
Route::post('/trading/update-order/{id}', [\App\Http\Controllers\TradingController::class, 'updateOrder'])->name('trading.update-order');
Route::post('/trading/update-balance', [\App\Http\Controllers\TradingController::class, 'updateBalance'])->name('trading.update-balance');
Route::get('/admin/binance-balance', [\App\Http\Controllers\TradingController::class, 'getBinanceBalance']);
Route::get('/admin/trading/binance-balance', [\App\Http\Controllers\TradingController::class, 'getBinanceBalance']);

// Switcher de Empresas (Local)
Route::post('/switch-company', [\App\Http\Controllers\EmpresaSwitcherController::class, 'switch'])->name('company.switch');

// Verificación de Seguridad
Route::middleware(['auth'])->group(function () {
    Route::get('/verificar-codigo', [\App\Http\Controllers\LoginCodeController::class, 'index'])->name('verify.index');
    Route::post('/verificar-codigo', [\App\Http\Controllers\LoginCodeController::class, 'store'])->name('verify.store');
    Route::post('/verificar-codigo/reenviar', [\App\Http\Controllers\LoginCodeController::class, 'resend'])->name('verify.resend');
});

// --- NOM-035 PUBLIC QUESTIONNAIRE ROUTES ---
Route::prefix('nom035')->group(function () {
    Route::get('/cuestionario', [\App\Http\Controllers\Nom035QuestionnaireController::class, 'index'])->name('nom035.questionnaire.index');
    Route::post('/cuestionario/start', [\App\Http\Controllers\Nom035QuestionnaireController::class, 'start'])->name('nom035.questionnaire.start');
    Route::get('/cuestionario/{uuid}/{guide?}', [\App\Http\Controllers\Nom035QuestionnaireController::class, 'show'])->name('nom035.questionnaire.show');
    Route::post('/cuestionario/{uuid}/submit', [\App\Http\Controllers\Nom035QuestionnaireController::class, 'submit'])->name('nom035.questionnaire.submit');
    Route::post('/cuestionario/{uuid}/signature', [\App\Http\Controllers\Nom035QuestionnaireController::class, 'saveSignature'])->name('nom035.questionnaire.signature');
    Route::get('/cuestionario/{uuid}/resultados', [\App\Http\Controllers\Nom035QuestionnaireController::class, 'results'])->name('nom035.questionnaire.results');
    Route::get('/verify/{uuid}', [\App\Http\Controllers\Nom035Controller::class, 'verify'])->name('nom035.verify');
    Route::get('/queja/acuse/{folio}', [\App\Http\Controllers\Api\Nom035ComplaintController::class, 'downloadReceipt'])->name('nom035.complaint.receipt');
    Route::get('/denuncia', [\App\Http\Controllers\Nom035ComplaintController::class, 'showPublicForm'])->name('nom035.denuncia.form');
    Route::post('/denuncia', [\App\Http\Controllers\Nom035ComplaintController::class, 'submitPublicComplaint'])->name('nom035.denuncia.submit');
});
// -------------------------------------------



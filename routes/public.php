<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\Public\ContactoController;
use App\Http\Controllers\PlanPolizaController;
use App\Http\Controllers\ContratacionPolizaController;
use App\Http\Controllers\VentaDocumentoController;
use App\Http\Controllers\CotizacionDocumentoController;
use App\Http\Controllers\PedidoDocumentoController;
use App\Http\Controllers\CitaPublicaController;
use App\Http\Controllers\LandingContentController;
use Inertia\Inertia;

// =====================================================
// RUTAS PÚBLICAS Y UTILIDADES
// =====================================================

// Health Check
Route::get('/health', HealthController::class);

// Versión de la APP
Route::get('/api/app-version', function () {
    return response()->json(['version' => \App\Support\VersionHelper::getVersion()]);
});

// GeoIP
Route::get('/api/geoip', function (Request $request) {
    try {
        $ip = $request->ip();
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return response()->json(['city' => 'Hermosillo', 'regionName' => 'Sonora']);
        }
        return \Illuminate\Support\Facades\Cache::remember("geoip_{$ip}", 3600, function () use ($ip) {
            $response = \Illuminate\Support\Facades\Http::get("http://ip-api.com/json/{$ip}?fields=city,regionName");
            return $response->json();
        });
    } catch (\Exception $e) {
        return response()->json([], 200);
    }
})->name('public.geoip');

// Landing y Páginas Estáticas
Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/privacidad', [LandingController::class, 'privacidad'])->name('public.privacidad');
Route::get('/terminos', [LandingController::class, 'terminos'])->name('public.terminos');
Route::get('/asesor-climatizacion', [LandingController::class, 'asesor'])->name('public.asesor');
Route::post('/asesor-lead', [LandingController::class, 'storeLead'])->name('public.asesor.store');
Route::get('/asesor-pdf', [LandingController::class, 'downloadReport'])->name('public.asesor.pdf');

// Contacto y Citas Públicas (Rápido)
Route::get('/contacto', [ContactoController::class, 'index'])->name('public.contacto');
Route::post('/contacto', [ContactoController::class, 'store'])->name('public.contacto.store');
Route::post('/cita', [ContactoController::class, 'storeCita'])->name('public.cita.store');

// Tienda (Público)
Route::get('/tienda', [CatalogoController::class, 'index'])->name('catalogo.index');
Route::get('/producto/{id}', [CatalogoController::class, 'show'])->name('catalogo.show');

// Pólizas (Público)
Route::get('/polizas', [PlanPolizaController::class, 'catalogo'])->name('catalogo.polizas');
Route::get('/polizas/plan/{slug}', [PlanPolizaController::class, 'detallePlan'])->name('catalogo.poliza.show');

// Checkout de Pólizas (Público inicia aquí)
Route::get('/contratar/{slug}', [ContratacionPolizaController::class, 'show'])->name('contratacion.show');
Route::post('/contratar/{slug}', [ContratacionPolizaController::class, 'procesar'])->name('contratacion.procesar');
Route::get('/contratar/{slug}/exito', [ContratacionPolizaController::class, 'exito'])->name('contratacion.exito');

// PDFs Públicos
Route::get('/share/venta/{id}/pdf', [VentaDocumentoController::class, 'generarPDF'])->name('ventas.pdf.public');
Route::get('/share/cotizacion/{id}/pdf', [CotizacionDocumentoController::class, 'generarPDF'])->name('cotizaciones.pdf.public');
Route::get('/share/pedido/{id}/pdf', [PedidoDocumentoController::class, 'generarPDF'])->name('pedidos.pdf.public');

// Agendamiento Público Detallado
Route::prefix('agendar')->name('agendar.')->group(function () {
    Route::get('/', [CitaPublicaController::class, 'index'])->name('index');
    Route::post('/', [CitaPublicaController::class, 'store'])->name('store');
    Route::get('/disponibilidad', [CitaPublicaController::class, 'disponibilidad'])->name('disponibilidad');
    Route::get('/horarios', [CitaPublicaController::class, 'horariosDisponibles'])->name('horarios');
    Route::get('/seguimiento/{uuid}', [CitaPublicaController::class, 'seguimiento'])->name('seguimiento');
});
Route::get('/mi-cita/{uuid}', [CitaPublicaController::class, 'seguimiento'])->name('mi-cita');

// Placeholder SVG
Route::get('/placeholder/{w}x{h}/{bg?}/{fg?}', function (int $w, int $h, $bg = 'e5e7eb', $fg = '6b7280') {
    $text = \Illuminate\Support\Str::of(request('text', 'Sin imagen'))->limit(40);
    $fontSize = max(12, min($w / 12, 24));
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$w}" height="{$h}" viewBox="0 0 {$w} {$h}">
  <rect width="100%" height="100%" fill="#{$bg}"/>
  <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
        font-family="system-ui, -apple-system, sans-serif" font-size="{$fontSize}" fill="#{$fg}"
        font-weight="500">
    {$text}
  </text>
</svg>
SVG;
    return response($svg, 200, ['Content-Type' => 'image/svg+xml', 'Cache-Control' => 'public, max-age=3600']);
})->name('placeholder');

// Debug y Pruebas (Solo desarrollo o utilidades)
Route::middleware(['web'])->group(function () {
    Route::get('/test-utf8', function () {
        $invalidUtf8 = "Valid text " . "\x80\x81\x82" . " more text";
        return response()->json([
            'original' => $invalidUtf8,
            'cleaned' => \App\Helpers\Utf8Helper::cleanString($invalidUtf8),
            'utf8_info' => \App\Helpers\Utf8Helper::getUtf8Info($invalidUtf8),
        ]);
    });

    Route::get('/debug-urls', function () {
        return response()->json([
            'app_url' => config('app.url'),
            'current_host' => request()->getHost(),
            'storage_url_example' => \App\Helpers\UrlHelper::storageUrl('profile-photos/test.png'),
            'is_misconfigured' => \App\Helpers\UrlHelper::isAppUrlMisconfigured(),
        ]);
    })->name('debug.urls');
});

// Rutas de Imágenes
Route::get('/test-images', function () {
    $profilePhotos = Storage::disk('public')->files('profile-photos');
    $images = [];
    foreach ($profilePhotos as $photo) {
        $images[] = [
            'filename' => basename($photo),
            'url' => asset('storage/' . $photo),
            'size' => Storage::disk('public')->size($photo),
            'exists' => Storage::disk('public')->exists($photo),
        ];
    }
    return response()->json(['images' => $images]);
})->name('test.images.json');

Route::get('/profile-photo/{filename}', [App\Http\Controllers\ImageController::class, 'serveProfilePhoto'])->name('serve-profile-photo');
Route::get('/api/profile-photos', [App\Http\Controllers\ImageController::class, 'listProfilePhotos'])->name('list-profile-photos');

Route::get('/img/profile-photos/{filename}', function ($filename) {
    $path = 'profile-photos/' . $filename;
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath))
        return response('Imagen no encontrada', 404);
    $mimeType = mime_content_type($fullPath) ?: 'image/png';
    return response()->file($fullPath, ['Content-Type' => $mimeType]);
})->name('image.profile-photo');

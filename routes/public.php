<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\Public\ContactoController;
use App\Http\Controllers\Public\SoportePublicoController; // Import Controller
use App\Http\Controllers\Public\FacebookCatalogController;
use App\Http\Controllers\Public\SitemapController;

Route::get('/soporte-tecnico', [SoportePublicoController::class, 'index'])->name('public.soporte'); // Nueva Ruta
Route::get('/facebook-catalog', [FacebookCatalogController::class, 'index'])->name('public.facebook-catalog');
Route::get('/feed/facebook-products.xml', \App\Http\Controllers\FacebookProductFeedController::class)->name('public.facebook-feed');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('public.sitemap');


// Tienda (Público)
use App\Http\Controllers\PlanPolizaController;
use App\Http\Controllers\PlanRentaController;
use App\Http\Controllers\ContratacionRentaController;
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

// GeoIP (Público pero con throttle para evitar abuso)
Route::get('/api/geoip', function (Request $request) {
    try {
        $ip = $request->ip();
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return response()->json(['city' => 'Hermosillo', 'regionName' => 'Sonora']);
        }
        return \Illuminate\Support\Facades\Cache::remember("geoip_{$ip}", 3600, function () use ($ip) {
            $response = \Illuminate\Support\Facades\Http::timeout(2)->connectTimeout(1)->get("http://ip-api.com/json/{$ip}?fields=city,regionName");
            return $response->json();
        });
    } catch (\Exception $e) {
        return response()->json([], 200);
    }
})->name('public.geoip')->middleware(['throttle:30,1']);

// Landing y Páginas Estáticas
Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/privacidad', [LandingController::class, 'privacidad'])->name('public.privacidad');
Route::get('/terminos', [LandingController::class, 'terminos'])->name('public.terminos');
Route::get('/eliminacion-datos', [LandingController::class, 'eliminacion'])->name('public.eliminacion');
Route::get('/asesor-climatizacion', [LandingController::class, 'asesor'])->name('public.asesor');
Route::post('/asesor-lead', [LandingController::class, 'storeLead'])->name('public.asesor.store')->middleware(['throttle:5,1']);
Route::get('/asesor-pdf', [LandingController::class, 'downloadReport'])->name('public.asesor.pdf');
Route::get('/propuesta-sgs', [LandingController::class, 'propuestaSgs'])->name('public.propuesta-sgs');
Route::get('/life-12-plus', [LandingController::class, 'life12plus'])->name('public.life12plus');
Route::get('/magnum-22-inverter', [LandingController::class, 'magnum22'])->name('public.magnum22');

// Servicios (Público)
Route::get('/reparacion-minisplit', [LandingController::class, 'reparacion'])->name('public.reparacion');
Route::get('/mantenimiento-preventivo', [LandingController::class, 'mantenimiento'])->name('public.mantenimiento');
Route::get('/instalacion-minisplit', [LandingController::class, 'instalacion'])->name('public.instalacion');
Route::get('/instalacion-gratis-mirage', [LandingController::class, 'instalacionMirage'])->name('public.instalacion-mirage');
Route::get('/instalacion-1500', [LandingController::class, 'instalacion1500'])->name('public.instalacion-con-costo');
Route::get('/recarga-gas', [LandingController::class, 'gas'])->name('public.gas');

// Contacto y Citas Públicas (Rápido)
Route::get('/contacto', [ContactoController::class, 'index'])->name('public.contacto');
Route::post('/contacto', [ContactoController::class, 'store'])->name('public.contacto.store')->middleware(['throttle:5,1']);
Route::post('/cita', [ContactoController::class, 'storeCita'])->name('public.cita.store')->middleware(['throttle:5,1']);
Route::get('/agendar-cita', [ContactoController::class, 'agendaRapida'])->name('public.agenda-rapida');
Route::post('/agendar-cita', [ContactoController::class, 'storeAgendaRapida'])->name('public.agenda-rapida.store')->middleware(['throttle:5,1']);
Route::get('/facturar', [\App\Http\Controllers\Public\FacturacionPublicaController::class, 'index'])->name('public.facturar');
Route::post('/facturar', [\App\Http\Controllers\Public\FacturacionPublicaController::class, 'store'])->name('public.facturar.store')->middleware(['throttle:5,1']);

// Tienda (Público)
Route::get('/tienda', [CatalogoController::class, 'index'])->name('catalogo.index');
Route::get('/producto/{id}', [CatalogoController::class, 'show'])->name('catalogo.show');
Route::get('/api/tienda/search-suggestions', [CatalogoController::class, 'searchSuggestions'])->name('api.tienda.search-suggestions');
Route::get('/api/tienda/categorias-nav', [CatalogoController::class, 'categoriasParaNav'])->name('api.tienda.categorias-nav');

// Blog (Público)
Route::get('/blog', [\App\Http\Controllers\BlogController::class, 'index'])->name('public.blog.index');
Route::get('/blog/{slug}', [\App\Http\Controllers\BlogController::class, 'show'])->name('public.blog.show');

// Pólizas (Público)
Route::get('/polizas', [PlanPolizaController::class, 'catalogo'])->name('catalogo.polizas');
Route::get('/polizas/plan/{slug}', [PlanPolizaController::class, 'detallePlan'])->name('catalogo.poliza.show');

// Rentas (Público)
Route::get('/rentas-equipos', [PlanRentaController::class, 'catalogo'])->name('catalogo.rentas');
Route::get('/contratar-renta/{slug}', [ContratacionRentaController::class, 'show'])->name('contratacion.renta.show');
Route::post('/contratar-renta/{slug}', [ContratacionRentaController::class, 'procesar'])->name('contratacion.renta.procesar');

// Páginas de Servicios Específicos
Route::get('/servicio/{slug}', [\App\Http\Controllers\PublicServicioController::class, 'show'])->name('public.servicio.show');

// Descargar APK
Route::get('/descargar-app', function () {
    $path = public_path('app.apk');
    if (!file_exists($path)) {
        return redirect('https://play.google.com/store/apps/details?id=com.asistenciavircom.app');
    }
    $versionPath = public_path('app-version.json');
    $version = '1.0.0';
    if (file_exists($versionPath)) {
        $meta = json_decode(file_get_contents($versionPath), true);
        $version = $meta['version'] ?? '1.0.0';
    }
    return response()->download($path, "AsistenciaVircom_v{$version}.apk", [
        'Content-Type' => 'application/vnd.android.package-archive',
    ]);
})->name('public.descargar-app');

// Checkout de Pólizas (Público inicia aquí)
Route::get('/contratar/{slug}', [ContratacionPolizaController::class, 'show'])->name('contratacion.show');
Route::post('/contratar/{slug}', [ContratacionPolizaController::class, 'procesar'])->name('contratacion.procesar');
Route::get('/contratar/{slug}/exito', [ContratacionPolizaController::class, 'exito'])->name('contratacion.exito');

// PDFs Públicos
Route::get('/share/venta/{token}/pdf', [VentaDocumentoController::class, 'generarPDFPublico'])->name('ventas.pdf.public');
Route::get('/share/cotizacion/{token}/pdf', [CotizacionDocumentoController::class, 'generarPDFPublico'])->name('cotizaciones.pdf.public');
Route::get('/share/pedido/{token}/pdf', [PedidoDocumentoController::class, 'generarPDFPublico'])->name('pedidos.pdf.public');

// Agendamiento Público Detallado
Route::redirect('/citas/agendar', '/agendar', 301);
Route::prefix('agendar')->name('agendar.')->group(function () {
    Route::get('/', [CitaPublicaController::class, 'index'])->name('index');
    Route::post('/', [CitaPublicaController::class, 'store'])->name('store')->middleware('throttle:5,1');
    Route::get('/disponibilidad', [CitaPublicaController::class, 'disponibilidad'])->name('disponibilidad')->middleware('throttle:20,1');
    Route::get('/horarios', [CitaPublicaController::class, 'horariosDisponibles'])->name('horarios')->middleware('throttle:20,1');
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

// Debug y Pruebas (Protegido)
Route::middleware(['auth:sanctum', 'role:admin|super-admin'])->group(function () {
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
            'storage_url_example' => \App\Helpers\UrlHelper::storageUrl('profile-photos/test.webp'),
            'is_misconfigured' => \App\Helpers\UrlHelper::isAppUrlMisconfigured(),
        ]);
    })->name('debug.urls');

    // Rutas de Imágenes (Movidas aquí para protección)
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
});

Route::get('/profile-photo/{filename}', [App\Http\Controllers\ImageController::class, 'serveProfilePhoto'])->name('serve-profile-photo');
Route::get('/api/profile-photos', [App\Http\Controllers\ImageController::class, 'listProfilePhotos'])->name('list-profile-photos')->middleware(['auth:sanctum']);

// Ruta alternativa para servir imágenes de servicios (evita colisiones con rutas tipo recurso)
Route::get('/servicios/{filename}', function ($filename) {
    $path = 'servicios/' . $filename;
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath)) {
        return response('Imagen no encontrada', 404);
    }
    $mimeType = mime_content_type($fullPath) ?: 'image/webp';
    return response()->file($fullPath, ['Content-Type' => $mimeType]);
})->where('filename', '.*\.(webp|png|jpg|jpeg|gif)$');

Route::get('/img/profile-photos/{filename}', function ($filename) {
    $path = 'profile-photos/' . $filename;
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath))
        return response('Imagen no encontrada', 404);
    $mimeType = mime_content_type($fullPath) ?: 'image/png';
    return response()->file($fullPath, ['Content-Type' => $mimeType]);
})->name('image.profile-photo');

// Webhook específico para Portal de Clientes (Pagos Ventas)
Route::post('/webhooks/portal/mercadopago', [\App\Http\Controllers\ClientPortal\PortalPaymentController::class, 'webhookMercadoPago'])
    ->name('portal.pagos.mercadopago.webhook')
    ->middleware('throttle:60,1')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// =====================================================
// RUTAS EXCLUSIVAS DE ASISTENCIA VIRCOM
// =====================================================
Route::get('/quienes-somos', [LandingController::class, 'quienesSomos'])->name('public.quienes-somos');
Route::get('/curriculum-pdf', [LandingController::class, 'curriculumPdf'])->name('public.curriculum-pdf');
Route::get('/puntos-de-venta', [LandingController::class, 'puntosVenta'])->name('public.puntos-venta');

// === RUTAS UNICAS PUBLIC ASISTENCIA ===
Route::get('/marcar/{token}', [\App\Http\Controllers\AsistenciaController::class, 'showByToken'])->name('asistencia.token');
Route::post('/marcar/{token}', [\App\Http\Controllers\AsistenciaController::class, 'storeByToken'])->middleware('throttle:10,1')->name('asistencia.token.store');
Route::get('/newsletter/unsubscribe', [\App\Http\Controllers\NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
Route::post('/newsletter/subscribe', [\App\Http\Controllers\NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');

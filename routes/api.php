<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\MarcaController;
use App\Http\Controllers\Api\ProveedorController;
use App\Http\Controllers\Api\AlmacenController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\CotizacionController;
use App\Http\Controllers\Api\PedidoController;
use App\Http\Controllers\Api\VentaController;
use App\Http\Controllers\Api\CitaController;
use App\Http\Controllers\Api\TecnicoController;
use App\Http\Controllers\Api\ServicioController;
use App\Http\Controllers\Api\UnidadMedidaController;
use App\Http\Controllers\Api\PrecioController;
use App\Http\Controllers\Api\PriceListController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Controllers\EmpresaWhatsAppController;
// Eliminado: API de herramientas
// use App\Http\Controllers\Api\HerramientaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Aquí defines todas las rutas API de tu aplicación. Estas rutas están
| automáticamente prefijadas con 'api' y tienen el middleware 'api' aplicado.
*/

// =====================================================
// RUTAS DE AUTENTICACIÓN
// =====================================================
// =====================================================
// AUTENTICACIÓN (Sanctum)
// =====================================================
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/user', [AuthController::class, 'me'])->name('api.user');
    Route::post('/refresh-token', [AuthController::class, 'refresh'])->name('api.refresh');
});

// =====================================================
// RUTAS PÚBLICAS (Sin autenticación)
// =====================================================

// Restauración de respaldo para el Wizard de Setup
Route::post('/setup/restore-backup', [\App\Http\Controllers\SetupController::class, 'restoreBackup'])->name('api.setup.restore-backup');

// MEGA Cloud API
Route::prefix('mega')->name('api.mega.')->group(function () {
    Route::post('/test-connection', [\App\Http\Controllers\Api\MegaController::class, 'testConnection'])->name('test');
    Route::get('/list', [\App\Http\Controllers\Api\MegaController::class, 'list'])->name('list');
    Route::get('/download', [\App\Http\Controllers\Api\MegaController::class, 'download'])->name('download');
    Route::post('/delete', [\App\Http\Controllers\Api\MegaController::class, 'delete'])->name('delete');
    Route::post('/upload', [\App\Http\Controllers\Api\MegaController::class, 'upload'])->name('upload');
});

// Google Drive API
Route::prefix('gdrive')->name('api.gdrive.')->group(function () {
    Route::get('/auth', [\App\Http\Controllers\Api\GoogleDriveController::class, 'auth'])->name('auth');
    Route::get('/callback', [\App\Http\Controllers\Api\GoogleDriveController::class, 'callback'])->name('callback');
    Route::post('/disconnect', [\App\Http\Controllers\Api\GoogleDriveController::class, 'disconnect'])->name('disconnect');
    Route::get('/test', [\App\Http\Controllers\Api\GoogleDriveController::class, 'test'])->name('test');
    Route::get('/list', [\App\Http\Controllers\Api\GoogleDriveController::class, 'list'])->name('list');
    Route::post('/upload', [\App\Http\Controllers\Api\GoogleDriveController::class, 'upload'])->name('upload');
    Route::get('/download', [\App\Http\Controllers\Api\GoogleDriveController::class, 'download'])->name('download');
    Route::post('/delete', [\App\Http\Controllers\Api\GoogleDriveController::class, 'delete'])->name('delete');
});

Route::get('/config', [App\Http\Controllers\Api\ConfigController::class, 'publicConfig'])->name('api.config');
Route::get('/check-email', [ClienteController::class, 'checkEmail'])->name('api.check-email');
Route::get('/clientes/check-email', [ClienteController::class, 'checkEmail'])->name('api.clientes.check-email');
Route::post('/validar-rfc', [ClienteController::class, 'validarRfc'])->name('api.validar-rfc');

// Endpoint para autocompletado de direcciones por código postal
Route::get('/cp/{cp}', function (string $cp) {
    // Validar que el CP tenga 5 dígitos
    if (!preg_match('/^\d{5}$/', $cp)) {
        return response()->json(['error' => 'Código postal debe tener 5 dígitos'], 400);
    }

    // Intentar primero con la base de datos local de Sepomex
    try {
        $sepomex = \Eclipxe\SepomexPhp\SepomexPhp::createForDatabaseFile(storage_path('sepomex.sqlite'));
        $zipCodeData = $sepomex->getZipCodeData($cp);

        if ($zipCodeData) {
            $colonias = [];
            foreach ($zipCodeData->locations as $location) {
                $colonias[] = $location->name;
            }

            return response()->json([
                'estado' => $zipCodeData->state->name,
                'municipio' => $zipCodeData->district->name,
                'colonias' => $colonias,
                'pais' => 'México',
            ]);
        }
    } catch (\Exception $e) {
        Log::warning('Sepomex local falló, usando API externa: ' . $e->getMessage(), ['cp' => $cp]);
    }

    // Fallback: API externa Zippopotam (siempre disponible, sin token)
    try {
        $response = Http::timeout(5)->get("https://api.zippopotam.us/mx/{$cp}");

        if ($response->successful()) {
            $data = $response->json();
            $colonias = array_map(fn($p) => $p['place name'], $data['places'] ?? []);
            $estado = $data['places'][0]['state'] ?? '';

            return response()->json([
                'estado' => $estado,
                'municipio' => $estado, // Zippopotam no da municipio exacto
                'colonias' => $colonias,
                'pais' => 'México',
            ]);
        }
    } catch (\Exception $e) {
        Log::error('API externa de CP también falló: ' . $e->getMessage(), ['cp' => $cp]);
    }

    return response()->json(['error' => 'Código postal no encontrado'], 404);
})->name('api.cp');

// Subida de documentos temporales para contratación
Route::post('/upload-temp', function (Request $request) {
    $request->validate([
        'documento' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,webp',
        'tipo' => 'required|string|in:ine_frontal,ine_trasera,comprobante_domicilio,solicitud_renta',
    ]);

    $file = $request->file('documento');
    $tipo = $request->input('tipo');

    // Generar nombre único
    $filename = $tipo . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

    // Guardar en carpeta temporal (se moverá cuando se complete el proceso)
    $path = $file->storeAs('temp/contratos', $filename, 'public');

    return response()->json([
        'path' => $path,
        'url' => Storage::disk('public')->url($path),
    ]);
})->name('api.upload-temp');

// =====================================================
// RECURSOS API (Con nombres únicos para evitar conflictos)
// =====================================================

// Catálogos SAT - ANTES de las rutas de clientes para evitar conflictos
Route::prefix('catalogs')->name('api.catalogs.')->group(function () {
    Route::get('/regimenes-fiscales', [CatalogController::class, 'regimenesFiscales'])->name('regimenes-fiscales');
    Route::get('/usos-cfdi', [CatalogController::class, 'usosCfdi'])->name('usos-cfdi');
    Route::get('/estados', [CatalogController::class, 'estados'])->name('estados');
    Route::get('/all', [CatalogController::class, 'all'])->name('all');
});

// Clientes - Definición manual para mayor control
Route::prefix('clientes')->name('api.clientes.')->group(function () {
    Route::get('/', [ClienteController::class, 'index'])->name('index');
    Route::post('/', [ClienteController::class, 'store'])->name('store');
    Route::get('/{cliente}', [ClienteController::class, 'show'])->name('show');
    Route::put('/{cliente}', [ClienteController::class, 'update'])->name('update');
    Route::delete('/{cliente}', [ClienteController::class, 'destroy'])->name('destroy');

    // Pólizas activas del cliente (para selector en citas)
    Route::get('/{cliente}/polizas', function ($clienteId) {
        $polizas = \App\Models\PolizaServicio::where('cliente_id', $clienteId)
            ->where('estado', 'activa')
            ->where('fecha_fin', '>=', now())
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'folio' => $p->folio,
                    'visitas_disponibles' => max(0, ($p->visitas_sitio_mensuales ?? 0) - ($p->visitas_sitio_consumidas_mes ?? 0)),
                    'tickets_disponibles' => ($p->limite_mensual_tickets ?? 999) - ($p->tickets_soporte_consumidos_mes ?? 0),
                ];
            });

        return response()->json(['polizas' => $polizas]);
    })->name('polizas');
});

// Productos - Definición manual para mayor control
Route::prefix('productos')->name('api.productos.')->group(function () {
    Route::get('/next-codigo', [ProductoController::class, 'nextCodigo'])->name('next-codigo');
    Route::get('/{producto}/series', [ProductoController::class, 'series'])->name('series');
    Route::get('/', [ProductoController::class, 'index'])->name('index');
    Route::post('/', [ProductoController::class, 'store'])->name('store');
    Route::get('/next-codigo', [ProductoController::class, 'nextCodigo'])->name('next-codigo');
    Route::get('/{producto}', [ProductoController::class, 'show'])->name('show');
    Route::put('/{producto}', [ProductoController::class, 'update'])->name('update');
    Route::delete('/{producto}', [ProductoController::class, 'destroy'])->name('destroy');
});

// Cotizaciones - Definición manual para mayor control
Route::prefix('cotizaciones')->name('api.cotizaciones.')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [CotizacionController::class, 'index'])->name('index');
    Route::post('/', [CotizacionController::class, 'store'])->name('store');
    Route::get('/{cotizacion}', [CotizacionController::class, 'show'])->name('show');
    Route::put('/{cotizacion}', [CotizacionController::class, 'update'])->name('update');
    Route::delete('/{cotizacion}', [CotizacionController::class, 'destroy'])->name('destroy');
});

// Recursos usando apiResource con nombres únicos
Route::apiResource('categorias', CategoriaController::class)->names('api.categorias');
Route::apiResource('marcas', MarcaController::class)->names('api.marcas');
Route::apiResource('proveedores', ProveedorController::class)->names('api.proveedores'); // ✅ CONFLICTO RESUELTO
Route::apiResource('almacenes', AlmacenController::class)->names('api.almacenes');
Route::apiResource('pedidos', PedidoController::class)->names('api.pedidos');

// Rutas específicas de ventas ANTES del apiResource
Route::post('/ventas/validate', [VentaController::class, 'validateSale'])->name('api.ventas.validate');
Route::get('/ventas/next-numero-venta', [VentaController::class, 'nextNumeroVenta'])->name('api.ventas.next-numero-venta');
Route::post('/ventas/{id}/marcar-pagado', [VentaController::class, 'marcarPagado'])->name('api.ventas.marcar-pagado');
Route::post('/ventas/{id}/facturar', [VentaController::class, 'facturar'])->name('api.ventas.facturar');
Route::post('/ventas/{id}/cancelar-factura', [VentaController::class, 'cancelarFactura'])->name('api.ventas.cancelar-factura');

// Ruta DELETE protegida con autenticación (necesaria para verificar rol de admin)
Route::middleware('auth:sanctum')->delete('/ventas/{venta}', [VentaController::class, 'destroy'])->name('api.ventas.destroy-protected');

// Recurso general de ventas (excepto destroy que está arriba con auth)
Route::apiResource('ventas', VentaController::class)->except(['destroy'])->names('api.ventas');

// Rutas de Citas protegidas (requieren autenticación)
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('citas', CitaController::class)->names('api.citas');
    Route::post('/citas/{cita}/reasignar', [CitaController::class, 'reasignar'])->name('api.citas.reasignar');
    Route::apiResource('tickets', \App\Http\Controllers\Api\TicketApiController::class)->names('api.tickets');
});

// Cuentas Bancarias
Route::get('/cuentas-bancarias/activas', [\App\Http\Controllers\Api\CuentaBancariaController::class, 'activas'])->name('api.cuentas-bancarias.activas');

Route::apiResource('tecnicos', TecnicoController::class)->names('api.tecnicos');
Route::apiResource('servicios', ServicioController::class)->names('api.servicios');

// Unidades de medida
Route::prefix('unidades-medida')->name('api.unidades-medida.')->group(function () {
    Route::get('/', [UnidadMedidaController::class, 'index'])->name('index');
    Route::post('/', [UnidadMedidaController::class, 'store'])->name('store');
    Route::get('/activas', [UnidadMedidaController::class, 'getActiveUnits'])->name('activas');
    Route::get('/{unidadMedida}', [UnidadMedidaController::class, 'show'])->name('show');
    Route::put('/{unidadMedida}', [UnidadMedidaController::class, 'update'])->name('update');
    Route::delete('/{unidadMedida}', [UnidadMedidaController::class, 'destroy'])->name('destroy');
});

// Precios
Route::prefix('precios')->name('api.precios.')->group(function () {
    Route::post('/recalcular', [PrecioController::class, 'recalcular'])->name('recalcular');
});

// Listas de precios
Route::prefix('price-lists')->name('api.price-lists.')->group(function () {
    Route::get('/', [PriceListController::class, 'index'])->name('index');
    Route::get('/all', [PriceListController::class, 'all'])->name('all');
    Route::get('/{priceList}', [PriceListController::class, 'show'])->name('show');
});

// CONTPAQi Bridge
Route::post('/ventas/{id}/timbrar-contpaqi', [\App\Http\Controllers\Api\ContpaqiController::class, 'timbrarVenta'])->name('api.ventas.timbrar-contpaqi');
Route::post('/cfdi/{uuid}/cancelar-contpaqi', [\App\Http\Controllers\Api\ContpaqiController::class, 'cancelarFactura'])->name('api.cfdi.cancelar-contpaqi');
Route::get('/contpaqi/status', [\App\Http\Controllers\Api\ContpaqiController::class, 'status'])->name('api.contpaqi.status');

// =====================================================
// PRÉSTAMOS (App Móvil - Solo SuperAdmin)
// =====================================================
Route::prefix('prestamos')->name('api.prestamos.')->middleware(['auth:sanctum', 'role:super-admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\PrestamoApiController::class, 'index'])->name('index');
    Route::post('/cotizar', [\App\Http\Controllers\Api\PrestamoApiController::class, 'cotizar'])->name('cotizar');
    Route::post('/', [\App\Http\Controllers\Api\PrestamoApiController::class, 'store'])->name('store');
    Route::get('/{id}', [\App\Http\Controllers\Api\PrestamoApiController::class, 'show'])->name('show');
});

// =====================================================
// COBRANZAS (Para app móvil - Admin y Cobranza)
// =====================================================
Route::prefix('cobranzas')->name('api.cobranzas.')->middleware(['auth:sanctum', 'role:admin|cobranza'])->group(function () {
    Route::get('/hoy', [\App\Http\Controllers\Api\CobranzaApiController::class, 'hoy'])->name('hoy');
    Route::get('/proximas', [\App\Http\Controllers\Api\CobranzaApiController::class, 'proximas'])->name('proximas');
    Route::get('/cuentas-bancarias', [\App\Http\Controllers\Api\CobranzaApiController::class, 'cuentasBancarias'])->name('cuentas-bancarias');
    Route::get('/{id}', [\App\Http\Controllers\Api\CobranzaApiController::class, 'show'])->name('show');
    Route::post('/{id}/pagar', [\App\Http\Controllers\Api\CobranzaApiController::class, 'registrarPago'])->name('pagar');
    Route::post('/{id}/pagar', [\App\Http\Controllers\Api\CobranzaApiController::class, 'registrarPago'])->name('pagar');
});

// Blog Robot Endpoint (Public but secured with Bearer Token in Controller)
Route::post('/blog/robot/draft', [\App\Http\Controllers\Api\BlogRobotController::class, 'storeDraft'])->name('api.blog.robot.draft');
Route::post('/blog/track/interest', [\App\Http\Controllers\Api\NewsletterTrackingController::class, 'reportInterest'])->name('api.blog.track.interest');


// Eliminado: rutas API de herramientas, asignaciones y alertas relacionadas

// =====================================================
// RUTAS DE WEBHOOKS WHATSAPP
// =====================================================
Route::prefix('webhooks')->name('api.webhooks.')->group(function () {
    Route::get('/whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('whatsapp.verify');
    Route::post('/whatsapp', [WhatsAppWebhookController::class, 'receive'])->name('whatsapp.receive');

    // Chatbot IA Webhook
    Route::post('/chat', [\App\Http\Controllers\Api\ChatbotController::class, 'chat'])->name('chat');
});

// =====================================================
// RUTAS DE PRUEBA WHATSAPP
// =====================================================
Route::post('/whatsapp/test', [EmpresaWhatsAppController::class, 'test'])->name('whatsapp.test');

// =====================================================
// RUTAS PROTEGIDAS (Opcional - descomenta si necesitas autenticación)
// =====================================================
/*
Route::middleware('auth:sanctum')->group(function () {
    // Aquí puedes poner rutas que requieran autenticación
    // Ejemplo: Route::apiResource('admin/usuarios', AdminUsuarioController::class);
});
*/

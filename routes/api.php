<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AsistenciaController;
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
use App\Http\Controllers\Api\CajaChicaApiController;
use App\Http\Controllers\Api\VentaController;
use App\Http\Controllers\Api\CompraController;
use App\Http\Controllers\Api\EntregaMiCorteApiController;
use App\Http\Controllers\Api\CitaController;
use App\Http\Controllers\Api\CitaPaymentController;
use App\Http\Controllers\Api\TecnicoController;
use App\Http\Controllers\Api\ServicioController;
use App\Http\Controllers\Api\UnidadMedidaController;
use App\Http\Controllers\Api\PrecioController;
use App\Http\Controllers\Api\PriceListController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Controllers\Api\MantenimientoController;
use App\Http\Controllers\Api\CrmController;
use App\Http\Controllers\Api\HerramientaTransferController;
use App\Http\Controllers\Api\ContabilidadApiController;
use App\Http\Controllers\Api\TodoController;
use App\Http\Controllers\Api\RustDeskController;
use App\Http\Controllers\Auth\SocialAuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Aquí defines todas las rutas API de tu aplicación. Estas rutas están
| automáticamente prefijadas con 'api' y tienen el middleware 'api' aplicado.
*/

// Root API Check removed

Route::get('/login', function () {
    return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
})->name('api.unauthenticated');

// Disponibilidad de citas
Route::get('/citas/check-availability', [CitaController::class, 'checkAvailability'])->name('api.citas.check-availability');
Route::get('/citas/busy-slots', [CitaController::class, 'getBusySlots'])->name('api.citas.busy-slots');

// Pólizas de cliente
Route::get('/clientes/{cliente}/polizas', function ($clienteId) {
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
})->name('api.clientes.polizas');

// Webhook de MercadoPago para Citas (Público)
Route::post('/webhooks/citas-payment', [CitaPaymentController::class, 'webhook'])->name('api.citas.pago.webhook');

// Chatbot interactivo de menú para la Web
Route::post('/chatbot/web', [\App\Http\Controllers\Api\ChatbotController::class, 'webMenuChat'])->name('api.chatbot.web');

// =====================================================
// RUTAS DE AUTENTICACIÓN
// =====================================================
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('api.login');

// =====================================================
// RUTAS PROTEGIDAS (Sanctum)
// =====================================================
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/user', [AuthController::class, 'me'])->name('api.user');
    Route::get('/dashboard/stats', [\App\Http\Controllers\Api\DashboardApiController::class, 'index'])->name('api.dashboard.stats');
    Route::post('/user/fcm-token', [AuthController::class, 'updateFcmToken'])->name('api.user.fcm-token');
    Route::post('/refresh-token', [AuthController::class, 'refresh'])->name('api.refresh');

    // Comisiones (Ionic)
    Route::get('/comisiones', [\App\Http\Controllers\Api\ComisionApiController::class, 'index'])->name('api.comisiones.index');
    Route::get('/comisiones/vendedores', [\App\Http\Controllers\Api\ComisionApiController::class, 'vendedores'])->name('api.comisiones.vendedores');
    Route::get('/comisiones/venta/{id}', [\App\Http\Controllers\Api\ComisionApiController::class, 'showVenta'])->name('api.comisiones.venta');
    Route::post('/comisiones/pagar', [\App\Http\Controllers\Api\ComisionApiController::class, 'registrarPago'])->name('api.comisiones.pagar');

    // Restauración de respaldo (Solo Admin)
    Route::post('/setup/restore-backup', [\App\Http\Controllers\SetupController::class, 'restoreBackup'])
        ->middleware(['role:super-admin', 'throttle:5,1'])
        ->name('api.setup.restore-backup');

    // Subida de documentos temporales (Protegida por Sanctum)
    Route::post('/upload-temp', function (Request $request) {
        $request->validate([
            'documento' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,webp',
            'tipo' => 'required|string|in:ine_frontal,ine_trasera,comprobante_domicilio,solicitud_renta',
        ]);

        $file = $request->file('documento');
        $tipo = $request->input('tipo');

        $filename = $tipo . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('temp/contratos', $filename, 'public');

        return response()->json([
            'path' => $path,
            'url' => Storage::url($path),
        ]);
    })->middleware('throttle:30,1')->name('api.upload-temp');

    // Listas de precios (Movido aquí para protección)
    Route::prefix('price-lists')->name('api.price-lists.')->group(function () {
        Route::get('/', [PriceListController::class, 'index'])->name('index');
        Route::get('/all', [PriceListController::class, 'all'])->name('all');
        Route::get('/{priceList}', [PriceListController::class, 'show'])->name('show');
    });

    Route::get('/mis-herramientas', [\App\Http\Controllers\HerramientaController::class, 'misHerramientasApi'])->name('api.mis-herramientas');
    Route::get('/herramientas/solicitudes-pendientes', [\App\Http\Controllers\HerramientaController::class, 'misSolicitudesPendientes'])->name('api.herramientas.solicitudes-pendientes');
    Route::post('/herramientas/solicitar-traspaso', [\App\Http\Controllers\HerramientaController::class, 'solicitarTraspaso'])->name('api.herramientas.solicitar-traspaso');
    Route::post('/herramientas/aceptar-traspaso', [\App\Http\Controllers\HerramientaController::class, 'aceptarTraspaso'])->name('api.herramientas.aceptar-traspaso');
    Route::post('/herramientas/reasignar-masivo', [\App\Http\Controllers\HerramientaController::class, 'reasignarMasivoApi'])->name('api.herramientas.reasignar-masivo');
    Route::post('/herramientas/{herramienta}/reasignar', [\App\Http\Controllers\HerramientaController::class, 'reasignarApi'])->name('api.herramientas.reasignar');

    // Nuevas Rutas de Transferencia Masiva
    Route::prefix('herramientas/transferencias')->name('api.herramientas.transferencias.')->group(function () {
        Route::get('/', [HerramientaTransferController::class, 'index'])->name('index');
        Route::post('/', [HerramientaTransferController::class, 'store'])->name('store');
        Route::post('/{transferencia}/aceptar', [HerramientaTransferController::class, 'accept'])->name('accept');
        Route::post('/{transferencia}/rechazar', [HerramientaTransferController::class, 'reject'])->name('reject');
        Route::post('/{transferencia}/cancelar', [HerramientaTransferController::class, 'cancel'])->name('cancel');
    });

    // Asistencia / Reloj Checador (Ionic)
    Route::get('/asistencia', [AsistenciaController::class, 'index'])->name('api.asistencia.index');
    Route::post('/asistencia', [AsistenciaController::class, 'registrar'])->name('api.asistencia.registrar');

    Route::post('/device-heartbeat', [\App\Http\Controllers\DeviceSessionController::class, 'heartbeat'])->name('api.device-heartbeat');
    Route::get('/device-sessions', [\App\Http\Controllers\DeviceSessionController::class, 'index'])->name('api.device-sessions');

    // NOM-035 Complaints (Buzón)
    Route::post('/nom035/complaints', [\App\Http\Controllers\Api\Nom035ComplaintController::class, 'store'])->name('api.nom035.complaints.store');
    Route::get('/nom035/complaints/{folio}/status', [\App\Http\Controllers\Api\Nom035ComplaintController::class, 'checkStatus'])->name('api.nom035.complaints.status');
    Route::get('/nom035/policy', [\App\Http\Controllers\Nom035ConfigController::class, 'getPolicyApi'])->name('api.nom035.policy');
    Route::post('/nom035/policy/accept', [\App\Http\Controllers\Nom035ConfigController::class, 'acceptPolicyApi'])->name('api.nom035.policy.accept');


});

Route::get('/config', [App\Http\Controllers\Api\ConfigController::class, 'publicConfig'])->name('api.config');
Route::get('/clientes/check-email', [ClienteController::class, 'checkEmail'])->middleware('throttle:10,1')->name('api.clientes.check-email');
Route::post('/validar-rfc', [ClienteController::class, 'validarRfc'])->middleware('throttle:10,1')->name('api.validar-rfc');

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

        return response()->json([
            'error' => 'Código postal no encontrado',
            'colonias' => [],
            'status' => 'not_found'
        ], 200);
})->name('api.cp');

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

Route::middleware(['auth:sanctum'])->group(function () {
    // Caja Chica
    Route::get('caja-chica', [CajaChicaApiController::class, 'index']);
    Route::post('caja-chica', [CajaChicaApiController::class, 'store']);
    Route::get('caja-chica/users', [CajaChicaApiController::class, 'users']);

    // Clientes
    Route::prefix('clientes')->name('api.clientes.')->group(function () {
        Route::get('/', [ClienteController::class, 'index'])->name('index');
        Route::post('/', [ClienteController::class, 'store'])->name('store');
        Route::get('/{cliente}', [ClienteController::class, 'show'])->name('show');
        Route::put('/{cliente}', [ClienteController::class, 'update'])->name('update');
        Route::delete('/{cliente}', [ClienteController::class, 'destroy'])
            ->middleware(['throttle:20,1', 'can:delete clientes'])
            ->name('destroy');

        // Pólizas activas del cliente (MOVIDA A GRUPO WEB ARRIBA)
    });

    // Productos (permisos alineados con el panel web)
    Route::prefix('productos')->name('api.productos.')->group(function () {
        Route::get('/next-codigo', [ProductoController::class, 'nextCodigo'])
            ->middleware('can:view productos')
            ->name('next-codigo');
        Route::get('/{producto}/series', [ProductoController::class, 'series'])
            ->middleware('can:view productos')
            ->name('series');
        Route::get('/', [ProductoController::class, 'index'])
            ->middleware('can:view productos')
            ->name('index');
        Route::post('/', [ProductoController::class, 'store'])
            ->middleware('can:create productos')
            ->name('store');
        Route::get('/{producto}', [ProductoController::class, 'show'])
            ->middleware('can:view productos')
            ->name('show');
        Route::put('/{producto}', [ProductoController::class, 'update'])
            ->middleware('can:edit productos')
            ->name('update');
        Route::delete('/{producto}', [ProductoController::class, 'destroy'])
            ->middleware(['throttle:20,1', 'can:delete productos'])
            ->name('destroy');
    });

    // Cotizaciones
    Route::prefix('cotizaciones')->name('api.cotizaciones.')->group(function () {
        Route::get('/', [CotizacionController::class, 'index'])->name('index');
        Route::post('/', [CotizacionController::class, 'store'])->name('store');
        Route::get('/{cotizacion}', [CotizacionController::class, 'show'])->name('show');
        Route::put('/{cotizacion}', [CotizacionController::class, 'update'])->name('update');
        Route::delete('/{cotizacion}', [CotizacionController::class, 'destroy'])
            ->middleware(['throttle:20,1', 'can:delete,cotizacion'])
            ->name('destroy');
    });

    // Recursos usando apiResource con destroy protegido
    Route::apiResource('categorias', CategoriaController::class)->except(['destroy'])->names('api.categorias');
    Route::delete('categorias/{categoria}', [CategoriaController::class, 'destroy'])->middleware('can:delete categorias')->name('api.categorias.destroy');

    Route::apiResource('marcas', MarcaController::class)->except(['destroy'])->names('api.marcas');
    Route::delete('marcas/{marca}', [MarcaController::class, 'destroy'])->middleware('can:delete marcas')->name('api.marcas.destroy');

    Route::apiResource('proveedores', ProveedorController::class)->except(['destroy'])->names('api.proveedores');
    Route::delete('proveedores/{proveedor}', [ProveedorController::class, 'destroy'])->middleware('can:delete proveedores')->name('api.proveedores.destroy');

    Route::get('almacenes/mi-inventario', [AlmacenController::class, 'miInventario'])->name('api.almacenes.mi-inventario');
    Route::post('almacenes/confirmar-recepcion/{id}', [AlmacenController::class, 'confirmarRecepcion'])->name('api.almacenes.confirmar-recepcion');
    Route::post('almacenes/finalizar-auditoria/{id}', [AlmacenController::class, 'finalizarAuditoria'])
        ->middleware('can:create ajustes_inventario')
        ->name('api.almacenes.finalizar-auditoria');
    Route::post('almacenes/aprobar-auditoria/{movimientoId}', [AlmacenController::class, 'aprobarAuditoria'])
        ->middleware('can:edit ajustes_inventario')
        ->name('api.almacenes.aprobar-auditoria');
    Route::get('almacenes/inventario/pdf', [AlmacenController::class, 'exportarPdf'])
        ->middleware('can:view almacenes')
        ->name('api.almacenes.inventario.pdf');

    Route::prefix('inventarios-fisicos')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\InventarioFisicoController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\InventarioFisicoController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Api\InventarioFisicoController::class, 'show']);
        Route::post('/{id}/finalize', [\App\Http\Controllers\Api\InventarioFisicoController::class, 'finalize']);
        Route::post('/{id}/approve', [\App\Http\Controllers\Api\InventarioFisicoController::class, 'approve']);
        Route::post('/{id}/reject', [\App\Http\Controllers\Api\InventarioFisicoController::class, 'reject']);
        Route::post('/{audit}/items/{item}', [\App\Http\Controllers\Api\InventarioFisicoController::class, 'updateItem']);
    });
    Route::get('almacenes/historial/{producto}', [AlmacenController::class, 'historial'])
        ->middleware('can:view movimientos_inventario')
        ->name('api.almacenes.historial');
    Route::post('almacenes/ajustar', [AlmacenController::class, 'ajustar'])
        ->middleware('can:create ajustes_inventario')
        ->name('api.almacenes.ajustar');
    Route::post('almacenes/traspasar', [\App\Http\Controllers\Api\TraspasoApiController::class, 'store'])
        ->middleware('can:create traspasos')
        ->name('api.almacenes.traspasar');
    Route::get('almacenes/traspasar/{traspaso}/pdf', [\App\Http\Controllers\Api\TraspasoApiController::class, 'downloadPdf'])->name('api.traspasos.pdf');
    Route::apiResource('almacenes', AlmacenController::class)->except(['destroy'])->names('api.almacenes');
    Route::delete('almacenes/{almacen}', [AlmacenController::class, 'destroy'])->middleware('can:delete almacenes')->name('api.almacenes.destroy');

    Route::apiResource('pedidos', PedidoController::class)->except(['destroy'])->names('api.pedidos');
    Route::delete('pedidos/{pedido}', [PedidoController::class, 'destroy'])->middleware('can:delete pedidos')->name('api.pedidos.destroy');

    // Rutas específicas de ventas ANTES del apiResource
    Route::post('/ventas/validate', [VentaController::class, 'validateSale'])->name('api.ventas.validate');
    Route::get('/ventas/{id}/pdf-base64', [\App\Http\Controllers\VentaDocumentoController::class, 'generarPDFBase64'])->name('api.ventas.pdf-base64');
    Route::get('/ventas/next-numero-venta', [VentaController::class, 'nextNumeroVenta'])->name('api.ventas.next-numero-venta');
    /** Resumen de cobros registrados por el usuario (corte técnico / vendedor) */
    Route::get('/ventas/mi-resumen-cobros', [VentaController::class, 'miResumenCobros'])->name('api.ventas.mi-resumen-cobros');
    Route::get('/ventas/usuarios-cobro', [VentaController::class, 'usuariosParaRegistrarCobro'])->name('api.ventas.usuarios-cobro');
    /*
     * Alias bajo /cobros/*: evita 404 cuando GET /ventas/{venta} absorbe segmentos como "mi-resumen-cobros"
     * (orden de rutas, route:cache o proxies). La app móvil usa preferentemente estas URLs.
     */
    Route::prefix('cobros')->name('api.cobros.')->group(function () {
        Route::get('/mi-resumen', [VentaController::class, 'miResumenCobros'])->name('mi-resumen');
        Route::get('/usuarios', [VentaController::class, 'usuariosParaRegistrarCobro'])->name('usuarios');
        Route::post('/declarar-entrega-mi-corte', [EntregaMiCorteApiController::class, 'declararEntregaMiCorte'])->name('declarar-entrega-mi-corte');
        Route::post('/lote', [EntregaMiCorteApiController::class, 'entregarLote'])->name('lote');
        Route::get('/entregas-efectivo/pendientes-recepcion', [EntregaMiCorteApiController::class, 'pendientesRecepcion'])->name('entregas-efectivo.pendientes');
        Route::post('/entregas-efectivo/{id}/confirmar-recepcion', [EntregaMiCorteApiController::class, 'confirmarRecepcion'])->name('entregas-efectivo.confirmar');
    });
    Route::post('/ventas/{id}/marcar-pagado', [VentaController::class, 'marcarPagado'])->name('api.ventas.marcar-pagado');
    Route::post('/ventas/{id}/facturar', [VentaController::class, 'facturar'])->name('api.ventas.facturar');
    Route::post('/ventas/{id}/cancelar-factura', [VentaController::class, 'cancelarFactura'])->name('api.ventas.cancelar-factura');
    Route::post('/ventas/{id}/change-vendedor', [VentaController::class, 'changeVendedor'])->name('api.ventas.change-vendedor');

    // Ruta DELETE protegida
    Route::delete('/ventas/{venta}', [VentaController::class, 'destroy'])
        ->middleware(['throttle:20,1', 'can:delete ventas'])
        ->name('api.ventas.destroy-protected');

    // Recurso general de ventas (excepto destroy)
    Route::apiResource('ventas', VentaController::class)->except(['destroy'])->names('api.ventas');

    // Compras
    Route::prefix('compras')->name('api.compras.')->group(function () {
        Route::get('/siguiente-numero', [CompraController::class, 'siguienteNumero'])->name('siguiente-numero');
        Route::get('/', [CompraController::class, 'index'])->name('index');
        Route::post('/', [CompraController::class, 'store'])->name('store');
        Route::get('/{id}', [CompraController::class, 'show'])->name('show');
        Route::delete('/{id}', [CompraController::class, 'destroy'])->middleware('can:delete compras')->name('destroy');
    });

    Route::prefix('citas')->group(function () {
        // Rutas check-availability y busy-slots MOVIDAS A GRUPO WEB ARRIBA
        Route::get('/proxima', [CitaController::class, 'proxima'])->name('api.citas.proxima');
    });
    // Rutas con segmentos fijos ANTES del apiResource para que no las absorba {cita}
    Route::post('/citas/{cita}/reasignar', [CitaController::class, 'reasignar'])->name('api.citas.reasignar');
    Route::post('/citas/{cita}/iniciar', [CitaController::class, 'iniciar'])->name('api.citas.iniciar');
    Route::post('/citas/{cita}/regresar', [CitaController::class, 'regresar'])->name('api.citas.regresar');
    Route::post('/citas/{cita}/cancelar', [CitaController::class, 'cancelar'])->name('api.citas.cancelar');
    Route::post('/citas/{cita}/completar', [CitaController::class, 'completar'])->name('api.citas.completar');
    Route::post("/citas/{cita}/set-pendiente", [CitaController::class, "setPendiente"])->name("api.citas.set-pendiente");
    Route::post("/citas/{cita}/programar-hoy", [CitaController::class, "programarHoy"])->name("api.citas.programar-hoy");

    Route::apiResource('citas', CitaController::class)->except(['destroy'])->names('api.citas')->whereNumber('cita');
    Route::delete('citas/{cita}', [CitaController::class, 'destroy'])->middleware('can:delete citas')->name('api.citas.destroy');

    // Pagos con QR / Link para Citas
    Route::prefix('citas')->group(function () {
        Route::post('/{id}/payment-preference', [CitaPaymentController::class, 'createPreference'])->name('api.citas.payment-preference');
        Route::get('/payment/success/{id}', [CitaPaymentController::class, 'success'])->name('api.citas.pago.exito');
        Route::get('/payment/error/{id}', [CitaPaymentController::class, 'error'])->name('api.citas.pago.error');
        Route::get('/payment/pending/{id}', [CitaPaymentController::class, 'pending'])->name('api.citas.pago.pendiente');
    });

    Route::apiResource('tickets', \App\Http\Controllers\Api\TicketApiController::class)->except(['destroy'])->names('api.tickets');
    Route::delete('tickets/{ticket}', [\App\Http\Controllers\Api\TicketApiController::class, 'destroy'])->middleware('can:delete tickets')->name('api.tickets.destroy');

    // Cuentas Bancarias
    Route::get('/cuentas-bancarias/activas', [\App\Http\Controllers\Api\CuentaBancariaController::class, 'activas'])->name('api.cuentas-bancarias.activas');

    Route::apiResource('tecnicos', TecnicoController::class)->except(['destroy'])->names('api.tecnicos');
    Route::delete('tecnicos/{tecnico}', [TecnicoController::class, 'destroy'])->middleware('can:delete tecnicos')->name('api.tecnicos.destroy');
    Route::post('/user/location', [TecnicoController::class, 'updateLocation'])->name('api.user.location');
    Route::get('/tecnicos/ubicaciones', [TecnicoController::class, 'ubicaciones'])->name('api.tecnicos.ubicaciones');

    Route::apiResource('servicios', ServicioController::class)->except(['destroy'])->names('api.servicios');
    Route::delete('servicios/{servicio}', [ServicioController::class, 'destroy'])->middleware('can:delete servicios')->name('api.servicios.destroy');

    // Unidades de medida
    Route::prefix('unidades-medida')->name('api.unidades-medida.')->group(function () {
        Route::get('/', [UnidadMedidaController::class, 'index'])->name('index');
        Route::post('/', [UnidadMedidaController::class, 'store'])->name('store');
        Route::get('/activas', [UnidadMedidaController::class, 'getActiveUnits'])->name('activas');
        Route::get('/{unidadMedida}', [UnidadMedidaController::class, 'show'])->name('show');
        Route::put('/{unidadMedida}', [UnidadMedidaController::class, 'update'])->name('update');
        Route::delete('/{unidadMedida}', [UnidadMedidaController::class, 'destroy'])
            ->middleware(['throttle:20,1', 'role:admin'])
            ->name('destroy');
    });

    // Gastos (App Móvil)
    Route::prefix('gastos')->name('api.gastos.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\GastoApiController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Api\GastoApiController::class, 'store'])->name('store');
        Route::get('/categories', [\App\Http\Controllers\Api\GastoApiController::class, 'categories'])->name('categories');
        Route::get('/bank-accounts', [\App\Http\Controllers\Api\GastoApiController::class, 'bankAccounts'])->name('bank-accounts');
        Route::get('/proyectos', [\App\Http\Controllers\Api\GastoApiController::class, 'proyectos'])->name('proyectos');
        Route::get('/tecnicos', [\App\Http\Controllers\Api\GastoApiController::class, 'tecnicos'])->name('tecnicos');
        Route::get('/balances/{userId}', [\App\Http\Controllers\GastoController::class, 'userBalances'])->name('balances');
        Route::get('/{id}', [\App\Http\Controllers\Api\GastoApiController::class, 'show'])->name('show');
        Route::put('/{id}', [\App\Http\Controllers\Api\GastoApiController::class, 'update'])->name('update');
        Route::post('/{id}', [\App\Http\Controllers\Api\GastoApiController::class, 'update'])->name('update-post'); // For FormData with _method=PUT
        Route::post('/{id}/change-user', [\App\Http\Controllers\Api\GastoApiController::class, 'changeUser'])->name('change-user');
        Route::delete('/{id}', [\App\Http\Controllers\Api\GastoApiController::class, 'destroy'])->name('destroy');
    });

    // Caja Chica (App Móvil)
    Route::prefix('caja-chica')->name('api.caja-chica.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\CajaChicaApiController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Api\CajaChicaApiController::class, 'store'])->name('store');
    });

    // Solicitudes de Material
    Route::prefix('solicitudes-material')->name('api.solicitudes-material.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\SolicitudMaterialController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Api\SolicitudMaterialController::class, 'store'])->name('store');
        Route::get('/{solicitud}', [\App\Http\Controllers\Api\SolicitudMaterialController::class, 'show'])->name('show');
        Route::put('/{solicitud}', [\App\Http\Controllers\Api\SolicitudMaterialController::class, 'update'])->name('update');
    });

    // To-Do List (Personal)
    Route::apiResource('todos', TodoController::class)->names('api.todos');
    Route::put('todos/steps/{step}/toggle', [TodoController::class, 'toggleStep'])->name('api.todos.steps.toggle');
    Route::post('todos/{todo}/attachments', [TodoController::class, 'uploadAttachment'])->name('api.todos.attachments.store');
    Route::delete('todos/{todo}/attachments/{attachment}', [TodoController::class, 'deleteAttachment'])->name('api.todos.attachments.destroy');

    // Users list for admin todo assignment
    Route::get('/users', function (\Illuminate\Http\Request $request) {
        if (!$request->user()->hasAnyRole(['admin', 'super-admin'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        return \App\Models\User::select('id', 'name')
            ->where(function ($q) {
                $q->whereNull('es_empleado')->orWhere('es_empleado', false);
            })
            ->orderBy('name')->get();
    })->name('api.users.list');

    // Mantenimientos (App Móvil)
    Route::post('/mantenimientos', [MantenimientoController::class, 'store'])->name('api.mantenimientos.store');

    Route::prefix('crm')->name('api.crm.')->group(function () {
        Route::get('/prospectos', [CrmController::class, 'index'])->name('prospectos.index');
        Route::post('/prospectos', [CrmController::class, 'store'])->name('prospectos.store');
        Route::get('/prospectos/{prospecto}', [CrmController::class, 'show'])->name('prospectos.show');
        Route::put('/prospectos/{prospecto}', [CrmController::class, 'update'])->name('prospectos.update');
        Route::post('/prospectos/{prospecto}/actividad', [CrmController::class, 'registrarActividad'])->name('prospectos.actividad');
        Route::post('/prospectos/{prospecto}/convertir', [CrmController::class, 'convertir'])->name('prospectos.convertir');
        Route::get('/tareas', [CrmController::class, 'tareas'])->name('tareas');
    });

    // Vacaciones (App Móvil)
    Route::prefix('vacaciones')->name('api.vacaciones.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\VacacionApiController::class, 'index'])->name('index');
        Route::get('/stats', [\App\Http\Controllers\Api\VacacionApiController::class, 'stats'])->name('stats');
        Route::post('/', [\App\Http\Controllers\Api\VacacionApiController::class, 'store'])->name('store');
        Route::post('/sync-employee', [\App\Http\Controllers\Api\VacacionApiController::class, 'syncEmployee'])->name('sync-employee');
        Route::post('/{id}/cancel', [\App\Http\Controllers\Api\VacacionApiController::class, 'cancel'])->name('cancel');
    });
    // =====================================================
    // TALLER (App Móvil)
    // =====================================================
    Route::prefix('taller')->name('api.taller.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\TallerController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Api\TallerController::class, 'store'])->name('store');
        Route::get('/{id}', [\App\Http\Controllers\Api\TallerController::class, 'show'])->name('show');
        Route::put('/{id}', [\App\Http\Controllers\Api\TallerController::class, 'update'])->name('update');
        Route::post('/{id}/entregar', [\App\Http\Controllers\Api\TallerController::class, 'entregar'])->name('entregar');
    });

    // Reverb private channel authentication route for Mobile App
    \Illuminate\Support\Facades\Broadcast::routes(['prefix' => 'api', 'middleware' => ['auth:sanctum']]);

    // =====================================================
    // WHATSAPP INBOX (App Móvil)
    // =====================================================
    Route::prefix('whatsapp-inbox')->name('api.whatsapp.')->middleware(['role:admin|super-admin|ventas'])->group(function () {
        Route::get('/init', [\App\Http\Controllers\Marketing\WhatsAppChatController::class, 'initJson'])->name('init');
        Route::get('/', [\App\Http\Controllers\Marketing\WhatsAppChatController::class, 'index'])->name('inbox');
        Route::get('/messages/{waId}', [\App\Http\Controllers\Marketing\WhatsAppChatController::class, 'getMessages'])->name('messages');
        Route::get('/context/{waId}', [\App\Http\Controllers\Marketing\WhatsAppChatController::class, 'getContactContext'])->name('context');
        Route::get('/ai-suggestion/{waId}', [\App\Http\Controllers\Marketing\WhatsAppChatController::class, 'getAISuggestion'])->name('ai-suggestion');
        Route::get('/audio/{messageId}', [\App\Http\Controllers\Marketing\WhatsAppChatController::class, 'getAudio'])->name('audio');
        Route::get('/image/{messageId}', [\App\Http\Controllers\Marketing\WhatsAppChatController::class, 'getImage'])->name('image');
        Route::post('/assign/{waId}', [\App\Http\Controllers\Marketing\WhatsAppChatController::class, 'assignAgent'])->name('assign');
        Route::post('/status/{waId}', [\App\Http\Controllers\Marketing\WhatsAppChatController::class, 'toggleStatus'])->name('status');
        Route::post('/toggle-chatbot', [\App\Http\Controllers\Marketing\WhatsAppChatController::class, 'toggleChatbot'])->name('toggle-chatbot');
        Route::put('/conversation/{waId}', [\App\Http\Controllers\Marketing\WhatsAppChatController::class, 'updateConversation'])->name('conversation.update');

        // Solo super-admin puede responder/modificar por API móvil
        Route::middleware(['role:super-admin'])->group(function () {
            Route::post('/send', [\App\Http\Controllers\Marketing\WhatsAppChatController::class, 'sendMessage'])->name('send');
            Route::post('/upload', [\App\Http\Controllers\Marketing\WhatsAppChatController::class, 'uploadAndSendMedia'])->name('upload');
            Route::post('/internal-note', [\App\Http\Controllers\Marketing\WhatsAppChatController::class, 'sendInternalNote'])->name('internal-note');
        });
    });

    // =====================================================
    // MANTENIMIENTO PÓLIZAS (App Móvil)
    // =====================================================
    Route::prefix('mantenimientos-poliza')->name('api.mantenimientos-poliza.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\PolizaMantenimientoTecnicoApiController::class, 'index'])->name('index');
        Route::post('/{id}/tomar', [\App\Http\Controllers\Api\PolizaMantenimientoTecnicoApiController::class, 'tomarTarea'])->name('tomar');
        Route::post('/{id}/iniciar', [\App\Http\Controllers\Api\PolizaMantenimientoTecnicoApiController::class, 'iniciar'])->name('iniciar');
        Route::post('/{id}/completar', [\App\Http\Controllers\Api\PolizaMantenimientoTecnicoApiController::class, 'completar'])->name('completar');
        Route::post('/{id}/liberar', [\App\Http\Controllers\Api\PolizaMantenimientoTecnicoApiController::class, 'liberarTarea'])->name('liberar');
        Route::get('/{id}/historial-equipo', [\App\Http\Controllers\Api\PolizaMantenimientoTecnicoApiController::class, 'historialEquipo'])->name('historial-equipo');
    });
});


// Precios (Eliminado de aquí, movido a grupo protegido)

// =====================================================
// PRÉSTAMOS (App Móvil - Solo SuperAdmin)
// =====================================================
Route::prefix('prestamos')->name('api.prestamos.')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\PrestamoApiController::class, 'index'])->name('index');
    Route::post('/cotizar', [\App\Http\Controllers\Api\PrestamoApiController::class, 'cotizar'])->name('cotizar');
    Route::post('/', [\App\Http\Controllers\Api\PrestamoApiController::class, 'store'])->name('store');
    Route::get('/{id}', [\App\Http\Controllers\Api\PrestamoApiController::class, 'show'])->name('show');
    Route::post('/{id}/pagar', [\App\Http\Controllers\Api\PrestamoApiController::class, 'registrarPago'])->name('pagar');
});

// =====================================================
// COBRANZAS (Para app móvil - Admin y Cobranza)
// =====================================================
Route::prefix('cobranzas')->name('api.cobranzas.')->middleware(['auth:sanctum', \App\Http\Middleware\EnsureCobranzasApiAccess::class])->group(function () {
    Route::get('/hoy', [\App\Http\Controllers\Api\CobranzaApiController::class, 'hoy'])->name('hoy');
    Route::get('/proximas', [\App\Http\Controllers\Api\CobranzaApiController::class, 'proximas'])->name('proximas');
    Route::get('/cuentas-bancarias', [\App\Http\Controllers\Api\CobranzaApiController::class, 'cuentasBancarias'])->name('cuentas-bancarias');
    Route::get('/{id}', [\App\Http\Controllers\Api\CobranzaApiController::class, 'show'])->name('show');
    Route::post('/{id}/pagar', [\App\Http\Controllers\Api\CobranzaApiController::class, 'registrarPago'])->name('pagar');
});


// Eliminado: rutas API de herramientas, asignaciones y alertas relacionadas

// =====================================================
// RUTAS DE CONTABILIDAD
// =====================================================
Route::prefix('contabilidad')->name('api.contabilidad.')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/upload-xml', [ContabilidadApiController::class, 'uploadXml'])->name('upload-xml');
    Route::get('/polizas', [ContabilidadApiController::class, 'indexPolizas'])->name('polizas.index');
    Route::get('/catalogo', [ContabilidadApiController::class, 'getCatalog'])->name('catalogo');
});

// =====================================================
// RUTAS DE WEBHOOKS WHATSAPP
// =====================================================
Route::prefix('webhooks')->name('api.webhooks.')->group(function () {
    Route::get('/whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('whatsapp.verify');
    Route::post('/whatsapp', [WhatsAppWebhookController::class, 'receive'])->middleware('throttle:30,1')->name('whatsapp.receive');

    // Chatbot IA Webhook
    Route::post('/chat', [\App\Http\Controllers\Api\ChatbotController::class, 'chat'])->name('chat');
});

// WhatsApp Test (Eliminado de aquí, movido a grupo protegido)

// Catch-all para callbacks de WhatsApp (interactive, location, etc.)
Route::any('/whatsapp-inbox/interactive/{any}', function () {
    return response('', 200);
})->where('any', '.*');
Route::any('/whatsapp-inbox/location/{any}', function () {
    return response('', 200);
})->where('any', '.*');

// =====================================================
// RUTAS PROTEGIDAS (Opcional - descomenta si necesitas autenticación)
// =====================================================
/*
Route::middleware('auth:sanctum')->group(function () {
    // Aquí puedes poner rutas que requieran autenticación
    // Ejemplo: Route::apiResource('admin/usuarios', AdminUsuarioController::class);
});
*/

// === RUTAS UNICAS API ASISTENCIA ===
Route::post('/setup/restore-backup', [\App\Http\Controllers\SetupController::class, 'restoreBackup'])->name('api.setup.restore-backup');
Route::post('/upload-temp', function (Request $request) {
    $file = $request->file('file');
    $filename = time() . '_' . $file->getClientOriginalName();
    $path = $file->storeAs('temp', $filename, 'public');
    return response()->json(['url' => Storage::url($path)]);
});
Route::post('/blog/robot/draft', [\App\Http\Controllers\Api\BlogRobotController::class, 'storeDraft'])->name('api.blog.robot.draft');
Route::post('/blog/track/interest', [\App\Http\Controllers\Api\NewsletterTrackingController::class, 'reportInterest'])->name('api.blog.track.interest');
// Contpaqi
Route::get('/contpaqi/status', [\App\Http\Controllers\Api\ContpaqiController::class, 'status'])->name('api.contpaqi.status');
Route::post('/cfdi/{uuid}/cancelar-contpaqi', [\App\Http\Controllers\Api\ContpaqiController::class, 'cancelarFactura'])->name('api.cfdi.cancelar-contpaqi');
Route::post('/ventas/{id}/timbrar-contpaqi', [\App\Http\Controllers\Api\ContpaqiController::class, 'timbrarVenta'])->name('api.ventas.timbrar-contpaqi');
// RustDesk
Route::post('/rustdesk/login', [RustDeskController::class, 'login'])->name('api.rustdesk.login');
Route::middleware('auth:sanctum')->prefix('rustdesk')->name('api.rustdesk.')->group(function () {
    Route::get('/status/{rustdeskId}', [RustDeskController::class, 'status'])->name('status');
    Route::get('/devices', [RustDeskController::class, 'devices'])->name('devices');
    Route::post('/sync-alias', [RustDeskController::class, 'syncAlias'])->name('sync-alias');
    Route::post('/sessions/start', [RustDeskController::class, 'startSession'])->name('sessions.start');
    Route::post('/sessions/{session}/complete', [RustDeskController::class, 'completeSession'])->name('sessions.complete');
});
// CVA Proxy (ecommerce)
// Microsoft auth
Route::get('/auth/microsoft', [SocialAuthController::class, 'redirectToMicrosoft'])->name('auth.microsoft');
Route::get('/auth/microsoft/callback', [SocialAuthController::class, 'handleMicrosoftCallback']);

<?php

use App\Http\Controllers\Admin\PosController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\AjusteInventarioController;
use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\BitacoraActividadController;
use App\Http\Controllers\CajaChicaController;
use App\Http\Controllers\CarroController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\CategoriaHerramientaController;
use App\Http\Controllers\CfdiController;
use App\Http\Controllers\ContratoController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ClienteCreditoPDFController;
use App\Http\Controllers\ClienteDocumentoController;
use App\Http\Controllers\ClienteHubController;
use App\Http\Controllers\ComisionController;
use App\Http\Controllers\CompraCfdiController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\CompraEstadoController;
use App\Http\Controllers\ConciliacionBancariaController;
use App\Http\Controllers\CotizacionAccionController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\CotizacionConversionController;
use App\Http\Controllers\CotizacionDocumentoController;
use App\Http\Controllers\CredencialController;
use App\Http\Controllers\CuentaBancariaController;
use App\Http\Controllers\CuentasPorCobrarController;
use App\Http\Controllers\CuentasPorPagarController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\DeviceSessionController;
use App\Http\Controllers\DisponibilidadTecnicoController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\EmpresasController;
use App\Http\Controllers\EntregaDineroController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\GarantiaController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\GestionHerramientasController;
use App\Http\Controllers\HerramientaController;
use App\Http\Controllers\KitController;
use App\Http\Controllers\MantenimientoController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\MovimientoInventarioController;
use App\Http\Controllers\NominaController;
use App\Http\Controllers\OrdenCompraController;
use App\Http\Controllers\PagoPrestamoController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\PedidoDocumentoController;
use App\Http\Controllers\PedidoEstadoController;
use App\Http\Controllers\PedidoVentaController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\PlanPolizaController;
use App\Http\Controllers\PlanRentaController;
use App\Http\Controllers\PolizaServicioController;
use App\Http\Controllers\PolizaServicioPDFController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ProyectoTareaController;
use App\Http\Controllers\RegistroVacacionesController;
use App\Http\Controllers\RentasContratoController;
use App\Http\Controllers\RentasController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ReporteMovimientosController;
use App\Http\Controllers\ReportesDashboardController;
use App\Http\Controllers\ReportesInventarioController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SatCatalogosController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\TecnicoController;
use App\Http\Controllers\TraspasoBancarioController;
use App\Http\Controllers\TraspasoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserNotificationController;
use App\Http\Controllers\VacacionController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\VentaDocumentoController;
use App\Http\Controllers\ContabilidadController;
use App\Http\Controllers\ComisionesController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Forzar patrón numérico para {herramienta}
Route::pattern('herramienta', '[0-9]+');

// Middleware de Exportación (Nivel superior en auth)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/clientes/export', [ClienteController::class, 'export'])->name('clientes.export')->middleware('can:export clientes');
    Route::get('/proveedores/export', [ProveedorController::class, 'export'])->name('proveedores.export')->middleware('can:export proveedores');
    Route::get('/tecnicos/export', [TecnicoController::class, 'export'])->name('tecnicos.export')->middleware('can:export tecnicos');
    Route::get('/usuarios/export', [UserController::class, 'export'])->name('usuarios.export')->middleware('can:export usuarios');
    Route::get('/citas/export', [CitaController::class, 'export'])->name('citas.export');
    Route::get('/productos/export', [ProductoController::class, 'export'])->name('productos.export')->middleware('can:export productos');

    // Pantalla inicial para usuarios autenticados sin contexto de empresa.
    Route::get('/empresas', [EmpresasController::class, 'index'])->name('empresas.index');
    Route::post('/empresas', [EmpresasController::class, 'store'])->name('empresas.store')->middleware('role:admin|super-admin');
});

Route::middleware(['auth'])->group(function () {

    // Dashboard y Panel
    Route::get('/offline', function () {
        return Inertia::render('Offline');
    })->name('offline');

    Route::get('/dashboard', function () {
        return redirect()->route('panel');
    })->name('dashboard');

    Route::get('/panel', [PanelController::class, 'index'])->name('panel');
    Route::get('/dispositivos', [DeviceSessionController::class, 'webView'])->name('dispositivos.index');



    // Comisiones
    Route::prefix('comisiones')->group(function () {
        Route::get('/', [ComisionController::class, 'index'])->name('comisiones.index');
        Route::get('/historial', [ComisionController::class, 'historial'])->name('comisiones.historial');
        Route::get('/vendedor/{vendedorType}/{vendedorId}', [ComisionController::class, 'show'])->name('comisiones.show');
        Route::post('/pagar', [ComisionController::class, 'pagar'])->name('comisiones.pagar');
        Route::get('/recibo/{pago}', [ComisionController::class, 'recibo'])->name('comisiones.recibo');
    });

    // Contabilidad
    Route::prefix('contabilidad')->name('contabilidad.')->group(function () {
        Route::get('/polizas', [ContabilidadController::class, 'index'])->name('index');
        Route::post('/polizas', [ContabilidadController::class, 'store'])->name('store');
        Route::get('/saldos-xml', [ContabilidadController::class, 'saldosXmlPage'])->name('saldos-xml');
        Route::get('/polizas/{poliza}', [ContabilidadController::class, 'show'])->name('show');
        Route::put('/polizas/{poliza}', [ContabilidadController::class, 'update'])->name('update');
        Route::delete('/polizas/{poliza}', [ContabilidadController::class, 'destroy'])->name('destroy');
        Route::post('/polizas/{poliza}/soportes', [ContabilidadController::class, 'uploadSoportes'])->name('polizas.soportes');
        Route::delete('/polizas/{poliza}/soportes/{index}', [ContabilidadController::class, 'destroySoporte'])->name('polizas.soportes.destroy');
        Route::get('/catalogo', [ContabilidadController::class, 'catalog'])->name('catalog');
        Route::get('/catalogo/pdf', [ContabilidadController::class, 'catalogPdf'])->name('catalog.pdf');
        Route::post('/cuentas', [ContabilidadController::class, 'storeCuenta'])->name('cuentas.store');
        Route::delete('/cuentas/{cuenta}', [ContabilidadController::class, 'destroyCuenta'])->name('cuentas.destroy');
        Route::post('/preview-xml', [ContabilidadController::class, 'previewXml'])->name('preview-xml');
        Route::post('/upload-xml', [ContabilidadController::class, 'uploadXml'])->name('upload-xml');
        Route::post('/polizas/{poliza}/soportes', [ContabilidadController::class, 'uploadSoportes'])->name('polizas.soportes');
        Route::delete('/polizas/{poliza}/soportes/{index}', [ContabilidadController::class, 'deleteSoporte'])->name('polizas.soportes.destroy');
        
        // Reportes
        Route::get('/reportes/balanza', [ContabilidadController::class, 'balanza'])->name('reportes.balanza');
        Route::get('/reportes/balanza/pdf', [ContabilidadController::class, 'balanzaPdf'])->name('reportes.balanza.pdf');
        Route::get('/reportes/estado-resultados', [ContabilidadController::class, 'estadoResultados'])->name('reportes.estado-resultados');
        Route::get('/reportes/estado-resultados/pdf', [ContabilidadController::class, 'estadoResultadosPdf'])->name('reportes.estado-resultados.pdf');
        Route::post('/api/estado-resultados-ai', [ContabilidadController::class, 'analizarEstadoResultadosAi'])->name('api.estado-resultados-ai');
        Route::post('/api/balanza-ai', [ContabilidadController::class, 'analizarBalanzaAi'])->name('api.balanza-ai');
        Route::get('/reportes/iva-mensual', [ContabilidadController::class, 'ivaMensual'])->name('reportes.iva-mensual');
        Route::get('/api/audit-balance', [ContabilidadController::class, 'auditBalance'])->name('api.audit-balance');
        Route::get('/api/audit-bancos-balanza', [ContabilidadController::class, 'auditBancosBalanza'])->name('api.audit-bancos-balanza');
        Route::get('/api/rayos-x', [ContabilidadController::class, 'rayosX'])->name('api.rayos-x');
        Route::get('/api/rayos-x-ai', [ContabilidadController::class, 'auditRayosXAi'])->name('api.rayos-x-ai');
        Route::get('/api/cuenta-detalle/{cuenta}', [ContabilidadController::class, 'detalleCuenta'])->name('api.cuenta-detalle');
        Route::get('/api/saldos-xml', [ContabilidadController::class, 'saldosXml'])->name('api.saldos-xml');
        Route::get('/api/saldos-xml/detalle/{uuid}', [ContabilidadController::class, 'obtenerDetalleFacturaXml'])->name('api.saldos-xml.detalle');
        Route::post('/api/saldos-xml/enviar-correo/{uuid}', [ContabilidadController::class, 'enviarCorreoXml'])->name('api.saldos-xml.enviar-correo');
        Route::post('/api/saldos-xml/enviar-whatsapp/{uuid}', [ContabilidadController::class, 'enviarWhatsAppXml'])->name('api.saldos-xml.enviar-whatsapp');
        Route::post('/api/conciliar-csv', [ContabilidadController::class, 'conciliarCsv'])->name('api.conciliar-csv');
        Route::post('/api/conciliar-pdf-banco', [ContabilidadController::class, 'procesarEstadoCuentaBancarioPdf'])->name('api.conciliar-pdf-banco');
        Route::post('/api/generar-poliza-bancaria', [ContabilidadController::class, 'generarPolizaBancariaDirecta'])->name('api.generar-poliza-bancaria');
        Route::post('/api/sync-anual', [ContabilidadController::class, 'syncAnualAi'])->name('api.sync-anual');
    });

    // Módulo de Bancos / Tesorería
    Route::prefix('bancos')->name('bancos.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Bancos\BancoController::class, 'index'])->name('index');
        Route::post('/api/cuentas', [\App\Http\Controllers\Bancos\BancoController::class, 'storeCuenta'])->middleware('throttle:30,1')->name('api.cuentas.store');
        Route::put('/api/cuentas/{id}', [\App\Http\Controllers\Bancos\BancoController::class, 'updateCuenta'])->middleware('throttle:30,1')->name('api.cuentas.update');
        Route::delete('/api/cuentas/{id}', [\App\Http\Controllers\Bancos\BancoController::class, 'destroyCuenta'])->middleware('throttle:30,1')->name('api.cuentas.destroy');
        Route::get('/api/movimientos', [\App\Http\Controllers\Bancos\BancoController::class, 'indexMovimientos'])->name('api.movimientos');
        Route::post('/api/movimientos', [\App\Http\Controllers\Bancos\BancoController::class, 'storeMovimiento'])->middleware('throttle:60,1')->name('api.movimientos.store');
        Route::put('/api/movimientos/{id}', [\App\Http\Controllers\Bancos\BancoController::class, 'updateMovimiento'])->middleware('throttle:60,1')->name('api.movimientos.update');
        Route::delete('/api/movimientos/{id}', [\App\Http\Controllers\Bancos\BancoController::class, 'destroyMovimiento'])->middleware('throttle:60,1')->name('api.movimientos.destroy');
        Route::get('/api/entregas-pendientes', [\App\Http\Controllers\Bancos\BancoController::class, 'indexEntregasPendientes'])->name('api.entregas.pendientes');
        Route::post('/api/entregas/{id}/aceptar', [\App\Http\Controllers\Bancos\BancoController::class, 'aceptarEntrega'])->middleware('throttle:30,1')->name('api.entregas.aceptar');
        Route::get('/api/cobranza-por-formalizar', [\App\Http\Controllers\Bancos\BancoController::class, 'indexCobranzaPorFormalizar'])->name('api.cobranza-formalizar');
    });

    require __DIR__.'/admin/crm.php';
    require __DIR__.'/admin/soporte.php';
    require __DIR__.'/admin/marketing.php';

    // Clientes Rutas Específicas
    Route::post('/clientes/validar-rfc', [ClienteController::class, 'validarRfc'])->name('clientes.validarRfc');
    Route::get('/clientes/validar-email', [ClienteController::class, 'validarEmail'])->name('clientes.validarEmail')->middleware('role:ventas|admin|super-admin');
    Route::post('/clientes/search', [ClienteController::class, 'search'])->name('clientes.search')->middleware('role:ventas|admin|super-admin');
    Route::get('/clientes/stats', [ClienteController::class, 'stats'])->name('clientes.stats')->middleware('role:ventas|admin|super-admin');
    Route::post('/clientes/{cliente}/approve', [ClienteController::class, 'approve'])->name('clientes.approve')->middleware('role:ventas|admin|super-admin');
    Route::put('/clientes/{cliente}/toggle', [ClienteController::class, 'toggle'])->name('clientes.toggle')->middleware('role:ventas|admin|super-admin');
    Route::get('/clientes/{cliente}/can-delete', [ClienteController::class, 'canDelete'])->name('clientes.can-delete')->middleware('can:view clientes')->where(['cliente' => '[0-9]+']);

    // Recursos Principales
    Route::get('/ordenescompra/siguiente-numero', [OrdenCompraController::class, 'obtenerSiguienteNumero'])->name('ordenescompra.siguiente-numero')->middleware('can:view ordenes_compra');
    Route::resource('ordenescompra', OrdenCompraController::class)->middleware('can:view ordenes_compra');
    Route::get('ordenescompra/{id}/pdf', [OrdenCompraController::class, 'generarPDF'])->name('ordenescompra.pdf');
    Route::post('ordenescompra/{id}/enviar-compra', [OrdenCompraController::class, 'enviarACompra'])->name('ordenescompra.enviar-compra');
    Route::post('ordenescompra/{id}/recibir-mercancia', [OrdenCompraController::class, 'recibirMercancia'])->name('ordenescompra.recibir-mercancia');
    Route::post('ordenescompra/{id}/cancelar', [OrdenCompraController::class, 'cancelar'])->name('ordenescompra.cancelar');

    Route::post('/clientes/{cliente}/quick-fiscal', [ClienteController::class, 'quickFiscalUpdate'])->name('clientes.quick-fiscal')->middleware('can:view clientes')->where(['cliente' => '[0-9]+']);
    Route::resource('clientes', ClienteController::class)->names('clientes')->middleware('can:view clientes')->where(['cliente' => '[0-9]+']);
    Route::post('clientes/{cliente}/whatsapp', [ClienteController::class, 'initWhatsApp'])->name('clientes.whatsapp');
    Route::get('clientes/{cliente}/descargar-solicitud-firmada', [ClienteController::class, 'descargarSolicitudFirmada'])->name('clientes.descargar-solicitud-firmada');
    Route::get('/clientes/{cliente}/hub', [ClienteHubController::class, 'show'])->name('clientes.hub')->middleware('can:view clientes');
    Route::post('clientes/{cliente}/documentos', [ClienteDocumentoController::class, 'store'])->name('clientes.documentos.store');
    Route::delete('clientes/documentos/{documento}', [ClienteDocumentoController::class, 'destroy'])->name('clientes.documentos.destroy');
    Route::get('clientes/{cliente}/contrato-credito', [ClienteCreditoPDFController::class, 'contrato'])->name('clientes.contrato-credito');
    // Préstamos y Pagos
    Route::prefix('prestamos')->group(function () {
        Route::post('calcular-pagos', [PrestamoController::class, 'calcularPagos'])->name('prestamos.calcular-pagos');
        Route::patch('{prestamo}/cambiar-estado', [PrestamoController::class, 'cambiarEstado'])->name('prestamos.cambiar-estado')->where('prestamo', '[0-9]+');
        Route::get('{prestamo}/pagare', [PrestamoController::class, 'generarPagare'])->name('prestamos.pagare')->where('prestamo', '[0-9]+');
        Route::get('{prestamo}/liquidacion', [PrestamoController::class, 'generarLiquidacion'])->name('prestamos.liquidacion')->where('prestamo', '[0-9]+')->middleware('role:ventas|admin|super-admin');
        Route::post('{prestamo}/enviar-recordatorio-whatsapp', [PrestamoController::class, 'enviarRecordatorioWhatsApp'])->name('prestamos.enviar-recordatorio-whatsapp')->where('prestamo', '[0-9]+');
    });
    Route::resource('prestamos', PrestamoController::class)->names('prestamos')->middleware('role:ventas|admin|super-admin')->where(['prestamo' => '[0-9]+']);
    Route::prefix('pagos')->group(function () {
        Route::get('comprobante/{historial}', [PagoPrestamoController::class, 'generarComprobante'])->name('pagos.comprobante')->where('historial', '[0-9]+')->middleware('can:view pagos');
    });
    Route::resource('pagos', PagoPrestamoController::class)->names('pagos')->middleware('can:view pagos');

    Route::get('/productos/ajax-catalogs', [ProductoController::class, 'getCatalogs'])->name('productos.ajax-catalogs')->middleware('can:view productos');
    Route::get('/productos/{producto}/series', [ProductoController::class, 'series'])->name('productos.series')->middleware('role:admin|editor|ventas|super-admin');
    Route::post('/productos/{producto}/series', [ProductoController::class, 'storeSeries'])->name('productos.series.store')->middleware('can:edit productos');
    Route::put('/productos/{producto}/series/{serie}', [ProductoController::class, 'updateSerie'])->name('productos.series.update')->whereNumber('serie')->middleware('can:edit productos');
    Route::get('/productos/{id}/stock-detalle', [ProductoController::class, 'getStockDetalle'])->name('productos.stock-detalle')->middleware('can:view productos');
    Route::put('/productos/{producto}/sat', [ProductoController::class, 'updateSat'])->name('productos.sat.update');
    Route::resource('productos', ProductoController::class)->names('productos')->middleware('can:view productos');
    Route::post('/productos/validate-stock', [ProductoController::class, 'validateStock'])->name('productos.validateStock')->middleware('can:view productos');
    Route::post('/productos/recalcular-precios', [ProductoController::class, 'recalcularPrecios'])->name('productos.recalcular-precios')->middleware('can:edit productos');
    Route::put('/productos/{producto}/toggle', [ProductoController::class, 'toggle'])->name('productos.toggle')->middleware('can:edit productos');
    Route::put('/productos/{producto}/toggle-destacado', [ProductoController::class, 'toggleDestacado'])->name('productos.toggle-destacado')->middleware('can:edit productos');
    Route::put('/productos/{producto}/toggle-catalogo-web', [ProductoController::class, 'toggleCatalogoWeb'])->name('productos.toggle-catalogo-web')->middleware('can:edit productos');
    Route::put('/productos/{producto}/prices', [ProductoController::class, 'updatePrices'])->name('productos.update-prices')->middleware('can:edit productos');
    
    // Solicitudes de Material
    Route::prefix('admin')->group(function () {
        Route::resource('solicitudes-material', \App\Http\Controllers\SolicitudMaterialController::class)
            ->names('solicitudes-material')
            ->parameters(['solicitudes-material' => 'solicitud']);
    });

    // Integración Mirage (Postventa)
    Route::prefix('mirage')->name('mirage.')->group(function () {
        Route::get('/solicitudes', [\App\Http\Controllers\Admin\MirageController::class, 'index'])->name('solicitudes');
        Route::get('/sync', [\App\Http\Controllers\Admin\MirageController::class, 'syncView'])->name('sync.view');
        // El robot de sincronización
        Route::post('/sync', [\App\Http\Controllers\Admin\MirageController::class, 'sync'])->name('sync');
        Route::post('/store-client', [\App\Http\Controllers\Admin\MirageController::class, 'storeClient'])->name('store-client');
    });

    // Gestión de Blog
    Route::resource('gestion-blog', App\Http\Controllers\Admin\BlogPostController::class)->names('admin.blog');

    Route::resource('proveedores', ProveedorController::class)->names('proveedores')->middleware('can:view proveedores');
    Route::put('/proveedores/{proveedor}/toggle', [ProveedorController::class, 'toggle'])->name('proveedores.toggle');

    Route::resource('categorias', CategoriaController::class)->names('categorias')->middleware('can:view categorias');
    Route::post('almacenes/finalizar-auditoria/{id}', [AlmacenController::class, 'finalizarAuditoria'])->name('almacenes.finalizar-auditoria');
    Route::get('almacenes/exportar-mermas', [AlmacenController::class, 'exportarMermas'])->name('almacenes.exportar-mermas-global');
    Route::get('almacenes/exportar-mermas/{id}', [AlmacenController::class, 'exportarMermas'])->name('almacenes.exportar-mermas');
    Route::post('almacenes/aprobar-auditoria/{movimientoId}', [AlmacenController::class, 'aprobarAuditoria'])->name('almacenes.aprobar-auditoria');
    Route::resource('almacenes', AlmacenController::class)->names('almacenes');
    Route::resource('traspasos', TraspasoController::class)->names('traspasos')->middleware('can:view traspasos');
    Route::resource('movimientos-inventario', MovimientoInventarioController::class)->names('movimientos-inventario')->middleware('can:view movimientos_inventario');

    // Pedidos Tienda Online
    Route::get('/pedidos-online', [App\Http\Controllers\Admin\PedidoOnlineController::class, 'index'])->name('pedidos-online.index');
    Route::get('/pedidos-online/{id}', [App\Http\Controllers\Admin\PedidoOnlineController::class, 'show'])->name('pedidos-online.show');
    Route::post('/pedidos-online/{id}/status', [App\Http\Controllers\Admin\PedidoOnlineController::class, 'updateStatus'])->name('pedidos-online.update-status');

    Route::resource('ajustes-inventario', AjusteInventarioController::class)->names('ajustes-inventario')->middleware('can:view ajustes_inventario');
    
    // Inventario Físico (Auditorías)
    Route::post('inventarios-fisicos/{id}/procesar', [App\Http\Controllers\InventarioFisicoController::class, 'procesar'])->name('inventarios-fisicos.procesar');
    Route::post('inventarios-fisicos/{audit}/items/{item}', [App\Http\Controllers\InventarioFisicoController::class, 'updateItem'])->name('inventarios-fisicos.update-item');
    Route::resource('inventarios-fisicos', App\Http\Controllers\InventarioFisicoController::class)->names('inventarios-fisicos');

    Route::get('/caja-chica/export', [CajaChicaController::class, 'export'])->name('caja-chica.export');
    Route::resource('caja-chica', CajaChicaController::class)->names('caja-chica');

    Route::get('/cotizaciones/siguiente-numero', [CotizacionController::class, 'obtenerSiguienteNumero'])->name('cotizaciones.siguiente-numero')->middleware('can:view cotizaciones');
    Route::resource('cotizaciones', CotizacionController::class)->names('cotizaciones')->parameters(['cotizaciones' => 'cotizacion'])->middleware('can:view cotizaciones');
    Route::post('/cotizaciones/{id}/convertir-a-venta', [CotizacionConversionController::class, 'convertirAVenta'])->name('cotizaciones.convertir-a-venta');
    Route::post('/cotizaciones/{id}/enviar-a-pedido', [CotizacionConversionController::class, 'enviarAPedido'])->name('cotizaciones.enviar-a-pedido');
    Route::post('/cotizaciones/{id}/duplicate', [CotizacionAccionController::class, 'duplicate'])->name('cotizaciones.duplicate');
    Route::post('/cotizaciones/{cotizacion}/cancel', [CotizacionController::class, 'cancel'])->name('cotizaciones.cancel');
    Route::post('/cotizaciones/{cotizacion}/whatsapp-api', [\App\Http\Controllers\Marketing\WhatsAppChatController::class, 'sendCotizacionPdfLink'])->name('cotizaciones.whatsapp-api')->middleware('can:view cotizaciones');
    Route::get('/cotizaciones/{id}/pdf', [CotizacionDocumentoController::class, 'generarPDF'])->name('cotizaciones.pdf');

    Route::get('/pedidos/siguiente-numero', [PedidoController::class, 'obtenerSiguienteNumero'])->name('pedidos.siguiente-numero')->middleware('can:view pedidos');
    Route::post('/pedidos/{id}/enviar-a-venta', [PedidoVentaController::class, 'enviarAVenta'])->name('pedidos.enviar-a-venta')->middleware('can:view pedidos');
    Route::post('/pedidos/{id}/confirmar', [PedidoEstadoController::class, 'confirmar'])->name('pedidos.confirmar')->middleware('can:view pedidos');
    Route::post('/pedidos/{id}/cancel', [PedidoEstadoController::class, 'cancel'])->name('pedidos.cancel')->middleware('can:view pedidos');
    Route::resource('pedidos', PedidoController::class)->names('pedidos')->middleware('can:view pedidos');
    Route::get('/pedidos/{id}/pdf', [PedidoDocumentoController::class, 'generarPDF'])->name('pedidos.pdf');

    Route::post('/citas/{cita}/vincular-venta', [VentaController::class, 'vincularVentaACita'])->name('citas.vincular-venta')->middleware('can:view ventas');
    Route::get('/citas/{cita}/ventas-cliente-candidatas', [VentaController::class, 'ventasClienteCandidatasParaCita'])->name('citas.ventas-cliente-candidatas')->middleware('can:view ventas');

    Route::get('/ventas/siguiente-numero', [VentaController::class, 'obtenerSiguienteNumero'])->name('ventas.siguiente-numero')->middleware('can:view ventas');
    Route::post('/ventas/validar-series', [VentaController::class, 'validarSeries'])->name('ventas.validar-series')->middleware('can:view ventas');
    Route::post('/ventas/{venta}/facturar', [VentaController::class, 'facturar'])->name('ventas.facturar')->middleware('can:view ventas');
    Route::post('/ventas/{venta}/cancelar', [VentaController::class, 'cancelar'])->name('ventas.cancelar')->middleware('can:view ventas');
    Route::resource('ventas', VentaController::class)->names('ventas')->middleware('can:view ventas');
    Route::post('/ventas/{venta}/marcar-pagado', [VentaController::class, 'marcarPagado'])->name('ventas.marcar-pagado');

    // POS
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index')->middleware('can:view ventas');

    // POS Caja Control
    Route::get('/pos/caja/status', [App\Http\Controllers\CajaSesionController::class, 'status'])->name('pos.caja.status');
    Route::post('/pos/caja/open', [App\Http\Controllers\CajaSesionController::class, 'open'])->name('pos.caja.open');
    Route::get('/pos/caja/closing-details', [App\Http\Controllers\CajaSesionController::class, 'closingDetails'])->name('pos.caja.closing-details');
    Route::post('/pos/caja/close', [App\Http\Controllers\CajaSesionController::class, 'close'])->name('pos.caja.close');

    // ✅ POS CHECKOUT: Ruta especial para guardar ventas del POS (devuelve JSON)
    Route::post('/pos/checkout', [VentaController::class, 'posCheckout'])->name('pos.checkout')->middleware('can:view ventas');
    Route::get('/pos/productos/{producto}/series', [VentaController::class, 'getSeriesDisponibles'])->name('pos.productos.series')->middleware('can:view ventas');
    Route::get('/ventas/{id}/pdf', [VentaDocumentoController::class, 'generarPDF'])->name('ventas.pdf');
    Route::get('/ventas/{id}/ticket', [VentaDocumentoController::class, 'generarTicket'])->name('ventas.ticket');

    // Módulo de Facturación CFDI
    Route::prefix('facturas')->name('facturas.')->middleware('can:view ventas')->group(function () {
        Route::get('/', [\App\Http\Controllers\FacturaController::class, 'index'])->name('index');
        Route::get('/crear', [\App\Http\Controllers\FacturaController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\FacturaController::class, 'store'])->name('store');
        Route::get('/{factura}', [\App\Http\Controllers\FacturaController::class, 'show'])->name('show');
        Route::post('/{factura}/timbrar', [\App\Http\Controllers\FacturaController::class, 'timbrar'])->name('timbrar');
        Route::post('/{factura}/cancelar', [\App\Http\Controllers\FacturaController::class, 'cancelar'])->name('cancelar');
        Route::get('/{factura}/xml', [\App\Http\Controllers\FacturaController::class, 'descargarXML'])->name('xml');
        Route::get('/{id}/pdf', [\App\Http\Controllers\FacturaController::class, 'generarPDF'])->name('pdf');
        Route::get('/{id}/preview', [\App\Http\Controllers\FacturaController::class, 'preview'])->name('preview');
        Route::delete('/{factura}', [\App\Http\Controllers\FacturaController::class, 'destroy'])->name('destroy');
    });

    Route::post('garantias/{id}/crear-cita', [GarantiaController::class, 'crearCitaGarantia'])->name('garantias.crear-cita');
    Route::resource('garantias', GarantiaController::class)->names('garantias')->middleware('can:view garantias');

    Route::prefix('kits')->name('kits.')->middleware('can:view kits')->group(function () {
        Route::get('/', [KitController::class, 'index'])->name('index');
        Route::get('/api/data', [KitController::class, 'apiIndex'])->name('api.data');
        Route::post('/api/calcular-costo', [KitController::class, 'apiCalcularCosto'])->name('api.calcular-costo');
        Route::get('/create', [KitController::class, 'create'])->name('create');
        Route::post('/', [KitController::class, 'store'])->name('store');
        Route::get('/{kit}', [KitController::class, 'show'])->name('show');
        Route::get('/{kit}/edit', [KitController::class, 'edit'])->name('edit');
        Route::put('/{kit}', [KitController::class, 'update'])->name('update');
        Route::delete('/{kit}', [KitController::class, 'destroy'])->name('destroy');
        Route::put('/{kit}/toggle-destacado', [KitController::class, 'toggleDestacado'])->name('toggle-destacado');
    });

    Route::put('/servicios/{servicio}/sat', [ServicioController::class, 'updateSat'])->name('servicios.sat.update');
    Route::resource('servicios', ServicioController::class)->names('servicios')->middleware('can:view servicios');
    Route::resource('usuarios', UserController::class)->names('usuarios');
    Route::put('/usuarios/{user}/toggle', [UserController::class, 'toggle'])->name('usuarios.toggle')->middleware('can:edit usuarios');
    Route::post('/usuarios/{user}/sync-permissions', [UserController::class, 'syncPermissions'])->name('usuarios.sync-permissions')->middleware('can:edit usuarios');
    Route::post('/usuarios/{user}/update-almacen-venta', [UserController::class, 'updateUserAlmacenVenta'])->name('usuarios.update-almacen-venta')->middleware('can:edit usuarios');
    Route::post('/usuarios/{user}/update-almacen-compra', [UserController::class, 'updateUserAlmacenCompra'])->name('usuarios.update-almacen-compra')->middleware('can:edit usuarios');
    Route::resource('roles', RoleController::class)->names('roles')->middleware('can:view roles');

    Route::get('/compras/siguiente-numero', [CompraController::class, 'obtenerSiguienteNumero'])->name('compras.siguiente-numero')->middleware('can:view compras');
    Route::get('/compras/received-cfdis', [CompraCfdiController::class, 'getReceivedCfdis'])->name('compras.received-cfdis')->middleware('can:view compras');
    Route::post('/compras/parse-xml', [CompraCfdiController::class, 'parseXmlCfdi'])->name('compras.parse-xml')->middleware('can:edit compras');
    Route::resource('compras', CompraController::class)->names('compras')->middleware('can:view compras');
    Route::post('/compras/{id}/cancel', [CompraEstadoController::class, 'cancel'])->name('compras.cancel');

    Route::post('/gastos/parse-xml', [GastoController::class, 'parseXmlCfdi'])->name('gastos.parse-xml')->middleware('can:edit gastos');
    Route::get('/gastos/exportar/excel', [GastoController::class, 'exportExcel'])->name('gastos.export.excel');
    Route::get('/gastos/balances/{userId}', [GastoController::class, 'userBalances'])->name('gastos.balances');
    Route::resource('gastos', GastoController::class)->names('gastos')->middleware('can:view gastos');

    Route::prefix('conciliacion-bancaria')->middleware('can:view conciliacion_bancaria')->group(function () {
        Route::get('/', [ConciliacionBancariaController::class, 'index'])->name('conciliacion.index');
        Route::post('/importar', [ConciliacionBancariaController::class, 'importar'])->name('conciliacion.importar');
        Route::post('/conciliar', [ConciliacionBancariaController::class, 'conciliar'])->name('conciliacion.conciliar');
        Route::get('/ai-analisis', [ConciliacionBancariaController::class, 'analizarConciliacionAi'])->name('conciliacion.ai-analisis');
    });

    Route::get('cuentas-bancarias/{cuentas_bancaria}/movimientos', [CuentaBancariaController::class, 'movimientos'])->name('cuentas-bancarias.movimientos');
    Route::post('cuentas-bancarias/{cuentas_bancaria}/registrar-movimiento', [CuentaBancariaController::class, 'registrarMovimientoManual'])->name('cuentas-bancarias.registrar-movimiento');
    // Ruta explícita para evitar conflicto con resource binding
    Route::get('cuentas-bancarias/activas', [CuentaBancariaController::class, 'activas'])->name('cuentas-bancarias.activas');
    Route::post('cuentas-bancarias/traspaso', [CuentaBancariaController::class, 'traspaso'])->name('cuentas-bancarias.traspaso');
    Route::resource('cuentas-bancarias', CuentaBancariaController::class)->names('cuentas-bancarias');
    Route::get('cuentas-por-pagar/get-payment-cfdis', [CuentasPorPagarController::class, 'getPaymentCfdis'])->name('cuentas-por-pagar.get-payment-cfdis');
    Route::post('cuentas-por-pagar/import-payment-xml', [CuentasPorPagarController::class, 'importPaymentXml'])->name('cuentas-por-pagar.import-payment-xml');
    Route::post('cuentas-por-pagar/process-payment-cfdi', [CuentasPorPagarController::class, 'processPaymentCfdi'])->name('cuentas-por-pagar.process-payment-cfdi');
    Route::post('cuentas-por-pagar/apply-payments-xml', [CuentasPorPagarController::class, 'applyPaymentsFromXml'])->name('cuentas-por-pagar.apply-payments-xml');
    Route::post('cuentas-por-pagar/{id}/registrar-pago', [CuentasPorPagarController::class, 'registrarPago'])->name('cuentas-por-pagar.registrar-pago');
    Route::post('cuentas-por-pagar/{id}/marcar-pagado', [CuentasPorPagarController::class, 'marcarPagado'])->name('cuentas-por-pagar.marcar-pagado');
    Route::post('cuentas-por-pagar/{id}/cancelar', [CuentasPorPagarController::class, 'cancelar'])->name('cuentas-por-pagar.cancelar');
    Route::resource('cuentas-por-pagar', CuentasPorPagarController::class)->names('cuentas-por-pagar');

    // Rutas específicas de Cuentas por Cobrar
    Route::post('cuentas-por-cobrar/import-payment-xml', [CuentasPorCobrarController::class, 'importPaymentXml'])->name('cuentas-por-cobrar.import-payment-xml');
    Route::post('cuentas-por-cobrar/apply-payments-xml', [CuentasPorCobrarController::class, 'applyPaymentsFromXml'])->name('cuentas-por-cobrar.apply-payments-xml');
    Route::post('cuentas-por-cobrar/{id}/registrar-pago', [CuentasPorCobrarController::class, 'registrarPago'])->name('cuentas-por-cobrar.registrar-pago');
    Route::post('cuentas-por-cobrar/anular-pago/{id}', [CuentasPorCobrarController::class, 'anularPago'])->name('cuentas-por-cobrar.anular-pago')->middleware('role:admin|super-admin');
    Route::post('cuentas-por-cobrar/{id}/timbrar-rep', [CuentasPorCobrarController::class, 'timbrarReciboPago'])->name('cuentas-por-cobrar.timbrar-rep');

    Route::resource('cuentas-por-cobrar', CuentasPorCobrarController::class)->names('cuentas-por-cobrar');
    Route::resource('traspasos-bancarios', TraspasoBancarioController::class)->names('traspasos-bancarios');
    // Reportes Operativos
    Route::prefix('reportes')->name('reportes.')->middleware('role:admin|super-admin|ventas')->group(function () {
        Route::get('/citas-por-tecnico', [ReporteController::class, 'citasPorTecnicoDetalle'])->name('citas-por-tecnico');
        Route::get('/ventas-semana', [ReporteController::class, 'ventasSemanaOperativo'])->name('ventas-semana');
        Route::get('/productos-para-comprar', [ReporteController::class, 'productosParaComprar'])->name('productos-para-comprar');
        Route::get('/ventas-utilidad', [ReporteController::class, 'reporteVentasUtilidad'])->name('ventas-utilidad');
    });


    Route::resource('tecnicos', TecnicoController::class)->names('tecnicos');
    Route::put('/tecnicos/{tecnico}/toggle', [TecnicoController::class, 'toggle'])->name('tecnicos.toggle');

    // Herramientas Rutas Adicionales
    Route::get('herramientas/dashboard', [HerramientaController::class, 'dashboard'])->name('herramientas.dashboard');
    Route::get('herramientas/alertas', [HerramientaController::class, 'alertas'])->name('herramientas.alertas');
    Route::get('herramientas/historial', [HerramientaController::class, 'historial'])->name('herramientas.historial');
    Route::get('herramientas/mantenimiento-lista', [HerramientaController::class, 'mantenimiento'])->name('herramientas.mantenimiento');
    Route::post('herramientas/{herramienta}/registrar-mantenimiento', [HerramientaController::class, 'registrarMantenimiento'])->name('herramientas.registrar-mantenimiento');
    Route::get('herramientas/reportes', [HerramientaController::class, 'reportes'])->name('herramientas.reportes');

    Route::patch('herramientas/categorias/{categoria}/toggle', [CategoriaHerramientaController::class, 'toggle'])->name('herramientas.categorias.toggle');
    Route::resource('herramientas/categorias', CategoriaHerramientaController::class)->names('herramientas.categorias');

    Route::post('herramientas/bulk-reassign', [HerramientaController::class, 'bulkReassign'])->name('herramientas.bulk-reassign');
    Route::resource('herramientas', HerramientaController::class)->names('herramientas');

    // Gestión de asignaciones
    Route::get('herramientas/gestion', [GestionHerramientasController::class, 'index'])->name('herramientas.gestion.index');
    Route::get('herramientas/gestion/create', [GestionHerramientasController::class, 'create'])->name('herramientas.gestion.create');
    Route::post('herramientas/gestion/asignar', [GestionHerramientasController::class, 'asignar'])->name('herramientas.gestion.asignar');
    Route::get('herramientas/gestion/{tecnico}/edit', [GestionHerramientasController::class, 'edit'])->name('herramientas.gestion.edit');
    Route::put('herramientas/gestion/{tecnico}', [GestionHerramientasController::class, 'update'])->name('herramientas.gestion.update');
    Route::post('herramientas/reasignar', [GestionHerramientasController::class, 'reasignar'])->name('herramientas.gestion.reasignar');
    Route::get('herramientas/gestion/{tecnico}/exportar', [GestionHerramientasController::class, 'exportarPorTecnico'])->name('herramientas.gestion.exportar');
    Route::get('herramientas/gestion/{tecnico}/descargar-reporte', [GestionHerramientasController::class, 'descargarReporteTecnico'])->name('herramientas.gestion.descargar-reporte');

    Route::get('/citas/calendario', [CitaController::class, 'calendario'])->name('citas.calendario')->middleware('role:ventas|admin|super-admin|tecnico|editor');
    Route::get('/citas/check-visits-limit', [CitaController::class, 'checkVisitsLimit'])->name('citas.check-visits-limit');
    Route::get('/citas/{cita}/descargar-evidencias', [CitaController::class, 'downloadEvidenciasCita'])->name('citas.download-evidencias')->middleware('role:ventas|admin|super-admin|tecnico|editor');
    // Deben ir antes del resource para no colisionar con {cita} (show/edit/update)
    Route::post('citas/{cita}/cambiar-estado', [CitaController::class, 'changeStatus'])->name('citas.cambiar-estado')->middleware('role:ventas|admin|super-admin|tecnico|editor');
    Route::post('citas/{cita}/iniciar', [CitaController::class, 'iniciar'])->name('citas.iniciar')->middleware('role:ventas|admin|super-admin|tecnico|editor');
    Route::post('citas/{cita}/completar', [CitaController::class, 'completar'])->name('citas.completar')->middleware('role:ventas|admin|super-admin|tecnico|editor');
    Route::post('citas/{cita}/cancelar', [CitaController::class, 'cancelar'])->name('citas.cancelar')->middleware('role:ventas|admin|super-admin|tecnico|editor');
    Route::get('citas/{cita}/recordatorio-reprogramacion', [CitaController::class, 'enviarRecordatorioReprogramacion'])->name('citas.recordatorio-reprogramacion')->middleware('role:ventas|admin|super-admin|tecnico|editor');
    Route::resource('citas', CitaController::class)->names('citas')->middleware('role:ventas|admin|super-admin|tecnico|editor')->whereNumber('cita');
    Route::get('/mi-agenda', [CitaController::class, 'miAgenda'])->name('citas.mi-agenda')->middleware('role:ventas|admin|super-admin|tecnico|editor');

    Route::get('/disponibilidad-tecnicos', [DisponibilidadTecnicoController::class, 'index'])->name('disponibilidad-tecnicos.index')->middleware('role:admin|super-admin');
    Route::resource('carros', CarroController::class)->names('carros')->middleware('role:admin|editor|super-admin');

    Route::get('mantenimientos/export', [MantenimientoController::class, 'export'])->name('mantenimientos.export')->middleware('role:admin|editor|super-admin');
    Route::post('mantenimientos/validar-servicio', [MantenimientoController::class, 'validarServicio'])->name('mantenimientos.validar-servicio')->middleware('role:admin|editor|super-admin');
    Route::get('mantenimientos/api/{carro}/servicios/{tipo_servicio}', [MantenimientoController::class, 'getServiciosPorTipo'])
        ->where('tipo_servicio', '.*')
        ->name('mantenimientos.api.servicios-por-tipo')
        ->middleware('role:admin|editor|super-admin');
    Route::patch('mantenimientos/{mantenimiento}/completar', [MantenimientoController::class, 'completar'])->name('mantenimientos.completar')->middleware('role:admin|editor|super-admin');
    Route::patch('mantenimientos/{mantenimiento}/posponer', [MantenimientoController::class, 'posponer'])->name('mantenimientos.posponer')->middleware('role:admin|editor|super-admin');
    Route::patch('mantenimientos/{mantenimiento}/reprogramar', [MantenimientoController::class, 'reprogramar'])->name('mantenimientos.reprogramar')->middleware('role:admin|editor|super-admin');
    Route::resource('mantenimientos', MantenimientoController::class)->names('mantenimientos')->middleware('role:admin|editor|super-admin');
    Route::resource('equipos', EquipoController::class)->middleware('role:admin|editor|super-admin');
    Route::resource('rentas', RentasController::class)->middleware('role:admin|editor|super-admin');
    Route::get('/rentas/{renta}/contrato', [RentasContratoController::class, 'contratoPDF'])->name('rentas.contrato');

    // Taller
    Route::get('taller/{taller}/reporte', [\App\Http\Controllers\TallerController::class, 'reporte'])->name('taller.reporte');
    Route::post('taller/{taller}/facturar', [\App\Http\Controllers\TallerController::class, 'facturar'])->name('taller.facturar');
    Route::resource('taller', \App\Http\Controllers\TallerController::class)->names('taller');

    // Dashboard Técnico de Mantenimientos (Pólizas)
    Route::prefix('tecnico/mantenimientos')->name('admin.mantenimientos.tecnico.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PolizaMantenimientoTecnicoController::class, 'index'])->name('index');
        Route::post('/{id}/tomar', [\App\Http\Controllers\Admin\PolizaMantenimientoTecnicoController::class, 'tomarTarea'])->name('tomar');
        Route::post('/{id}/completar', [\App\Http\Controllers\Admin\PolizaMantenimientoTecnicoController::class, 'completar'])->name('completar');
    });

    Route::get('/polizas-servicio/dashboard', [PolizaServicioController::class, 'dashboard'])->name('polizas-servicio.dashboard');
    Route::get('/polizas-servicio/rentabilidad', [PolizaServicioController::class, 'reporteRentabilidad'])->name('polizas-servicio.rentabilidad');
    Route::get('/polizas-servicio/{polizaServicio}/historial', [PolizaServicioController::class, 'historialConsumo'])->name('polizas-servicio.historial');
    Route::get('/polizas-servicio/{polizaServicio}/pdf-beneficios', [PolizaServicioPDFController::class, 'beneficios'])->name('polizas-servicio.pdf-beneficios');
    Route::get('/polizas-servicio/{polizaServicio}/pdf-contrato', [PolizaServicioPDFController::class, 'contrato'])->name('polizas-servicio.pdf-contrato');
    Route::post('/polizas-servicio/{polizaServicio}/generar-cobro', [PolizaServicioController::class, 'generarCobro'])->name('polizas-servicio.generar-cobro');
    Route::post('/polizas-servicio/{polizaServicio}/enviar-recordatorio', [PolizaServicioController::class, 'enviarRecordatorioRenovacion'])->name('polizas-servicio.enviar-recordatorio');
    Route::resource('polizas-servicio', PolizaServicioController::class)->middleware('role:admin|editor|super-admin');
    Route::resource('planes-poliza', PlanPolizaController::class)->middleware('role:admin|editor|super-admin');
    Route::put('/planes-poliza/{planes_poliza}/toggle-destacado', [PlanPolizaController::class, 'toggleDestacado'])->name('planes-poliza.toggle-destacado');

    Route::resource('planes-renta', PlanRentaController::class)->middleware('role:admin|editor|super-admin');
    Route::put('/planes-renta/{planes_renta}/toggle', [PlanRentaController::class, 'toggle']);

    Route::resource('entregas-dinero', EntregaDineroController::class)->middleware('role:admin|ventas|super-admin|tesorero-efectivo');
    Route::post('/entregas-dinero/lote', [EntregaDineroController::class, 'entregarLote'])->name('entregas-dinero.lote')->middleware('role:admin|ventas|super-admin|tesorero-efectivo');
    Route::post('/entregas-dinero/{id}/marcar-recibido', [EntregaDineroController::class, 'marcarRecibido'])->name('entregas-dinero.marcar-recibido')->middleware('role:admin|super-admin|tesorero-efectivo');
    // Auditoría Automática (Logs del sistema)
    Route::get('auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
    Route::delete('auditoria/clear', [AuditoriaController::class, 'clear'])->name('auditoria.clear');

    // Bitácora de Actividades (Técnicas / Tareas)
    Route::resource('bitacora', BitacoraActividadController::class)->names('bitacora');
    Route::patch('bitacora/{bitacora}/cambiar-estado', [BitacoraActividadController::class, 'cambiarEstado'])->name('bitacora.cambiar-estado');
    Route::get('bitacora-exportar', [BitacoraActividadController::class, 'export'])->name('bitacora.export');

    require __DIR__.'/admin/empresa.php';

    // SAT
    Route::prefix('sat')->name('sat.')->group(function () {
        Route::get('/buscar-clave-prod-serv', [SatCatalogosController::class, 'buscarClaveProdServ'])->name('buscar-clave-prod-serv');
    });

    // Vacaciones y RRHH
    // Reloj checador / bitácora (enlaces del sidebar)
    Route::get('/asistencia', [AsistenciaController::class, 'index'])->name('asistencia.index');
    Route::post('/asistencia', [AsistenciaController::class, 'registrar'])->name('asistencia.registrar');
    Route::get('/asistencia/registros', [AsistenciaController::class, 'registros'])->name('asistencia.registros');

    // Importante: La ruta mis-vacaciones debe ir ANTES del resource 'vacaciones' para evitar conflicto con {vacacione}
    Route::get('/mis-vacaciones', [VacacionController::class, 'misVacaciones'])->name('vacaciones.mis-vacaciones');
    Route::resource('vacaciones', VacacionController::class)->names('vacaciones');
    Route::get('/registro-vacaciones/export', [RegistroVacacionesController::class, 'export'])->name('registro-vacaciones.export');
    Route::resource('registro-vacaciones', RegistroVacacionesController::class)->names('registro-vacaciones');

    // Empleados
    Route::post('empleados/import-xml', [EmpleadoController::class, 'importFromXml'])->name('empleados.import-xml');
    Route::get('empleados/plantillas', [ContratoController::class, 'indexPlantillas'])->name('empleados.plantillas.index');
    Route::post('empleados/plantillas', [ContratoController::class, 'storePlantilla'])->name('empleados.plantillas.store');
    
    // Matriz de Cumplimiento y Acciones Masivas (Fase 4)
    Route::get('empleados/cumplimiento', [ContratoController::class, 'matrizCumplimiento'])->name('empleados.cumplimiento.index');
    Route::post('empleados/cumplimiento/generar-masivo', [ContratoController::class, 'generarMasivo'])->name('empleados.cumplimiento.generar-masivo');
    
    Route::get('empleados/{empleado}/expediente', [ContratoController::class, 'expediente'])->name('empleados.expediente');
    Route::post('empleados/{empleado}/contratos/generar', [ContratoController::class, 'generarDesdePlantilla'])->name('empleados.contratos.generar');
    Route::post('empleados/{empleado}/contratos', [ContratoController::class, 'store'])->name('empleados.contratos.store');
    // --- CUMPLIMIENTO LEGAL & COMISIONES (Fase 6) ---
    Route::prefix('comisiones')->group(function () {
        Route::get('/seguridad-higiene', [ComisionesController::class, 'nom019'])->name('comisiones.nom019');
        Route::get('/recorridos', [ComisionesController::class, 'recorridos'])->name('comisiones.recorridos');
        Route::get('/repse', [\App\Http\Controllers\RepseController::class, 'index'])->name('comisiones.repse');
        Route::get('/repse/mis-contratos/file/{id}', [\App\Http\Controllers\RepseController::class, 'viewContract'])->name('comisiones.repse.my_contracts.file');
        Route::get('/repse/mis-contratos', [\App\Http\Controllers\RepseController::class, 'myContracts'])->name('comisiones.repse.my_contracts');
        Route::post('/repse/mis-contratos', [\App\Http\Controllers\RepseController::class, 'storeContract'])->name('comisiones.repse.my_contracts.store');
        Route::patch('/repse/mis-contratos/{id}', [\App\Http\Controllers\RepseController::class, 'updateContract'])->name('comisiones.repse.my_contracts.update');
        Route::get('/repse/mis-contratos/{contract}/export', [\App\Http\Controllers\RepseController::class, 'exportICSOE'])->name('comisiones.repse.my_contracts.export');
        Route::get('/repse/mis-contratos/{contract}/sisub', [\App\Http\Controllers\RepseController::class, 'exportSISUB'])->name('comisiones.repse.my_contracts.sisub');
        Route::get('/repse/export-global-icsoe', [\App\Http\Controllers\RepseController::class, 'exportGlobalICSOE'])->name('comisiones.repse.export_global_icsoe');
        Route::get('/repse/export-global-sisub', [\App\Http\Controllers\RepseController::class, 'exportGlobalSISUB'])->name('comisiones.repse.export_global_sisub');
        Route::post('/repse/mis-contratos/{contract}/evidence', [\App\Http\Controllers\RepseController::class, 'storeEvidence'])->name('comisiones.repse.my_contracts.evidence.store');
        Route::get('/repse/{contratista}', [\App\Http\Controllers\RepseController::class, 'show'])->name('comisiones.repse.show');
        Route::get('/repse/{contratista}/dossier', [\App\Http\Controllers\RepseController::class, 'exportDossier'])->name('comisiones.repse.dossier');
        Route::post('/repse/{contratista}/doc', [\App\Http\Controllers\RepseController::class, 'storeDoc'])->name('comisiones.repse.doc.store');
        Route::post('/repse/doc/{doc}/status', [\App\Http\Controllers\RepseController::class, 'updateDocStatus'])->name('comisiones.repse.doc.status');
        Route::get('/repse/proveedor/{proveedor}/toggle', [\App\Http\Controllers\RepseController::class, 'toggleRepse']);
        Route::post('/repse/proveedor/{proveedor}/toggle', [\App\Http\Controllers\RepseController::class, 'toggleRepse'])->name('comisiones.repse.toggle');
        Route::post('/repse/proveedor/{proveedor}/info', [\App\Http\Controllers\RepseController::class, 'updateRepseInfo'])->name('comisiones.repse.info.update');
        Route::get('/vencimientos', [\App\Http\Controllers\RepseController::class, 'vencimientos'])->name('comisiones.vencimientos');
        Route::post('/repse/proveedor/{proveedor}/validate-sat', [\App\Http\Controllers\RepseController::class, 'validateSat'])->name('comisiones.repse.validate_sat');

        // Plantillas de Contratos
        Route::get('/contratos/plantillas', [\App\Http\Controllers\ContratoPlantillaController::class, 'index'])->name('contratos.plantillas.index');
        Route::get('/contratos/plantillas/crear', [\App\Http\Controllers\ContratoPlantillaController::class, 'create'])->name('contratos.plantillas.create');
        Route::get('/contratos/plantillas/{template}/editar', [\App\Http\Controllers\ContratoPlantillaController::class, 'edit'])->name('contratos.plantillas.edit');
        Route::post('/contratos/plantillas', [\App\Http\Controllers\ContratoPlantillaController::class, 'store'])->name('contratos.plantillas.store');
        Route::post('/contratos/plantillas/{template}', [\App\Http\Controllers\ContratoPlantillaController::class, 'update'])->name('contratos.plantillas.update');
        Route::delete('/contratos/plantillas/{template}', [\App\Http\Controllers\ContratoPlantillaController::class, 'destroy'])->name('contratos.plantillas.destroy');

        // Gestión de Contratos Generados para Clientes
        Route::get('/contratos/clientes', [\App\Http\Controllers\ClientContratoController::class, 'index'])->name('contratos.clientes.index');
        Route::post('/contratos/clientes/generar', [\App\Http\Controllers\ClientContratoController::class, 'generate'])->name('contratos.clientes.generate');

        Route::get('/pulse', [ComisionesController::class, 'pulse'])->name('comisiones.pulse');
        Route::get('/pulse/config', [ComisionesController::class, 'pulseConfig'])->name('comisiones.pulse_config');
    });

    Route::resource('empleados', EmpleadoController::class)->names('empleados')->middleware('role:admin|super-admin');

    // Nóminas
    Route::prefix('nominas')->name('nominas.')->middleware('role:admin|super-admin')->group(function () {
        Route::get('/', [NominaController::class, 'index'])->name('index');
        Route::post('/', [NominaController::class, 'store'])->name('store');
        Route::get('/{nomina}', [NominaController::class, 'show'])->name('show');
    });

    // Notificaciones
    Route::get('/notifications', [UserNotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [UserNotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/mark-as-read', [UserNotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/mark-all-as-read', [UserNotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::delete('/notifications/{id}', [UserNotificationController::class, 'destroy'])->name('notifications.destroy');

    // Backup
    Route::middleware(['auth', 'can:manage-backups'])->prefix('admin/backup')->name('backup.')->group(function () {
        Route::get('/', [DatabaseBackupController::class, 'index'])->name('index');
        Route::post('/create', [DatabaseBackupController::class, 'create'])->name('create');
        Route::post('/create-full', [DatabaseBackupController::class, 'createFull'])->name('create-full');
        Route::post('/create-incremental', [DatabaseBackupController::class, 'createIncremental'])->name('create-incremental');
        Route::post('/create-secure', [DatabaseBackupController::class, 'createSecure'])->name('create-secure');
        Route::post('/create-remote', [DatabaseBackupController::class, 'createRemote'])->name('create-remote');
        Route::get('/download/{filename}', [DatabaseBackupController::class, 'download'])->name('download');
        Route::delete('/delete/{filename}', [DatabaseBackupController::class, 'delete'])->name('delete');
        Route::post('/delete-multiple', [DatabaseBackupController::class, 'deleteMultiple'])->name('delete-multiple');
        Route::post('/restore/{filename}', [DatabaseBackupController::class, 'restore'])->name('restore');
        Route::post('/clean', [DatabaseBackupController::class, 'clean'])->name('clean');
        Route::get('/vps-status', [DatabaseBackupController::class, 'vpsStatus'])->name('vps-status');
        Route::get('/stats', [DatabaseBackupController::class, 'stats'])->name('stats');
        Route::get('/verify/{filename}', [DatabaseBackupController::class, 'verify'])->name('verify');
        Route::get('/info/{filename}', [DatabaseBackupController::class, 'info'])->name('info');
        Route::get('/health-report', [DatabaseBackupController::class, 'healthReport'])->name('health-report');
        Route::get('/monitoring', [DatabaseBackupController::class, 'monitoring'])->name('monitoring');
        Route::get('/remote-list', [DatabaseBackupController::class, 'listRemote'])->name('remote.list');
        Route::get('/remote-download/{remotePath}', [DatabaseBackupController::class, 'downloadRemote'])->name('remote.download');
        Route::get('/logs/{type?}', [DatabaseBackupController::class, 'logs'])->name('logs');
    });

    // Reportes (Nuevo)


    // Google Drive API (Movido a admin para mejor soporte de sesión web)
    Route::prefix('api/gdrive')->name('api.gdrive.')->middleware('role:admin|super-admin')->group(function () {
        Route::get('/auth', [\App\Http\Controllers\Api\GoogleDriveController::class, 'auth'])->name('auth');
        Route::get('/callback', [\App\Http\Controllers\Api\GoogleDriveController::class, 'callback'])->name('callback');
        Route::post('/disconnect', [\App\Http\Controllers\Api\GoogleDriveController::class, 'disconnect'])->name('disconnect');
        Route::get('/test', [\App\Http\Controllers\Api\GoogleDriveController::class, 'test'])->name('test');
        Route::get('/list', [\App\Http\Controllers\Api\GoogleDriveController::class, 'list'])->name('list');
        Route::post('/upload', [\App\Http\Controllers\Api\GoogleDriveController::class, 'upload'])->name('upload');
        Route::get('/download', [\App\Http\Controllers\Api\GoogleDriveController::class, 'download'])->name('download');
        Route::post('/delete', [\App\Http\Controllers\Api\GoogleDriveController::class, 'delete'])->name('delete');
    });

    // MEGA Cloud API (Solo Admin)
    Route::prefix('api/mega')->name('api.mega.')->middleware('role:admin|super-admin')->group(function () {
        Route::post('/test-connection', [\App\Http\Controllers\Api\MegaController::class, 'testConnection'])->name('test');
        Route::get('/list', [\App\Http\Controllers\Api\MegaController::class, 'list'])->name('list');
        Route::get('/download', [\App\Http\Controllers\Api\MegaController::class, 'download'])->name('download');
        Route::post('/delete', [\App\Http\Controllers\Api\MegaController::class, 'delete'])->name('delete');
        Route::post('/upload', [\App\Http\Controllers\Api\MegaController::class, 'upload'])->name('upload');
    });

    // Recálculo de precios
    Route::prefix('api/precios')->name('api.precios.')->middleware('role:admin|super-admin')->group(function () {
        Route::post('/recalcular', [\App\Http\Controllers\Api\PrecioController::class, 'recalcular'])->name('recalcular');
    });

    // Prueba de WhatsApp
    Route::post('api/whatsapp/test', [\App\Http\Controllers\EmpresaWhatsAppController::class, 'test'])
        ->middleware(['role:admin|super-admin', 'throttle:10,1'])
        ->name('whatsapp.test');

    // CFDI
    Route::get('/cfdi', [CfdiController::class, 'index'])->name('cfdi.index');
    Route::post('/cfdi', [CfdiController::class, 'store'])->name('cfdi.store');
    Route::get('/cfdi/{uuid}/xml', [CfdiController::class, 'descargarXml'])->name('cfdi.xml');
    Route::get('/cfdi/{uuid}/ver-xml', [CfdiController::class, 'verXmlView'])->name('cfdi.ver-xml-view');
    Route::get('/cfdi/{uuid}/pdf', [CfdiController::class, 'verPdf'])->name('cfdi.ver-pdf');
    Route::get('/cfdi/{uuid}/ver-pdf', [CfdiController::class, 'verPdfView'])->name('cfdi.ver-pdf-view');
    Route::post('/cfdi/{id}/check-sat', [CfdiController::class, 'checkSatStatus'])->name('cfdi.check-sat');
    Route::post('/cfdi/{uuid}/create-provider', [CfdiController::class, 'createProviderFromCfdi'])->name('cfdi.create-provider');
    Route::post('/cfdi/{uuid}/enviar-correo', [CfdiController::class, 'enviarCorreo'])->name('cfdi.enviar-correo');
    Route::get('/cfdi/reporte-mensual', [CfdiController::class, 'exportarMensualPdf'])->name('cfdi.reporte-mensual');
    Route::get('/cfdi/{cfdi}', [CfdiController::class, 'show'])->name('cfdi.show');
    Route::delete('/cfdi/{cfdi}', [CfdiController::class, 'destroy'])->name('cfdi.destroy')->middleware('throttle:10,1');

    // Descarga Masiva SAT
    Route::post('/cfdi/descarga-masiva', [CfdiController::class, 'solicitarDescargaMasiva'])->name('cfdi.descarga-masiva');
    Route::post('/cfdi/descarga-masiva/{id}/verificar', [CfdiController::class, 'verificarDescargaMasiva'])->name('cfdi.descarga-masiva.verificar');
    Route::get('/cfdi/descarga-masiva/{id}/detalles', [CfdiController::class, 'getDescargaDetalles'])->name('cfdi.descarga-masiva.detalles');
    Route::post('/cfdi/descarga-masiva/importar', [CfdiController::class, 'importarSeleccionados'])->name('cfdi.descarga-masiva.importar');
    Route::delete('/cfdi/descarga-masiva/{descarga}', [CfdiController::class, 'eliminarDescargaMasiva'])->name('cfdi.descarga-masiva.destroy');
    Route::delete('/cfdi/descarga-masiva', [CfdiController::class, 'eliminarDescargasMasivas'])->name('cfdi.descarga-masiva.bulk-destroy');
    Route::post('/cfdi/descarga-masiva/{descarga}/reintentar', [CfdiController::class, 'reintentarDescargaMasiva'])->name('cfdi.descarga-masiva.reintentar');

    // Bulk y utilerías
    Route::post('/cfdi/preview-xml', [CfdiController::class, 'previewXml'])->name('cfdi.preview-xml');
    Route::post('/cfdi/bulk-check-sat', [CfdiController::class, 'bulkCheckSatStatus'])->name('cfdi.bulk-check-sat');
    Route::post('/cfdi/bulk-send-email', [CfdiController::class, 'bulkSendEmail'])->name('cfdi.bulk-send-email');
    Route::post('/cfdi/bulk-download', [CfdiController::class, 'bulkDownload'])->name('cfdi.bulk-download');
    Route::post('/cfdi/bulk-destroy', [CfdiController::class, 'bulkDestroy'])->name('cfdi.bulk-destroy');
    Route::delete('/cfdi/{cfdi}/poliza', [CfdiController::class, 'anularPolizaDeCfdi'])->name('cfdi.anular-poliza');

    // Contabilidad
    Route::post('/cfdi/{uuid}/contabilidad/preview', [CfdiController::class, 'previsualizarContabilidad'])->name('cfdi.contabilidad.preview');
    Route::post('/cfdi/{uuid}/contabilidad', [CfdiController::class, 'enviarAContabilidad'])->name('cfdi.contabilidad');
    Route::get('/contabilidad/api/cfdis-pendientes', [ContabilidadController::class, 'cfdisPendientes'])->name('contabilidad.cfdis-pendientes');
    Route::post('/contabilidad/api/integrar-multi', [ContabilidadController::class, 'integrarMultiCfdi'])->name('contabilidad.integrar-multi');
    Route::post('/contabilidad/api/preview-multi', [ContabilidadController::class, 'previewMultiCfdi'])->name('contabilidad.preview-multi');

    // Proyectos
    Route::resource('proyectos', \App\Http\Controllers\ProyectoController::class);
    Route::post('/proyectos/{proyecto}/share', [\App\Http\Controllers\ProyectoController::class, 'share'])->name('proyectos.share');
    Route::delete('/proyectos/{proyecto}/members/{user}', [\App\Http\Controllers\ProyectoController::class, 'removeMember'])->name('proyectos.members.remove');
    Route::post('/proyectos/{proyecto}/productos', [\App\Http\Controllers\ProyectoController::class, 'addProducto'])->name('proyectos.productos.add');
    Route::delete('/proyectos/{proyecto}/productos/{producto}', [\App\Http\Controllers\ProyectoController::class, 'removeProducto'])->name('proyectos.productos.remove');
    Route::post('/proyectos/{proyecto}/gastos', [\App\Http\Controllers\ProyectoController::class, 'addGasto'])->name('proyectos.gastos.add');
    Route::delete('/proyectos/{proyecto}/gastos/{gasto}', [\App\Http\Controllers\ProyectoController::class, 'removeGasto'])->name('proyectos.gastos.remove');
    Route::post('/proyectos/categorias/gasto', [\App\Http\Controllers\ProyectoController::class, 'addCategoriaGasto'])->name('proyectos.categorias.add');

    Route::post('/proyecto/tareas/reorder', [ProyectoTareaController::class, 'reorder'])->name('proyecto.tareas.reorder');
    Route::resource('proyecto/tareas', ProyectoTareaController::class, ['as' => 'proyecto']);

    // Bóveda de Credenciales
    Route::get('/credenciales', [CredencialController::class, 'index'])->name('credenciales.index');
    Route::post('/credenciales', [CredencialController::class, 'store'])->name('credenciales.store');
    Route::get('/credenciales/{credencial}/reveal', [CredencialController::class, 'reveal'])->name('credenciales.reveal');
    Route::put('/credenciales/{credencial}', [CredencialController::class, 'update'])->name('credenciales.update');
    Route::delete('/credenciales/{credencial}', [CredencialController::class, 'destroy'])->name('credenciales.destroy');

    // Perfil
    Route::get('/perfil', [UserController::class, 'profile'])->name('perfil');



    // Presencia de usuarios
    Route::post('/presence/join', [PresenceController::class, 'join'])->name('presence.join');
    Route::post('/presence/leave', [PresenceController::class, 'leave'])->name('presence.leave');

    // Trading y Cripto (Solo Admin/Super Admin)
    Route::prefix('trading')->name('trading.')->middleware('role:admin|super-admin')->group(function () {
        Route::get('/simulacion', [\App\Http\Controllers\TradingController::class, 'simulacion'])->name('simulacion');
        Route::get('/binance', [\App\Http\Controllers\TradingController::class, 'binance'])->name('binance');
        Route::post('/log-performance', [\App\Http\Controllers\TradingController::class, 'logPerformance'])->name('log-performance');
        Route::post('/save-experience', [\App\Http\Controllers\TradingController::class, 'saveExperience'])->name('save-experience');
        Route::get('/get-history', [\App\Http\Controllers\TradingController::class, 'getHistory'])->name('get-history');
        Route::get('/get-weights', [\App\Http\Controllers\TradingController::class, 'getWeights'])->name('get-weights');
        Route::get('/get-api-keys', [\App\Http\Controllers\TradingController::class, 'getApiKeys'])->name('get-api-keys');
        Route::post('/save-api-keys', [\App\Http\Controllers\TradingController::class, 'saveApiKeys'])->name('save-api-keys');
        Route::post('/execute-order', [\App\Http\Controllers\TradingController::class, 'executeOrder'])->name('execute-order');
        Route::get('/binance-balance', [\App\Http\Controllers\TradingController::class, 'getBinanceBalance'])->name('binance-balance');
        Route::get('/executed-orders', [\App\Http\Controllers\TradingController::class, 'getExecutedOrders'])->name('executed-orders');
    });

    // Mis Pendientes (To-do List)
    Route::resource('mis-pendientes', \App\Http\Controllers\Admin\TodoController::class)
        ->names('todos')
        ->parameters(['mis-pendientes' => 'todo']);

    // NOM-035
    Route::prefix('nom035')->name('nom035.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Nom035Controller::class, 'index'])->name('index');
        // ... (rest of nom035 routes)
    });
    // ...
});

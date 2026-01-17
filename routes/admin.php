<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

use App\Http\Controllers\SatCatalogosController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ClienteDocumentoController;
use App\Http\Controllers\ClienteCreditoPDFController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\PagoPrestamoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\CotizacionConversionController;
use App\Http\Controllers\CotizacionAccionController;
use App\Http\Controllers\CotizacionDocumentoController;
use App\Http\Controllers\CotizacionBorradorController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\PedidoEstadoController;
use App\Http\Controllers\PedidoVentaController;
use App\Http\Controllers\PedidoAccionController;
use App\Http\Controllers\PedidoDocumentoController;
use App\Http\Controllers\UserNotificationController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\VentaDocumentoController;
use App\Http\Controllers\VentaEstadoController;
use App\Http\Controllers\CuentasPorCobrarController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\CompraCfdiController;
use App\Http\Controllers\CompraSeriesController;
use App\Http\Controllers\CompraEstadoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\TecnicoController;
use App\Http\Controllers\CarroController;
use App\Http\Controllers\MantenimientoController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\EmpresasController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\PolizaServicioController;
use App\Http\Controllers\PolizaServicioPDFController;
use App\Http\Controllers\PlanPolizaController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\OrdenCompraController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\RentasController;
use App\Http\Controllers\RentasContratoController;
use App\Http\Controllers\EntregaDineroController;
use App\Http\Controllers\BitacoraActividadController;
use App\Http\Controllers\CfdiController;
use App\Http\Controllers\ProyectoTareaController;
use App\Http\Controllers\ReporteTecnicoController;
use App\Http\Controllers\ReportesDashboardController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketCategoryController;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\ReporteMovimientosController;
use App\Http\Controllers\HerramientaController;
use App\Http\Controllers\GestionHerramientasController;
use App\Http\Controllers\TraspasoController;
use App\Http\Controllers\MovimientoInventarioController;
use App\Http\Controllers\AjusteInventarioController;
use App\Http\Controllers\ReportesInventarioController;
use App\Http\Controllers\EmpresaConfiguracionController;
use App\Http\Controllers\FolioConfigController;
use App\Http\Controllers\Config\AparienciaConfigController;
use App\Http\Controllers\Config\EmailConfigController;
use App\Http\Controllers\Config\CertificadosConfigController;
use App\Http\Controllers\Config\GeneralConfigController;
use App\Http\Controllers\Config\DocumentosConfigController;
use App\Http\Controllers\Config\ImpuestosConfigController;
use App\Http\Controllers\Config\BancariosConfigController;
use App\Http\Controllers\Config\SistemaConfigController;
use App\Http\Controllers\Config\SeguridadConfigController;
use App\Http\Controllers\Config\TiendaConfigController;
use App\Http\Controllers\EmpresaWhatsAppController;
use App\Http\Controllers\CategoriaHerramientaController;
use App\Http\Controllers\VacacionController;
use App\Http\Controllers\RegistroVacacionesController;
use App\Http\Controllers\CajaChicaController;
use App\Http\Controllers\KitController;
use App\Http\Controllers\GarantiaController;
use App\Http\Controllers\ComisionController;
use App\Http\Controllers\CrmController;
use App\Http\Controllers\SoporteRemotoController;
use App\Http\Controllers\ConciliacionBancariaController;
use App\Http\Controllers\CuentaBancariaController;
use App\Http\Controllers\CuentasPorPagarController;
use App\Http\Controllers\TraspasoBancarioController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\NominaController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\DisponibilidadTecnicoController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\LandingContentController;
use App\Http\Controllers\CredencialController;
use App\Http\Controllers\Reportes\ReporteSoporteController;

// Forzar patrón numérico para {herramienta}
Route::pattern('herramienta', '[0-9]+');

// Middleware de Exportación (Nivel superior en auth)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/clientes/export', [ClienteController::class, 'export'])->name('clientes.export')->middleware('can:export clientes');
    Route::get('/proveedores/export', [ProveedorController::class, 'export'])->name('proveedores.export')->middleware('can:export proveedores');
    Route::get('/tecnicos/export', [TecnicoController::class, 'export'])->name('tecnicos.export')->middleware('can:export tecnicos');
    Route::get('/usuarios/export', [UserController::class, 'export'])->name('usuarios.export')->middleware('can:export usuarios');
    Route::get('/citas/export', [CitaController::class, 'export'])->name('citas.export');
    Route::get('/productos/export', [ProductoController::class, 'export'])->name('productos.export');
});

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {

    // Dashboard y Panel
    Route::get('/offline', function () {
        return Inertia::render('Offline');
    })->name('offline');

    Route::get('/dashboard', function () {
        return redirect()->route('reportes.dashboard');
    })->name('dashboard');

    Route::get('/panel', [PanelController::class, 'index'])->name('panel');

    // Finanzas Dashboard
    Route::get('/finanzas', function () {
        return Inertia::render('Finanzas/Index');
    })->name('finanzas.index')->middleware('can:view finanzas');

    // Comisiones
    Route::prefix('comisiones')->middleware('can:view comisiones')->group(function () {
        Route::get('/', [ComisionController::class, 'index'])->name('comisiones.index');
        Route::get('/historial', [ComisionController::class, 'historial'])->name('comisiones.historial');
        Route::get('/vendedor/{vendedorType}/{vendedorId}', [ComisionController::class, 'show'])->name('comisiones.show');
        Route::post('/pagar', [ComisionController::class, 'pagar'])->name('comisiones.pagar');
        Route::get('/recibo/{pago}', [ComisionController::class, 'recibo'])->name('comisiones.recibo');
    });

    // CRM
    Route::prefix('crm')->group(function () {
        Route::get('/', [CrmController::class, 'index'])->name('crm.index');
        Route::get('/prospectos', [CrmController::class, 'prospectos'])->name('crm.prospectos');
        Route::post('/prospectos', [CrmController::class, 'crearProspecto'])->name('crm.prospecto.crear');
        Route::get('/prospectos/{prospecto}', [CrmController::class, 'showProspecto'])->name('crm.prospecto.show');
        Route::put('/prospectos/{prospecto}', [CrmController::class, 'actualizarProspecto'])->name('crm.prospecto.actualizar');
        Route::patch('/prospectos/{prospecto}/etapa', [CrmController::class, 'moverEtapa'])->name('crm.prospecto.mover');
        Route::post('/prospectos/{prospecto}/actividad', [CrmController::class, 'registrarActividad'])->name('crm.prospecto.actividad');
        Route::post('/prospectos/{prospecto}/convertir', [CrmController::class, 'convertirACliente'])->name('crm.prospecto.convertir');
        Route::post('/prospectos/importar', [CrmController::class, 'importarClientes'])->name('crm.prospectos.importar');

        Route::get('/tareas', [CrmController::class, 'tareas'])->name('crm.tareas');
        Route::post('/tareas', [CrmController::class, 'crearTarea'])->name('crm.tarea.crear');
        Route::patch('/tareas/{tarea}/completar', [CrmController::class, 'completarTarea'])->name('crm.tarea.completar');

        Route::middleware('role:admin|super-admin')->group(function () {
            Route::get('/scripts', [CrmController::class, 'scripts'])->name('crm.scripts');
            Route::post('/scripts', [CrmController::class, 'guardarScript'])->name('crm.script.guardar');
            Route::delete('/scripts/{script}', [CrmController::class, 'eliminarScript'])->name('crm.script.eliminar');
            Route::get('/metas', [CrmController::class, 'metas'])->name('crm.metas');
            Route::post('/metas', [CrmController::class, 'guardarMeta'])->name('crm.meta.guardar');
            Route::delete('/metas/{meta}', [CrmController::class, 'eliminarMeta'])->name('crm.meta.eliminar');
            Route::get('/metas/exportar', [CrmController::class, 'exportarVendedoresCSV'])->name('crm.metas.exportar');
            Route::post('/metas/importar', [CrmController::class, 'importarMetasCSV'])->name('crm.metas.importar');
            Route::get('/campanias', [CrmController::class, 'campanias'])->name('crm.campanias');
            Route::post('/campanias', [CrmController::class, 'guardarCampania'])->name('crm.campania.guardar');
            Route::get('/campanias/{campania}', [CrmController::class, 'verCampania'])->name('crm.campania.ver');
            Route::patch('/campanias/{campania}/toggle', [CrmController::class, 'toggleCampania'])->name('crm.campania.toggle');
        });
    });

    // Soporte / Helpdesk
    Route::prefix('soporte')->middleware('can:view soporte')->group(function () {
        Route::get('/', [TicketController::class, 'index'])->name('soporte.index');
        Route::get('/dashboard', [TicketController::class, 'dashboard'])->name('soporte.dashboard');
        Route::get('/buscar-cliente', [TicketController::class, 'buscarClientePorTelefono'])->name('soporte.buscar-cliente');

        Route::prefix('categorias')->middleware('can:edit soporte')->group(function () {
            Route::get('/', [TicketCategoryController::class, 'index'])->name('soporte.categorias.index');
            Route::post('/', [TicketCategoryController::class, 'store'])->name('soporte.categorias.store');
            Route::put('/{categoria}', [TicketCategoryController::class, 'update'])->name('soporte.categorias.update');
            Route::delete('/{categoria}', [TicketCategoryController::class, 'destroy'])->name('soporte.categorias.destroy');
        });

        Route::prefix('kb')->group(function () {
            Route::get('/', [KnowledgeBaseController::class, 'index'])->name('soporte.kb.index');
            Route::get('/crear', [KnowledgeBaseController::class, 'create'])->name('soporte.kb.create')->middleware('role:admin|super-admin');
            Route::post('/', [KnowledgeBaseController::class, 'store'])->name('soporte.kb.store')->middleware('can:create soporte');
            Route::get('/{articulo}', [KnowledgeBaseController::class, 'show'])->name('soporte.kb.show');
            Route::get('/{articulo}/editar', [KnowledgeBaseController::class, 'edit'])->name('soporte.kb.edit')->middleware('role:admin|super-admin');
            Route::put('/{articulo}', [KnowledgeBaseController::class, 'update'])->name('soporte.kb.update')->middleware('role:admin|super-admin');
            Route::delete('/{articulo}', [KnowledgeBaseController::class, 'destroy'])->name('soporte.kb.destroy')->middleware('role:admin|super-admin');
            Route::post('/{articulo}/votar', [KnowledgeBaseController::class, 'votar'])->name('soporte.kb.votar');
        });

        Route::resource('tickets', TicketController::class)->except(['index'])->names('soporte');
        Route::post('/{ticket}/estado', [TicketController::class, 'cambiarEstado'])->name('soporte.cambiar-estado');
        Route::post('/{ticket}/asignar', [TicketController::class, 'asignar'])->name('soporte.asignar');
        Route::post('/{ticket}/comentario', [TicketController::class, 'agregarComentario'])->name('soporte.comentario');
        Route::post('/{ticket}/generar-venta', [TicketController::class, 'generarVenta'])->name('soporte.generar-venta');

        // Reportes PDF de Soporte
        Route::get('/reportes/consumo-poliza/{poliza}', [ReporteSoporteController::class, 'consumoPoliza'])->name('soporte.reporte.consumo-poliza');
        Route::get('/reportes/horas-tecnico/{usuario?}', [ReporteSoporteController::class, 'horasTecnico'])->name('soporte.reporte.horas-tecnico');
    });

    Route::get('/soporte-remoto', [SoporteRemotoController::class, 'index'])->name('soporte-remoto.index')->middleware('can:view soporte');

    // Clientes Rutas Específicas
    Route::post('/clientes/validar-rfc', [ClienteController::class, 'validarRfc'])->name('clientes.validarRfc');
    Route::get('/clientes/validar-email', [ClienteController::class, 'validarEmail'])->name('clientes.validarEmail')->middleware('role:ventas|admin|super-admin');
    Route::post('/clientes/search', [ClienteController::class, 'search'])->name('clientes.search')->middleware('role:ventas|admin|super-admin');
    Route::get('/clientes/stats', [ClienteController::class, 'stats'])->name('clientes.stats')->middleware('role:ventas|admin|super-admin');
    Route::post('/clientes/{cliente}/approve', [ClienteController::class, 'approve'])->name('clientes.approve')->middleware('role:ventas|admin|super-admin');
    Route::put('/clientes/{cliente}/toggle', [ClienteController::class, 'toggle'])->name('clientes.toggle')->middleware('role:ventas|admin|super-admin');

    // Recursos Principales
    Route::get('/ordenescompra/siguiente-numero', [OrdenCompraController::class, 'obtenerSiguienteNumero'])->name('ordenescompra.siguiente-numero')->middleware('can:view ordenes_compra');
    Route::resource('ordenescompra', OrdenCompraController::class)->middleware('can:view ordenes_compra');
    Route::post('ordenescompra/{id}/enviar-compra', [OrdenCompraController::class, 'enviarACompra'])->name('ordenescompra.enviar-compra');
    Route::post('ordenescompra/{id}/recibir-mercancia', [OrdenCompraController::class, 'recibirMercancia'])->name('ordenescompra.recibir-mercancia');
    Route::post('ordenescompra/{id}/cancelar', [OrdenCompraController::class, 'cancelar'])->name('ordenescompra.cancelar');

    Route::resource('clientes', ClienteController::class)->names('clientes')->middleware('can:view clientes')->where(['cliente' => '[0-9]+']);
    Route::post('clientes/{cliente}/documentos', [ClienteDocumentoController::class, 'store'])->name('clientes.documentos.store');
    Route::delete('clientes/documentos/{documento}', [ClienteDocumentoController::class, 'destroy'])->name('clientes.documentos.destroy');
    Route::get('clientes/{cliente}/contrato-credito', [ClienteCreditoPDFController::class, 'contrato'])->name('clientes.contrato-credito');
    Route::resource('prestamos', PrestamoController::class)->names('prestamos')->middleware('role:ventas|admin|super-admin');
    Route::resource('pagos', PagoPrestamoController::class)->names('pagos')->middleware('can:view pagos');

    Route::get('/productos/ajax-catalogs', [ProductoController::class, 'getCatalogs'])->name('productos.ajax-catalogs');
    Route::get('/productos/{producto}/series', [ProductoController::class, 'series'])->name('productos.series')->middleware('role:admin|editor|ventas|super-admin');
    Route::resource('productos', ProductoController::class)->names('productos')->middleware('can:view productos');
    Route::post('/productos/validate-stock', [ProductoController::class, 'validateStock'])->name('productos.validateStock');
    Route::post('/productos/recalcular-precios', [ProductoController::class, 'recalcularPrecios'])->name('productos.recalcular-precios');
    Route::put('/productos/{producto}/toggle', [ProductoController::class, 'toggle'])->name('productos.toggle');

    // Integración CVA Admin
    Route::prefix('cva')->name('cva.')->group(function () {
        Route::get('/importar', [App\Http\Controllers\Admin\CVAController::class, 'importView'])->name('import');
        Route::get('/buscar', [App\Http\Controllers\Admin\CVAController::class, 'search'])->name('search');
        Route::post('/importar-producto', [App\Http\Controllers\Admin\CVAController::class, 'import'])->name('import-product');
    });

    // Gestión de Blog
    Route::resource('blog', App\Http\Controllers\Admin\BlogPostController::class)->names('admin.blog');

    Route::resource('proveedores', ProveedorController::class)->names('proveedores')->middleware('can:view proveedores');
    Route::put('/proveedores/{proveedor}/toggle', [ProveedorController::class, 'toggle'])->name('proveedores.toggle');

    Route::resource('categorias', CategoriaController::class)->names('categorias')->middleware('can:view categorias');
    Route::resource('marcas', MarcaController::class)->names('marcas')->middleware('can:view marcas');
    Route::resource('almacenes', AlmacenController::class)->names('almacenes');
    Route::resource('traspasos', TraspasoController::class)->names('traspasos')->middleware('can:view traspasos');
    Route::resource('movimientos-inventario', MovimientoInventarioController::class)->names('movimientos-inventario')->middleware('can:view movimientos_inventario');

    // Pedidos Tienda Online
    Route::get('/pedidos-online', [App\Http\Controllers\Admin\PedidoOnlineController::class, 'index'])->name('pedidos-online.index');
    Route::get('/pedidos-online/{id}', [App\Http\Controllers\Admin\PedidoOnlineController::class, 'show'])->name('pedidos-online.show');
    Route::post('/pedidos-online/{id}/status', [App\Http\Controllers\Admin\PedidoOnlineController::class, 'updateStatus'])->name('pedidos-online.update-status');

    Route::resource('ajustes-inventario', AjusteInventarioController::class)->names('ajustes-inventario')->middleware('can:view ajustes_inventario');
    Route::get('/caja-chica/export', [CajaChicaController::class, 'export'])->name('caja-chica.export');
    Route::resource('caja-chica', CajaChicaController::class)->names('caja-chica');

    Route::get('/cotizaciones/siguiente-numero', [CotizacionController::class, 'obtenerSiguienteNumero'])->name('cotizaciones.siguiente-numero')->middleware('can:view cotizaciones');
    Route::resource('cotizaciones', CotizacionController::class)->names('cotizaciones')->middleware('can:view cotizaciones');
    Route::post('/cotizaciones/{id}/convertir-a-venta', [CotizacionConversionController::class, 'convertirAVenta'])->name('cotizaciones.convertir-a-venta');
    Route::get('/cotizaciones/{id}/pdf', [CotizacionDocumentoController::class, 'generarPDF'])->name('cotizaciones.pdf');

    Route::get('/pedidos/siguiente-numero', [PedidoController::class, 'obtenerSiguienteNumero'])->name('pedidos.siguiente-numero')->middleware('can:view pedidos');
    Route::resource('pedidos', PedidoController::class)->names('pedidos')->middleware('can:view pedidos');
    Route::get('/pedidos/{id}/pdf', [PedidoDocumentoController::class, 'generarPDF'])->name('pedidos.pdf');

    Route::get('/ventas/siguiente-numero', [VentaController::class, 'obtenerSiguienteNumero'])->name('ventas.siguiente-numero')->middleware('can:view ventas');
    Route::post('/ventas/validar-series', [VentaController::class, 'validarSeries'])->name('ventas.validar-series')->middleware('can:view ventas');
    Route::post('/ventas/{venta}/facturar', [VentaController::class, 'facturar'])->name('ventas.facturar')->middleware('can:view ventas');
    Route::resource('ventas', VentaController::class)->names('ventas')->middleware('can:view ventas');
    Route::post('/ventas/{venta}/marcar-pagado', [VentaController::class, 'marcarPagado'])->name('ventas.marcar-pagado');
    Route::get('/ventas/{id}/pdf', [VentaDocumentoController::class, 'generarPDF'])->name('ventas.pdf');
    Route::get('/ventas/{id}/ticket', [VentaDocumentoController::class, 'generarTicket'])->name('ventas.ticket');

    Route::resource('garantias', GarantiaController::class)->names('garantias')->middleware('can:view garantias');

    Route::prefix('kits')->name('kits.')->middleware('can:view kits')->group(function () {
        Route::get('/', [KitController::class, 'index'])->name('index');
        Route::get('/api/data', [KitController::class, 'apiIndex'])->name('api.data'); // Added this line
        Route::get('/create', [KitController::class, 'create'])->name('create');
        Route::post('/', [KitController::class, 'store'])->name('store');
        Route::get('/{kit}', [KitController::class, 'show'])->name('show');
        Route::get('/{kit}/edit', [KitController::class, 'edit'])->name('edit');
        Route::put('/{kit}', [KitController::class, 'update'])->name('update');
        Route::delete('/{kit}', [KitController::class, 'destroy'])->name('destroy');
    });

    Route::resource('servicios', ServicioController::class)->names('servicios')->middleware('can:view servicios');
    Route::resource('usuarios', UserController::class)->names('usuarios')->middleware('can:view usuarios');
    Route::put('/usuarios/{user}/toggle', [UserController::class, 'toggle'])->name('usuarios.toggle');
    Route::resource('roles', RoleController::class)->names('roles')->middleware('can:view roles');

    Route::get('/compras/siguiente-numero', [CompraController::class, 'obtenerSiguienteNumero'])->name('compras.siguiente-numero')->middleware('can:view compras');
    Route::get('/compras/received-cfdis', [CompraCfdiController::class, 'getReceivedCfdis'])->name('compras.received-cfdis')->middleware('can:view compras');
    Route::post('/compras/parse-xml', [CompraCfdiController::class, 'parseXmlCfdi'])->name('compras.parse-xml')->middleware('can:edit compras');
    Route::resource('compras', CompraController::class)->names('compras')->middleware('can:view compras');
    Route::post('/compras/{id}/cancel', [CompraEstadoController::class, 'cancel'])->name('compras.cancel');

    Route::post('/gastos/parse-xml', [GastoController::class, 'parseXmlCfdi'])->name('gastos.parse-xml')->middleware('can:edit gastos');
    Route::resource('gastos', GastoController::class)->names('gastos')->middleware('can:view gastos');

    Route::prefix('conciliacion-bancaria')->middleware('can:view conciliacion_bancaria')->group(function () {
        Route::get('/', [ConciliacionBancariaController::class, 'index'])->name('conciliacion.index');
        Route::post('/importar', [ConciliacionBancariaController::class, 'importar'])->name('conciliacion.importar');
        Route::post('/conciliar', [ConciliacionBancariaController::class, 'conciliar'])->name('conciliacion.conciliar');
    });

    Route::get('cuentas-bancarias/{cuentas_bancaria}/movimientos', [CuentaBancariaController::class, 'movimientos'])->name('cuentas-bancarias.movimientos');
    Route::post('cuentas-bancarias/{cuentas_bancaria}/registrar-movimiento', [CuentaBancariaController::class, 'registrarMovimientoManual'])->name('cuentas-bancarias.registrar-movimiento');
    Route::resource('cuentas-bancarias', CuentaBancariaController::class)->names('cuentas-bancarias');
    Route::resource('cuentas-por-pagar', CuentasPorPagarController::class)->names('cuentas-por-pagar');

    // Rutas específicas de Cuentas por Cobrar
    Route::post('cuentas-por-cobrar/import-payment-xml', [CuentasPorCobrarController::class, 'importPaymentXml'])->name('cuentas-por-cobrar.import-payment-xml');
    Route::post('cuentas-por-cobrar/apply-payments-xml', [CuentasPorCobrarController::class, 'applyPaymentsFromXml'])->name('cuentas-por-cobrar.apply-payments-xml');
    Route::post('cuentas-por-cobrar/{id}/registrar-pago', [CuentasPorCobrarController::class, 'registrarPago'])->name('cuentas-por-cobrar.registrar-pago');
    Route::post('cuentas-por-cobrar/anular-pago/{id}', [CuentasPorCobrarController::class, 'anularPago'])->name('cuentas-por-cobrar.anular-pago')->middleware('role:admin|super-admin');
    Route::post('cuentas-por-cobrar/{id}/timbrar-rep', [CuentasPorCobrarController::class, 'timbrarReciboPago'])->name('cuentas-por-cobrar.timbrar-rep');

    Route::resource('cuentas-por-cobrar', CuentasPorCobrarController::class)->names('cuentas-por-cobrar');
    Route::resource('traspasos-bancarios', TraspasoBancarioController::class)->names('traspasos-bancarios');

    // Reportes
    Route::get('/reportes', [ReportesDashboardController::class, 'indexTabs'])->name('reportes.index')->middleware('role:admin|super-admin|ventas');
    Route::get('/reportes/dashboard', [ReportesDashboardController::class, 'index'])->name('reportes.dashboard')->middleware('role:admin|super-admin|ventas');

    // Rutas específicas de reportes
    Route::prefix('reportes')->name('reportes.')->middleware('role:admin|super-admin')->group(function () {
        Route::get('/ventas', [ReporteController::class, 'ventas'])->name('ventas');
        Route::get('/ganancias', [ReporteController::class, 'ganancias'])->name('ganancias');
        Route::get('/ventas-pendientes', [ReporteController::class, 'ventasPendientes'])->name('ventas-pendientes');
        Route::get('/balance-comparativo', [ReporteController::class, 'balanceComparativo'])->name('balance-comparativo');
        Route::get('/productos', [ReporteController::class, 'productos'])->name('productos');
        Route::get('/gastos-operativos', [ReporteController::class, 'gastosOperativos'])->name('gastos-operativos');
        Route::get('/compras-proveedores', [ReporteController::class, 'comprasProveedores'])->name('compras-proveedores');
        Route::get('/inventario', [ReporteController::class, 'inventario'])->name('inventario');
        Route::get('/corte-diario', [ReporteController::class, 'corteDiario'])->name('corte-diario');
        Route::get('/cobranzas', [ReporteController::class, 'cobranzas'])->name('cobranzas');
        Route::get('/antiguedad-saldos', [ReporteController::class, 'antiguedadSaldos'])->name('antiguedad-saldos');
        Route::get('/citas', [ReporteController::class, 'citas'])->name('citas');
        Route::get('/mantenimientos', [ReporteController::class, 'mantenimientos'])->name('mantenimientos');
        Route::get('/tecnicos', [ReporteController::class, 'getRendimientoTecnicos'])->name('tecnicos');
        Route::get('/clientes', [ReporteController::class, 'clientes'])->name('clientes');
        Route::get('/servicios', [ReporteController::class, 'servicios'])->name('servicios');
        Route::get('/rentas', [ReporteController::class, 'rentas'])->name('rentas');
        Route::get('/proveedores', [ReporteController::class, 'proveedores'])->name('proveedores');
        Route::get('/auditoria', [ReporteController::class, 'auditoria'])->name('auditoria');
        Route::get('/empleados', [ReporteController::class, 'empleados'])->name('empleados');
        Route::get('/export', [ReporteController::class, 'exportarCorteDiario'])->name('export');
        Route::get('/movimientos-inventario/{id}', [ReporteMovimientosController::class, 'show'])->name('movimientos-inventario.show');
        Route::get('/reportes/inventario/stock-por-almacen', [ReportesInventarioController::class, 'stockPorAlmacen'])->name('inventario.stock-por-almacen');
        Route::get('/reportes/inventario/productos-bajo-stock', [ReportesInventarioController::class, 'productosBajoStock'])->name('inventario.productos-bajo-stock');
        Route::get('/reportes/inventario/movimientos-por-periodo', [ReportesInventarioController::class, 'movimientosPorPeriodo'])->name('inventario.movimientos-por-periodo');
        Route::get('/reportes/inventario/costos-inventario', [ReportesInventarioController::class, 'costosInventario'])->name('inventario.costos');
        Route::get('/reportes/prestamos-por-cliente', [ReporteController::class, 'prestamosPorCliente'])->name('prestamos-por-cliente');
    });

    Route::resource('reportes', ReporteController::class)->except(['index', 'show'])->middleware('role:admin|super-admin');

    Route::resource('tecnicos', TecnicoController::class)->names('tecnicos');
    Route::put('/tecnicos/{tecnico}/toggle', [TecnicoController::class, 'toggle'])->name('tecnicos.toggle');

    // Herramientas Rutas Adicionales
    Route::get('herramientas/dashboard', [HerramientaController::class, 'dashboard'])->name('herramientas-dashboard');
    Route::get('herramientas/alertas', [HerramientaController::class, 'alertas'])->name('herramientas-alertas');
    Route::get('herramientas/mantenimiento-lista', [HerramientaController::class, 'mantenimiento'])->name('herramientas-mantenimiento');
    Route::post('herramientas/{herramienta}/registrar-mantenimiento', [HerramientaController::class, 'registrarMantenimiento'])->name('herramientas.registrar-mantenimiento');
    Route::get('herramientas/reportes', [HerramientaController::class, 'reportes'])->name('herramientas-reportes');

    Route::resource('herramientas', HerramientaController::class)->names('herramientas');
    Route::get('herramientas/gestion', [GestionHerramientasController::class, 'index'])->name('herramientas.gestion.index');
    Route::post('herramientas/gestion/asignar', [GestionHerramientasController::class, 'asignar'])->name('herramientas.gestion.asignar');

    Route::get('/citas-calendario', [CitaController::class, 'calendario'])->name('citas-calendario')->middleware('role:ventas|admin|super-admin|tecnico');
    Route::get('/citas/check-visits-limit', [CitaController::class, 'checkVisitsLimit'])->name('citas.check-visits-limit')->middleware('role:ventas|admin|super-admin');
    Route::resource('citas', CitaController::class)->names('citas')->middleware('role:ventas|admin|super-admin');
    Route::get('/mi-agenda', [CitaController::class, 'miAgenda'])->name('citas.mi-agenda')->middleware('role:ventas|admin|super-admin|tecnico');

    Route::get('/disponibilidad-tecnicos', [DisponibilidadTecnicoController::class, 'index'])->name('disponibilidad-tecnicos.index')->middleware('role:admin|super-admin');
    Route::resource('carros', CarroController::class)->names('carros')->middleware('role:admin|editor|super-admin');
    Route::resource('mantenimientos', MantenimientoController::class)->names('mantenimientos')->middleware('role:admin|editor|super-admin');
    Route::resource('equipos', EquipoController::class)->middleware('role:admin|editor|super-admin');
    Route::resource('rentas', RentasController::class)->middleware('role:admin|editor|super-admin');
    Route::get('/rentas/{renta}/contrato', [RentasContratoController::class, 'contratoPDF'])->name('rentas.contrato');
    // Dashboard Técnico de Mantenimientos (Pólizas)
    Route::prefix('tecnico/mantenimientos')->name('admin.mantenimientos.tecnico.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PolizaMantenimientoTecnicoController::class, 'index'])->name('index');
        Route::post('/{id}/tomar', [\App\Http\Controllers\Admin\PolizaMantenimientoTecnicoController::class, 'tomarTarea'])->name('tomar');
        Route::post('/{id}/completar', [\App\Http\Controllers\Admin\PolizaMantenimientoTecnicoController::class, 'completar'])->name('completar');
    });

    Route::get('/polizas-servicio/dashboard', [PolizaServicioController::class, 'dashboard'])->name('polizas-servicio.dashboard');
    Route::get('/polizas-servicio/{polizaServicio}/historial', [PolizaServicioController::class, 'historialConsumo'])->name('polizas-servicio.historial');
    Route::get('/polizas-servicio/{polizaServicio}/pdf-beneficios', [PolizaServicioPDFController::class, 'beneficios'])->name('polizas-servicio.pdf-beneficios');
    Route::get('/polizas-servicio/{polizaServicio}/pdf-contrato', [PolizaServicioPDFController::class, 'contrato'])->name('polizas-servicio.pdf-contrato');
    Route::post('/polizas-servicio/{polizaServicio}/generar-cobro', [PolizaServicioController::class, 'generarCobro'])->name('polizas-servicio.generar-cobro');
    Route::post('/polizas-servicio/{polizaServicio}/enviar-recordatorio', [PolizaServicioController::class, 'enviarRecordatorioRenovacion'])->name('polizas-servicio.enviar-recordatorio');
    Route::resource('polizas-servicio', PolizaServicioController::class)->middleware('role:admin|editor|super-admin');
    Route::resource('planes-poliza', PlanPolizaController::class)->middleware('role:admin|editor|super-admin');

    Route::resource('entregas-dinero', EntregaDineroController::class)->middleware('role:admin|ventas|super-admin');
    Route::post('/entregas-dinero/{id}/marcar-recibido', [EntregaDineroController::class, 'marcarRecibido'])->name('entregas-dinero.marcar-recibido')->middleware('role:admin|super-admin');
    Route::resource('bitacora', BitacoraActividadController::class)->names('bitacora');

    // Empresas
    Route::get('/empresas', [EmpresasController::class, 'index'])->name('empresas.index')->middleware('role:admin|super-admin');
    Route::post('/empresas', [EmpresasController::class, 'store'])->name('empresas.store')->middleware('role:admin|super-admin');

    // Configuración Empresa
    Route::prefix('empresa')->name('empresa-configuracion.')->middleware('role:admin|super-admin')->group(function () {
        Route::get('/configuracion', [EmpresaConfiguracionController::class, 'index'])->name('index');
        Route::get('/configuracion/api', [EmpresaConfiguracionController::class, 'getConfig'])->name('api');
        Route::put('/configuracion/general', [GeneralConfigController::class, 'update'])->name('general.update');
        Route::put('/configuracion/visual', [AparienciaConfigController::class, 'updateColores'])->name('visual.update');
        Route::put('/configuracion/correo', [EmailConfigController::class, 'update'])->name('correo.update');
        Route::get('/folios/config', [FolioConfigController::class, 'index'])->name('folios.config.index');
        Route::put('/configuracion/tienda', [TiendaConfigController::class, 'update'])->name('tienda.update');
        Route::put('/configuracion/whatsapp', [EmpresaWhatsAppController::class, 'update'])->name('whatsapp.update');
        Route::get('/configuracion/logs', [SistemaConfigController::class, 'getLogs'])->name('sistema.logs');
        Route::post('/configuracion/logs/clear', [SistemaConfigController::class, 'clearLogs'])->name('sistema.logs.clear');

        // Contenido de Landing
        Route::prefix('landing-content')->name('landing-content.')->group(function () {
            Route::get('/', [LandingContentController::class, 'index'])->name('index');
            Route::put('/hero', [LandingContentController::class, 'updateHero'])->name('hero.update');

            Route::post('/faqs', [LandingContentController::class, 'storeFaq'])->name('faqs.store');
            Route::put('/faqs/{faq}', [LandingContentController::class, 'updateFaq'])->name('faqs.update');
            Route::delete('/faqs/{faq}', [LandingContentController::class, 'destroyFaq'])->name('faqs.destroy');

            Route::post('/testimonios', [LandingContentController::class, 'storeTestimonio'])->name('testimonios.store');
            Route::post('/testimonios/{testimonio}', [LandingContentController::class, 'updateTestimonio'])->name('testimonios.update');
            Route::delete('/testimonios/{testimonio}', [LandingContentController::class, 'destroyTestimonio'])->name('testimonios.destroy');

            Route::post('/logos', [LandingContentController::class, 'storeLogo'])->name('logos.store');
            Route::post('/logos/{logo}', [LandingContentController::class, 'updateLogo'])->name('logos.update');
            Route::delete('/logos/{logo}', [LandingContentController::class, 'destroyLogo'])->name('logos.destroy');

            Route::post('/marcas', [LandingContentController::class, 'storeMarca'])->name('marcas.store');
            Route::post('/marcas/{marca}', [LandingContentController::class, 'updateMarca'])->name('marcas.update');
            Route::delete('/marcas/{marca}', [LandingContentController::class, 'destroyMarca'])->name('marcas.destroy');

            Route::post('/procesos', [LandingContentController::class, 'storeProceso'])->name('procesos.store');
            Route::put('/procesos/{proceso}', [LandingContentController::class, 'updateProceso'])->name('procesos.update');
            Route::delete('/procesos/{proceso}', [LandingContentController::class, 'destroyProceso'])->name('procesos.destroy');

            Route::post('/ofertas', [LandingContentController::class, 'storeOferta'])->name('ofertas.store');
            Route::put('/ofertas/{oferta}', [LandingContentController::class, 'updateOferta'])->name('ofertas.update');
            Route::delete('/ofertas/{oferta}', [LandingContentController::class, 'destroyOferta'])->name('ofertas.destroy');
        });
    });

    // SAT
    Route::prefix('sat')->name('sat.')->group(function () {
        Route::get('/buscar-clave-prod-serv', [SatCatalogosController::class, 'buscarClaveProdServ'])->name('buscar-clave-prod-serv');
    });

    // Vacaciones y RRHH
    // Importante: La ruta mis-vacaciones debe ir ANTES del resource 'vacaciones' para evitar conflicto con {vacacione}
    Route::get('/mis-vacaciones', [VacacionController::class, 'misVacaciones'])->name('vacaciones.mis-vacaciones');
    Route::resource('vacaciones', VacacionController::class)->names('vacaciones');
    Route::get('/registro-vacaciones/export', [RegistroVacacionesController::class, 'export'])->name('registro-vacaciones.export');
    Route::resource('registro-vacaciones', RegistroVacacionesController::class)->names('registro-vacaciones');
    Route::resource('empleados', EmpleadoController::class)->names('empleados')->middleware('role:admin|super-admin');

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
    Route::delete('/notifications', [UserNotificationController::class, 'destroyMultiple'])->name('notifications.destroyMultiple');

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
        Route::post('/granular-restore/{filename}', [DatabaseBackupController::class, 'granularRestore'])->name('granular-restore');
        Route::post('/clean', [DatabaseBackupController::class, 'clean'])->name('clean');
        Route::post('/upload', [DatabaseBackupController::class, 'upload'])->name('upload');
        Route::get('/stats', [DatabaseBackupController::class, 'stats'])->name('stats');
        Route::get('/verify/{filename}', [DatabaseBackupController::class, 'verify'])->name('verify');
        Route::get('/verify-advanced/{filename}', [DatabaseBackupController::class, 'verifyAdvanced'])->name('verify-advanced');
        Route::get('/info/{filename}', [DatabaseBackupController::class, 'info'])->name('info');
        Route::get('/health-report', [DatabaseBackupController::class, 'healthReport'])->name('health-report');
        Route::get('/monitoring', [DatabaseBackupController::class, 'monitoring'])->name('monitoring');
        Route::get('/security-stats', [DatabaseBackupController::class, 'securityStats'])->name('security.stats');
        Route::get('/compression-stats', [DatabaseBackupController::class, 'compressionStats'])->name('compression.stats');
        Route::get('/remote-list', [DatabaseBackupController::class, 'listRemote'])->name('remote.list');
        Route::get('/remote-download/{remotePath}', [DatabaseBackupController::class, 'downloadRemote'])->name('remote.download');
    });

    // CFDI
    Route::get('/cfdi', [CfdiController::class, 'index'])->name('cfdi.index');
    Route::post('/cfdi', [CfdiController::class, 'store'])->name('cfdi.store');
    Route::get('/cfdi/{cfdi}', [CfdiController::class, 'show'])->name('cfdi.show');
    Route::get('/cfdi/{id}/ver-pdf', [CfdiController::class, 'verPdfView'])->name('cfdi.ver-pdf-view');
    Route::delete('/cfdi/{cfdi}', [CfdiController::class, 'destroy'])->name('cfdi.destroy');

    // Proyectos
    Route::resource('proyectos', \App\Http\Controllers\ProyectoController::class);
    Route::resource('proyecto/tareas', ProyectoTareaController::class, ['as' => 'proyecto']);

    // Bóveda de Credenciales
    Route::get('/credenciales', [CredencialController::class, 'index'])->name('credenciales.index');
    Route::post('/credenciales', [CredencialController::class, 'store'])->name('credenciales.store');
    Route::get('/credenciales/{credencial}/reveal', [CredencialController::class, 'reveal'])->name('credenciales.reveal');
    Route::put('/credenciales/{credencial}', [CredencialController::class, 'update'])->name('credenciales.update');
    Route::delete('/credenciales/{credencial}', [CredencialController::class, 'destroy'])->name('credenciales.destroy');

    // Perfil
    Route::get('/perfil', [UserController::class, 'profile'])->name('perfil');


});

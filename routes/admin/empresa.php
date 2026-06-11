<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmpresaConfiguracionController;
use App\Http\Controllers\FolioConfigController;
use App\Http\Controllers\Config\AparienciaConfigController;
use App\Http\Controllers\Config\EmailConfigController;
use App\Http\Controllers\Config\GeneralConfigController;
use App\Http\Controllers\Config\SistemaConfigController;
use App\Http\Controllers\Config\TiendaConfigController;
use App\Http\Controllers\Config\MeliAuthController;
use App\Http\Controllers\Config\ApiKeysConfigController;
use App\Http\Controllers\Config\SeguridadConfigController;
use App\Http\Controllers\Config\BancariosConfigController;
use App\Http\Controllers\Config\DocumentosConfigController;
use App\Http\Controllers\Config\ImpuestosConfigController;
use App\Http\Controllers\Config\CobrosConfigController;
use App\Http\Controllers\Config\PagosConfigController;
use App\Http\Controllers\Config\RedConfigController;
use App\Http\Controllers\Config\CertificadosConfigController;
use App\Http\Controllers\Config\RepseConfigController;
use App\Http\Controllers\EmpresaWhatsAppController;
use App\Http\Controllers\LandingContentController;

// Configuración Empresa
Route::prefix('empresa')->name('empresa-configuracion.')->middleware('role:admin|super-admin')->group(function () {
    Route::get('/configuracion', [EmpresaConfiguracionController::class, 'index'])->name('index');
    Route::get('/configuracion/api', [EmpresaConfiguracionController::class, 'getConfig'])->name('api');
    Route::put('/configuracion/general', [GeneralConfigController::class, 'update'])->name('general.update');
    Route::put('/configuracion/repse', [RepseConfigController::class, 'update'])->name('repse.update');
    Route::post('/configuracion/legal-doc', [RepseConfigController::class, 'uploadDoc'])->name('legal-doc.upload');
    Route::put('/configuracion/visual', [AparienciaConfigController::class, 'updateColores'])->name('visual.update');
    Route::post('/configuracion/logo', [AparienciaConfigController::class, 'subirLogo'])->name('subir-logo');
    Route::post('/configuracion/favicon', [AparienciaConfigController::class, 'subirFavicon'])->name('subir-favicon');
    Route::delete('/configuracion/logo', [AparienciaConfigController::class, 'eliminarLogo'])->name('eliminar-logo');
    Route::delete('/configuracion/favicon', [AparienciaConfigController::class, 'eliminarFavicon'])->name('eliminar-favicon');
    Route::put('/correo', [EmailConfigController::class, 'update'])->name('correo.update');
    
    // Folios
    Route::get('/folios/config', [FolioConfigController::class, 'index'])->name('folios.config.index');
    Route::put('/folios/config/{id}', [FolioConfigController::class, 'update'])->name('folios.config.update');
    Route::post('/folios/config/sync/{id}', [FolioConfigController::class, 'sync'])->name('folios.config.sync');

    Route::put('/tienda', [TiendaConfigController::class, 'update'])->name('tienda.update');

    // MercadoLibre OAuth
    Route::get('/meli/auth', [MeliAuthController::class, 'redirect'])->name('meli.auth');
    Route::post('/meli/disconnect', [MeliAuthController::class, 'disconnect'])->name('meli.disconnect');
    Route::put('/configuracion/respaldos', [SistemaConfigController::class, 'updateRespaldos'])->name('respaldos.update');
    Route::put('/configuracion/whatsapp', [EmpresaWhatsAppController::class, 'update'])->name('whatsapp.update');
    Route::put('/configuracion/api-keys', [ApiKeysConfigController::class, 'update'])->name('api-keys.update');
    Route::put('/configuracion/seguridad', [SeguridadConfigController::class, 'update'])->name('seguridad.update');
    Route::put('/configuracion/bancarios', [BancariosConfigController::class, 'update'])->name('bancarios.update');
    Route::put('/configuracion/documentos', [DocumentosConfigController::class, 'update'])->name('documentos.update');
    Route::put('/configuracion/impuestos', [ImpuestosConfigController::class, 'update'])->name('impuestos.update');
    Route::put('/configuracion/cobros', [CobrosConfigController::class, 'update'])->name('cobros.update');
    Route::put('/configuracion/pagos', [PagosConfigController::class, 'update'])->name('pagos.update');
    Route::put('/configuracion/red', [RedConfigController::class, 'update'])->name('red.update');
    Route::put('/configuracion/redes-sociales', [GeneralConfigController::class, 'updateRedesSociales'])->name('redes-sociales.update');
    Route::put('/configuracion/sistema', [SistemaConfigController::class, 'update'])->name('sistema.update');
    Route::get('/configuracion/logs', [SistemaConfigController::class, 'getLogs'])->name('sistema.logs');
    Route::post('/configuracion/logs/clear', [SistemaConfigController::class, 'clearLogs'])->name('sistema.logs.clear');

    // Certificados SAT (FIEL/CSD)
    Route::get('/certificados-info', [CertificadosConfigController::class, 'getCertificadosInfo'])->name('certificados.info');
    Route::post('/certificado-fiel', [CertificadosConfigController::class, 'subirCertificadoFiel'])->name('fiel.subir');
    Route::post('/certificado-csd', [CertificadosConfigController::class, 'subirCertificadoCsd'])->name('csd.subir');
    Route::post('/certificado-fiel/eliminar', [CertificadosConfigController::class, 'eliminarCertificadoFiel'])->name('fiel.eliminar');
    Route::post('/certificado-csd/eliminar', [CertificadosConfigController::class, 'eliminarCertificadoCsd'])->name('csd.eliminar');
    Route::post('/pac', [CertificadosConfigController::class, 'guardarPac'])->name('pac.guardar');
    Route::post('/pac/test', [CertificadosConfigController::class, 'testPac'])->name('pac.test');

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

// Callback de MercadoLibre (sin auth de admin, viene de redirect externo)
Route::get('/empresa/meli/callback', [MeliAuthController::class, 'callback'])->name('empresa-configuracion.meli.callback');

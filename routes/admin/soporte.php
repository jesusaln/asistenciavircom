<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketCategoryController;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\Reportes\ReporteSoporteController;
use App\Http\Controllers\SoporteRemotoController;

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

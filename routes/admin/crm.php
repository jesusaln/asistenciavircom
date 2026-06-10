<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CrmController;

// CRM
Route::prefix('crm')->group(function () {
    Route::get('/', [CrmController::class, 'index'])->name('crm.index');
    Route::get('/prospectos', [CrmController::class, 'prospectos'])->name('crm.prospectos');
    Route::post('/prospectos', [CrmController::class, 'crearProspecto'])->name('crm.prospecto.crear');
    Route::get('/prospectos/{prospecto}', [CrmController::class, 'showProspecto'])->name('crm.prospecto.show');
    Route::put('/prospectos/{prospecto}', [CrmController::class, 'actualizarProspecto'])->name('crm.prospecto.actualizar');
    Route::delete('/prospectos/{prospecto}', [CrmController::class, 'eliminarProspecto'])->name('crm.prospecto.eliminar');
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

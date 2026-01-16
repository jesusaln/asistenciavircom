<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientPortal\AuthController;
use App\Http\Controllers\ClientPortal\PortalController;
use App\Http\Controllers\ClientPortal\PasswordResetLinkController;
use App\Http\Controllers\ClientPortal\NewPasswordController;

// =====================================================
// PORTAL DE CLIENTES
// =====================================================

Route::prefix('portal')->group(function () {
    // Autenticación de Clientes (Guard: client)
    Route::middleware('guest:client')->group(function () {
        Route::get('/login', [AuthController::class, 'create'])->name('portal.login');
        Route::post('/login', [AuthController::class, 'store']);
        Route::get('/register', [AuthController::class, 'register'])->name('portal.register');
        Route::post('/register', [AuthController::class, 'storeRegister']);

        Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('portal.password.request');
        Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('portal.password.email');
        Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('portal.password.reset');
        Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('portal.password.store');
    });

    // Rutas Protegidas del Portal
    Route::middleware('auth:client')->group(function () {
        Route::post('/logout', [AuthController::class, 'destroy'])->name('portal.logout');
        Route::get('/', [PortalController::class, 'dashboard'])->name('portal.dashboard');

        // Tickets (Cliente)
        Route::get('/tickets/crear', [PortalController::class, 'create'])->name('portal.tickets.create');
        Route::post('/tickets', [PortalController::class, 'store'])->name('portal.tickets.store');
        Route::get('/tickets/{ticket}', [PortalController::class, 'show'])->name('portal.tickets.show');
        Route::post('/tickets/{ticket}/comentarios', [PortalController::class, 'storeComment'])->name('portal.tickets.comments.store');

        // Pólizas (Cliente)
        Route::get('/polizas', [PortalController::class, 'polizasIndex'])->name('portal.polizas.index');
        Route::get('/polizas/{poliza}', [PortalController::class, 'polizaShow'])->name('portal.polizas.show');
        Route::get('/polizas/{poliza}/imprimir', [PortalController::class, 'imprimirContrato'])->name('portal.polizas.imprimir');

        // Pedidos Online (Cliente)
        Route::get('/pedidos', [PortalController::class, 'pedidosIndex'])->name('portal.pedidos.index');
        Route::get('/pedidos/{pedido}', [PortalController::class, 'pedidoShow'])->name('portal.pedidos.show');

        // Credenciales (Cliente)
        Route::post('/credenciales/{id}/revelar', [PortalController::class, 'revelarCredencial'])->name('portal.credenciales.revelar');
    });
});

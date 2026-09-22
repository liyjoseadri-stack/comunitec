<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));
Route::middleware('guest')->group(function () {
    Route::get('/iniciar-sesion', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/iniciar-sesion', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});
Route::middleware('auth')->group(function () {
    Route::get('/panel', fn () => view('dashboard'))->name('dashboard');
    Route::post('/cerrar-sesion', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::middleware('role:admin')->group(function () {
        Route::get('/administracion/usuarios', [UserController::class, 'index'])->name('admin.users.index');
        Route::post('/administracion/usuarios', [UserController::class, 'store'])->name('admin.users.store');
        Route::put('/administracion/usuarios/{user}', [UserController::class, 'update'])->name('admin.users.update');
        Route::patch('/administracion/usuarios/{user}/estado', [UserController::class, 'updateStatus'])->name('admin.users.status');
    });
});

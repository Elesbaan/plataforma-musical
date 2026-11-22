<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\PublicPageController;
use Illuminate\Support\Facades\Route;

// Sitio público
Route::get('/', [PublicPageController::class, 'home'])->name('home');
Route::get('/aviso-de-privacidad', [PublicPageController::class, 'privacy'])->name('privacy');

// Acceso al panel (disponible en /admin/login y en /login)
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login.short');
    Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store.short');
});

// Panel administrativo
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::post('/session/keep-alive', [AuthenticatedSessionController::class, 'keepAlive'])->name('session.keep-alive');

    // Identidad y marca
    Route::get('/identidad', [SiteSettingController::class, 'edit'])->name('settings.edit');
    Route::put('/identidad', [SiteSettingController::class, 'update'])->name('settings.update');
});

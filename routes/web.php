<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\PublicPageController;
use Illuminate\Support\Facades\Route;

// Sitio público
Route::get('/', [PublicPageController::class, 'home'])->name('home');
Route::get('/aviso-de-privacidad', [PublicPageController::class, 'privacy'])->name('privacy');

// Acceso al panel
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

// Panel administrativo
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

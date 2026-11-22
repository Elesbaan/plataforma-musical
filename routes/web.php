<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CoverageZoneController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaChunkController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\ServiceMediaController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\UserController;
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

// Panel administrativo (cada módulo exige su permiso; ver config/permissions.php)
Route::middleware(['auth', 'active'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::post('/session/keep-alive', [AuthenticatedSessionController::class, 'keepAlive'])->name('session.keep-alive');

    // Identidad y marca
    Route::middleware('can:settings.manage')->group(function () {
        Route::get('/identidad', [SiteSettingController::class, 'edit'])->name('settings.edit');
        Route::put('/identidad', [SiteSettingController::class, 'update'])->name('settings.update');
    });

    // Zonas de cobertura y viáticos
    Route::resource('zonas', CoverageZoneController::class)
        ->parameters(['zonas' => 'zone'])
        ->only(['index', 'store', 'update', 'destroy'])
        ->names('zones')
        ->middleware('can:zones.manage');

    // Catálogo: categorías y servicios
    Route::resource('categorias', CategoryController::class)
        ->parameters(['categorias' => 'category'])
        ->only(['index', 'store', 'update', 'destroy'])
        ->names('categories')
        ->middleware('can:catalog.manage_all');

    Route::middleware('can:catalog.manage_all')->group(function () {
        Route::resource('servicios', ServiceController::class)
            ->parameters(['servicios' => 'service'])
            ->except(['show'])
            ->names('services');

        // Gestor multimedia de servicios
        Route::post('/servicios/{service}/media/fotos', [ServiceMediaController::class, 'storeImages'])->name('services.media.images');
        Route::post('/servicios/{service}/media/video', [ServiceMediaController::class, 'storeEmbed'])->name('services.media.embed');
        Route::patch('/media/{media}', [ServiceMediaController::class, 'update'])->name('media.update');
        Route::delete('/media/{media}', [ServiceMediaController::class, 'destroy'])->name('media.destroy');
        Route::post('/media/upload-chunk', [MediaChunkController::class, 'store'])->name('media.upload-chunk');
    });

    // Perfiles y matriz de permisos
    Route::resource('perfiles', RoleController::class)
        ->parameters(['perfiles' => 'role'])
        ->except(['show'])
        ->names('roles')
        ->middleware('can:roles.manage');

    // Usuarios
    Route::resource('usuarios', UserController::class)
        ->parameters(['usuarios' => 'user'])
        ->except(['show'])
        ->names('users')
        ->middleware('can:users.manage');
});

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
use App\Http\Controllers\QuoteApiController;
use App\Models\Service;
use Illuminate\Support\Facades\Route;

// Sitio público
Route::get('/', [PublicPageController::class, 'home'])->name('home');
Route::get('/aviso-de-privacidad', [PublicPageController::class, 'privacy'])->name('privacy');

// API del cotizador (JSON, con límite de peticiones por IP)
Route::prefix('api')->name('api.')->group(function () {
    Route::get('/availability', [QuoteApiController::class, 'availability'])->middleware('throttle:60,1')->name('availability');
    Route::post('/cotizador/calcular', [QuoteApiController::class, 'preview'])->middleware('throttle:120,1')->name('quote.preview');
});

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

    // Servicios: ServicePolicy distingue catalog.manage_all de catalog.manage_own
    // (solo servicios vinculados y sin cambiar precios).
    Route::middleware('can:viewAny,'.Service::class)->group(function () {
        Route::get('/servicios', [ServiceController::class, 'index'])->name('services.index');
        Route::get('/servicios/crear', [ServiceController::class, 'create'])->middleware('can:create,'.Service::class)->name('services.create');
        Route::post('/servicios', [ServiceController::class, 'store'])->middleware('can:create,'.Service::class)->name('services.store');
        Route::get('/servicios/{service}/editar', [ServiceController::class, 'edit'])->middleware('can:update,service')->name('services.edit');
        Route::match(['put', 'patch'], '/servicios/{service}', [ServiceController::class, 'update'])->middleware('can:update,service')->name('services.update');
        Route::delete('/servicios/{service}', [ServiceController::class, 'destroy'])->middleware('can:delete,service')->name('services.destroy');

        // Gestor multimedia (cada acción se autoriza contra el servicio dueño del archivo)
        Route::post('/servicios/{service}/media/fotos', [ServiceMediaController::class, 'storeImages'])->middleware('can:update,service')->name('services.media.images');
        Route::post('/servicios/{service}/media/video', [ServiceMediaController::class, 'storeEmbed'])->middleware('can:update,service')->name('services.media.embed');
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

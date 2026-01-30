<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TMDBController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\AdminController;

// Public Routes
Route::get('/', [TMDBController::class, 'index'])->name('home');
Route::get('/search', [TMDBController::class, 'search'])->name('search');

// Particles API (public - for frontend)
Route::get('/api/particles/config', [AdminController::class, 'getActiveThemeConfig'])->name('api.particles.config');

// Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Admin Routes (protected)
Route::prefix('admin')->middleware('auth')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    
    // Particles Management
    Route::get('/particles', [AdminController::class, 'particles'])->name('particles');
    Route::post('/particles/theme', [AdminController::class, 'storeTheme'])->name('particles.store');
    Route::put('/particles/theme/{theme}', [AdminController::class, 'updateTheme'])->name('particles.update');
    Route::delete('/particles/theme/{theme}', [AdminController::class, 'destroyTheme'])->name('particles.destroy');
    Route::post('/particles/theme/{theme}/activate', [AdminController::class, 'activateTheme'])->name('particles.activate');
    Route::post('/particles/seed-presets', [AdminController::class, 'seedPresets'])->name('particles.seed');
    
    // Settings
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::post('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');
});

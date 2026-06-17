<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Authentication
// ---------------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/', fn () => redirect()->route('dashboard'));

// ---------------------------------------------------------------------------
// Application (ต้อง login)
// ---------------------------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('menu:kpi.dashboard,view')
        ->name('dashboard');

    // โหมดจอ Monitor เต็มจอ (สำหรับทีวี LCD)
    Route::get('/monitor', [DashboardController::class, 'monitor'])
        ->middleware('menu:kpi.dashboard,view')
        ->name('monitor');

    // CRUD modules จะถูกเพิ่มใน Phase 5
    require __DIR__ . '/kpi.php';
});

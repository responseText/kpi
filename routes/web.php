<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
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

    // ข้อมูลส่วนตัว (ทุกผู้ใช้แก้ไขของตัวเองได้ — ไม่ต้องมีสิทธิ์เมนู)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // CRUD modules จะถูกเพิ่มใน Phase 5
    require __DIR__ . '/kpi.php';
});

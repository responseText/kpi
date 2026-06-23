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
// แดชบอร์ด / Monitor — เปิดสาธารณะ (ทุกคนเข้าดูได้โดยไม่ต้องล็อกอิน)
// ทั้งหมด + แยกตามระดับ (กระทรวง/จังหวัด/โรงพยาบาล) ส่ง level ผ่าน route default
// ---------------------------------------------------------------------------
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/ministry', [DashboardController::class, 'index'])->defaults('level', 'ministry')->name('dashboard.ministry');
Route::get('/dashboard/province', [DashboardController::class, 'index'])->defaults('level', 'province')->name('dashboard.province');
Route::get('/dashboard/hospital', [DashboardController::class, 'index'])->defaults('level', 'hospital')->name('dashboard.hospital');

// โหมดจอ Monitor เต็มจอ (สำหรับทีวี LCD) — เปิดสาธารณะเช่นกัน
Route::get('/monitor', [DashboardController::class, 'monitor'])->name('monitor');

// ---------------------------------------------------------------------------
// Application (ต้อง login)
// ---------------------------------------------------------------------------
Route::middleware('auth')->group(function () {
    // ข้อมูลส่วนตัว (ทุกผู้ใช้แก้ไขของตัวเองได้ — ไม่ต้องมีสิทธิ์เมนู)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // CRUD modules จะถูกเพิ่มใน Phase 5
    require __DIR__ . '/kpi.php';
});

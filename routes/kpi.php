<?php

/**
 * เส้นทางของโมดูล KPI (CRUD) — กลุ่มนี้อยู่ภายใต้ middleware 'auth' แล้ว (จาก web.php)
 * การตรวจสิทธิ์ต่อ action กำหนดในแต่ละ Controller (HasMiddleware → menu:<code>,<action>)
 */

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\IndicatorController;
use App\Http\Controllers\LevelManagerController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\StrategyController;
use App\Http\Controllers\SubStrategyController;
use App\Http\Controllers\TargetController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

// ยุทธศาสตร์
Route::resource('strategies', StrategyController::class)
    ->parameters(['strategies' => 'strategy'])
    ->except(['show'])->names('strategies');

// กลยุทธ์
Route::resource('sub-strategies', SubStrategyController::class)
    ->parameters(['sub-strategies' => 'subStrategy'])
    ->except(['show'])->names('sub-strategies');

// หมวด KPI
Route::resource('categories', CategoryController::class)
    ->parameters(['categories' => 'category'])
    ->except(['show'])->names('categories');

// KPI หลัก
Route::resource('mains', MainController::class)
    ->parameters(['mains' => 'main'])
    ->except(['show'])->names('mains');

// ตัวชี้วัด
Route::resource('indicators', IndicatorController::class)
    ->parameters(['indicators' => 'indicator'])
    ->names('indicators');

// ค่าเป้าหมาย (ผูกกับตัวชี้วัด)
Route::get('targets', [TargetController::class, 'index'])->name('targets.index');
Route::get('targets/{indicator}/edit', [TargetController::class, 'edit'])->name('targets.edit');
Route::put('targets/{indicator}', [TargetController::class, 'update'])->name('targets.update');

// บันทึกผลงาน (ผูกกับตัวชี้วัด)
Route::get('results', [ResultController::class, 'index'])->name('results.index');
Route::get('results/{indicator}/edit', [ResultController::class, 'edit'])->name('results.edit');
Route::put('results/{indicator}', [ResultController::class, 'update'])->name('results.update');

// ผู้รับผิดชอบระดับ
Route::get('level-managers', [LevelManagerController::class, 'index'])->name('level-managers.index');
Route::post('level-managers', [LevelManagerController::class, 'store'])->name('level-managers.store');
Route::delete('level-managers/{levelManager}', [LevelManagerController::class, 'destroy'])->name('level-managers.destroy');

// หน่วยวัด KPI (master — เฉพาะผู้ดูแลระบบสูงสุด)
Route::resource('units', UnitController::class)
    ->parameters(['units' => 'unit'])
    ->except(['show'])->names('units');

// รายงาน
Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

// นำเข้าข้อมูล (Excel) — เฉพาะผู้ดูแลระบบสูงสุด/ผู้ดูแลตัวชี้วัดทั้งหมด
Route::get('imports', [ImportController::class, 'index'])->name('imports.index');
Route::get('imports/{type}/template', [ImportController::class, 'template'])->name('imports.template');
Route::post('imports/{type}', [ImportController::class, 'store'])->name('imports.store');

// สิทธิ์ผู้ใช้งาน
Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
Route::get('permissions/{user}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
Route::put('permissions/{user}', [PermissionController::class, 'update'])->name('permissions.update');

// จัดการผู้ใช้งาน (เปลี่ยนรหัสผ่าน / สถานะ / ระดับ — เฉพาะผู้ดูแลระบบสูงสุด)
Route::get('users', [UserManagementController::class, 'index'])->name('users.index');
Route::get('users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
Route::put('users/{user}', [UserManagementController::class, 'update'])->name('users.update');
Route::put('users/{user}/password', [UserManagementController::class, 'updatePassword'])->name('users.password');

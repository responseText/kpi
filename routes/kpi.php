<?php

/**
 * เส้นทางของโมดูล KPI (CRUD) — กลุ่มนี้อยู่ภายใต้ middleware 'auth' แล้ว (จาก web.php)
 * การตรวจสิทธิ์ต่อ action กำหนดในแต่ละ Controller (HasMiddleware → menu:<code>,<action>)
 */

use App\Http\Controllers\IndicatorController;
use App\Http\Controllers\LevelManagerController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\StrategyController;
use App\Http\Controllers\SubStrategyController;
use App\Http\Controllers\TargetController;
use Illuminate\Support\Facades\Route;

// ยุทธศาสตร์
Route::resource('strategies', StrategyController::class)
    ->parameters(['strategies' => 'strategy'])
    ->except(['show'])->names('strategies');

// กลยุทธ์
Route::resource('sub-strategies', SubStrategyController::class)
    ->parameters(['sub-strategies' => 'subStrategy'])
    ->except(['show'])->names('sub-strategies');

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

// รายงาน
Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

// สิทธิ์ผู้ใช้งาน
Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
Route::get('permissions/{user}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
Route::put('permissions/{user}', [PermissionController::class, 'update'])->name('permissions.update');

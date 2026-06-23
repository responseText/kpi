<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ตัวชี้วัดย้ายมาอยู่ภายใต้ "KPI หลัก" (kpi_main_id)
 * - เพิ่ม kpi_main_id (FK kpi_mains, nullable, ตั้งเป็น null เมื่อ KPI หลักถูกลบ)
 * - ทำให้ sub_strategy_id เป็น nullable (ตัวชี้วัดไม่ผูกกลยุทธ์โดยตรงอีกต่อไป)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('kpi_indicators', 'kpi_main_id')) {
            Schema::table('kpi_indicators', function (Blueprint $table) {
                $table->foreignId('kpi_main_id')->nullable()->after('id')
                    ->constrained('kpi_mains')->nullOnDelete();
            });
        }

        // sub_strategy_id เดิม NOT NULL → ทำให้รับค่าว่างได้ (ตัวชี้วัดที่นำเข้าไม่ผูกกลยุทธ์)
        Schema::table('kpi_indicators', function (Blueprint $table) {
            $table->foreignId('sub_strategy_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('kpi_indicators', 'kpi_main_id')) {
            Schema::table('kpi_indicators', function (Blueprint $table) {
                $table->dropConstrainedForeignId('kpi_main_id');
            });
        }
    }
};

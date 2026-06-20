<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม soft delete ให้ kpi_units (คอลัมน์ deleted_at)
 * และเปลี่ยน unique ของ name → ดัชนีปกติ เพราะความไม่ซ้ำถูกบังคับที่ชั้นแอป (มองข้ามแถวที่ถูกลบ)
 * เพื่อให้ใช้ชื่อเดิมที่เคยลบ (soft delete) สร้างใหม่ได้
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_units', function (Blueprint $table) {
            if (! Schema::hasColumn('kpi_units', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // ปลด unique เดิมของ name แล้วทำเป็นดัชนีปกติแทน (ความไม่ซ้ำเช็คที่ UnitRequest)
        Schema::table('kpi_units', function (Blueprint $table) {
            $table->dropUnique('kpi_units_name_unique');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_units', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->unique('name');
            $table->dropSoftDeletes();
        });
    }
};

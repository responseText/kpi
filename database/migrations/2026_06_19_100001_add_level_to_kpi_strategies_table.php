<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มคอลัมน์ level ให้ kpi_strategies — 1 ยุทธศาสตร์ สังกัด 1 ระดับตัวชี้วัด
 * (hospital/province/ministry) ชื่อยุทธศาสตร์ซ้ำกันได้ถ้าอยู่คนละระดับ (บังคับที่ชั้น validation)
 * แถวเดิมที่มีอยู่จะถูกตั้งค่าเริ่มต้นเป็น 'hospital'
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kpi_strategies') || Schema::hasColumn('kpi_strategies', 'level')) {
            return;
        }

        Schema::table('kpi_strategies', function (Blueprint $table) {
            $table->string('level', 20)->default('hospital')->after('year')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('kpi_strategies', 'level')) {
            return;
        }

        Schema::table('kpi_strategies', function (Blueprint $table) {
            $table->dropIndex(['level']);
            $table->dropColumn('level');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มค่า "ตัวตั้ง (A)" และ "ตัวหาร (B)" รายช่วงเวลาในการบันทึกผล
 * สำหรับตัวชี้วัดประเภทที่ต้องมี A/B (PERCENT/RATE/AVERAGE/RATIO)
 * ระบบจะคำนวณ result_value จาก A/B ตามสูตรของประเภทการวัดให้อัตโนมัติ
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_results', function (Blueprint $table) {
            if (! Schema::hasColumn('kpi_results', 'numerator_value')) {
                $table->decimal('numerator_value', 15, 4)->nullable()->after('result_value');
            }
            if (! Schema::hasColumn('kpi_results', 'denominator_value')) {
                $table->decimal('denominator_value', 15, 4)->nullable()->after('numerator_value');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kpi_results', function (Blueprint $table) {
            foreach (['numerator_value', 'denominator_value'] as $col) {
                if (Schema::hasColumn('kpi_results', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

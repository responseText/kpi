<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ตาราง kpi_units — หน่วยวัดของตัวชี้วัด (master) จัดกลุ่มตามหลักการบริหารผลงาน (Performance Measurement)
 * group_code: กลุ่ม KPI 5 ประเภท (quantity/quality/time/cost/efficiency) — ค่าคงที่ในระบบ (KpiUnit::GROUPS)
 * จัดการได้เฉพาะผู้ดูแลระบบสูงสุด — หน่วยวัดถูกเก็บลง kpi_indicators.unit เป็นข้อความ (ไม่ผูก FK)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kpi_units')) {
            return;
        }

        Schema::create('kpi_units', function (Blueprint $table) {
            $table->id();
            $table->string('group_code', 20)->index();      // quantity|quality|time|cost|efficiency
            $table->string('name', 50)->unique();            // เช่น ร้อยละ, ครั้ง, คน
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('orderby')->default(0);
            $table->string('status', 20)->default('enable');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_units');
    }
};

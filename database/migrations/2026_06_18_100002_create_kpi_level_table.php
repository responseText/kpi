<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ตาราง kpi_level — ระดับสิทธิ์การใช้งานของระบบ KPI (master)
 * ใช้กำหนดบทบาทของผู้ใช้ เช่น ผู้ดูแลระบบสูงสุด / ผู้ดูแลตัวชี้วัด (รายระดับ) / ผู้รับผิดชอบตัวชี้วัด
 * scope: ขอบเขตของผู้ดูแลตัวชี้วัด (all/hospital/province/ministry) — null สำหรับบทบาทที่ไม่อิงระดับ
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kpi_level')) {
            return;
        }

        Schema::create('kpi_level', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();         // รหัสบทบาท เช่น super_admin
            $table->string('name', 150);                    // ชื่อแสดงผล (ไทย)
            $table->string('scope', 20)->nullable();        // all|hospital|province|ministry|null
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('orderby')->default(0);
            $table->string('status', 20)->default('enable');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_level');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * kpi_categories — หมวด KPI (อยู่ภายใต้กลยุทธ์ — ไม่บังคับ)
 * โครงสร้าง: ยุทธศาสตร์ → กลยุทธ์ → หมวด KPI → KPI หลัก → ตัวชี้วัด
 * sub_strategy_id เป็น nullable เพราะข้อมูลที่นำเข้าจาก MOU ยังไม่ได้ผูกกับกลยุทธ์
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kpi_categories')) {
            return;
        }

        Schema::create('kpi_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_strategy_id')->nullable()->constrained('kpi_sub_strategies')->nullOnDelete();
            $table->string('code', 50)->nullable();
            $table->string('name', 500);
            $table->text('description')->nullable();
            $table->integer('orderby')->default(0);
            $table->string('status', 20)->default('enable');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_categories');
    }
};

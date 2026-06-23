<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * kpi_mains — KPI หลัก (อยู่ภายใต้หมวด KPI เดียว)
 * ใน 1 หมวด KPI มีได้หลาย KPI หลัก และตัวชี้วัดอยู่ภายใต้ KPI หลัก
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kpi_mains')) {
            return;
        }

        Schema::create('kpi_mains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('kpi_categories')->cascadeOnDelete();
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
        Schema::dropIfExists('kpi_mains');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * kpi_sub_strategies — กลยุทธ์ (อยู่ภายใต้ยุทธศาสตร์เดียว)
 * ใน 1 ยุทธศาสตร์มีหลายกลยุทธ์ และแต่ละกลยุทธ์มี 1 ยุทธศาสตร์เท่านั้น
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kpi_sub_strategies')) {
            return;
        }

        Schema::create('kpi_sub_strategies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_id')->constrained('kpi_strategies')->cascadeOnDelete();
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
        Schema::dropIfExists('kpi_sub_strategies');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * kpi_strategies — ยุทธศาสตร์ (เปลี่ยนทุกปีผ่านคอลัมน์ year = ปี พ.ศ.)
 * ใน 1 ปีมีหลายยุทธศาสตร์
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kpi_strategies')) {
            return;
        }

        Schema::create('kpi_strategies', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('year')->index();          // ปี พ.ศ. ของแผน เช่น 2569
            $table->string('code', 50)->nullable();         // รหัสยุทธศาสตร์
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
        Schema::dropIfExists('kpi_strategies');
    }
};

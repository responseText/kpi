<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * kpi_level_managers — ผู้รับผิดชอบ/ผู้กำหนดตัวชี้วัดในแต่ละระดับ
 * role:
 *   - responsible = ผู้รับผิดชอบแต่ละระดับ
 *   - definer     = ผู้รับผิดชอบในการกำหนดตัวชี้วัดแต่ละระดับ
 * year (null = ใช้ได้ทุกปี)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kpi_level_managers')) {
            return;
        }

        Schema::create('kpi_level_managers', function (Blueprint $table) {
            $table->id();
            $table->enum('level', ['hospital', 'province', 'ministry'])->index();
            $table->unsignedInteger('user_id')->index();
            $table->enum('role', ['responsible', 'definer'])->default('responsible');
            $table->smallInteger('year')->nullable();
            $table->timestamps();

            $table->unique(['level', 'user_id', 'role', 'year'], 'kpi_level_managers_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_level_managers');
    }
};

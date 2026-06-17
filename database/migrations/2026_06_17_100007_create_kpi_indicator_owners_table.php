<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * kpi_indicator_owners — ผู้รับผิดชอบตัวชี้วัด (อย่างน้อย 1 คน, ได้หลายคน)
 * user_id อ้างอิง users.id
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kpi_indicator_owners')) {
            return;
        }

        Schema::create('kpi_indicator_owners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained('kpi_indicators')->cascadeOnDelete();
            $table->unsignedInteger('user_id')->index();
            $table->boolean('is_primary')->default(false);   // ผู้รับผิดชอบหลัก
            $table->timestamps();

            $table->unique(['indicator_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_indicator_owners');
    }
};

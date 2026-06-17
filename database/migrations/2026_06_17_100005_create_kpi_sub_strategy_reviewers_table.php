<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * kpi_sub_strategy_reviewers — ผู้ตรวจสอบกลยุทธ์ (อย่างน้อย 1 คน, ได้หลายคน)
 * user_id อ้างอิง users.id
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kpi_sub_strategy_reviewers')) {
            return;
        }

        Schema::create('kpi_sub_strategy_reviewers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_strategy_id')->constrained('kpi_sub_strategies')->cascadeOnDelete();
            $table->unsignedInteger('user_id')->index();
            $table->timestamps();

            $table->unique(['sub_strategy_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_sub_strategy_reviewers');
    }
};

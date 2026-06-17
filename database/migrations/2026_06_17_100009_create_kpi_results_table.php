<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * kpi_results — ผลงานที่ทำได้ ตามช่วงเวลา (ผูกกับ kpi_targets)
 * pass_status: pass/fail/pending ประเมินจาก operator+target_value
 * recorded_by: ผู้บันทึกค่าผลงาน (users.id)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kpi_results')) {
            return;
        }

        Schema::create('kpi_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_id')->constrained('kpi_targets')->cascadeOnDelete();
            $table->foreignId('indicator_id')->constrained('kpi_indicators')->cascadeOnDelete();
            $table->decimal('result_value', 15, 2)->nullable();
            $table->string('result_text', 191)->nullable();   // กรณีผ่าน/ไม่ผ่าน
            $table->enum('pass_status', ['pass', 'fail', 'pending'])->default('pending');
            $table->text('note')->nullable();
            $table->unsignedInteger('recorded_by')->nullable()->index();   // ผู้บันทึก
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('target_id');   // 1 ผลงานต่อ 1 ช่วงเวลา
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_results');
    }
};

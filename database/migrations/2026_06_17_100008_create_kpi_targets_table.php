<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * kpi_targets — ค่าเป้าหมายรายช่วงเวลาของตัวชี้วัด
 * period_no: 0 = รายปี (ทั้งปี), 1..4 = ไตรมาส
 * operator: เงื่อนไขเกณฑ์ > >= < <= != = หรือ ผ่าน/ไม่ผ่าน
 * start_date/end_date เก็บเป็นวันที่ ค.ศ. (คำนวณจาก year_type + year + period_no)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kpi_targets')) {
            return;
        }

        Schema::create('kpi_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained('kpi_indicators')->cascadeOnDelete();
            $table->unsignedTinyInteger('period_no');         // 0=รายปี, 1..4=ไตรมาส
            $table->string('period_label', 50);               // เช่น "รายปี", "ไตรมาส 1"
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('operator', ['gt', 'gte', 'lt', 'lte', 'ne', 'eq', 'passfail'])->default('gte');
            $table->decimal('target_value', 15, 2)->nullable();
            $table->string('target_text', 191)->nullable();   // กรณีผ่าน/ไม่ผ่าน หรือคำอธิบายเป้าหมาย
            $table->timestamps();

            $table->unique(['indicator_id', 'period_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_targets');
    }
};

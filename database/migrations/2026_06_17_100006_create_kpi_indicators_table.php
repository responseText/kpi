<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * kpi_indicators — ตัวชี้วัด (KPI)
 * - อยู่ภายใต้กลยุทธ์เดียว (ยุทธศาสตร์อนุมานผ่านกลยุทธ์)
 * - level: ระดับ รพ.ทองแสนขัน / จังหวัด / กระทรวง
 * - year_type: ปีพ.ศ. (buddhist) หรือ ปีงบประมาณ (fiscal)
 * - period_type: รายปี (annual) หรือ รายไตรมาส (quarterly)
 * ค่าเป้าหมาย/เกณฑ์เก็บแยกในตาราง kpi_targets (รายช่วงเวลา)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kpi_indicators')) {
            return;
        }

        Schema::create('kpi_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_strategy_id')->constrained('kpi_sub_strategies')->cascadeOnDelete();
            $table->enum('level', ['hospital', 'province', 'ministry'])->index();
            $table->string('code', 50)->nullable();
            $table->string('name', 500);
            $table->enum('year_type', ['buddhist', 'fiscal'])->default('fiscal');
            $table->smallInteger('year')->index();           // ปี พ.ศ. เช่น 2569
            $table->enum('period_type', ['annual', 'quarterly'])->default('annual');
            $table->string('unit', 50)->nullable();          // หน่วยวัด เช่น ร้อยละ ครั้ง คน
            $table->text('description')->nullable();          // นิยาม/รายละเอียด
            $table->integer('orderby')->default(0);
            $table->string('status', 20)->default('enable');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_indicators');
    }
};

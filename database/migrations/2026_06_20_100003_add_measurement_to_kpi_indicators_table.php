<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มข้อมูล "ประเภทการวัด" (Measurement Type) และรายละเอียดสูตรให้ kpi_indicators
 *   - measurement_type  : ประเภทการวัด (count/score/level/.../ratio) — ค่าคงที่ในระบบ
 *   - numerator_label   : นิยามตัวตั้ง (A)  เช่น "จำนวนผู้ป่วยที่ได้รับการรักษาตามเกณฑ์"
 *   - denominator_label : นิยามตัวหาร (B)  เช่น "จำนวนผู้ป่วยทั้งหมด"
 *   - formula           : สูตร/เกณฑ์ที่กรอกเอง (เกณฑ์ระดับ/การจัดอันดับ/สูตร INDEX)
 *   - factor            : ค่าคงที่ K สำหรับประเภท RATE เช่น 100000 (ต่อแสนประชากร)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_indicators', function (Blueprint $table) {
            if (! Schema::hasColumn('kpi_indicators', 'measurement_type')) {
                $table->string('measurement_type', 20)->nullable()->after('unit')->index();
            }
            if (! Schema::hasColumn('kpi_indicators', 'numerator_label')) {
                $table->string('numerator_label', 255)->nullable()->after('measurement_type');
            }
            if (! Schema::hasColumn('kpi_indicators', 'denominator_label')) {
                $table->string('denominator_label', 255)->nullable()->after('numerator_label');
            }
            if (! Schema::hasColumn('kpi_indicators', 'formula')) {
                $table->string('formula', 500)->nullable()->after('denominator_label');
            }
            if (! Schema::hasColumn('kpi_indicators', 'factor')) {
                $table->decimal('factor', 15, 4)->nullable()->after('formula');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kpi_indicators', function (Blueprint $table) {
            foreach (['measurement_type', 'numerator_label', 'denominator_label', 'formula', 'factor'] as $col) {
                if (Schema::hasColumn('kpi_indicators', $col)) {
                    if ($col === 'measurement_type') {
                        $table->dropIndex(['measurement_type']);
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};

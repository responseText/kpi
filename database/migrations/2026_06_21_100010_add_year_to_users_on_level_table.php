<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม "ปี พ.ศ. ที่รับผิดชอบ" ให้กับการมอบหมายบทบาทใน users_on_level
 * - ใช้กับบทบาทผู้ดูแลระดับตัวชี้วัด (รพ./จังหวัด/กระทรวง) เพื่อจำกัดขอบเขตตามปี
 * - null = ทุกปี (ผู้ดูแลทั้งหมด/ผู้ดูแลระบบสูงสุด/บทบาทที่ไม่ผูกปี และแถวเดิมก่อนเพิ่มฟีเจอร์)
 * 1 ผู้ใช้มีได้หลายแถว (level_id + year) เพื่อรับผิดชอบหลายปี/หลายระดับ
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users_on_level') || Schema::hasColumn('users_on_level', 'year')) {
            return;
        }

        Schema::table('users_on_level', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->nullable()->after('level_id')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users_on_level') || ! Schema::hasColumn('users_on_level', 'year')) {
            return;
        }

        Schema::table('users_on_level', function (Blueprint $table) {
            $table->dropIndex(['year']);
            $table->dropColumn('year');
        });
    }
};

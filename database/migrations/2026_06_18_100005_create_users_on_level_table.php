<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ตาราง users_on_level — บทบาท/ระดับสิทธิ์ของผู้ใช้ "แยกตามระบบ" (รองรับหลายระบบบนฐานข้อมูล coretsk)
 * - alias_system : ระบบที่บทบาทนี้สังกัด (เช่น 'kpi')
 * - level_id     : id ของระดับสิทธิ์ในตารางระดับของระบบนั้น (kpi → kpi_level.id) — ไม่ผูก hard FK เพื่อความเป็นกลางข้ามระบบ
 * - is_super_admin : ผู้ดูแลระบบสูงสุดของระบบนั้น
 * 1 ผู้ใช้มีได้ 1 บทบาทต่อ 1 ระบบ (unique user_id + alias_system)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users_on_level')) {
            return;
        }

        Schema::create('users_on_level', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->index();
            $table->string('alias_system', 50)->default('kpi')->index();
            $table->unsignedBigInteger('level_id')->nullable()->index();
            $table->boolean('is_super_admin')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'alias_system']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_on_level');
    }
};

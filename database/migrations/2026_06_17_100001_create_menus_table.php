<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ตาราง menus — รายการเมนู/สิทธิ์การใช้งานกลาง (ใช้ร่วมได้หลายระบบ)
 * "ชื่อเสมือน" ของการใช้งาน = system + code เช่น system='kpi', code='kpi.indicator'
 * ออกแบบให้ต่อยอดระบบอื่นในอนาคตได้ด้วยการเพิ่มแถวที่ system อื่น
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('menus')) {
            return;
        }

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('system', 50)->default('kpi')->index();   // ระบบที่เมนูสังกัด
            $table->string('code', 100)->unique();                    // รหัสเสมือน เช่น kpi.indicator
            $table->string('name', 191);                              // ชื่อแสดงผล (ไทย)
            $table->unsignedBigInteger('parent_id')->nullable();      // เมนูแม่ (เมนูย่อย)
            $table->string('route', 191)->nullable();                 // ชื่อ route ปลายทาง
            $table->string('icon', 100)->nullable();
            $table->integer('orderby')->default(0);
            $table->string('status', 20)->default('enable');
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('menus')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};

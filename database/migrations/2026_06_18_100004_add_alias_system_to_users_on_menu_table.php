<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม alias_system ให้ users_on_menu — ระบุว่าสิทธิ์นี้เป็นของระบบใด (เริ่มต้น 'kpi')
 * รองรับการต่อยอดสิทธิ์ข้ามระบบในอนาคต
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users_on_menu', 'alias_system')) {
            return;
        }

        Schema::table('users_on_menu', function (Blueprint $table) {
            $table->string('alias_system', 50)->default('kpi')->after('user_id')->index();
        });

        // เติมค่าให้แถวเดิมที่ยังว่าง
        Schema::getConnection()->table('users_on_menu')
            ->whereNull('alias_system')->orWhere('alias_system', '')
            ->update(['alias_system' => 'kpi']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('users_on_menu', 'alias_system')) {
            Schema::table('users_on_menu', function (Blueprint $table) {
                $table->dropColumn('alias_system');
            });
        }
    }
};

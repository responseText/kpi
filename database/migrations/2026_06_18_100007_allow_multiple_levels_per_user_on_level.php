<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * อนุญาตให้ผู้ใช้ 1 คนมีได้หลายบทบาทต่อ 1 ระบบ
 * → ถอด unique(user_id, alias_system) ออก เหลือเป็น index ปกติ
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users_on_level')) {
            return;
        }

        Schema::table('users_on_level', function (Blueprint $table) {
            try {
                $table->dropUnique(['user_id', 'alias_system']);
            } catch (\Throwable $e) {
                // ไม่มี unique เดิมก็ข้าม
            }
            // index ช่วยค้นหาบทบาทของผู้ใช้ในระบบหนึ่ง ๆ (ไม่บังคับ unique)
            $table->index(['user_id', 'alias_system'], 'users_on_level_user_alias_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users_on_level')) {
            return;
        }

        Schema::table('users_on_level', function (Blueprint $table) {
            try {
                $table->dropIndex('users_on_level_user_alias_idx');
            } catch (\Throwable $e) {
            }
            $table->unique(['user_id', 'alias_system']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ตาราง users_on_menu — สิทธิ์การใช้งานต่อ user ต่อเมนู (action flags)
 * user_id อ้างอิง users.id (int unsigned) — ใช้ index ไม่ผูก hard FK ข้ามตาราง legacy
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users_on_menu')) {
            return;
        }

        Schema::create('users_on_menu', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->index();
            $table->unsignedBigInteger('menu_id');
            $table->boolean('can_view')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'menu_id']);
            $table->foreign('menu_id')->references('id')->on('menus')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_on_menu');
    }
};

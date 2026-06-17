<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม kpi_level_id ให้ users — บทบาทของผู้ใช้ในระบบ KPI (FK → kpi_level)
 * ใช้ index + nullOnDelete (ไม่ผูก hard FK ถ้าตาราง kpi_level ยังไม่มี)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'kpi_level_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('kpi_level_id')->nullable()->index()->after('is_super_admin');
        });

        if (Schema::hasTable('kpi_level')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('kpi_level_id')->references('id')->on('kpi_level')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'kpi_level_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            try {
                $table->dropForeign(['kpi_level_id']);
            } catch (\Throwable $e) {
                // ไม่มี FK ก็ข้าม
            }
            $table->dropColumn('kpi_level_id');
        });
    }
};

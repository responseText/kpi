<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ย้ายข้อมูลบทบาทของระบบ KPI (is_super_admin, kpi_level_id) ออกจากตาราง users
 * ไปเก็บที่ users_on_level (alias_system='kpi') แล้วลบคอลัมน์ออกจาก users
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) สำเนาข้อมูลเดิมจาก users → users_on_level
        if (Schema::hasTable('users_on_level')
            && (Schema::hasColumn('users', 'is_super_admin') || Schema::hasColumn('users', 'kpi_level_id'))) {

            $rows = DB::table('users')
                ->select('id', 'is_super_admin', 'kpi_level_id')
                ->where(function ($q) {
                    $q->where('is_super_admin', 1)->orWhereNotNull('kpi_level_id');
                })
                ->get();

            foreach ($rows as $r) {
                DB::table('users_on_level')->updateOrInsert(
                    ['user_id' => $r->id, 'alias_system' => 'kpi'],
                    [
                        'level_id' => $r->kpi_level_id,
                        'is_super_admin' => (int) $r->is_super_admin,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        // 2) ลบคอลัมน์ออกจาก users
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'kpi_level_id')) {
                try {
                    $table->dropForeign(['kpi_level_id']);
                } catch (\Throwable $e) {
                    // ไม่มี FK ก็ข้าม
                }
                $table->dropColumn('kpi_level_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_super_admin')) {
                $table->dropColumn('is_super_admin');
            }
        });
    }

    public function down(): void
    {
        // คืนคอลัมน์ + สำเนาข้อมูลกลับ
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_super_admin')) {
                $table->boolean('is_super_admin')->default(false)->after('status');
            }
            if (! Schema::hasColumn('users', 'kpi_level_id')) {
                $table->unsignedBigInteger('kpi_level_id')->nullable()->index()->after('is_super_admin');
            }
        });

        if (Schema::hasTable('users_on_level')) {
            $rows = DB::table('users_on_level')->where('alias_system', 'kpi')->get();
            foreach ($rows as $r) {
                DB::table('users')->where('id', $r->user_id)->update([
                    'is_super_admin' => (int) $r->is_super_admin,
                    'kpi_level_id' => $r->level_id,
                ]);
            }
        }
    }
};

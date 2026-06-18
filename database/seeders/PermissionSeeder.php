<?php

namespace Database\Seeders;

use App\Models\KpiLevel;
use App\Models\Menu;
use App\Models\UserOnLevel;
use App\Models\UserOnMenu;
use Illuminate\Database\Seeder;

/**
 * ตั้งสิทธิ์เริ่มต้น (เก็บบทบาทที่ users_on_level — แยกตามระบบ):
 * - user id=1 (admin) → ผู้ดูแลระบบสูงสุด (is_super_admin = true + บทบาท super_admin)
 * - user id=2 (dogtorart) → สิทธิ์เต็มรายเมนู (ไม่ใช่ super admin)
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // id=1 เป็น super admin — เก็บที่ users_on_level (alias_system='kpi')
        // ระบุ is_super_admin ในเงื่อนไขค้นหา เพราะ 1 ผู้ใช้มีได้หลายบทบาทต่อระบบ
        $superLevelId = KpiLevel::where('code', KpiLevel::SUPER_ADMIN)->value('id');
        UserOnLevel::updateOrCreate(
            ['user_id' => 1, 'alias_system' => 'kpi', 'is_super_admin' => true],
            ['level_id' => $superLevelId],
        );

        // id=2 สิทธิ์เต็มรายเมนู (เฉพาะเมนูระดับบนสุด — เมนูย่อยสืบทอดสิทธิ์)
        $menuIds = Menu::system('kpi')->whereNull('parent_id')->pluck('id');
        foreach ($menuIds as $menuId) {
            UserOnMenu::updateOrCreate(
                ['user_id' => 2, 'menu_id' => $menuId],
                [
                    'alias_system' => 'kpi',
                    'can_view'   => true,
                    'can_create' => true,
                    'can_edit'   => true,
                    'can_delete' => true,
                ]
            );
        }
    }
}

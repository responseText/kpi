<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\User;
use App\Models\UserOnMenu;
use Illuminate\Database\Seeder;

/**
 * ตั้งสิทธิ์เริ่มต้น:
 * - user id=1 (admin) → ผู้ดูแลระบบสูงสุด (is_super_admin = true)
 * - user id=2 (dogtorart) → สิทธิ์เต็มรายเมนู (ไม่ใช่ super admin)
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // id=1 เป็น super admin — ไม่ต้องตั้งรายเมนู
        User::where('id', 1)->update(['is_super_admin' => true]);

        // id=2 สิทธิ์เต็มรายเมนู
        $menuIds = Menu::system('kpi')->pluck('id');
        foreach ($menuIds as $menuId) {
            UserOnMenu::updateOrCreate(
                ['user_id' => 2, 'menu_id' => $menuId],
                [
                    'can_view'   => true,
                    'can_create' => true,
                    'can_edit'   => true,
                    'can_delete' => true,
                ]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\UserOnMenu;
use Illuminate\Database\Seeder;

/**
 * ให้สิทธิ์เต็มแก่ผู้ดูแลระบบเริ่มต้น (admin = id 1, dogtorart = id 2)
 * ในทุกเมนูของระบบ KPI
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $adminUserIds = [1, 2];
        $menuIds = Menu::system('kpi')->pluck('id');

        foreach ($adminUserIds as $userId) {
            foreach ($menuIds as $menuId) {
                UserOnMenu::updateOrCreate(
                    ['user_id' => $userId, 'menu_id' => $menuId],
                    [
                        'can_view' => true,
                        'can_create' => true,
                        'can_edit' => true,
                        'can_delete' => true,
                    ]
                );
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

/**
 * เมนู/สิทธิ์ของระบบ KPI (system = 'kpi')
 * โครงสร้างนี้สามารถต่อยอดระบบอื่นได้ในอนาคต โดยเพิ่ม system อื่น
 */
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            ['code' => 'kpi.dashboard', 'name' => 'แดชบอร์ด / Monitor', 'route' => 'dashboard', 'icon' => 'dashboard'],
            ['code' => 'kpi.strategy', 'name' => 'ยุทธศาสตร์', 'route' => 'strategies.index', 'icon' => 'strategy'],
            ['code' => 'kpi.sub_strategy', 'name' => 'กลยุทธ์', 'route' => 'sub-strategies.index', 'icon' => 'sub_strategy'],
            ['code' => 'kpi.indicator', 'name' => 'ตัวชี้วัด', 'route' => 'indicators.index', 'icon' => 'indicator'],
            ['code' => 'kpi.target', 'name' => 'กำหนดค่าเป้าหมาย', 'route' => 'targets.index', 'icon' => 'target'],
            ['code' => 'kpi.result', 'name' => 'บันทึกผลงาน', 'route' => 'results.index', 'icon' => 'result'],
            ['code' => 'kpi.level_manager', 'name' => 'ผู้รับผิดชอบระดับ', 'route' => 'level-managers.index', 'icon' => 'level'],
            ['code' => 'kpi.report', 'name' => 'รายงานสรุปผล', 'route' => 'reports.index', 'icon' => 'report'],
            ['code' => 'kpi.permission', 'name' => 'สิทธิ์ผู้ใช้งาน', 'route' => 'permissions.index', 'icon' => 'permission'],
        ];

        foreach ($menus as $i => $menu) {
            Menu::updateOrCreate(
                ['code' => $menu['code']],
                [
                    'system' => 'kpi',
                    'name' => $menu['name'],
                    'route' => $menu['route'],
                    'icon' => $menu['icon'],
                    'parent_id' => null,
                    'orderby' => ($i + 1) * 10,
                    'status' => 'enable',
                ]
            );
        }
    }
}

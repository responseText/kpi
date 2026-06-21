<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * บริการจัดการสิทธิ์ + สร้างแถบนำทางตามสิทธิ์ของผู้ใช้
 */
class PermissionService
{
    /** ตรวจสิทธิ์ผู้ใช้ต่อเมนู+action */
    public function check(?User $user, string $menuCode, string $action = 'view'): bool
    {
        return $user?->canMenu($menuCode, $action) ?? false;
    }

    /**
     * เมนูสำหรับแถบนำทาง (เฉพาะที่ผู้ใช้มี can_view) จัดเป็นโครงสร้าง parent → children
     *
     * @return Collection<int, Menu>
     */
    public function navigationFor(User $user, string $system = 'kpi'): Collection
    {
        $menus = Menu::system($system)->enabled()->orderBy('orderby')->get();

        $byParent = $menus->groupBy(fn (Menu $m) => $m->parent_id ?? 0);

        // เมนูระดับบนสุดที่ผู้ใช้มีสิทธิ์ดู — เมนูย่อยสืบทอดสิทธิ์จากเมนูแม่
        return $menus
            ->whereNull('parent_id')
            ->filter(function (Menu $m) use ($user) {
                // แดชบอร์ด / Monitor: เปิดให้ผู้ใช้ทุกคนเห็นเสมอ (ไม่อิงสิทธิ์รายเมนู)
                if ($m->code === 'kpi.dashboard') {
                    return true;
                }
                // เมนูพิเศษที่ควบคุมด้วยบทบาท (ไม่อิงสิทธิ์รายเมนู)
                // เฉพาะผู้ดูแลระบบสูงสุด/ผู้ดูแลตัวชี้วัดทั้งหมดเท่านั้นที่เห็น
                if ($m->code === 'kpi.permission') {
                    return $user->canManagePermissions();
                }
                if ($m->code === 'kpi.level_manager') {
                    return $user->canManageLevelManagers();
                }
                // จัดการผู้ใช้งาน: เฉพาะผู้ดูแลระบบสูงสุดเท่านั้น
                if ($m->code === 'kpi.user') {
                    return $user->canManageUsers();
                }
                // จัดการหน่วยวัด KPI: เฉพาะผู้ดูแลระบบสูงสุดเท่านั้น
                if ($m->code === 'kpi.unit') {
                    return $user->canManageUnits();
                }
                // ยุทธศาสตร์ + กลยุทธ์ + ตัวชี้วัด + กำหนดค่าเป้าหมาย:
                // เฉพาะผู้ดูแลตัวชี้วัด (ทุกระดับ/ทั้งหมด/รายระดับ) — เนื้อหาถูกสโคปตามระดับอีกชั้นในแต่ละหน้า
                if (in_array($m->code, ['kpi.strategy', 'kpi.sub_strategy', 'kpi.indicator', 'kpi.target'], true)) {
                    return $user->isIndicatorManager();
                }
                // บันทึกผลงาน: ผู้รับผิดชอบตัวชี้วัด/ผู้ดูแล เห็นได้เสมอ (ไม่ต้องรอกำหนดสิทธิ์เมนู)
                if ($m->code === 'kpi.result') {
                    return $user->canAccessResults();
                }

                return $user->hasMenu($m->code);
            })
            ->map(function (Menu $m) use ($byParent) {
                $m->setRelation('visibleChildren', $byParent->get($m->id, collect())->values());

                return $m;
            })
            ->values();
    }
}

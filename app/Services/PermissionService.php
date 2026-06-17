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

        // เฉพาะเมนูที่ผู้ใช้ดูได้
        $visible = $menus->filter(fn (Menu $m) => $user->hasMenu($m->code));

        $byParent = $visible->groupBy(fn (Menu $m) => $m->parent_id ?? 0);

        // เมนูระดับบนสุด พร้อมแนบลูกที่มองเห็นได้
        return $visible
            ->whereNull('parent_id')
            ->map(function (Menu $m) use ($byParent) {
                $m->setRelation('visibleChildren', $byParent->get($m->id, collect()));

                return $m;
            })
            ->values();
    }
}

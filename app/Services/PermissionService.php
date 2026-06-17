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

        // เมนูกำหนดสิทธิ์: เฉพาะผู้ดูแลระบบสูงสุดเท่านั้นที่เห็น
        $visible = $menus->filter(function (Menu $m) use ($user) {
            if ($m->code === 'kpi.permission' && ! $user->is_super_admin) {
                return false;
            }

            return $user->hasMenu($m->code);
        });

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

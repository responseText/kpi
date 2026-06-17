<?php

namespace App\Repositories\Eloquent;

use App\Models\KpiLevel;
use App\Models\Menu;
use App\Models\User;
use App\Models\UserOnMenu;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PermissionRepository implements PermissionRepositoryInterface
{
    public function paginateUsers(?string $search, int $perPage = 30): LengthAwarePaginator
    {
        return User::query()
            ->with(['employee', 'kpiLevel'])
            ->withCount('menuPermissions')
            ->when($search, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function assignableLevels(): Collection
    {
        // ผู้ดูแลระบบสูงสุดกำหนดผ่าน UI ไม่ได้ (bootstrap ผ่าน seeder/DB เท่านั้น)
        return KpiLevel::query()
            ->enabled()
            ->where('code', '!=', KpiLevel::SUPER_ADMIN)
            ->orderBy('orderby')
            ->get();
    }

    public function menus(string $system = 'kpi'): Collection
    {
        return Menu::system($system)->orderBy('orderby')->get();
    }

    public function permissionsForUser(int $userId): Collection
    {
        return UserOnMenu::where('user_id', $userId)->get()->keyBy('menu_id');
    }

    public function syncUserPermissions(int $userId, array $rows): void
    {
        foreach ($rows as $menuId => $flags) {
            $hasAny = ($flags['can_view'] ?? false) || ($flags['can_create'] ?? false)
                || ($flags['can_edit'] ?? false) || ($flags['can_delete'] ?? false);

            if (! $hasAny) {
                // ไม่มีสิทธิ์ใด ๆ → ลบแถวออกเพื่อไม่ให้รก
                UserOnMenu::where('user_id', $userId)->where('menu_id', $menuId)->delete();

                continue;
            }

            UserOnMenu::updateOrCreate(
                ['user_id' => $userId, 'menu_id' => $menuId],
                [
                    'alias_system' => 'kpi',
                    'can_view' => (bool) ($flags['can_view'] ?? false),
                    'can_create' => (bool) ($flags['can_create'] ?? false),
                    'can_edit' => (bool) ($flags['can_edit'] ?? false),
                    'can_delete' => (bool) ($flags['can_delete'] ?? false),
                ]
            );
        }
    }

    public function selectableUsers(): Collection
    {
        return User::query()
            ->with('employee')
            ->where('status', 'enable')
            ->orderBy('name')
            ->get();
    }
}

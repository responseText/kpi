<?php

namespace App\Http\Controllers;

use App\Models\KpiLevel;
use App\Models\User;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class PermissionController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissions,
    ) {}

    public static function middleware(): array
    {
        return [
            // ผู้ดูแลระบบสูงสุด หรือผู้ดูแลตัวชี้วัดทั้งหมด เท่านั้นที่กำหนดสิทธิ์ผู้ใช้งานอื่นได้
            function ($request, $next) {
                abort_unless(
                    (bool) $request->user()?->canManagePermissions(),
                    403,
                    'คุณไม่มีสิทธิ์กำหนดสิทธิ์การใช้งานของผู้ใช้อื่น'
                );

                return $next($request);
            },
        ];
    }

    public function index(Request $request): View
    {
        $search = $request->string('search')->toString() ?: null;
        $users = $this->permissions->paginateUsers($search);

        return view('permissions.index', compact('users', 'search'));
    }

    public function edit(User $user): View
    {
        $menus = $this->permissions->menus('kpi');
        $current = $this->permissions->permissionsForUser($user->id);
        $levels = $this->permissions->assignableLevels();
        $years = $this->permissions->assignableYears();

        return view('permissions.edit', compact('user', 'menus', 'current', 'levels', 'years'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        // ผู้ดูแลระบบสูงสุดถูกป้องกัน — ไม่มีผู้ใด (รวมถึงตนเอง) ปรับสิทธิ์ได้
        if ($user->is_super_admin) {
            return back()->with('error', 'ไม่สามารถปรับสิทธิ์ของผู้ดูแลระบบสูงสุดได้');
        }

        $validated = $request->validate([
            'kpi_level_ids' => ['array'],
            'kpi_level_ids.*' => ['integer', 'exists:kpi_level,id'],
            'kpi_level_years' => ['array'],
            'kpi_level_years.*' => ['array'],
            'permissions' => ['array'],
            'permissions.*.can_view' => ['nullable', 'boolean'],
            'permissions.*.can_create' => ['nullable', 'boolean'],
            'permissions.*.can_edit' => ['nullable', 'boolean'],
            'permissions.*.can_delete' => ['nullable', 'boolean'],
        ]);

        $levelIds = $validated['kpi_level_ids'] ?? [];

        // ห้ามกำหนดบทบาทผู้ดูแลระบบสูงสุดผ่านหน้านี้ (bootstrap ผ่าน seeder/DB เท่านั้น)
        if ($levelIds && KpiLevel::whereIn('id', $levelIds)->where('code', KpiLevel::SUPER_ADMIN)->exists()) {
            return back()->with('error', 'ไม่สามารถกำหนดบทบาทผู้ดูแลระบบสูงสุดผ่านหน้านี้ได้');
        }

        // เก็บบทบาทที่ users_on_level (แยกตามระบบ, ได้หลายบทบาท + ปีที่รับผิดชอบ) — ผ่าน UI กำหนด super admin ไม่ได้
        $this->permissions->setUserKpiLevels($user->id, $levelIds, $validated['kpi_level_years'] ?? []);

        // เมนูทั้งหมดของระบบ เพื่อให้ลบสิทธิ์ที่ไม่ติ๊กออกด้วย
        $rows = [];
        foreach ($this->permissions->menus('kpi') as $menu) {
            $flags = $validated['permissions'][$menu->id] ?? [];
            $rows[$menu->id] = [
                'can_view' => (bool) ($flags['can_view'] ?? false),
                'can_create' => (bool) ($flags['can_create'] ?? false),
                'can_edit' => (bool) ($flags['can_edit'] ?? false),
                'can_delete' => (bool) ($flags['can_delete'] ?? false),
            ];
        }

        $this->permissions->syncUserPermissions($user->id, $rows);

        return redirect()->route('permissions.index')->with('success', "บันทึกสิทธิ์ของ {$user->name} เรียบร้อยแล้ว");
    }
}

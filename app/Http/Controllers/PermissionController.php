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
            // เฉพาะผู้ดูแลระบบสูงสุดเท่านั้นที่กำหนดสิทธิ์ผู้ใช้งานอื่นได้
            function ($request, $next) {
                abort_unless(
                    (bool) $request->user()?->is_super_admin,
                    403,
                    'เฉพาะผู้ดูแลระบบสูงสุดเท่านั้นที่สามารถกำหนดสิทธิ์การใช้งานได้'
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

        return view('permissions.edit', compact('user', 'menus', 'current', 'levels'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        // ผู้ดูแลระบบสูงสุดถูกป้องกัน — ไม่มีผู้ใด (รวมถึงตนเอง) ปรับสิทธิ์ได้
        if ($user->is_super_admin) {
            return back()->with('error', 'ไม่สามารถปรับสิทธิ์ของผู้ดูแลระบบสูงสุดได้');
        }

        $validated = $request->validate([
            'kpi_level_id' => ['nullable', 'integer', 'exists:kpi_level,id'],
            'permissions' => ['array'],
            'permissions.*.can_view' => ['nullable', 'boolean'],
            'permissions.*.can_create' => ['nullable', 'boolean'],
            'permissions.*.can_edit' => ['nullable', 'boolean'],
            'permissions.*.can_delete' => ['nullable', 'boolean'],
        ]);

        $levelId = $validated['kpi_level_id'] ?? null;

        // ห้ามกำหนดบทบาทผู้ดูแลระบบสูงสุดผ่านหน้านี้ (bootstrap ผ่าน seeder/DB เท่านั้น)
        if ($levelId !== null && KpiLevel::whereKey($levelId)->value('code') === KpiLevel::SUPER_ADMIN) {
            return back()->with('error', 'ไม่สามารถกำหนดบทบาทผู้ดูแลระบบสูงสุดผ่านหน้านี้ได้');
        }

        $user->update([
            'kpi_level_id' => $levelId,
            'is_super_admin' => false,
        ]);

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

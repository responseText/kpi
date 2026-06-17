<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class PermissionController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissions,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('menu:kpi.permission,view', only: ['index']),
            new Middleware('menu:kpi.permission,edit', only: ['edit', 'update']),
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

        return view('permissions.edit', compact('user', 'menus', 'current'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'is_super_admin' => ['nullable', 'boolean'],
            'permissions' => ['array'],
            'permissions.*.can_view' => ['nullable', 'boolean'],
            'permissions.*.can_create' => ['nullable', 'boolean'],
            'permissions.*.can_edit' => ['nullable', 'boolean'],
            'permissions.*.can_delete' => ['nullable', 'boolean'],
        ]);

        $isSuperAdmin = (bool) ($validated['is_super_admin'] ?? false);

        // ป้องกันถอนสิทธิ์ super admin ของตัวเอง
        if ($user->id === auth()->id() && ! $isSuperAdmin && $user->is_super_admin) {
            return back()->with('error', 'ไม่สามารถถอนสิทธิ์ผู้ดูแลระบบสูงสุดของตัวเองได้');
        }

        $user->update(['is_super_admin' => $isSuperAdmin]);

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

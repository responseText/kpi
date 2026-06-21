<?php

namespace App\Http\Controllers;

use App\Models\KpiLevel;
use App\Models\User;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * จัดการผู้ใช้งานคนอื่น — เฉพาะผู้ดูแลระบบสูงสุด
 * เปลี่ยนรหัสผ่าน, สถานะการใช้งาน (เปิด/ปิด), และระดับ/บทบาทในระบบ KPI
 */
class UserManagementController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissions,
    ) {}

    public static function middleware(): array
    {
        return [
            // เฉพาะผู้ดูแลระบบสูงสุดเท่านั้นที่จัดการบัญชีผู้ใช้คนอื่นได้
            function ($request, $next) {
                abort_unless(
                    (bool) $request->user()?->canManageUsers(),
                    403,
                    'คุณไม่มีสิทธิ์จัดการผู้ใช้งาน (เฉพาะผู้ดูแลระบบสูงสุดเท่านั้น)'
                );

                return $next($request);
            },
        ];
    }

    public function index(Request $request): View
    {
        $search = $request->string('search')->toString() ?: null;
        $users = $this->permissions->paginateUsers($search);

        return view('users.index', compact('users', 'search'));
    }

    public function edit(User $user): View
    {
        $levels = $this->permissions->assignableLevels();
        $years = $this->permissions->assignableYears();

        return view('users.edit', compact('user', 'levels', 'years'));
    }

    /** อัปเดตสถานะการใช้งาน + ระดับ/บทบาทของผู้ใช้ */
    public function update(Request $request, User $user): RedirectResponse
    {
        // ผู้ดูแลระบบสูงสุดถูกป้องกัน — ไม่มีผู้ใด (รวมถึงตนเอง) ปรับบัญชีนี้ผ่านหน้านี้ได้
        if ($user->is_super_admin) {
            return back()->with('error', 'ไม่สามารถจัดการบัญชีของผู้ดูแลระบบสูงสุดได้');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:enable,disable'],
            'kpi_level_ids' => ['array'],
            'kpi_level_ids.*' => ['integer', 'exists:kpi_level,id'],
            'kpi_level_years' => ['array'],
            'kpi_level_years.*' => ['array'],
        ], [], [
            'status' => 'สถานะการใช้งาน',
        ]);

        $levelIds = $validated['kpi_level_ids'] ?? [];

        // ห้ามกำหนดบทบาทผู้ดูแลระบบสูงสุดผ่านหน้านี้ (bootstrap ผ่าน seeder/DB เท่านั้น)
        if ($levelIds && KpiLevel::whereIn('id', $levelIds)->where('code', KpiLevel::SUPER_ADMIN)->exists()) {
            return back()->with('error', 'ไม่สามารถกำหนดบทบาทผู้ดูแลระบบสูงสุดผ่านหน้านี้ได้');
        }

        $user->update(['status' => $validated['status']]);
        $this->permissions->setUserKpiLevels($user->id, $levelIds, $validated['kpi_level_years'] ?? []);

        return redirect()->route('users.edit', $user)->with('success', "บันทึกข้อมูลผู้ใช้ {$user->name} เรียบร้อยแล้ว");
    }

    /** ตั้งรหัสผ่านใหม่ให้ผู้ใช้ (ผู้ดูแลระบบสูงสุดรีเซ็ตให้ — ไม่ต้องยืนยันรหัสผ่านเดิม) */
    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        if ($user->is_super_admin) {
            return back()->with('error', 'ไม่สามารถเปลี่ยนรหัสผ่านของผู้ดูแลระบบสูงสุดได้');
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'password.required' => 'กรุณากรอกรหัสผ่านใหม่',
            'password.confirmed' => 'การยืนยันรหัสผ่านใหม่ไม่ตรงกัน',
            'password.min' => 'รหัสผ่านใหม่ต้องมีอย่างน้อย :min ตัวอักษร',
        ], [
            'password' => 'รหัสผ่านใหม่',
        ]);

        // คอลัมน์ password ถูก cast เป็น 'hashed' อยู่แล้ว — กำหนดค่าดิบได้เลย ระบบจะ hash ให้
        $user->update(['password' => $validated['password']]);

        return redirect()->route('users.edit', $user)->with('success', "เปลี่ยนรหัสผ่านของ {$user->name} เรียบร้อยแล้ว");
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\LevelManagerRequest;
use App\Models\KpiIndicator;
use App\Models\KpiLevelManager;
use App\Repositories\Contracts\LevelManagerRepositoryInterface;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Repositories\Contracts\StrategyRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class LevelManagerController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly LevelManagerRepositoryInterface $levelManagers,
        private readonly PermissionRepositoryInterface $permissions,
        private readonly StrategyRepositoryInterface $strategies,
    ) {}

    public static function middleware(): array
    {
        return [
            // เฉพาะผู้ดูแลระบบสูงสุด หรือผู้ดูแลตัวชี้วัดทั้งหมด เท่านั้นที่ใช้งานเมนูนี้ได้
            function ($request, $next) {
                abort_unless(
                    (bool) $request->user()?->canManageLevelManagers(),
                    403,
                    'คุณไม่มีสิทธิ์ใช้งานเมนูผู้รับผิดชอบระดับ (เฉพาะผู้ดูแลระบบสูงสุดหรือผู้ดูแลตัวชี้วัดทั้งหมด)'
                );

                return $next($request);
            },
        ];
    }

    public function index(Request $request): View
    {
        $year = $request->integer('year') ?: null;

        $managers = $this->levelManagers->allWithUser($year);
        $users = $this->permissions->selectableUsers();
        $levels = KpiIndicator::LEVELS;
        $roles = KpiLevelManager::ROLES;

        // ปีให้เลือกกรอง: รวมปีจากรายการผู้รับผิดชอบ + ปีจากยุทธศาสตร์
        $years = $this->levelManagers->availableYears()
            ->merge($this->strategies->availableYears())
            ->unique()
            ->sortDesc()
            ->values();

        return view('level_managers.index', compact('managers', 'users', 'levels', 'roles', 'years', 'year'));
    }

    public function store(LevelManagerRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $exists = $this->levelManagers->query()
            ->where('level', $data['level'])
            ->where('user_id', $data['user_id'])
            ->where('role', $data['role'])
            ->where('year', $data['year'] ?? null)
            ->exists();

        if ($exists) {
            return back()->with('error', 'มีรายการนี้อยู่แล้ว');
        }

        $this->levelManagers->create($data);

        return back()->with('success', 'เพิ่มผู้รับผิดชอบระดับเรียบร้อยแล้ว');
    }

    public function destroy(KpiLevelManager $levelManager): RedirectResponse
    {
        $this->levelManagers->delete($levelManager);

        return back()->with('success', 'ลบรายการเรียบร้อยแล้ว');
    }
}

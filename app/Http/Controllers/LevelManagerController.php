<?php

namespace App\Http\Controllers;

use App\Http\Requests\LevelManagerRequest;
use App\Models\KpiIndicator;
use App\Models\KpiLevelManager;
use App\Repositories\Contracts\LevelManagerRepositoryInterface;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class LevelManagerController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly LevelManagerRepositoryInterface $levelManagers,
        private readonly PermissionRepositoryInterface $permissions,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('menu:kpi.level_manager,view', only: ['index']),
            new Middleware('menu:kpi.level_manager,create', only: ['store']),
            new Middleware('menu:kpi.level_manager,delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $managers = $this->levelManagers->allWithUser();
        $users = $this->permissions->selectableUsers();
        $levels = KpiIndicator::LEVELS;
        $roles = KpiLevelManager::ROLES;

        return view('level_managers.index', compact('managers', 'users', 'levels', 'roles'));
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

<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnitRequest;
use App\Models\KpiUnit;
use App\Repositories\Contracts\UnitRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

/**
 * จัดการหน่วยวัด KPI (master) — จัดกลุ่มตามหลักการบริหารผลงาน
 * เฉพาะผู้ดูแลระบบสูงสุดเท่านั้น (เช่นเดียวกับเมนู "จัดการผู้ใช้งาน")
 */
class UnitController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly UnitRepositoryInterface $units,
    ) {}

    public static function middleware(): array
    {
        return [
            function ($request, $next) {
                abort_unless(
                    (bool) $request->user()?->canManageUnits(),
                    403,
                    'คุณไม่มีสิทธิ์จัดการหน่วยวัด (เฉพาะผู้ดูแลระบบสูงสุดเท่านั้น)'
                );

                return $next($request);
            },
        ];
    }

    public function index(Request $request): View
    {
        $group = $request->string('group')->toString() ?: null;
        if ($group !== null && ! array_key_exists($group, KpiUnit::GROUPS)) {
            $group = null;
        }

        $units = $this->units->paginateByGroup($group);

        return view('units.index', compact('units', 'group'));
    }

    public function create(): View
    {
        return view('units.create');
    }

    public function store(UnitRequest $request): RedirectResponse
    {
        $this->units->create($request->validated());

        return redirect()->route('units.index')->with('success', 'เพิ่มหน่วยวัดเรียบร้อยแล้ว');
    }

    public function edit(KpiUnit $unit): View
    {
        return view('units.edit', compact('unit'));
    }

    public function update(UnitRequest $request, KpiUnit $unit): RedirectResponse
    {
        $this->units->update($unit, $request->validated());

        return redirect()->route('units.index')->with('success', 'แก้ไขหน่วยวัดเรียบร้อยแล้ว');
    }

    public function destroy(KpiUnit $unit): RedirectResponse
    {
        $this->units->delete($unit);

        return redirect()->route('units.index')->with('success', 'ลบหน่วยวัดเรียบร้อยแล้ว');
    }
}

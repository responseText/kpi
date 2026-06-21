<?php

namespace App\Http\Controllers;

use App\Http\Requests\StrategyRequest;
use App\Models\KpiStrategy;
use App\Repositories\Contracts\StrategyRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class StrategyController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly StrategyRepositoryInterface $strategies,
    ) {}

    public static function middleware(): array
    {
        return [
            // เฉพาะผู้ดูแลตัวชี้วัด (ระบบสูงสุด/ทั้งหมด/รายระดับ) เท่านั้นที่ใช้เมนูยุทธศาสตร์ได้
            function ($request, $next) {
                abort_unless(
                    (bool) $request->user()?->isIndicatorManager(),
                    403,
                    'คุณไม่มีสิทธิ์ใช้งานเมนูยุทธศาสตร์ (เฉพาะผู้ดูแลตัวชี้วัดเท่านั้น)'
                );

                return $next($request);
            },
        ];
    }

    public function index(Request $request): View
    {
        $year = $request->integer('year') ?: null;
        $level = $request->string('level')->toString() ?: null;
        // แสดงเฉพาะยุทธศาสตร์ในระดับที่ผู้ใช้เป็นผู้ดูแล
        $strategies = $this->strategies->paginateByYear($year, $level, $request->user());
        $years = $this->strategies->availableYears();
        $levels = $this->levelsFor($request);

        return view('strategies.index', compact('strategies', 'years', 'year', 'level', 'levels'));
    }

    public function create(Request $request): View
    {
        $this->authorizeManage($request, 'create');

        return view('strategies.create', ['levels' => $this->levelsFor($request)]);
    }

    public function store(StrategyRequest $request): RedirectResponse
    {
        $data = $request->validated();
        // ต้องมีสิทธิ์ "เพิ่ม" และอยู่ในระดับ+ปีที่ตนรับผิดชอบ
        $this->authorizeManage($request, 'create', $data['level'], (int) $data['year']);

        $this->strategies->create($data);

        return redirect()->route('strategies.index')->with('success', 'เพิ่มยุทธศาสตร์เรียบร้อยแล้ว');
    }

    public function edit(Request $request, KpiStrategy $strategy): View
    {
        $this->authorizeManage($request, 'edit', $strategy->level, (int) $strategy->year);

        return view('strategies.edit', ['strategy' => $strategy, 'levels' => $this->levelsFor($request)]);
    }

    public function update(StrategyRequest $request, KpiStrategy $strategy): RedirectResponse
    {
        $data = $request->validated();
        // ต้องมีสิทธิ์ "แก้ไข" ทั้งระดับ+ปีเดิม และระดับ+ปีใหม่ที่ส่งมา
        $this->authorizeManage($request, 'edit', $strategy->level, (int) $strategy->year);
        $this->authorizeManage($request, 'edit', $data['level'], (int) $data['year']);

        $this->strategies->update($strategy, $data);

        return redirect()->route('strategies.index')->with('success', 'แก้ไขยุทธศาสตร์เรียบร้อยแล้ว');
    }

    public function destroy(Request $request, KpiStrategy $strategy): RedirectResponse
    {
        // ลบแบบ soft delete (โมเดลใช้ SoftDeletes)
        $this->authorizeManage($request, 'delete', $strategy->level, (int) $strategy->year);

        $this->strategies->delete($strategy);

        return redirect()->route('strategies.index')->with('success', 'ลบยุทธศาสตร์เรียบร้อยแล้ว');
    }

    /**
     * อนุญาตเฉพาะผู้ที่ได้รับสิทธิ์ action ในเมนูยุทธศาสตร์ และอยู่ในขอบเขตระดับที่ดูแล
     * (ถ้าไม่มีสิทธิ์ action → ดูได้อย่างเดียว เข้าถึงการเพิ่ม/แก้ไข/ลบไม่ได้)
     */
    private function authorizeManage(Request $request, string $action, ?string $level = null, ?int $year = null): void
    {
        abort_unless(
            $request->user()->canManageIndicatorData('kpi.strategy', $action, $level, $year),
            403,
            'คุณไม่มีสิทธิ์ดำเนินการนี้กับยุทธศาสตร์ (ตรวจสอบสิทธิ์เมนู ระดับ และปีที่รับผิดชอบ)'
        );
    }

    /** ระดับที่ผู้ใช้เลือก/กรองได้ (ทุกระดับสำหรับผู้ดูแลทั้งหมด; เฉพาะระดับของตนสำหรับผู้ดูแลรายระดับ) */
    private function levelsFor(Request $request): array
    {
        $user = $request->user();

        if ($user->canManageAllIndicatorLevels()) {
            return KpiStrategy::LEVELS;
        }

        return array_intersect_key(KpiStrategy::LEVELS, array_flip($user->manageableIndicatorLevels()));
    }
}

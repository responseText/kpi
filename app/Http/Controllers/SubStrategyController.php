<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubStrategyRequest;
use App\Models\KpiStrategy;
use App\Models\KpiSubStrategy;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Repositories\Contracts\StrategyRepositoryInterface;
use App\Repositories\Contracts\SubStrategyRepositoryInterface;
use App\Support\IndicatorScopeFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class SubStrategyController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly SubStrategyRepositoryInterface $subStrategies,
        private readonly StrategyRepositoryInterface $strategies,
        private readonly PermissionRepositoryInterface $permissions,
    ) {}

    public static function middleware(): array
    {
        return [
            // เฉพาะผู้ดูแลตัวชี้วัด (ระบบสูงสุด/ทั้งหมด/รายระดับ) เท่านั้นที่ใช้เมนูกลยุทธ์ได้
            function ($request, $next) {
                abort_unless(
                    (bool) $request->user()?->isIndicatorManager(),
                    403,
                    'คุณไม่มีสิทธิ์ใช้งานเมนูกลยุทธ์ (เฉพาะผู้ดูแลตัวชี้วัดเท่านั้น)'
                );

                return $next($request);
            },
        ];
    }

    public function index(Request $request): View
    {
        $year = $request->integer('year') ?: null;
        $strategyId = $request->integer('strategy_id') ?: null;

        // กลยุทธ์สืบทอดระดับจากยุทธศาสตร์แม่ — แสดงเฉพาะระดับที่ผู้ใช้เป็นผู้ดูแล
        $subStrategies = $this->subStrategies->paginateFiltered($year, $strategyId, $request->user());
        $years = $this->strategies->availableYears();

        return view('sub_strategies.index', compact('subStrategies', 'years', 'year', 'strategyId'));
    }

    public function create(Request $request): View
    {
        // เปิดฟอร์มเพิ่มได้ถ้ามีสิทธิ์ "เพิ่ม" (ระดับถูกจำกัดผ่านตัวเลือกยุทธศาสตร์ในฟอร์ม + ตรวจซ้ำตอน store)
        abort_unless(
            $request->user()->canManageIndicatorData('kpi.sub_strategy', 'create'),
            403,
            'คุณไม่มีสิทธิ์เพิ่มกลยุทธ์'
        );

        return view('sub_strategies.create', $this->formData($request));
    }

    public function store(SubStrategyRequest $request): RedirectResponse
    {
        $data = $request->validated();
        // ต้องมีสิทธิ์ "เพิ่ม" และยุทธศาสตร์แม่ที่เลือกอยู่ในระดับ+ปีที่ตนรับผิดชอบ
        [$level, $year] = $this->strategyScope((int) $data['strategy_id']);
        $this->authorizeManage($request, 'create', $level, $year);

        $reviewers = $data['reviewers'];
        unset($data['reviewers']);

        $subStrategy = $this->subStrategies->create($data);
        $this->subStrategies->syncReviewers($subStrategy, $reviewers);

        return redirect()->route('sub-strategies.index')->with('success', 'เพิ่มกลยุทธ์เรียบร้อยแล้ว');
    }

    public function edit(Request $request, KpiSubStrategy $subStrategy): View
    {
        [$level, $year] = $this->subStrategyScope($subStrategy);
        $this->authorizeManage($request, 'edit', $level, $year);
        $subStrategy->load('reviewers');

        return view('sub_strategies.edit', array_merge($this->formData($request), ['subStrategy' => $subStrategy]));
    }

    public function update(SubStrategyRequest $request, KpiSubStrategy $subStrategy): RedirectResponse
    {
        // ต้องมีสิทธิ์ "แก้ไข" ทั้งยุทธศาสตร์แม่เดิม และยุทธศาสตร์แม่ใหม่ที่ส่งมา (ระดับ+ปี)
        [$oldLevel, $oldYear] = $this->subStrategyScope($subStrategy);
        $this->authorizeManage($request, 'edit', $oldLevel, $oldYear);

        $data = $request->validated();
        [$newLevel, $newYear] = $this->strategyScope((int) $data['strategy_id']);
        $this->authorizeManage($request, 'edit', $newLevel, $newYear);

        $reviewers = $data['reviewers'];
        unset($data['reviewers']);

        $this->subStrategies->update($subStrategy, $data);
        $this->subStrategies->syncReviewers($subStrategy, $reviewers);

        return redirect()->route('sub-strategies.index')->with('success', 'แก้ไขกลยุทธ์เรียบร้อยแล้ว');
    }

    public function destroy(Request $request, KpiSubStrategy $subStrategy): RedirectResponse
    {
        // ลบแบบ soft delete (โมเดลใช้ SoftDeletes)
        [$level, $year] = $this->subStrategyScope($subStrategy);
        $this->authorizeManage($request, 'delete', $level, $year);

        $this->subStrategies->delete($subStrategy);

        return redirect()->route('sub-strategies.index')->with('success', 'ลบกลยุทธ์เรียบร้อยแล้ว');
    }

    /** ระดับ+ปีของยุทธศาสตร์ตาม id (null ถ้าไม่พบ → กันไว้ให้ตกเป็นไม่มีสิทธิ์) */
    private function strategyScope(int $strategyId): array
    {
        $row = KpiStrategy::whereKey($strategyId)->first(['level', 'year']);

        return [$row?->level, $row !== null ? (int) $row->year : null];
    }

    /** ระดับ+ปีของกลยุทธ์ (สืบทอดจากยุทธศาสตร์แม่) */
    private function subStrategyScope(KpiSubStrategy $subStrategy): array
    {
        $strategy = $subStrategy->strategy;

        return [$strategy?->level, $strategy !== null ? (int) $strategy->year : null];
    }

    /**
     * อนุญาตเฉพาะผู้ที่ได้รับสิทธิ์ action ในเมนูกลยุทธ์ และอยู่ในขอบเขตระดับ+ปีของยุทธศาสตร์แม่
     * ระดับ null (เช่นหายุทธศาสตร์แม่ไม่พบ) ถือว่าไม่อยู่ในขอบเขต → ปฏิเสธ (ยกเว้น super admin)
     */
    private function authorizeManage(Request $request, string $action, ?string $level, ?int $year = null): void
    {
        abort_unless(
            $request->user()->canManageIndicatorData('kpi.sub_strategy', $action, $level ?? '', $year),
            403,
            'คุณไม่มีสิทธิ์ดำเนินการนี้กับกลยุทธ์ (ตรวจสอบสิทธิ์เมนู ระดับ และปีที่รับผิดชอบ)'
        );
    }

    private function formData(Request $request): array
    {
        $user = $request->user();

        // ตัวเลือกยุทธศาสตร์: ผู้ดูแลรายระดับเห็นเฉพาะยุทธศาสตร์ในระดับ+ปีที่ตนรับผิดชอบ
        $strategyOptions = $this->strategies->query()
            ->when(! $user->canManageAllIndicatorLevels(),
                fn ($q) => $q->where(function ($w) use ($user) {
                    $w->whereRaw('1 = 0');
                    IndicatorScopeFilter::orWhereScopes($w, $user->indicatorAdminScopeYears());
                }))
            ->orderByDesc('year')
            ->orderBy('orderby')
            ->get();

        return [
            'strategyOptions' => $strategyOptions,
            'users' => $this->permissions->selectableUsers(),
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\KpiCategory;
use App\Models\KpiStrategy;
use App\Models\KpiSubStrategy;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\StrategyRepositoryInterface;
use App\Repositories\Contracts\SubStrategyRepositoryInterface;
use App\Support\IndicatorScopeFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class CategoryController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
        private readonly SubStrategyRepositoryInterface $subStrategies,
        private readonly StrategyRepositoryInterface $strategies,
    ) {}

    public static function middleware(): array
    {
        return [
            // เฉพาะผู้ดูแลตัวชี้วัด (ระบบสูงสุด/ทั้งหมด/รายระดับ) เท่านั้นที่ใช้เมนูหมวด KPI ได้
            function ($request, $next) {
                abort_unless(
                    (bool) $request->user()?->isIndicatorManager(),
                    403,
                    'คุณไม่มีสิทธิ์ใช้งานเมนูหมวด KPI (เฉพาะผู้ดูแลตัวชี้วัดเท่านั้น)'
                );

                return $next($request);
            },
        ];
    }

    public function index(Request $request): View
    {
        $year          = $request->integer('year') ?: null;
        $level         = $request->string('level')->toString() ?: null;
        $strategyId    = $request->integer('strategy_id') ?: null;
        $subStrategyId = $request->integer('sub_strategy_id') ?: null;
        $name          = $request->string('name')->trim()->toString() ?: null;

        $categories = $this->categories->paginateFiltered($year, $strategyId, $subStrategyId, $level, $name, $request->user());

        $years = $this->strategies->availableYears();

        $levelOptions = KpiStrategy::LEVELS;

        // ยุทธศาสตร์: กรองตามปี+ระดับที่เลือก (+ scope ผู้ใช้)
        $strategyOptions = $this->strategies->query()
            ->when(! $request->user()->canManageAllIndicatorLevels(),
                fn ($q) => $q->where(function ($w) use ($request) {
                    $w->whereRaw('1 = 0');
                    IndicatorScopeFilter::orWhereScopes($w, $request->user()->indicatorAdminScopeYears());
                }))
            ->when($year,  fn ($q) => $q->where('year', $year))
            ->when($level, fn ($q) => $q->where('level', $level))
            ->orderByDesc('year')->orderBy('orderby')->get();

        // กลยุทธ์: กรองตามยุทธศาสตร์+ปี+ระดับที่เลือก (+ scope ผู้ใช้)
        $subStrategyOptions = $this->subStrategies->query()
            ->with('strategy')
            ->when(! $request->user()->canManageAllIndicatorLevels(),
                fn ($q) => $q->whereHas('strategy', fn ($s) => $s->where(function ($w) use ($request) {
                    $w->whereRaw('1 = 0');
                    IndicatorScopeFilter::orWhereScopes($w, $request->user()->indicatorAdminScopeYears());
                })))
            ->when($strategyId, fn ($q) => $q->where('strategy_id', $strategyId))
            ->when($year,  fn ($q) => $q->whereHas('strategy', fn ($s) => $s->where('year', $year)))
            ->when($level, fn ($q) => $q->whereHas('strategy', fn ($s) => $s->where('level', $level)))
            ->orderBy('orderby')->get();

        return view('categories.index', compact(
            'categories', 'years', 'year', 'level', 'name',
            'strategyId', 'subStrategyId',
            'strategyOptions', 'subStrategyOptions', 'levelOptions'
        ));
    }

    public function create(Request $request): View
    {
        abort_unless(
            $request->user()->canManageIndicatorData('kpi.category', 'create'),
            403,
            'คุณไม่มีสิทธิ์เพิ่มหมวด KPI'
        );

        return view('categories.create', $this->formData($request));
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        [$level, $year] = $this->subStrategyScope($data['sub_strategy_id'] ?? null);
        $this->authorizeManage($request, 'create', $level, $year);

        $this->categories->create($data);

        return redirect()->route('categories.index')->with('success', 'เพิ่มหมวด KPI เรียบร้อยแล้ว');
    }

    public function edit(Request $request, KpiCategory $category): View
    {
        [$level, $year] = $this->categoryScope($category);
        $this->authorizeManage($request, 'edit', $level, $year);

        return view('categories.edit', array_merge($this->formData($request), ['category' => $category]));
    }

    public function update(CategoryRequest $request, KpiCategory $category): RedirectResponse
    {
        [$oldLevel, $oldYear] = $this->categoryScope($category);
        $this->authorizeManage($request, 'edit', $oldLevel, $oldYear);

        $data = $request->validated();
        [$newLevel, $newYear] = $this->subStrategyScope($data['sub_strategy_id'] ?? null);
        $this->authorizeManage($request, 'edit', $newLevel, $newYear);

        $this->categories->update($category, $data);

        return redirect()->route('categories.index')->with('success', 'แก้ไขหมวด KPI เรียบร้อยแล้ว');
    }

    public function destroy(Request $request, KpiCategory $category): RedirectResponse
    {
        [$level, $year] = $this->categoryScope($category);
        $this->authorizeManage($request, 'delete', $level, $year);

        $this->categories->delete($category);

        return redirect()->route('categories.index')->with('success', 'ลบหมวด KPI เรียบร้อยแล้ว');
    }

    /** ระดับ+ปีของหมวด KPI (สืบทอดจากกลยุทธ์→ยุทธศาสตร์; null ถ้ายังไม่ผูกกลยุทธ์) */
    private function subStrategyScope(?int $subStrategyId): array
    {
        if (! $subStrategyId) {
            return [null, null];
        }

        $strategy = KpiSubStrategy::with('strategy')->find($subStrategyId)?->strategy;

        return [$strategy?->level, $strategy !== null ? (int) $strategy->year : null];
    }

    private function categoryScope(KpiCategory $category): array
    {
        $strategy = $category->subStrategy?->strategy;

        return [$strategy?->level, $strategy !== null ? (int) $strategy->year : null];
    }

    /**
     * อนุญาตเฉพาะผู้มีสิทธิ์ action ในเมนูหมวด KPI และอยู่ในขอบเขตระดับ+ปีของกลยุทธ์แม่
     * ระดับ null (ยังไม่ผูกกลยุทธ์) → เฉพาะผู้ดูแลทั้งหมด/ระบบสูงสุดเท่านั้นที่จัดการได้
     */
    private function authorizeManage(Request $request, string $action, ?string $level = null, ?int $year = null): void
    {
        abort_unless(
            $request->user()->canManageIndicatorData('kpi.category', $action, $level ?? '', $year),
            403,
            'คุณไม่มีสิทธิ์ดำเนินการนี้กับหมวด KPI (ตรวจสอบสิทธิ์เมนู ระดับ และปีที่รับผิดชอบ)'
        );
    }

    private function formData(Request $request): array
    {
        $user = $request->user();

        $subStrategyOptions = $this->subStrategies->query()
            ->with('strategy')
            ->when(! $user->canManageAllIndicatorLevels(),
                fn ($q) => $q->whereHas('strategy', fn ($s) => $s->where(function ($w) use ($user) {
                    $w->whereRaw('1 = 0');
                    IndicatorScopeFilter::orWhereScopes($w, $user->indicatorAdminScopeYears());
                })))
            ->orderBy('orderby')
            ->get();

        return [
            'subStrategyOptions' => $subStrategyOptions,
            'levelOptions'       => KpiStrategy::LEVELS,
            'years'              => $this->strategies->availableYears(),
        ];
    }
}

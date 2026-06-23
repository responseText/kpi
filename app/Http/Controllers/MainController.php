<?php

namespace App\Http\Controllers;

use App\Http\Requests\MainRequest;
use App\Models\KpiCategory;
use App\Models\KpiMain;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\MainRepositoryInterface;
use App\Support\IndicatorScopeFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class MainController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly MainRepositoryInterface $mains,
        private readonly CategoryRepositoryInterface $categories,
    ) {}

    public static function middleware(): array
    {
        return [
            // เฉพาะผู้ดูแลตัวชี้วัด (ระบบสูงสุด/ทั้งหมด/รายระดับ) เท่านั้นที่ใช้เมนู KPI หลัก ได้
            function ($request, $next) {
                abort_unless(
                    (bool) $request->user()?->isIndicatorManager(),
                    403,
                    'คุณไม่มีสิทธิ์ใช้งานเมนู KPI หลัก (เฉพาะผู้ดูแลตัวชี้วัดเท่านั้น)'
                );

                return $next($request);
            },
        ];
    }

    public function index(Request $request): View
    {
        $categoryId = $request->integer('category_id') ?: null;
        $mains = $this->mains->paginateFiltered($categoryId, $request->user());

        return view('mains.index', array_merge($this->formData($request), compact('mains', 'categoryId')));
    }

    public function create(Request $request): View
    {
        abort_unless(
            $request->user()->canManageIndicatorData('kpi.main', 'create'),
            403,
            'คุณไม่มีสิทธิ์เพิ่ม KPI หลัก'
        );

        return view('mains.create', $this->formData($request));
    }

    public function store(MainRequest $request): RedirectResponse
    {
        $data = $request->validated();
        [$level, $year] = $this->categoryScope((int) $data['category_id']);
        $this->authorizeManage($request, 'create', $level, $year);

        $this->mains->create($data);

        return redirect()->route('mains.index')->with('success', 'เพิ่ม KPI หลัก เรียบร้อยแล้ว');
    }

    public function edit(Request $request, KpiMain $main): View
    {
        [$level, $year] = $this->mainScope($main);
        $this->authorizeManage($request, 'edit', $level, $year);

        return view('mains.edit', array_merge($this->formData($request), ['main' => $main]));
    }

    public function update(MainRequest $request, KpiMain $main): RedirectResponse
    {
        [$oldLevel, $oldYear] = $this->mainScope($main);
        $this->authorizeManage($request, 'edit', $oldLevel, $oldYear);

        $data = $request->validated();
        [$newLevel, $newYear] = $this->categoryScope((int) $data['category_id']);
        $this->authorizeManage($request, 'edit', $newLevel, $newYear);

        $this->mains->update($main, $data);

        return redirect()->route('mains.index')->with('success', 'แก้ไข KPI หลัก เรียบร้อยแล้ว');
    }

    public function destroy(Request $request, KpiMain $main): RedirectResponse
    {
        [$level, $year] = $this->mainScope($main);
        $this->authorizeManage($request, 'delete', $level, $year);

        $this->mains->delete($main);

        return redirect()->route('mains.index')->with('success', 'ลบ KPI หลัก เรียบร้อยแล้ว');
    }

    /** ระดับ+ปีของหมวด KPI ตาม id (ผ่านกลยุทธ์→ยุทธศาสตร์) */
    private function categoryScope(?int $categoryId): array
    {
        if (! $categoryId) {
            return [null, null];
        }

        $strategy = KpiCategory::with('subStrategy.strategy')->find($categoryId)?->subStrategy?->strategy;

        return [$strategy?->level, $strategy !== null ? (int) $strategy->year : null];
    }

    /** ระดับ+ปีของ KPI หลัก (สืบทอดจากหมวด→กลยุทธ์→ยุทธศาสตร์) */
    private function mainScope(KpiMain $main): array
    {
        $strategy = $main->category?->subStrategy?->strategy;

        return [$strategy?->level, $strategy !== null ? (int) $strategy->year : null];
    }

    private function authorizeManage(Request $request, string $action, ?string $level = null, ?int $year = null): void
    {
        abort_unless(
            $request->user()->canManageIndicatorData('kpi.main', $action, $level ?? '', $year),
            403,
            'คุณไม่มีสิทธิ์ดำเนินการนี้กับ KPI หลัก (ตรวจสอบสิทธิ์เมนู ระดับ และปีที่รับผิดชอบ)'
        );
    }

    private function formData(Request $request): array
    {
        $user = $request->user();

        $categoryOptions = $this->categories->query()
            ->with('subStrategy.strategy')
            ->when(! $user->canManageAllIndicatorLevels(),
                fn ($q) => $q->whereHas('subStrategy.strategy', fn ($s) => $s->where(function ($w) use ($user) {
                    $w->whereRaw('1 = 0');
                    IndicatorScopeFilter::orWhereScopes($w, $user->indicatorAdminScopeYears());
                })))
            ->orderBy('orderby')
            ->get();

        return ['categoryOptions' => $categoryOptions];
    }
}

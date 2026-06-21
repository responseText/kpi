<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndicatorRequest;
use App\Models\KpiIndicator;
use App\Models\KpiUnit;
use App\Repositories\Contracts\IndicatorRepositoryInterface;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Repositories\Contracts\StrategyRepositoryInterface;
use App\Repositories\Contracts\SubStrategyRepositoryInterface;
use App\Repositories\Contracts\TargetRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class IndicatorController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly IndicatorRepositoryInterface $indicators,
        private readonly SubStrategyRepositoryInterface $subStrategies,
        private readonly StrategyRepositoryInterface $strategies,
        private readonly TargetRepositoryInterface $targets,
        private readonly PermissionRepositoryInterface $permissions,
    ) {}

    public static function middleware(): array
    {
        return [
            // เฉพาะผู้ดูแลตัวชี้วัด (ระบบสูงสุด/ทั้งหมด/รายระดับ) เท่านั้นที่ใช้เมนูตัวชี้วัดได้
            function ($request, $next) {
                abort_unless(
                    (bool) $request->user()?->isIndicatorManager(),
                    403,
                    'คุณไม่มีสิทธิ์ใช้งานเมนูตัวชี้วัด (เฉพาะผู้ดูแลตัวชี้วัดเท่านั้น)'
                );

                return $next($request);
            },
        ];
    }

    public function index(Request $request): View
    {
        $filters = [
            'level' => $request->string('level')->toString() ?: null,
            'year' => $request->integer('year') ?: null,
            'year_type' => $request->string('year_type')->toString() ?: null,
            'search' => $request->string('search')->toString() ?: null,
        ];

        // แสดงเฉพาะตัวชี้วัดในระดับที่ผู้ใช้เป็นผู้ดูแลเท่านั้น
        $indicators = $this->indicators->paginateManageableLevels(array_filter($filters), $request->user());
        $years = $this->strategies->availableYears();

        return view('indicators.index', compact('indicators', 'years', 'filters'));
    }

    public function create(Request $request): View
    {
        $this->authorizeManage($request, 'create');

        return view('indicators.create', $this->formData($request));
    }

    public function store(IndicatorRequest $request): RedirectResponse
    {
        // ต้องมีสิทธิ์ "เพิ่ม" และอยู่ในระดับ+ปีที่ตนรับผิดชอบ
        $this->authorizeManage($request, 'create', $request->validated()['level'], (int) $request->validated()['year']);

        $indicator = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $owners = $data['owners'];
            $primary = $data['primary_owner'] ?? null;
            unset($data['owners'], $data['primary_owner']);

            $indicator = $this->indicators->create($data);
            $this->indicators->syncOwners($indicator, $owners, $primary);
            $this->targets->syncPeriods($indicator); // สร้างช่วงเวลา (รายปี/รายไตรมาส)

            return $indicator;
        });

        return redirect()->route('indicators.show', $indicator)
            ->with('success', 'เพิ่มตัวชี้วัดเรียบร้อยแล้ว — กรุณากำหนดค่าเป้าหมายรายช่วง');
    }

    public function show(Request $request, KpiIndicator $indicator): View
    {
        $this->authorizeLevel($request, $indicator);

        $indicator = $this->indicators->loadFull($indicator);

        return view('indicators.show', compact('indicator'));
    }

    public function edit(Request $request, KpiIndicator $indicator): View
    {
        $this->authorizeManage($request, 'edit', $indicator->level, (int) $indicator->year);

        $indicator->load('owners');

        return view('indicators.edit', array_merge($this->formData($request), ['indicator' => $indicator]));
    }

    public function update(IndicatorRequest $request, KpiIndicator $indicator): RedirectResponse
    {
        // ต้องมีสิทธิ์ "แก้ไข" ทั้งระดับ+ปีเดิมของตัวชี้วัด และระดับ+ปีใหม่ที่ส่งมา
        $this->authorizeManage($request, 'edit', $indicator->level, (int) $indicator->year);
        $this->authorizeManage($request, 'edit', $request->validated()['level'], (int) $request->validated()['year']);

        DB::transaction(function () use ($request, $indicator) {
            $data = $request->validated();
            $owners = $data['owners'];
            $primary = $data['primary_owner'] ?? null;
            unset($data['owners'], $data['primary_owner']);

            $this->indicators->update($indicator, $data);
            $this->indicators->syncOwners($indicator, $owners, $primary);
            $this->targets->syncPeriods($indicator); // ปรับช่วงเวลาตาม year/period_type ที่อาจเปลี่ยน
        });

        return redirect()->route('indicators.show', $indicator)->with('success', 'แก้ไขตัวชี้วัดเรียบร้อยแล้ว');
    }

    public function destroy(Request $request, KpiIndicator $indicator): RedirectResponse
    {
        // ลบแบบ soft delete (โมเดลใช้ SoftDeletes)
        $this->authorizeManage($request, 'delete', $indicator->level, (int) $indicator->year);

        $this->indicators->delete($indicator);

        return redirect()->route('indicators.index')->with('success', 'ลบตัวชี้วัดเรียบร้อยแล้ว');
    }

    /** อนุญาตให้ "ดู" ตัวชี้วัดระดับ+ปีนี้ (ตามบทบาทระดับ/ปี ไม่ต้องมีสิทธิ์ action) */
    private function authorizeLevel(Request $request, KpiIndicator $indicator): void
    {
        abort_unless(
            $request->user()->canManageIndicatorLevel($indicator->level, (int) $indicator->year),
            403,
            'คุณไม่มีสิทธิ์เข้าถึงตัวชี้วัดระดับ/ปีนี้ (เฉพาะผู้ดูแลที่รับผิดชอบระดับและปีนี้เท่านั้น)'
        );
    }

    /**
     * อนุญาตเฉพาะผู้ที่ได้รับสิทธิ์ action ในเมนูตัวชี้วัด และอยู่ในขอบเขตระดับ+ปีที่ดูแล
     * (ถ้าไม่มีสิทธิ์ action → ดูได้อย่างเดียว เข้าถึงการเพิ่ม/แก้ไข/ลบไม่ได้)
     */
    private function authorizeManage(Request $request, string $action, ?string $level = null, ?int $year = null): void
    {
        abort_unless(
            $request->user()->canManageIndicatorData('kpi.indicator', $action, $level, $year),
            403,
            'คุณไม่มีสิทธิ์ดำเนินการนี้กับตัวชี้วัด (ตรวจสอบสิทธิ์เมนู ระดับ และปีที่รับผิดชอบ)'
        );
    }

    private function formData(Request $request): array
    {
        return [
            'subStrategyOptions' => $this->subStrategies->query()->with('strategy')->orderBy('orderby')->get(),
            'users' => $this->permissions->selectableUsers(),
            'levels' => $this->levelsFor($request),
            'yearTypes' => KpiIndicator::YEAR_TYPES,
            'periodTypes' => KpiIndicator::PERIOD_TYPES,
            'unitGroups' => KpiUnit::groupedEnabled(),
        ];
    }

    /** ระดับที่ผู้ใช้เลือกได้ในฟอร์ม (ทุกระดับ สำหรับผู้ดูแลทั้งหมด/ระบบสูงสุด; เฉพาะระดับของตน สำหรับผู้ดูแลรายระดับ) */
    private function levelsFor(Request $request): array
    {
        $user = $request->user();

        if ($user->canManageAllIndicatorLevels()) {
            return KpiIndicator::LEVELS;
        }

        return array_intersect_key(KpiIndicator::LEVELS, array_flip($user->manageableIndicatorLevels()));
    }
}

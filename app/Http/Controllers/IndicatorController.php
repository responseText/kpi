<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndicatorRequest;
use App\Models\KpiIndicator;
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
        return view('indicators.create', $this->formData($request));
    }

    public function store(IndicatorRequest $request): RedirectResponse
    {
        // สร้างได้เฉพาะตัวชี้วัดในระดับที่ตนดูแล
        $this->assertCanUseLevel($request, $request->validated()['level']);

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
        $this->authorizeLevel($request, $indicator);

        $indicator->load('owners');

        return view('indicators.edit', array_merge($this->formData($request), ['indicator' => $indicator]));
    }

    public function update(IndicatorRequest $request, KpiIndicator $indicator): RedirectResponse
    {
        // ต้องดูแลระดับเดิมของตัวชี้วัด และระดับใหม่ที่ส่งมาด้วย
        $this->authorizeLevel($request, $indicator);
        $this->assertCanUseLevel($request, $request->validated()['level']);

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
        $this->authorizeLevel($request, $indicator);

        $this->indicators->delete($indicator);

        return redirect()->route('indicators.index')->with('success', 'ลบตัวชี้วัดเรียบร้อยแล้ว');
    }

    /** อนุญาตเฉพาะผู้ดูแลที่ครอบคลุมระดับของตัวชี้วัดนี้ */
    private function authorizeLevel(Request $request, KpiIndicator $indicator): void
    {
        abort_unless(
            $request->user()->canManageIndicatorLevel($indicator->level),
            403,
            'คุณไม่มีสิทธิ์จัดการตัวชี้วัดระดับนี้ (เฉพาะผู้ดูแลระดับที่เกี่ยวข้องเท่านั้น)'
        );
    }

    /** อนุญาตเฉพาะการสร้าง/บันทึกตัวชี้วัดในระดับที่ผู้ใช้ดูแล */
    private function assertCanUseLevel(Request $request, string $level): void
    {
        abort_unless(
            $request->user()->canManageIndicatorLevel($level),
            403,
            'คุณไม่มีสิทธิ์บันทึกตัวชี้วัดในระดับนี้ (เฉพาะผู้ดูแลระดับที่เกี่ยวข้องเท่านั้น)'
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

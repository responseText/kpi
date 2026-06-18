<?php

namespace App\Http\Controllers;

use App\Http\Requests\TargetRequest;
use App\Models\KpiIndicator;
use App\Repositories\Contracts\IndicatorRepositoryInterface;
use App\Repositories\Contracts\StrategyRepositoryInterface;
use App\Repositories\Contracts\TargetRepositoryInterface;
use App\Services\KpiEvaluator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class TargetController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly IndicatorRepositoryInterface $indicators,
        private readonly TargetRepositoryInterface $targets,
        private readonly StrategyRepositoryInterface $strategies,
    ) {}

    public static function middleware(): array
    {
        return [
            // เฉพาะผู้ดูแลตัวชี้วัด (ระบบสูงสุด/ทั้งหมด/รายระดับ) เท่านั้นที่ใช้เมนูกำหนดค่าเป้าหมายได้
            function ($request, $next) {
                abort_unless(
                    (bool) $request->user()?->isIndicatorManager(),
                    403,
                    'คุณไม่มีสิทธิ์ใช้งานเมนูกำหนดค่าเป้าหมาย (เฉพาะผู้ดูแลตัวชี้วัดเท่านั้น)'
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
            'search' => $request->string('search')->toString() ?: null,
        ];

        // แสดงเฉพาะตัวชี้วัดในระดับที่ผู้ใช้เป็นผู้ดูแลเท่านั้น
        $indicators = $this->indicators->paginateManageableLevels(array_filter($filters), $request->user());
        $years = $this->strategies->availableYears();

        return view('targets.index', compact('indicators', 'years', 'filters'));
    }

    public function edit(Request $request, KpiIndicator $indicator): View
    {
        $this->authorizeLevel($request, $indicator);

        $this->targets->syncPeriods($indicator);          // กันกรณีช่วงเวลายังไม่ถูกสร้าง
        $indicator->load('targets');
        $operators = KpiEvaluator::LABELS;

        return view('targets.edit', compact('indicator', 'operators'));
    }

    public function update(TargetRequest $request, KpiIndicator $indicator): RedirectResponse
    {
        $this->authorizeLevel($request, $indicator);

        $this->targets->saveTargets($indicator, $request->validated()['targets']);

        return redirect()->route('indicators.show', $indicator)->with('success', 'บันทึกค่าเป้าหมายเรียบร้อยแล้ว');
    }

    /**
     * อนุญาตเฉพาะผู้ดูแลที่ครอบคลุมระดับของตัวชี้วัดนี้
     * (ผู้ดูแลระบบสูงสุด/ผู้ดูแลตัวชี้วัดทั้งหมด ผ่านทุกระดับ; ผู้ดูแลระดับ ผ่านเฉพาะระดับของตน)
     */
    private function authorizeLevel(Request $request, KpiIndicator $indicator): void
    {
        abort_unless(
            $request->user()->canManageIndicatorLevel($indicator->level),
            403,
            'คุณไม่มีสิทธิ์กำหนดค่าเป้าหมายของตัวชี้วัดระดับนี้ (เฉพาะผู้ดูแลระดับที่เกี่ยวข้องเท่านั้น)'
        );
    }
}

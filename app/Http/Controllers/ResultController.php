<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResultRequest;
use App\Models\KpiIndicator;
use App\Repositories\Contracts\IndicatorRepositoryInterface;
use App\Repositories\Contracts\ResultRepositoryInterface;
use App\Repositories\Contracts\StrategyRepositoryInterface;
use App\Repositories\Contracts\TargetRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ResultController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly IndicatorRepositoryInterface $indicators,
        private readonly TargetRepositoryInterface $targets,
        private readonly ResultRepositoryInterface $results,
        private readonly StrategyRepositoryInterface $strategies,
    ) {}

    public static function middleware(): array
    {
        return [
            // ด่านเข้าเมนู: ผู้รับผิดชอบตัวชี้วัด/ผู้ดูแล/ผู้ได้รับสิทธิ์เมนูโดยตรง
            // (การบันทึกผลรายตัวชี้วัดยังคุมด้วย authorizeRecord ในเมธอด edit/update)
            function ($request, $next) {
                abort_unless((bool) $request->user()?->canAccessResults(), 403);

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

        // แสดงเฉพาะตัวชี้วัดที่ผู้ใช้มีสิทธิ์บันทึกผลเท่านั้น
        $indicators = $this->indicators->paginateRecordable(array_filter($filters), $request->user());
        $years = $this->strategies->availableYears();

        return view('results.index', compact('indicators', 'years', 'filters'));
    }

    public function edit(Request $request, KpiIndicator $indicator): View
    {
        $this->authorizeRecord($request, $indicator);

        $this->targets->syncPeriods($indicator);
        $indicator->load('targets.result.recorder');

        return view('results.edit', compact('indicator'));
    }

    public function update(ResultRequest $request, KpiIndicator $indicator): RedirectResponse
    {
        $this->authorizeRecord($request, $indicator);

        $rows = $request->validated()['results'];
        $userId = (int) $request->user()->id;

        // ตัวชี้วัดประเภท A/B → ผู้ใช้กรอก A,B แล้วระบบคำนวณ result_value ให้เอง
        $usesAb = $indicator->usesNumeratorDenominator();

        // map target_id -> target (เฉพาะที่เป็นของตัวชี้วัดนี้)
        $targetsById = $indicator->targets()->get()->keyBy('id');

        DB::transaction(function () use ($rows, $targetsById, $userId, $indicator, $usesAb) {
            foreach ($rows as $targetId => $row) {
                $target = $targetsById->get((int) $targetId);
                if (! $target) {
                    continue;
                }

                $numerator = $usesAb ? ($row['numerator_value'] ?? null) : null;
                $denominator = $usesAb ? ($row['denominator_value'] ?? null) : null;
                $text = $row['result_text'] ?? null;

                // ค่าผลงาน: ประเภท A/B → คำนวณจาก A,B ตามสูตร; ที่เหลือ → ค่าที่กรอกโดยตรง
                $value = $usesAb
                    ? $indicator->computeResultValue($numerator, $denominator)
                    : ($row['result_value'] ?? null);

                // ข้ามแถวที่ไม่ได้กรอกอะไรเลย
                $hasAb = ($numerator !== null && $numerator !== '') || ($denominator !== null && $denominator !== '');
                $hasValue = ($value !== null && $value !== '');
                $hasText = ($text !== null && $text !== '');
                if (! $hasAb && ! $hasValue && ! $hasText && empty($row['note'])) {
                    continue;
                }

                $this->results->record($target, [
                    'result_value' => ($value === '' ? null : $value),
                    'numerator_value' => ($numerator === '' ? null : $numerator),
                    'denominator_value' => ($denominator === '' ? null : $denominator),
                    'result_text' => ($text === '' ? null : $text),
                    'note' => $row['note'] ?? null,
                ], $userId);
            }
        });

        return redirect()->route('indicators.show', $indicator)->with('success', 'บันทึกผลงานเรียบร้อยแล้ว');
    }

    /**
     * เฉพาะผู้รับผิดชอบตัวชี้วัด หรือผู้ดูแล (ระบบสูงสุด/ตัวชี้วัดทั้งหมด/ระดับที่ตรงกัน) เท่านั้น
     * ที่บันทึกผลของตัวชี้วัดนี้ได้
     */
    private function authorizeRecord(Request $request, KpiIndicator $indicator): void
    {
        abort_unless(
            $request->user()->canRecordResultFor($indicator),
            403,
            'คุณไม่มีสิทธิ์บันทึกผลของตัวชี้วัดนี้ (เฉพาะผู้รับผิดชอบหรือผู้ดูแลที่เกี่ยวข้องเท่านั้น)'
        );
    }
}

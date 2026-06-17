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
use Illuminate\Routing\Controllers\Middleware;
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
            new Middleware('menu:kpi.result,view', only: ['index']),
            new Middleware('menu:kpi.result,edit', only: ['edit', 'update']),
        ];
    }

    public function index(Request $request): View
    {
        $filters = [
            'level' => $request->string('level')->toString() ?: null,
            'year' => $request->integer('year') ?: null,
            'search' => $request->string('search')->toString() ?: null,
        ];

        $indicators = $this->indicators->paginateFiltered(array_filter($filters));
        $years = $this->strategies->availableYears();

        return view('results.index', compact('indicators', 'years', 'filters'));
    }

    public function edit(KpiIndicator $indicator): View
    {
        $this->targets->syncPeriods($indicator);
        $indicator->load('targets.result.recorder');

        return view('results.edit', compact('indicator'));
    }

    public function update(ResultRequest $request, KpiIndicator $indicator): RedirectResponse
    {
        $rows = $request->validated()['results'];
        $userId = (int) $request->user()->id;

        // map target_id -> target (เฉพาะที่เป็นของตัวชี้วัดนี้)
        $targetsById = $indicator->targets()->get()->keyBy('id');

        DB::transaction(function () use ($rows, $targetsById, $userId) {
            foreach ($rows as $targetId => $row) {
                $target = $targetsById->get((int) $targetId);
                if (! $target) {
                    continue;
                }

                // ข้ามแถวที่ไม่ได้กรอกอะไรเลย
                $value = $row['result_value'] ?? null;
                $text = $row['result_text'] ?? null;
                if (($value === null || $value === '') && ($text === null || $text === '') && empty($row['note'])) {
                    continue;
                }

                $this->results->record($target, [
                    'result_value' => ($value === '' ? null : $value),
                    'result_text' => ($text === '' ? null : $text),
                    'note' => $row['note'] ?? null,
                ], $userId);
            }
        });

        return redirect()->route('indicators.show', $indicator)->with('success', 'บันทึกผลงานเรียบร้อยแล้ว');
    }
}

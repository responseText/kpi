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
use Illuminate\Routing\Controllers\Middleware;
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
            new Middleware('menu:kpi.target,view', only: ['index']),
            new Middleware('menu:kpi.target,edit', only: ['edit', 'update']),
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

        return view('targets.index', compact('indicators', 'years', 'filters'));
    }

    public function edit(KpiIndicator $indicator): View
    {
        $this->targets->syncPeriods($indicator);          // กันกรณีช่วงเวลายังไม่ถูกสร้าง
        $indicator->load('targets');
        $operators = KpiEvaluator::LABELS;

        return view('targets.edit', compact('indicator', 'operators'));
    }

    public function update(TargetRequest $request, KpiIndicator $indicator): RedirectResponse
    {
        $this->targets->saveTargets($indicator, $request->validated()['targets']);

        return redirect()->route('indicators.show', $indicator)->with('success', 'บันทึกค่าเป้าหมายเรียบร้อยแล้ว');
    }
}

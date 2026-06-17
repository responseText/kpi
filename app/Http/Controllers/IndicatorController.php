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
use Illuminate\Routing\Controllers\Middleware;
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
            new Middleware('menu:kpi.indicator,view', only: ['index', 'show']),
            new Middleware('menu:kpi.indicator,create', only: ['create', 'store']),
            new Middleware('menu:kpi.indicator,edit', only: ['edit', 'update']),
            new Middleware('menu:kpi.indicator,delete', only: ['destroy']),
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

        $indicators = $this->indicators->paginateFiltered(array_filter($filters));
        $years = $this->strategies->availableYears();

        return view('indicators.index', compact('indicators', 'years', 'filters'));
    }

    public function create(): View
    {
        return view('indicators.create', $this->formData());
    }

    public function store(IndicatorRequest $request): RedirectResponse
    {
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

    public function show(KpiIndicator $indicator): View
    {
        $indicator = $this->indicators->loadFull($indicator);

        return view('indicators.show', compact('indicator'));
    }

    public function edit(KpiIndicator $indicator): View
    {
        $indicator->load('owners');

        return view('indicators.edit', array_merge($this->formData(), ['indicator' => $indicator]));
    }

    public function update(IndicatorRequest $request, KpiIndicator $indicator): RedirectResponse
    {
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

    public function destroy(KpiIndicator $indicator): RedirectResponse
    {
        $this->indicators->delete($indicator);

        return redirect()->route('indicators.index')->with('success', 'ลบตัวชี้วัดเรียบร้อยแล้ว');
    }

    private function formData(): array
    {
        return [
            'subStrategyOptions' => $this->subStrategies->query()->with('strategy')->orderBy('orderby')->get(),
            'users' => $this->permissions->selectableUsers(),
            'levels' => KpiIndicator::LEVELS,
            'yearTypes' => KpiIndicator::YEAR_TYPES,
            'periodTypes' => KpiIndicator::PERIOD_TYPES,
        ];
    }
}

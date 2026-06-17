<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubStrategyRequest;
use App\Models\KpiSubStrategy;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Repositories\Contracts\StrategyRepositoryInterface;
use App\Repositories\Contracts\SubStrategyRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
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
            new Middleware('menu:kpi.sub_strategy,view', only: ['index']),
            new Middleware('menu:kpi.sub_strategy,create', only: ['create', 'store']),
            new Middleware('menu:kpi.sub_strategy,edit', only: ['edit', 'update']),
            new Middleware('menu:kpi.sub_strategy,delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $year = $request->integer('year') ?: null;
        $strategyId = $request->integer('strategy_id') ?: null;

        $subStrategies = $this->subStrategies->paginateFiltered($year, $strategyId);
        $years = $this->strategies->availableYears();

        return view('sub_strategies.index', compact('subStrategies', 'years', 'year', 'strategyId'));
    }

    public function create(): View
    {
        return view('sub_strategies.create', $this->formData());
    }

    public function store(SubStrategyRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $reviewers = $data['reviewers'];
        unset($data['reviewers']);

        $subStrategy = $this->subStrategies->create($data);
        $this->subStrategies->syncReviewers($subStrategy, $reviewers);

        return redirect()->route('sub-strategies.index')->with('success', 'เพิ่มกลยุทธ์เรียบร้อยแล้ว');
    }

    public function edit(KpiSubStrategy $subStrategy): View
    {
        $subStrategy->load('reviewers');

        return view('sub_strategies.edit', array_merge($this->formData(), ['subStrategy' => $subStrategy]));
    }

    public function update(SubStrategyRequest $request, KpiSubStrategy $subStrategy): RedirectResponse
    {
        $data = $request->validated();
        $reviewers = $data['reviewers'];
        unset($data['reviewers']);

        $this->subStrategies->update($subStrategy, $data);
        $this->subStrategies->syncReviewers($subStrategy, $reviewers);

        return redirect()->route('sub-strategies.index')->with('success', 'แก้ไขกลยุทธ์เรียบร้อยแล้ว');
    }

    public function destroy(KpiSubStrategy $subStrategy): RedirectResponse
    {
        $this->subStrategies->delete($subStrategy);

        return redirect()->route('sub-strategies.index')->with('success', 'ลบกลยุทธ์เรียบร้อยแล้ว');
    }

    private function formData(): array
    {
        return [
            'strategyOptions' => $this->strategies->query()->orderByDesc('year')->orderBy('orderby')->get(),
            'users' => $this->permissions->selectableUsers(),
        ];
    }
}

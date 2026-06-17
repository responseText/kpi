<?php

namespace App\Http\Controllers;

use App\Http\Requests\StrategyRequest;
use App\Models\KpiStrategy;
use App\Repositories\Contracts\StrategyRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class StrategyController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly StrategyRepositoryInterface $strategies,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('menu:kpi.strategy,view', only: ['index']),
            new Middleware('menu:kpi.strategy,create', only: ['create', 'store']),
            new Middleware('menu:kpi.strategy,edit', only: ['edit', 'update']),
            new Middleware('menu:kpi.strategy,delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $year = $request->integer('year') ?: null;
        $strategies = $this->strategies->paginateByYear($year);
        $years = $this->strategies->availableYears();

        return view('strategies.index', compact('strategies', 'years', 'year'));
    }

    public function create(): View
    {
        return view('strategies.create');
    }

    public function store(StrategyRequest $request): RedirectResponse
    {
        $this->strategies->create($request->validated());

        return redirect()->route('strategies.index')->with('success', 'เพิ่มยุทธศาสตร์เรียบร้อยแล้ว');
    }

    public function edit(KpiStrategy $strategy): View
    {
        return view('strategies.edit', compact('strategy'));
    }

    public function update(StrategyRequest $request, KpiStrategy $strategy): RedirectResponse
    {
        $this->strategies->update($strategy, $request->validated());

        return redirect()->route('strategies.index')->with('success', 'แก้ไขยุทธศาสตร์เรียบร้อยแล้ว');
    }

    public function destroy(KpiStrategy $strategy): RedirectResponse
    {
        $this->strategies->delete($strategy);

        return redirect()->route('strategies.index')->with('success', 'ลบยุทธศาสตร์เรียบร้อยแล้ว');
    }
}

<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ReportController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly DashboardRepositoryInterface $dashboard,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('menu:kpi.report,view', only: ['index']),
        ];
    }

    public function index(Request $request): View
    {
        $years = $this->dashboard->availableYears();
        $year = (int) ($request->integer('year') ?: ($years->first() ?? (now()->year + 543)));
        $level = $request->string('level')->toString() ?: null;

        $summary = $this->dashboard->summaryByLevel(['year' => $year]);
        $indicators = $this->dashboard->indicators(array_filter(['year' => $year, 'level' => $level]));
        $statuses = $indicators->mapWithKeys(fn ($i) => [$i->id => $this->dashboard->overallStatus($i)]);

        return view('reports.index', compact('years', 'year', 'level', 'summary', 'indicators', 'statuses'));
    }
}

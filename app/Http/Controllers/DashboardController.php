<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardRepositoryInterface $dashboard,
    ) {}

    public function index(Request $request, ?string $level = null): View
    {
        // ระดับมาจาก route default (กระทรวง/จังหวัด/โรงพยาบาล) — ค่าอื่น = ทั้งหมด
        $level = in_array($level, array_keys(\App\Models\KpiIndicator::LEVELS), true) ? $level : null;

        $years = $this->dashboard->availableYears();
        $year = (int) ($request->integer('year') ?: ($years->first() ?? (now()->year + 543)));

        $summaryAll = $this->dashboard->summaryByLevel(['year' => $year]);
        // กรองการ์ดสรุป/กราฟให้เหลือเฉพาะระดับที่เลือก (ถ้าเลือก)
        $summary = $level ? [$level => $summaryAll[$level]] : $summaryAll;

        // แจกแจงผ่าน/ไม่ผ่าน ตามยุทธศาสตร์และกลยุทธ์ ในแต่ละระดับ
        $breakdown = $this->dashboard->breakdownByLevel(array_filter(['year' => $year, 'level' => $level]));

        $indicators = $this->dashboard->indicators(array_filter(['year' => $year, 'level' => $level]));
        $statuses = $indicators->mapWithKeys(fn ($i) => [$i->id => $this->dashboard->overallStatus($i)]);

        return view('dashboard.index', compact('years', 'year', 'level', 'summary', 'breakdown', 'indicators', 'statuses'));
    }

    public function monitor(Request $request): View
    {
        $years = $this->dashboard->availableYears();
        $year = (int) ($request->integer('year') ?: ($years->first() ?? (now()->year + 543)));
        $level = $request->string('level')->toString() ?: null;

        $summary = $this->dashboard->summaryByLevel(['year' => $year]);
        $indicators = $this->dashboard->indicators(array_filter(['year' => $year, 'level' => $level]));
        $statuses = $indicators->mapWithKeys(fn ($i) => [$i->id => $this->dashboard->overallStatus($i)]);

        return view('dashboard.monitor', compact('years', 'year', 'level', 'summary', 'indicators', 'statuses'));
    }
}

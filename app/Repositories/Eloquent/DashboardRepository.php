<?php

namespace App\Repositories\Eloquent;

use App\Models\KpiIndicator;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Services\KpiEvaluator;
use Illuminate\Support\Collection;

class DashboardRepository implements DashboardRepositoryInterface
{
    public function availableYears(): Collection
    {
        return KpiIndicator::query()
            ->select('year')->distinct()->orderByDesc('year')->pluck('year');
    }

    public function indicators(array $filters): Collection
    {
        return KpiIndicator::query()
            ->enabled()
            ->with(['main.category', 'targets.result', 'owners.employee'])
            ->when($filters['year'] ?? null, fn ($q, $v) => $q->where('year', $v))
            ->when($filters['level'] ?? null, fn ($q, $v) => $q->where('level', $v))
            ->orderBy('level')
            ->orderBy('orderby')
            ->get();
    }

    public function summaryByLevel(array $filters): array
    {
        $indicators = $this->indicators($filters);

        $base = fn () => ['total' => 0, 'pass' => 0, 'fail' => 0, 'pending' => 0];
        $summary = [
            KpiIndicator::LEVEL_HOSPITAL => $base(),
            KpiIndicator::LEVEL_PROVINCE => $base(),
            KpiIndicator::LEVEL_MINISTRY => $base(),
        ];

        foreach ($indicators as $ind) {
            $status = $this->overallStatus($ind);
            $summary[$ind->level]['total']++;
            $summary[$ind->level][$status]++;
        }

        return $summary;
    }

    public function breakdownByLevel(array $filters): array
    {
        $indicators = $this->indicators($filters);

        $result = [
            KpiIndicator::LEVEL_HOSPITAL => ['categories' => [], 'mains' => []],
            KpiIndicator::LEVEL_PROVINCE => ['categories' => [], 'mains' => []],
            KpiIndicator::LEVEL_MINISTRY => ['categories' => [], 'mains' => []],
        ];

        foreach ($indicators as $ind) {
            if (! isset($result[$ind->level])) {
                continue;
            }

            $status = $this->overallStatus($ind);   // pass | fail | pending
            $main = $ind->main;
            $category = $main?->category;

            // นับตามหมวด KPI (ผ่าน/ไม่ผ่าน/รอ ของตัวชี้วัดภายใต้หมวดนั้น)
            if ($category) {
                $bucket = &$result[$ind->level]['categories'][$category->id];
                $bucket ??= ['name' => $category->name, 'code' => $category->code, 'total' => 0, 'pass' => 0, 'fail' => 0, 'pending' => 0];
                $bucket['total']++;
                $bucket[$status]++;
                unset($bucket);
            }

            // นับตาม KPI หลัก
            if ($main) {
                $bucket = &$result[$ind->level]['mains'][$main->id];
                $bucket ??= ['name' => $main->name, 'code' => $main->code, 'category' => $category?->name, 'total' => 0, 'pass' => 0, 'fail' => 0, 'pending' => 0];
                $bucket['total']++;
                $bucket[$status]++;
                unset($bucket);
            }
        }

        return $result;
    }

    public function passRateTrend(?string $level = null): array
    {
        // ดึงตัวชี้วัดทุกปีในครั้งเดียว (กรองระดับถ้าระบุ) แล้วจัดกลุ่มตามปีใน PHP
        $indicators = $this->indicators(array_filter(['level' => $level]));

        $byYear = [];
        foreach ($indicators as $ind) {
            $year = (int) $ind->year;
            $byYear[$year] ??= ['total' => 0, 'pass' => 0];
            $byYear[$year]['total']++;
            if ($this->overallStatus($ind) === KpiEvaluator::STATUS_PASS) {
                $byYear[$year]['pass']++;
            }
        }

        ksort($byYear); // เรียงปีจากน้อยไปมาก

        $trend = [];
        foreach ($byYear as $year => $c) {
            $trend[] = [
                'year' => $year,
                'pct' => $c['total'] > 0 ? (int) round($c['pass'] / $c['total'] * 100) : 0,
                'total' => $c['total'],
                'pass' => $c['pass'],
            ];
        }

        return $trend;
    }

    /**
     * สถานะรวมของตัวชี้วัดหนึ่ง ๆ:
     * - fail   ถ้ามีช่วงใดไม่ผ่าน
     * - pending ถ้ายังมีช่วงที่ยังไม่บันทึก (และไม่มี fail)
     * - pass   ถ้าทุกช่วงผ่าน
     */
    public function overallStatus(KpiIndicator $indicator): string
    {
        $targets = $indicator->targets;
        if ($targets->isEmpty()) {
            return KpiEvaluator::STATUS_PENDING;
        }

        $hasFail = false;
        $hasPending = false;

        foreach ($targets as $target) {
            $status = $target->result?->pass_status ?? KpiEvaluator::STATUS_PENDING;
            if ($status === KpiEvaluator::STATUS_FAIL) {
                $hasFail = true;
            } elseif ($status === KpiEvaluator::STATUS_PENDING) {
                $hasPending = true;
            }
        }

        if ($hasFail) {
            return KpiEvaluator::STATUS_FAIL;
        }

        return $hasPending ? KpiEvaluator::STATUS_PENDING : KpiEvaluator::STATUS_PASS;
    }
}

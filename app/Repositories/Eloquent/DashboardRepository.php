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
            ->with(['subStrategy.strategy', 'targets.result', 'owners.employee'])
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

<?php

namespace App\Repositories\Eloquent;

use App\Models\KpiIndicator;
use App\Models\KpiTarget;
use App\Repositories\Contracts\TargetRepositoryInterface;
use App\Services\PeriodCalculator;
use Illuminate\Database\Eloquent\Model;

class TargetRepository extends BaseRepository implements TargetRepositoryInterface
{
    protected function makeModel(): Model
    {
        return new KpiTarget();
    }

    public function syncPeriods(KpiIndicator $indicator): void
    {
        $periodNumbers = PeriodCalculator::periodNumbers($indicator->period_type);

        // ลบช่วงที่ไม่อยู่ในรูปแบบปัจจุบัน (เช่น เปลี่ยนรายไตรมาส -> รายปี)
        $indicator->targets()->whereNotIn('period_no', $periodNumbers)->delete();

        foreach ($periodNumbers as $periodNo) {
            $range = PeriodCalculator::resolve($indicator->year_type, (int) $indicator->year, $periodNo);

            KpiTarget::updateOrCreate(
                ['indicator_id' => $indicator->id, 'period_no' => $periodNo],
                [
                    'period_label' => $range['label'],
                    'start_date' => $range['start'],
                    'end_date' => $range['end'],
                    // ตั้ง operator เริ่มต้นเฉพาะตอนสร้างใหม่ (updateOrCreate จะไม่ทับถ้ามีอยู่แล้วและไม่ส่งค่า)
                ]
            );
        }
    }

    public function saveTargets(KpiIndicator $indicator, array $rows): void
    {
        foreach ($rows as $periodNo => $row) {
            $target = $indicator->targets()->where('period_no', $periodNo)->first();
            if (! $target) {
                continue;
            }

            $target->operator = $row['operator'];
            $target->target_value = $row['target_value'] ?? null;
            $target->target_text = $row['target_text'] ?? null;
            $target->save();
        }
    }
}

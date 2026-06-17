<?php

namespace Database\Seeders;

use App\Models\KpiIndicator;
use App\Models\KpiStrategy;
use App\Models\KpiSubStrategy;
use App\Models\KpiTarget;
use App\Services\KpiEvaluator;
use App\Services\PeriodCalculator;
use Illuminate\Database\Seeder;

/**
 * ข้อมูลตัวอย่างสำหรับทดสอบ/สาธิต (ปี 2569) — idempotent, ปลอดภัยต่อข้อมูลจริง
 */
class KpiDemoSeeder extends Seeder
{
    public function run(): void
    {
        $ownerId = 1;     // admin
        $reviewerId = 2;  // dogtorart

        $strategy = KpiStrategy::firstOrCreate(
            ['year' => 2569, 'code' => 'DEMO-1'],
            ['name' => 'พัฒนาคุณภาพบริการสุขภาพ', 'status' => 'enable', 'orderby' => 1]
        );

        $sub = KpiSubStrategy::firstOrCreate(
            ['strategy_id' => $strategy->id, 'code' => 'DEMO-1.1'],
            ['name' => 'ยกระดับความปลอดภัยผู้ป่วย', 'status' => 'enable', 'orderby' => 1]
        );
        $sub->reviewers()->syncWithoutDetaching([$reviewerId]);

        $defs = [
            ['code' => 'D-H1', 'level' => 'hospital', 'name' => 'อัตราความพึงพอใจผู้รับบริการ', 'year_type' => 'fiscal', 'period_type' => 'quarterly', 'unit' => 'ร้อยละ', 'op' => 'gte', 'target' => 85, 'results' => [90, 88, null, null]],
            ['code' => 'D-H2', 'level' => 'hospital', 'name' => 'อัตราการติดเชื้อในโรงพยาบาล', 'year_type' => 'buddhist', 'period_type' => 'quarterly', 'unit' => 'ร้อยละ', 'op' => 'lte', 'target' => 5, 'results' => [3, 6, null, null]],
            ['code' => 'D-P1', 'level' => 'province', 'name' => 'ร้อยละการคัดกรองเบาหวานความดัน', 'year_type' => 'fiscal', 'period_type' => 'annual', 'unit' => 'ร้อยละ', 'op' => 'gte', 'target' => 90, 'results' => [92]],
            ['code' => 'D-M1', 'level' => 'ministry', 'name' => 'ผ่านการประเมิน HA ขั้นที่ 3', 'year_type' => 'buddhist', 'period_type' => 'annual', 'unit' => null, 'op' => 'passfail', 'target' => null, 'results' => ['pass']],
        ];

        foreach ($defs as $i => $d) {
            $indicator = KpiIndicator::firstOrCreate(
                ['code' => $d['code']],
                [
                    'sub_strategy_id' => $sub->id, 'level' => $d['level'], 'name' => $d['name'],
                    'year_type' => $d['year_type'], 'year' => 2569, 'period_type' => $d['period_type'],
                    'unit' => $d['unit'], 'status' => 'enable', 'orderby' => $i + 1,
                ]
            );
            $indicator->owners()->syncWithoutDetaching([$ownerId => ['is_primary' => true]]);

            // สร้าง targets ตามช่วงเวลา
            $periodNumbers = PeriodCalculator::periodNumbers($indicator->period_type);
            foreach ($periodNumbers as $idx => $periodNo) {
                $range = PeriodCalculator::resolve($indicator->year_type, 2569, $periodNo);
                $target = KpiTarget::updateOrCreate(
                    ['indicator_id' => $indicator->id, 'period_no' => $periodNo],
                    [
                        'period_label' => $range['label'],
                        'start_date' => $range['start'],
                        'end_date' => $range['end'],
                        'operator' => $d['op'],
                        'target_value' => $d['op'] === 'passfail' ? null : $d['target'],
                        'target_text' => $d['op'] === 'passfail' ? 'ผ่านการประเมิน' : null,
                    ]
                );

                // บันทึกผลตัวอย่าง
                $resultVal = $d['results'][$idx] ?? null;
                if ($resultVal !== null) {
                    if ($d['op'] === 'passfail') {
                        $status = KpiEvaluator::evaluate($d['op'], null, null, $resultVal);
                        $target->result()->updateOrCreate([], [
                            'indicator_id' => $indicator->id, 'result_text' => $resultVal,
                            'pass_status' => $status, 'recorded_by' => $ownerId, 'recorded_at' => now(),
                        ]);
                    } else {
                        $status = KpiEvaluator::evaluate($d['op'], (float) $d['target'], (float) $resultVal);
                        $target->result()->updateOrCreate([], [
                            'indicator_id' => $indicator->id, 'result_value' => $resultVal,
                            'pass_status' => $status, 'recorded_by' => $ownerId, 'recorded_at' => now(),
                        ]);
                    }
                }
            }
        }
    }
}

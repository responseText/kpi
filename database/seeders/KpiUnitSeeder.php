<?php

namespace Database\Seeders;

use App\Models\KpiUnit;
use Illuminate\Database\Seeder;

/**
 * หน่วยวัดเริ่มต้น จัดกลุ่มตามหลักการบริหารผลงาน (Performance Measurement)
 * อ้างอิงค่าคงที่กลุ่ม KPI ใน KpiUnit::GROUPS
 */
class KpiUnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            KpiUnit::GROUP_QUANTITY => ['จำนวน', 'คน', 'ราย', 'ครั้ง', 'ฉบับ', 'โครงการ', 'หน่วย', 'เตียง'],
            KpiUnit::GROUP_QUALITY => ['คะแนน', 'ระดับ', 'อันดับ', 'ดัชนี'],
            KpiUnit::GROUP_TIME => ['วัน', 'ชั่วโมง', 'นาที'],
            KpiUnit::GROUP_COST => ['บาท'],
            KpiUnit::GROUP_EFFICIENCY => ['ร้อยละ', 'อัตรา', 'ค่าเฉลี่ย', 'สัดส่วน'],
        ];

        foreach ($units as $groupCode => $names) {
            foreach ($names as $i => $name) {
                KpiUnit::updateOrCreate(
                    ['name' => $name],
                    [
                        'group_code' => $groupCode,
                        'orderby' => ($i + 1) * 10,
                        'status' => 'enable',
                    ]
                );
            }
        }
    }
}

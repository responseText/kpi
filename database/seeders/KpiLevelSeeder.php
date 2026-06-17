<?php

namespace Database\Seeders;

use App\Models\KpiLevel;
use Illuminate\Database\Seeder;

/**
 * ระดับสิทธิ์การใช้งานของระบบ KPI
 */
class KpiLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            [
                'code' => KpiLevel::SUPER_ADMIN,
                'name' => 'ผู้ดูแลระบบสูงสุด',
                'scope' => null,
                'description' => 'มีสิทธิ์ทุกอย่างในระบบ และไม่มีผู้ใดปรับสิทธิ์ได้',
            ],
            [
                'code' => KpiLevel::ADMIN_ALL,
                'name' => 'ผู้ดูแลตัวชี้วัดทั้งหมด',
                'scope' => KpiLevel::SCOPE_ALL,
                'description' => 'จัดการข้อมูลตัวชี้วัดได้ทุกระดับ',
            ],
            [
                'code' => KpiLevel::ADMIN_HOSPITAL,
                'name' => 'ผู้ดูแลระดับตัวชี้วัดโรงพยาบาล',
                'scope' => 'hospital',
                'description' => 'จัดการข้อมูลได้เฉพาะตัวชี้วัดระดับโรงพยาบาล',
            ],
            [
                'code' => KpiLevel::ADMIN_PROVINCE,
                'name' => 'ผู้ดูแลระดับตัวชี้วัดจังหวัด',
                'scope' => 'province',
                'description' => 'จัดการข้อมูลได้เฉพาะตัวชี้วัดระดับจังหวัด',
            ],
            [
                'code' => KpiLevel::ADMIN_MINISTRY,
                'name' => 'ผู้ดูแลระดับตัวชี้วัดกระทรวง',
                'scope' => 'ministry',
                'description' => 'จัดการข้อมูลได้เฉพาะตัวชี้วัดระดับกระทรวง',
            ],
            [
                'code' => KpiLevel::OWNER,
                'name' => 'ผู้รับผิดชอบตัวชี้วัด',
                'scope' => null,
                'description' => 'บันทึกผลได้เฉพาะตัวชี้วัดที่ตนรับผิดชอบ',
            ],
        ];

        foreach ($levels as $i => $level) {
            KpiLevel::updateOrCreate(
                ['code' => $level['code']],
                [
                    'name' => $level['name'],
                    'scope' => $level['scope'],
                    'description' => $level['description'],
                    'orderby' => ($i + 1) * 10,
                    'status' => 'enable',
                ]
            );
        }
    }
}

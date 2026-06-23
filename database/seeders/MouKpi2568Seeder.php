<?php

namespace Database\Seeders;

use App\Models\KpiCategory;
use App\Models\KpiIndicator;
use App\Models\KpiMain;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * นำเข้าโครงสร้างตัวชี้วัดจากเอกสาร KPI MOU คปสอ.ทองแสนขัน ปีงบประมาณ 2568
 * ลำดับชั้น: หมวด KPI → KPI หลัก → ตัวชี้วัด (17 หมวด / 26 KPI หลัก / 51 ตัวชี้วัด)
 *
 * - ลบ/ล้างตัวชี้วัดเดิมทั้งหมด (รวมข้อมูลตัวอย่าง) แล้วนำเข้าใหม่จาก database/data/mou2568.json
 * - หมวด KPI ที่นำเข้ายังไม่ผูกกับกลยุทธ์ (sub_strategy_id = null) — กำหนดภายหลังผ่านหน้าจอได้
 * - ผู้รับผิดชอบในไฟล์เป็นชื่อต้น (แมปเป็น user อัตโนมัติไม่ได้) จึงเก็บไว้ในช่อง "รายละเอียด"
 * - รันซ้ำได้ (idempotent): ล้างตัวชี้วัด/KPI หลัก/หมวด KPI ก่อนนำเข้าใหม่ทุกครั้ง
 */
class MouKpi2568Seeder extends Seeder
{
    private const YEAR = 2568;

    public function run(): void
    {
        $path = database_path('data/mou2568.json');
        if (! is_file($path)) {
            $this->command?->warn("ไม่พบไฟล์ข้อมูล: {$path}");

            return;
        }

        $cats = json_decode((string) file_get_contents($path), true);
        if (! is_array($cats)) {
            $this->command?->warn('อ่านไฟล์ mou2568.json ไม่สำเร็จ');

            return;
        }

        DB::transaction(function () use ($cats) {
            $this->clearExisting();

            $catOrder = 0;
            foreach ($cats as $cat) {
                [$catCode, $catName] = $this->splitCategory($cat['raw'] ?? '');
                $catOrder += 10;

                $category = KpiCategory::create([
                    'sub_strategy_id' => null,
                    'code' => $catCode,
                    'name' => $catName,
                    'orderby' => $catOrder,
                    'status' => 'enable',
                ]);

                $mainOrder = 0;
                foreach ($cat['mains'] ?? [] as $main) {
                    [$mainCode, $mainName] = $this->splitMain($main['raw'] ?? '');
                    $mainOrder += 10;

                    $kpiMain = KpiMain::create([
                        'category_id' => $category->id,
                        'code' => $mainCode,
                        'name' => $mainName,
                        'orderby' => $mainOrder,
                        'status' => 'enable',
                    ]);

                    $indOrder = 0;
                    foreach ($main['indicators'] ?? [] as $ind) {
                        [$indCode, $indName] = $this->splitIndicator($ind['name'] ?? '');
                        if ($indName === '') {
                            continue;
                        }
                        $indOrder += 10;

                        KpiIndicator::create([
                            'kpi_main_id' => $kpiMain->id,
                            'sub_strategy_id' => null,
                            'level' => KpiIndicator::LEVEL_HOSPITAL,
                            'code' => $indCode,
                            'name' => $indName,
                            'year_type' => KpiIndicator::YEAR_FISCAL,
                            'year' => self::YEAR,
                            'period_type' => KpiIndicator::PERIOD_ANNUAL,
                            'description' => $this->buildDescription($ind),
                            'orderby' => $indOrder,
                            'status' => 'enable',
                        ]);
                    }
                }
            }
        });

        $this->command?->info(sprintf(
            'นำเข้า MOU 2568 สำเร็จ: หมวด KPI %d · KPI หลัก %d · ตัวชี้วัด %d',
            KpiCategory::count(),
            KpiMain::count(),
            KpiIndicator::count(),
        ));
    }

    /** ล้างตัวชี้วัดเดิมทั้งหมด (รวมข้อมูลที่อ้างถึง) + KPI หลัก + หมวด KPI */
    private function clearExisting(): void
    {
        $indicatorIds = DB::table('kpi_indicators')->pluck('id');
        if ($indicatorIds->isNotEmpty()) {
            DB::table('kpi_results')->whereIn('indicator_id', $indicatorIds)->delete();
            DB::table('kpi_targets')->whereIn('indicator_id', $indicatorIds)->delete();
            DB::table('kpi_indicator_owners')->whereIn('indicator_id', $indicatorIds)->delete();
        }
        // ลบจริง (ไม่ใช่ soft delete) เพื่อเริ่มต้นใหม่สะอาด
        DB::table('kpi_indicators')->delete();
        DB::table('kpi_mains')->delete();
        DB::table('kpi_categories')->delete();
    }

    /** "หมวด KPI ที่ 1 ชื่อ..." → ['หมวด 1', 'ชื่อ...'] */
    private function splitCategory(string $raw): array
    {
        if (preg_match('/^หมวด\s*KPI\s*ที่\s*(\d+)\s*(.*)$/u', $raw, $m)) {
            return ['หมวด ' . $m[1], trim($m[2])];
        }

        return [null, trim($raw)];
    }

    /** "KPI 1 ชื่อ..." → ['KPI 1', 'ชื่อ...'] */
    private function splitMain(string $raw): array
    {
        if (preg_match('/^KPI\s*(\d+)\s*(.*)$/u', $raw, $m)) {
            return ['KPI ' . $m[1], trim($m[2])];
        }

        return [null, trim($raw)];
    }

    /** "1. ร้อยละ..." → ['1', 'ร้อยละ...'] */
    private function splitIndicator(string $raw): array
    {
        if (preg_match('/^(\d+)\.\s*(.*)$/u', $raw, $m)) {
            return [$m[1], trim($m[2])];
        }

        return [null, trim($raw)];
    }

    /** ประกอบรายละเอียด หน่วยงาน/ผู้รับผิดชอบ จากไฟล์ MOU (เก็บไว้กันข้อมูลหาย) */
    private function buildDescription(array $ind): ?string
    {
        $parts = [];
        if (! empty($ind['unit_org'])) {
            $parts[] = 'หน่วยงานรับผิดชอบ: ' . $ind['unit_org'];
        }
        if (! empty($ind['owner_sso'])) {
            $parts[] = 'ผู้รับผิดชอบ สสอ.: ' . $ind['owner_sso'];
        }
        if (! empty($ind['owner_rp'])) {
            $parts[] = 'ผู้รับผิดชอบ รพ.: ' . $ind['owner_rp'];
        }

        return $parts === [] ? null : implode(' · ', $parts);
    }
}

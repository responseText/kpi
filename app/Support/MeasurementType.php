<?php

namespace App\Support;

use App\Models\KpiUnit;

/**
 * ประเภทการวัดของตัวชี้วัด (Measurement Type) ตามหลักการบริหารผลงาน (Performance Measurement)
 *
 * เป็น "ค่าคงที่ในระบบ" — แต่ละประเภทจับคู่กับ:
 *   - กลุ่ม KPI (group) ตรงกับ KpiUnit::GROUPS (quantity/quality/time/cost/efficiency)
 *   - สูตรมาตรฐาน (formula) สำหรับแสดงผล
 *   - เงื่อนไขว่าต้องระบุ ตัวตั้ง (A) / ตัวหาร (B) / สูตร / ค่าคงที่ K หรือไม่
 *
 * ใช้ขับฟอร์มตัวชี้วัด (แสดง/ซ่อนช่องตามเงื่อนไข) และการตรวจสอบใน IndicatorRequest
 */
final class MeasurementType
{
    public const COUNT = 'count';

    public const SCORE = 'score';

    public const LEVEL = 'level';

    public const RANKING = 'ranking';

    public const INDEX = 'index';

    public const DURATION = 'duration';

    public const COST_SUM = 'cost_sum';

    public const PERCENT = 'percent';

    public const RATE = 'rate';

    public const AVERAGE = 'average';

    public const RATIO = 'ratio';

    /**
     * เมทาดาทาของแต่ละประเภท
     *   group            => รหัสกลุ่ม KPI (ตรงกับ KpiUnit group_code)
     *   label            => ชื่อแสดงผล
     *   formula          => สูตรมาตรฐาน (แสดงให้ผู้ใช้เห็น)
     *   requires_a/b     => ต้องระบุนิยาม ตัวตั้ง (A) / ตัวหาร (B) หรือไม่
     *   allows_ab        => อนุญาตให้ระบุ A/B แบบไม่บังคับ (เช่น INDEX)
     *   requires_formula => ต้องระบุสูตร/เกณฑ์เอง (LEVEL/RANKING/INDEX)
     *   formula_label    => ป้ายช่องกรอกสูตร/เกณฑ์ (null = ไม่มีช่องกรอก)
     *   requires_factor  => ต้องระบุค่าคงที่ K (เช่น RATE)
     */
    public const META = [
        self::COUNT => [
            'group' => KpiUnit::GROUP_QUANTITY, 'label' => 'นับจำนวน (Count)', 'formula' => 'Actual',
            'requires_a' => false, 'requires_b' => false, 'allows_ab' => false,
            'requires_formula' => false, 'formula_label' => null, 'requires_factor' => false,
        ],
        self::SCORE => [
            'group' => KpiUnit::GROUP_QUALITY, 'label' => 'คะแนน (Score)', 'formula' => 'Actual Score',
            'requires_a' => false, 'requires_b' => false, 'allows_ab' => false,
            'requires_formula' => false, 'formula_label' => null, 'requires_factor' => false,
        ],
        self::LEVEL => [
            'group' => KpiUnit::GROUP_QUALITY, 'label' => 'ระดับขั้น (Level)', 'formula' => 'Compare Criteria',
            'requires_a' => false, 'requires_b' => false, 'allows_ab' => false,
            'requires_formula' => true, 'formula_label' => 'เกณฑ์การให้ระดับ', 'requires_factor' => false,
        ],
        self::RANKING => [
            'group' => KpiUnit::GROUP_QUALITY, 'label' => 'จัดอันดับ (Ranking)', 'formula' => 'Ranking',
            'requires_a' => false, 'requires_b' => false, 'allows_ab' => false,
            'requires_formula' => true, 'formula_label' => 'เกณฑ์การจัดอันดับ', 'requires_factor' => false,
        ],
        self::INDEX => [
            'group' => KpiUnit::GROUP_QUALITY, 'label' => 'ดัชนี (Index)', 'formula' => 'Custom Formula',
            'requires_a' => false, 'requires_b' => false, 'allows_ab' => true,
            'requires_formula' => true, 'formula_label' => 'สูตรคำนวณ (กำหนดเอง)', 'requires_factor' => false,
        ],
        self::DURATION => [
            'group' => KpiUnit::GROUP_TIME, 'label' => 'ระยะเวลา (Duration)', 'formula' => 'End Date − Start Date',
            'requires_a' => false, 'requires_b' => false, 'allows_ab' => false,
            'requires_formula' => false, 'formula_label' => null, 'requires_factor' => false,
        ],
        self::COST_SUM => [
            'group' => KpiUnit::GROUP_COST, 'label' => 'ผลรวมต้นทุน (Cost Sum)', 'formula' => 'SUM(Value)',
            'requires_a' => false, 'requires_b' => false, 'allows_ab' => false,
            'requires_formula' => false, 'formula_label' => null, 'requires_factor' => false,
        ],
        self::PERCENT => [
            'group' => KpiUnit::GROUP_EFFICIENCY, 'label' => 'ร้อยละ (Percent)', 'formula' => '(A/B)×100',
            'requires_a' => true, 'requires_b' => true, 'allows_ab' => false,
            'requires_formula' => false, 'formula_label' => null, 'requires_factor' => false,
        ],
        self::RATE => [
            'group' => KpiUnit::GROUP_EFFICIENCY, 'label' => 'อัตรา (Rate)', 'formula' => '(A/B)×K',
            'requires_a' => true, 'requires_b' => true, 'allows_ab' => false,
            'requires_formula' => false, 'formula_label' => null, 'requires_factor' => true,
        ],
        self::AVERAGE => [
            'group' => KpiUnit::GROUP_EFFICIENCY, 'label' => 'ค่าเฉลี่ย (Average)', 'formula' => 'SUM(X)/N',
            'requires_a' => true, 'requires_b' => true, 'allows_ab' => false,
            'requires_formula' => false, 'formula_label' => null, 'requires_factor' => false,
        ],
        self::RATIO => [
            'group' => KpiUnit::GROUP_EFFICIENCY, 'label' => 'สัดส่วน (Ratio)', 'formula' => 'A:B',
            'requires_a' => true, 'requires_b' => true, 'allows_ab' => false,
            'requires_formula' => false, 'formula_label' => null, 'requires_factor' => false,
        ],
    ];

    /** รหัสประเภททั้งหมด */
    public static function keys(): array
    {
        return array_keys(self::META);
    }

    /** เมทาดาทาของประเภทหนึ่ง (null ถ้าไม่รู้จัก) */
    public static function meta(?string $type): ?array
    {
        return $type !== null ? (self::META[$type] ?? null) : null;
    }

    /** ชื่อแสดงผลของประเภท (คืนค่า $type เดิมถ้าไม่รู้จัก) */
    public static function label(?string $type): ?string
    {
        if ($type === null || $type === '') {
            return null;
        }

        return self::META[$type]['label'] ?? $type;
    }

    /** ชื่อกลุ่ม KPI ของประเภท (เช่น "เชิงประสิทธิภาพ (Efficiency)") */
    public static function groupLabel(?string $type): ?string
    {
        $group = self::META[$type]['group'] ?? null;

        return $group ? (KpiUnit::GROUPS[$group] ?? $group) : null;
    }

    /**
     * ประเภทจัดกลุ่มตามกลุ่ม KPI สำหรับสร้าง <optgroup> ในฟอร์ม
     * เรียงตามลำดับกลุ่มใน KpiUnit::GROUPS
     *
     * @return array<string, array<string, string>> [groupCode => [typeCode => label]]
     */
    public static function optgroups(): array
    {
        $groups = [];
        foreach (array_keys(KpiUnit::GROUPS) as $groupCode) {
            foreach (self::META as $type => $meta) {
                if ($meta['group'] === $groupCode) {
                    $groups[$groupCode][$type] = $meta['label'];
                }
            }
        }

        return $groups;
    }

    /**
     * รหัสประเภทที่ "บังคับ" ให้ระบุฟิลด์ที่กำหนด — ใช้สร้าง rule required_if
     *
     * @param  string  $field  a|b|formula|factor
     * @return array<int, string>
     */
    public static function typesRequiring(string $field): array
    {
        $key = match ($field) {
            'a' => 'requires_a',
            'b' => 'requires_b',
            'formula' => 'requires_formula',
            'factor' => 'requires_factor',
            default => null,
        };

        if ($key === null) {
            return [];
        }

        return array_keys(array_filter(self::META, fn (array $m) => $m[$key] === true));
    }

    /**
     * ประเภทนี้ "ใช้งาน" ฟิลด์นี้หรือไม่ (บังคับ หรืออนุญาตให้กรอก)
     * ใช้ล้างค่าฟิลด์ที่ไม่เกี่ยวข้องก่อนบันทึก
     *
     * @param  string  $field  a|b|formula|factor
     */
    public static function usesField(?string $type, string $field): bool
    {
        $meta = self::meta($type);
        if ($meta === null) {
            return false;
        }

        return match ($field) {
            'a' => $meta['requires_a'] || $meta['allows_ab'],
            'b' => $meta['requires_b'] || $meta['allows_ab'],
            'formula' => $meta['requires_formula'],
            'factor' => $meta['requires_factor'],
            default => false,
        };
    }
}

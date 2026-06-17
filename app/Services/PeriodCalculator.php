<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * คำนวณช่วงเวลาเก็บข้อมูลของตัวชี้วัด
 *
 * รองรับ 2 แบบปี:
 *   - buddhist  : ปี พ.ศ.        (1 ม.ค. – 31 ธ.ค. ของปีนั้น)
 *   - fiscal    : ปีงบประมาณ     (1 ต.ค. ปีก่อน – 30 ก.ย. ปีนั้น)
 *
 * และ 2 แบบการเก็บผลงาน:
 *   - annual    : รายปี (period_no = 0)
 *   - quarterly : รายไตรมาส (period_no = 1..4)
 *
 * ปีที่รับเข้า/ส่งออกเป็น "ปี พ.ศ." ส่วนวันที่ที่คืนเป็นปฏิทิน ค.ศ. (พ.ศ. − 543)
 */
class PeriodCalculator
{
    public const YEAR_BUDDHIST = 'buddhist';
    public const YEAR_FISCAL = 'fiscal';

    public const PERIOD_ANNUAL = 'annual';
    public const PERIOD_QUARTERLY = 'quarterly';

    /** ชื่อเดือนไทยแบบย่อสำหรับแสดงผล */
    private const THAI_MONTHS = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.',
        7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.',
    ];

    /**
     * คืนรายการ period_no ที่ต้องสร้าง target ตามรูปแบบการเก็บผลงาน
     *
     * @return int[]  เช่น [0] สำหรับรายปี, [1,2,3,4] สำหรับรายไตรมาส
     */
    public static function periodNumbers(string $periodType): array
    {
        return $periodType === self::PERIOD_QUARTERLY ? [1, 2, 3, 4] : [0];
    }

    /**
     * คำนวณช่วงวันที่ของ period หนึ่ง ๆ
     *
     * @param  string  $yearType   buddhist|fiscal
     * @param  int     $yearBE     ปี พ.ศ. เช่น 2569
     * @param  int     $periodNo   0=รายปี, 1..4=ไตรมาส
     * @return array{start: CarbonImmutable, end: CarbonImmutable, label: string}
     */
    public static function resolve(string $yearType, int $yearBE, int $periodNo): array
    {
        $ad = $yearBE - 543;

        if ($periodNo === 0) {
            [$start, $end] = self::annualRange($yearType, $ad);
            $label = 'รายปี';
        } elseif ($periodNo >= 1 && $periodNo <= 4) {
            [$start, $end] = self::quarterRange($yearType, $ad, $periodNo);
            $label = 'ไตรมาส ' . $periodNo;
        } else {
            throw new InvalidArgumentException("period_no ไม่ถูกต้อง: {$periodNo}");
        }

        return ['start' => $start, 'end' => $end, 'label' => $label];
    }

    /** ช่วงรายปี (annual) */
    private static function annualRange(string $yearType, int $ad): array
    {
        if ($yearType === self::YEAR_FISCAL) {
            // 1 ต.ค. (ปีก่อน) – 30 ก.ย. (ปีนั้น)
            return [
                CarbonImmutable::create($ad - 1, 10, 1),
                CarbonImmutable::create($ad, 9, 30),
            ];
        }

        // buddhist: 1 ม.ค. – 31 ธ.ค.
        return [
            CarbonImmutable::create($ad, 1, 1),
            CarbonImmutable::create($ad, 12, 31),
        ];
    }

    /** ช่วงรายไตรมาส (quarter 1..4) */
    private static function quarterRange(string $yearType, int $ad, int $q): array
    {
        if ($yearType === self::YEAR_FISCAL) {
            // ปีงบประมาณ: Q1 เริ่ม ต.ค. ปีก่อน
            return match ($q) {
                1 => [CarbonImmutable::create($ad - 1, 10, 1), CarbonImmutable::create($ad - 1, 12, 31)],
                2 => [CarbonImmutable::create($ad, 1, 1), CarbonImmutable::create($ad, 3, 31)],
                3 => [CarbonImmutable::create($ad, 4, 1), CarbonImmutable::create($ad, 6, 30)],
                4 => [CarbonImmutable::create($ad, 7, 1), CarbonImmutable::create($ad, 9, 30)],
            };
        }

        // ปี พ.ศ.: Q1 เริ่ม ม.ค.
        return match ($q) {
            1 => [CarbonImmutable::create($ad, 1, 1), CarbonImmutable::create($ad, 3, 31)],
            2 => [CarbonImmutable::create($ad, 4, 1), CarbonImmutable::create($ad, 6, 30)],
            3 => [CarbonImmutable::create($ad, 7, 1), CarbonImmutable::create($ad, 9, 30)],
            4 => [CarbonImmutable::create($ad, 10, 1), CarbonImmutable::create($ad, 12, 31)],
        };
    }

    /** แสดงช่วงวันที่แบบไทย เช่น "1 ต.ค. 2568 – 31 ธ.ค. 2568" */
    public static function thaiRange(\DateTimeInterface $start, \DateTimeInterface $end): string
    {
        return self::thaiDate($start) . ' – ' . self::thaiDate($end);
    }

    public static function thaiDate(\DateTimeInterface $d): string
    {
        $day = (int) $d->format('j');
        $month = self::THAI_MONTHS[(int) $d->format('n')];
        $yearBE = (int) $d->format('Y') + 543;

        return "{$day} {$month} {$yearBE}";
    }
}

<?php

namespace App\Services;

/**
 * ประเมินผลงานเทียบกับค่าเป้าหมาย ตามเงื่อนไขเกณฑ์ (operator)
 */
class KpiEvaluator
{
    public const OP_GT = 'gt';        // >
    public const OP_GTE = 'gte';      // >=
    public const OP_LT = 'lt';        // <
    public const OP_LTE = 'lte';      // <=
    public const OP_NE = 'ne';        // !=
    public const OP_EQ = 'eq';        // =
    public const OP_PASSFAIL = 'passfail';   // ผ่าน/ไม่ผ่าน (ไม่มีค่าตัวเลข)

    public const STATUS_PASS = 'pass';
    public const STATUS_FAIL = 'fail';
    public const STATUS_PENDING = 'pending';

    /** สัญลักษณ์สำหรับแสดงผล */
    public const SYMBOLS = [
        self::OP_GT => '>',
        self::OP_GTE => '≥',
        self::OP_LT => '<',
        self::OP_LTE => '≤',
        self::OP_NE => '≠',
        self::OP_EQ => '=',
        self::OP_PASSFAIL => 'ผ่าน/ไม่ผ่าน',
    ];

    /** ป้ายกำกับภาษาไทยสำหรับฟอร์ม */
    public const LABELS = [
        self::OP_GT => 'มากกว่า (>)',
        self::OP_GTE => 'มากกว่าหรือเท่ากับ (≥)',
        self::OP_LT => 'น้อยกว่า (<)',
        self::OP_LTE => 'น้อยกว่าหรือเท่ากับ (≤)',
        self::OP_NE => 'ไม่เท่ากับ (≠)',
        self::OP_EQ => 'เท่ากับ (=)',
        self::OP_PASSFAIL => 'ผ่าน/ไม่ผ่าน',
    ];

    /**
     * ประเมินสถานะผ่าน/ไม่ผ่าน
     *
     * @param  string       $operator
     * @param  float|null   $target     ค่าเป้าหมาย (กรณีตัวเลข)
     * @param  float|null   $result     ค่าผลงาน (กรณีตัวเลข)
     * @param  string|null  $resultText สำหรับ operator passfail: 'pass' หรือ 'fail'
     * @return string  pass|fail|pending
     */
    public static function evaluate(string $operator, ?float $target, ?float $result, ?string $resultText = null): string
    {
        if ($operator === self::OP_PASSFAIL) {
            if ($resultText === null || $resultText === '') {
                return self::STATUS_PENDING;
            }

            return $resultText === self::STATUS_PASS ? self::STATUS_PASS : self::STATUS_FAIL;
        }

        if ($result === null || $target === null) {
            return self::STATUS_PENDING;
        }

        $ok = match ($operator) {
            self::OP_GT => $result > $target,
            self::OP_GTE => $result >= $target,
            self::OP_LT => $result < $target,
            self::OP_LTE => $result <= $target,
            self::OP_NE => $result != $target,
            self::OP_EQ => $result == $target,
            default => false,
        };

        return $ok ? self::STATUS_PASS : self::STATUS_FAIL;
    }

    /**
     * ร้อยละความสำเร็จเทียบเป้าหมาย (ใช้กับ gauge/แถบความคืบหน้า)
     * คืน null หากคำนวณไม่ได้ (passfail หรือไม่มีค่า)
     */
    public static function achievementPercent(string $operator, ?float $target, ?float $result): ?float
    {
        if ($operator === self::OP_PASSFAIL || $result === null || $target === null || (float) $target == 0.0) {
            return null;
        }

        // เกณฑ์แบบ "ยิ่งน้อยยิ่งดี" → กลับด้านการเทียบ
        $lowerIsBetter = in_array($operator, [self::OP_LT, self::OP_LTE], true);
        $percent = $lowerIsBetter ? ($target / $result) * 100 : ($result / $target) * 100;

        return round(max(0, $percent), 1);
    }

    public static function isValidOperator(string $operator): bool
    {
        return array_key_exists($operator, self::SYMBOLS);
    }
}

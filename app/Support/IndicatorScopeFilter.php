<?php

namespace App\Support;

use Illuminate\Contracts\Database\Query\Builder;

/**
 * ตัวช่วยจำกัด query ตามขอบเขต "ระดับ + ปีที่รับผิดชอบ" ของผู้ดูแลรายระดับ
 * ใช้กับตารางที่มีคอลัมน์ level + year (kpi_indicators / kpi_strategies)
 *
 * รับ scopeYears จาก User::indicatorAdminScopeYears() รูปแบบ [scope => [year..]]
 * โดยค่า null ในชุดปี = "ทุกปีของระดับนั้น" (ไม่ใส่เงื่อนไขปี)
 */
class IndicatorScopeFilter
{
    /**
     * เพิ่มเงื่อนไขแบบ OR ของแต่ละ (ระดับ + ปี) ลงใน builder ที่ caller จัดกลุ่ม (where(function...)) ไว้แล้ว
     *
     * @param  array<string, array<int|null>>  $scopeYears
     */
    public static function orWhereScopes(Builder $query, array $scopeYears, string $levelColumn = 'level', string $yearColumn = 'year'): void
    {
        foreach ($scopeYears as $level => $years) {
            $query->orWhere(function ($w) use ($level, $years, $levelColumn, $yearColumn) {
                $w->where($levelColumn, $level);
                // ถ้าไม่มี null (ทุกปี) ในชุด → จำกัดเฉพาะปีที่รับผิดชอบ
                if (! in_array(null, $years, true)) {
                    $w->whereIn($yearColumn, $years);
                }
            });
        }
    }
}

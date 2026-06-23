<?php

namespace App\Repositories\Contracts;

use App\Models\KpiIndicator;
use Illuminate\Support\Collection;

interface DashboardRepositoryInterface
{
    /** สถานะรวมของตัวชี้วัด: pass|fail|pending */
    public function overallStatus(KpiIndicator $indicator): string;

    /** ปีที่มีตัวชี้วัด (สำหรับตัวเลือก) */
    public function availableYears(): Collection;

    /**
     * ตัวชี้วัดพร้อมเป้าหมาย+ผลงาน สำหรับหน้า Monitor
     * @param  array{year?:int,level?:string}  $filters
     */
    public function indicators(array $filters): Collection;

    /**
     * สรุปจำนวนผ่าน/ไม่ผ่าน/รอบันทึก แยกตามระดับ
     * @param  array{year?:int}  $filters
     * @return array<string, array{total:int,pass:int,fail:int,pending:int}>
     */
    public function summaryByLevel(array $filters): array;

    /**
     * แจกแจงผ่าน/ไม่ผ่าน/รอบันทึก ของตัวชี้วัด แยกตามยุทธศาสตร์และกลยุทธ์ ในแต่ละระดับ
     * @param  array{year?:int,level?:string}  $filters
     * @return array<string, array{strategies:array<int,array{name:string,code:?string,total:int,pass:int,fail:int,pending:int}>, subStrategies:array<int,array{name:string,code:?string,strategy:?string,total:int,pass:int,fail:int,pending:int}>}>
     */
    public function breakdownByLevel(array $filters): array;

    /**
     * แนวโน้มอัตราผ่านรวม (% ของตัวชี้วัดที่ผ่าน) ย้อนหลังรายปี เรียงปีจากน้อยไปมาก
     * ใช้วาดกราฟเส้นพื้นหลังของแดชบอร์ด
     * @return array<int, array{year:int,pct:int,total:int,pass:int}>
     */
    public function passRateTrend(?string $level = null): array;
}

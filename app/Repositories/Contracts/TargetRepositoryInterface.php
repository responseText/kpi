<?php

namespace App\Repositories\Contracts;

use App\Models\KpiIndicator;

interface TargetRepositoryInterface extends RepositoryInterface
{
    /**
     * สร้าง/ปรับช่วงเวลา (kpi_targets) ให้ตรงกับ period_type ของตัวชี้วัด
     * - คงค่าเป้าหมายเดิมไว้ถ้ามี, ลบช่วงที่ไม่ใช้แล้ว, คำนวณ start/end ใหม่ตาม year_type/year
     */
    public function syncPeriods(KpiIndicator $indicator): void;

    /**
     * บันทึกค่าเป้าหมาย/operator ของแต่ละช่วง
     * @param  array<int, array{operator:string,target_value:?float,target_text:?string}>  $rows  key = period_no
     */
    public function saveTargets(KpiIndicator $indicator, array $rows): void;
}

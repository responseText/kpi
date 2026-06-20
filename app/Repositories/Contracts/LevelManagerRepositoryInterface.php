<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface LevelManagerRepositoryInterface extends RepositoryInterface
{
    /**
     * ทั้งหมด จัดกลุ่มตามระดับ พร้อมข้อมูล user
     * ถ้าระบุปี จะแสดงเฉพาะผู้รับผิดชอบของปีนั้น รวมรายการที่ไม่ระบุปี (ทุกปี) ด้วย
     */
    public function allWithUser(?int $year = null): Collection;

    /** ปี พ.ศ. ที่มีอยู่ในรายการผู้รับผิดชอบระดับ (ไม่รวมรายการที่ไม่ระบุปี) */
    public function availableYears(): Collection;
}

<?php

namespace App\Repositories\Contracts;

use App\Models\KpiResult;
use App\Models\KpiTarget;

interface ResultRepositoryInterface extends RepositoryInterface
{
    /**
     * บันทึก/แก้ไขผลงานของช่วงเวลาหนึ่ง พร้อมประเมินผ่าน/ไม่ผ่านอัตโนมัติ
     *
     * @param  array{result_value:?float,result_text:?string,note:?string}  $data
     */
    public function record(KpiTarget $target, array $data, int $recordedBy): KpiResult;
}

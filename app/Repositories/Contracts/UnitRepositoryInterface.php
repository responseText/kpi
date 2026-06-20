<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UnitRepositoryInterface extends RepositoryInterface
{
    /** หน่วยวัด (กรองตามกลุ่ม + แบ่งหน้า) เรียงตามกลุ่มและลำดับ */
    public function paginateByGroup(?string $groupCode, int $perPage = 50): LengthAwarePaginator;
}

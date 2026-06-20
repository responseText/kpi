<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface StrategyRepositoryInterface extends RepositoryInterface
{
    public function paginateByYear(?int $year, ?string $level = null, int $perPage = 20): LengthAwarePaginator;

    /** รายปีที่มีในระบบ (สำหรับ dropdown) */
    public function availableYears(): Collection;

    /** ยุทธศาสตร์ที่เปิดใช้งานของปีหนึ่ง ๆ (สำหรับ dropdown) */
    public function enabledForYear(int $year): Collection;
}

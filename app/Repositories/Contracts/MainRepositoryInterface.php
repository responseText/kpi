<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MainRepositoryInterface extends RepositoryInterface
{
    /** KPI หลัก พร้อมตัวกรอง — สโคปตามระดับ+ปีที่ผู้ใช้รับผิดชอบ (ผ่านหมวด→กลยุทธ์→ยุทธศาสตร์) */
    public function paginateFiltered(?int $year, ?int $strategyId, ?int $subStrategyId, ?int $categoryId, ?string $level = null, ?string $name = null, ?User $user = null, int $perPage = 20): LengthAwarePaginator;
}

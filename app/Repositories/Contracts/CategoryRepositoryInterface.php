<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CategoryRepositoryInterface extends RepositoryInterface
{
    /** หมวด KPI พร้อมตัวกรอง — ผู้ดูแลรายระดับเห็นเฉพาะระดับ+ปีที่รับผิดชอบ (ผ่านกลยุทธ์→ยุทธศาสตร์) */
    public function paginateFiltered(?int $subStrategyId, ?User $user = null, int $perPage = 20): LengthAwarePaginator;
}

<?php

namespace App\Repositories\Contracts;

use App\Models\KpiSubStrategy;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SubStrategyRepositoryInterface extends RepositoryInterface
{
    public function paginateFiltered(?int $year, ?int $strategyId, ?string $level = null, ?User $user = null, int $perPage = 20): LengthAwarePaginator;

    /** กลยุทธ์ที่เปิดใช้งาน (สำหรับ dropdown) พร้อมยุทธศาสตร์ */
    public function enabledForYear(int $year): Collection;

    /** ตั้งค่าผู้ตรวจสอบ (sync) */
    public function syncReviewers(KpiSubStrategy $subStrategy, array $userIds): void;
}

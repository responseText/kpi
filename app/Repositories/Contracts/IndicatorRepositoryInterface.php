<?php

namespace App\Repositories\Contracts;

use App\Models\KpiIndicator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IndicatorRepositoryInterface extends RepositoryInterface
{
    /**
     * @param  array{level?:string,year?:int,year_type?:string,sub_strategy_id?:int,search?:string}  $filters
     */
    public function paginateFiltered(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function loadFull(KpiIndicator $indicator): KpiIndicator;

    /** ตั้งค่าผู้รับผิดชอบ (sync) — รักษา is_primary */
    public function syncOwners(KpiIndicator $indicator, array $userIds, ?int $primaryUserId = null): void;
}

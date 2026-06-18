<?php

namespace App\Repositories\Contracts;

use App\Models\KpiIndicator;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IndicatorRepositoryInterface extends RepositoryInterface
{
    /**
     * @param  array{level?:string,year?:int,year_type?:string,sub_strategy_id?:int,search?:string}  $filters
     */
    public function paginateFiltered(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * เหมือน paginateFiltered แต่จำกัดเฉพาะตัวชี้วัดที่ผู้ใช้มีสิทธิ์บันทึกผล
     * (ผู้ดูแลระบบสูงสุด/ผู้ดูแลตัวชี้วัดทั้งหมด เห็นทุกตัว; ผู้ดูแลระดับเห็นเฉพาะระดับของตน + ตัวที่ตนรับผิดชอบ)
     *
     * @param  array{level?:string,year?:int,search?:string}  $filters
     */
    public function paginateRecordable(array $filters, User $user, int $perPage = 20): LengthAwarePaginator;

    /**
     * เหมือน paginateFiltered แต่จำกัดตามระดับที่ผู้ใช้เป็นผู้ดูแล (สำหรับเมนูกำหนดค่าเป้าหมาย)
     * ผู้ดูแลระบบสูงสุด/ผู้ดูแลตัวชี้วัดทั้งหมด เห็นทุกตัว; ผู้ดูแลระดับเห็นเฉพาะระดับของตน (ไม่นับความเป็นผู้รับผิดชอบ)
     *
     * @param  array{level?:string,year?:int,search?:string}  $filters
     */
    public function paginateManageableLevels(array $filters, User $user, int $perPage = 20): LengthAwarePaginator;

    public function loadFull(KpiIndicator $indicator): KpiIndicator;

    /** ตั้งค่าผู้รับผิดชอบ (sync) — รักษา is_primary */
    public function syncOwners(KpiIndicator $indicator, array $userIds, ?int $primaryUserId = null): void;
}

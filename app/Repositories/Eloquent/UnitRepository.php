<?php

namespace App\Repositories\Eloquent;

use App\Models\KpiUnit;
use App\Repositories\Contracts\UnitRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class UnitRepository extends BaseRepository implements UnitRepositoryInterface
{
    protected function makeModel(): Model
    {
        return new KpiUnit;
    }

    public function paginateByGroup(?string $groupCode, int $perPage = 50): LengthAwarePaginator
    {
        // เรียงตามลำดับกลุ่มใน GROUPS (ผ่าน FIELD()) แล้วตามลำดับการแสดง/ชื่อ
        $groupOrder = implode(',', array_map(
            fn (string $code) => "'".$code."'",
            array_keys(KpiUnit::GROUPS)
        ));

        return $this->query()
            ->when($groupCode, fn ($q) => $q->where('group_code', $groupCode))
            ->orderByRaw("FIELD(group_code, {$groupOrder})")
            ->orderBy('orderby')
            ->orderBy('name')
            ->paginate($perPage);
    }
}

<?php

namespace App\Repositories\Eloquent;

use App\Models\KpiIndicator;
use App\Models\User;
use App\Repositories\Contracts\IndicatorRepositoryInterface;
use App\Support\IndicatorScopeFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class IndicatorRepository extends BaseRepository implements IndicatorRepositoryInterface
{
    protected function makeModel(): Model
    {
        return new KpiIndicator;
    }

    /** คิวรีพื้นฐานพร้อมตัวกรอง (ระดับ/ปี/แบบปี/ยุทธศาสตร์/กลยุทธ์/หมวด/KPIหลัก/ค้นหา) — ใช้ร่วมทุกเมธอด paginate */
    private function baseFilteredQuery(array $filters): Builder
    {
        return $this->query()
            ->with(['main.category', 'owners', 'targets.result'])
            ->when($filters['level'] ?? null, fn ($q, $v) => $q->where('level', $v))
            ->when($filters['year'] ?? null, fn ($q, $v) => $q->where('year', $v))
            ->when($filters['year_type'] ?? null, fn ($q, $v) => $q->where('year_type', $v))
            ->when($filters['kpi_main_id'] ?? null, fn ($q, $v) => $q->where('kpi_main_id', $v))
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->whereHas('main', fn ($m) => $m->where('category_id', $v)))
            ->when($filters['sub_strategy_id'] ?? null, fn ($q, $v) => $q->whereHas('main.category', fn ($c) => $c->where('sub_strategy_id', $v)))
            ->when($filters['strategy_id'] ?? null, fn ($q, $v) => $q->whereHas('main.category.subStrategy', fn ($s) => $s->where('strategy_id', $v)))
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($w) use ($v) {
                    $w->where('name', 'like', "%{$v}%")->orWhere('code', 'like', "%{$v}%");
                });
            });
    }

    public function paginateFiltered(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->baseFilteredQuery($filters)
            ->orderBy('level')
            ->orderBy('orderby')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateRecordable(array $filters, User $user, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->baseFilteredQuery($filters);

        // จำกัดสิทธิ์การมองเห็น: ผู้ที่บันทึกได้ทุกตัว (super admin / ผู้ดูแลตัวชี้วัดทั้งหมด) ไม่ต้องกรอง
        // ที่เหลือเห็นเฉพาะ "ระดับที่ตนเป็นผู้ดูแล" รวมกับ "ตัวชี้วัดที่ตนเป็นผู้รับผิดชอบ"
        if (! $user->canManageAllIndicatorLevels()) {
            $scopeYears = $user->indicatorAdminScopeYears();
            $ownedIds = $user->ownedIndicatorIds();

            $query->where(function ($q) use ($scopeYears, $ownedIds) {
                // เห็น "ตัวชี้วัดที่ตนรับผิดชอบ" รวมกับ "ระดับ+ปีที่ตนเป็นผู้ดูแล"
                $q->whereIn('id', $ownedIds);
                IndicatorScopeFilter::orWhereScopes($q, $scopeYears);
            });
        }

        return $query
            ->orderBy('level')
            ->orderBy('orderby')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateManageableLevels(array $filters, User $user, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->baseFilteredQuery($filters);

        // ผู้ดูแลทุกระดับ (super admin / ผู้ดูแลตัวชี้วัดทั้งหมด) เห็นทุกตัว
        // ผู้ดูแลรายระดับ → เห็นเฉพาะระดับ+ปีที่ตนรับผิดชอบ
        if (! $user->canManageAllIndicatorLevels()) {
            $scopeYears = $user->indicatorAdminScopeYears();
            $query->where(function ($q) use ($scopeYears) {
                $q->whereRaw('1 = 0');
                IndicatorScopeFilter::orWhereScopes($q, $scopeYears);
            });
        }

        return $query
            ->orderBy('level')
            ->orderBy('orderby')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function loadFull(KpiIndicator $indicator): KpiIndicator
    {
        return $indicator->load([
            'main.category',
            'owners',
            'targets.result.recorder',
        ]);
    }

    public function syncOwners(KpiIndicator $indicator, array $userIds, ?int $primaryUserId = null): void
    {
        $userIds = array_values(array_unique(array_filter($userIds)));

        $payload = [];
        foreach ($userIds as $uid) {
            $payload[$uid] = ['is_primary' => ($primaryUserId !== null && (int) $uid === (int) $primaryUserId)];
        }

        $indicator->owners()->sync($payload);
    }
}

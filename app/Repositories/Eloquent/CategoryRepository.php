<?php

namespace App\Repositories\Eloquent;

use App\Models\KpiCategory;
use App\Models\User;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Support\IndicatorScopeFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class CategoryRepository extends BaseRepository implements CategoryRepositoryInterface
{
    protected function makeModel(): Model
    {
        return new KpiCategory;
    }

    public function paginateFiltered(?int $subStrategyId, ?User $user = null, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query()
            ->with(['subStrategy.strategy'])
            ->withCount('mains')
            ->when($subStrategyId, fn ($q) => $q->where('sub_strategy_id', $subStrategyId))
            // หมวด KPI สืบทอดระดับ+ปีจากกลยุทธ์→ยุทธศาสตร์ — ผู้ดูแลรายระดับเห็นเฉพาะของตน
            // (หมวดที่ยังไม่ผูกกลยุทธ์จะเห็นเฉพาะผู้ดูแลทั้งหมด/ระบบสูงสุด)
            ->when($user && ! $user->canManageAllIndicatorLevels(),
                fn ($q) => $q->whereHas('subStrategy.strategy', fn ($s) => $s->where(function ($w) use ($user) {
                    $w->whereRaw('1 = 0');
                    IndicatorScopeFilter::orWhereScopes($w, $user->indicatorAdminScopeYears());
                })))
            ->orderBy('orderby')
            ->orderBy('id')
            ->paginate($perPage);
    }
}

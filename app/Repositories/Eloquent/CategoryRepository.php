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

    public function paginateFiltered(?int $year, ?int $strategyId, ?int $subStrategyId, ?string $level = null, ?string $name = null, ?User $user = null, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query()
            ->with(['subStrategy.strategy'])
            ->withCount('mains')
            ->when($subStrategyId, fn ($q) => $q->where('sub_strategy_id', $subStrategyId))
            ->when($strategyId, fn ($q) => $q->whereHas('subStrategy', fn ($s) => $s->where('strategy_id', $strategyId)))
            ->when($year,  fn ($q) => $q->whereHas('subStrategy.strategy', fn ($s) => $s->where('year', $year)))
            ->when($level, fn ($q) => $q->whereHas('subStrategy.strategy', fn ($s) => $s->where('level', $level)))
            // ค้นหาด้วยชื่อหมวด KPI (รวมรหัสหมวดด้วยเพื่อความสะดวก)
            ->when($name, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$name}%")
                ->orWhere('code', 'like', "%{$name}%")))
            // หมวด KPI สืบทอดระดับ+ปีจากกลยุทธ์→ยุทธศาสตร์ — ผู้ดูแลรายระดับเห็นเฉพาะของตน
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

<?php

namespace App\Repositories\Eloquent;

use App\Models\KpiMain;
use App\Models\User;
use App\Repositories\Contracts\MainRepositoryInterface;
use App\Support\IndicatorScopeFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class MainRepository extends BaseRepository implements MainRepositoryInterface
{
    protected function makeModel(): Model
    {
        return new KpiMain;
    }

    public function paginateFiltered(?int $year, ?int $strategyId, ?int $subStrategyId, ?int $categoryId, ?string $level = null, ?string $name = null, ?User $user = null, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query()
            ->with(['category.subStrategy.strategy'])
            ->withCount('indicators')
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($subStrategyId, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('sub_strategy_id', $subStrategyId)))
            ->when($strategyId, fn ($q) => $q->whereHas('category.subStrategy', fn ($s) => $s->where('strategy_id', $strategyId)))
            ->when($year,  fn ($q) => $q->whereHas('category.subStrategy.strategy', fn ($s) => $s->where('year', $year)))
            ->when($level, fn ($q) => $q->whereHas('category.subStrategy.strategy', fn ($s) => $s->where('level', $level)))
            // ค้นหาด้วยรหัส หรือ ชื่อ KPI หลัก
            ->when($name, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$name}%")
                ->orWhere('code', 'like', "%{$name}%")))
            ->when($user && ! $user->canManageAllIndicatorLevels(),
                fn ($q) => $q->whereHas('category.subStrategy.strategy', fn ($s) => $s->where(function ($w) use ($user) {
                    $w->whereRaw('1 = 0');
                    IndicatorScopeFilter::orWhereScopes($w, $user->indicatorAdminScopeYears());
                })))
            ->orderBy('orderby')
            ->orderBy('id')
            ->paginate($perPage);
    }
}

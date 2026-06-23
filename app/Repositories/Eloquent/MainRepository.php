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

    public function paginateFiltered(?int $categoryId, ?User $user = null, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query()
            ->with(['category.subStrategy.strategy'])
            ->withCount('indicators')
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
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

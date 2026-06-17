<?php

namespace App\Repositories\Eloquent;

use App\Models\KpiSubStrategy;
use App\Repositories\Contracts\SubStrategyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SubStrategyRepository extends BaseRepository implements SubStrategyRepositoryInterface
{
    protected function makeModel(): Model
    {
        return new KpiSubStrategy();
    }

    public function paginateFiltered(?int $year, ?int $strategyId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query()
            ->with(['strategy', 'reviewers'])
            ->withCount('indicators')
            ->when($strategyId, fn ($q) => $q->where('strategy_id', $strategyId))
            ->when($year, fn ($q) => $q->whereHas('strategy', fn ($s) => $s->where('year', $year)))
            ->orderBy('orderby')
            ->paginate($perPage);
    }

    public function enabledForYear(int $year): Collection
    {
        return $this->query()
            ->enabled()
            ->with('strategy')
            ->whereHas('strategy', fn ($s) => $s->where('year', $year))
            ->orderBy('orderby')
            ->get();
    }

    public function syncReviewers(KpiSubStrategy $subStrategy, array $userIds): void
    {
        $userIds = array_values(array_unique(array_filter($userIds)));
        $subStrategy->reviewers()->sync($userIds);
    }
}

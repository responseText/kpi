<?php

namespace App\Repositories\Eloquent;

use App\Models\KpiStrategy;
use App\Repositories\Contracts\StrategyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class StrategyRepository extends BaseRepository implements StrategyRepositoryInterface
{
    protected function makeModel(): Model
    {
        return new KpiStrategy();
    }

    public function paginateByYear(?int $year, ?string $level = null, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query()
            ->withCount('subStrategies')
            ->when($year, fn ($q) => $q->where('year', $year))
            ->when($level, fn ($q) => $q->where('level', $level))
            ->orderByDesc('year')
            ->orderBy('level')
            ->orderBy('orderby')
            ->paginate($perPage);
    }

    public function availableYears(): Collection
    {
        return $this->query()
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');
    }

    public function enabledForYear(int $year): Collection
    {
        return $this->query()
            ->enabled()
            ->where('year', $year)
            ->orderBy('orderby')
            ->get();
    }
}

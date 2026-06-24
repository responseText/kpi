<?php

namespace App\Repositories\Eloquent;

use App\Models\KpiSubStrategy;
use App\Models\User;
use App\Repositories\Contracts\SubStrategyRepositoryInterface;
use App\Support\IndicatorScopeFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SubStrategyRepository extends BaseRepository implements SubStrategyRepositoryInterface
{
    protected function makeModel(): Model
    {
        return new KpiSubStrategy;
    }

    public function paginateFiltered(?int $year, ?int $strategyId, ?string $level = null, ?User $user = null, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query()
            ->with(['strategy', 'reviewers'])
            ->withCount('indicators')
            ->when($strategyId, fn ($q) => $q->where('strategy_id', $strategyId))
            ->when($year, fn ($q) => $q->whereHas('strategy', fn ($s) => $s->where('year', $year)))
            ->when($level, fn ($q) => $q->whereHas('strategy', fn ($s) => $s->where('level', $level)))
            // กลยุทธ์สืบทอดระดับ+ปีจากยุทธศาสตร์แม่ — ผู้ดูแลรายระดับเห็นเฉพาะระดับ+ปีที่ตนรับผิดชอบ
            ->when($user && ! $user->canManageAllIndicatorLevels(),
                fn ($q) => $q->whereHas('strategy', fn ($s) => $s->where(function ($w) use ($user) {
                    $w->whereRaw('1 = 0');
                    IndicatorScopeFilter::orWhereScopes($w, $user->indicatorAdminScopeYears());
                })))
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

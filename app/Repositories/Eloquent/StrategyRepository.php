<?php

namespace App\Repositories\Eloquent;

use App\Models\KpiStrategy;
use App\Models\User;
use App\Repositories\Contracts\StrategyRepositoryInterface;
use App\Support\IndicatorScopeFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class StrategyRepository extends BaseRepository implements StrategyRepositoryInterface
{
    protected function makeModel(): Model
    {
        return new KpiStrategy;
    }

    public function paginateByYear(?int $year, ?string $level = null, ?User $user = null, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query()
            ->withCount('subStrategies')
            ->when($year, fn ($q) => $q->where('year', $year))
            ->when($level, fn ($q) => $q->where('level', $level))
            // ผู้ดูแลรายระดับเห็นเฉพาะระดับ+ปีที่ตนรับผิดชอบ; ผู้ดูแลทั้งหมด/ระบบสูงสุดเห็นทุกระดับทุกปี
            ->when($user && ! $user->canManageAllIndicatorLevels(),
                fn ($q) => $q->where(function ($w) use ($user) {
                    $w->whereRaw('1 = 0');
                    IndicatorScopeFilter::orWhereScopes($w, $user->indicatorAdminScopeYears());
                }))
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

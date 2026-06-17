<?php

namespace App\Repositories\Eloquent;

use App\Models\KpiIndicator;
use App\Repositories\Contracts\IndicatorRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class IndicatorRepository extends BaseRepository implements IndicatorRepositoryInterface
{
    protected function makeModel(): Model
    {
        return new KpiIndicator();
    }

    public function paginateFiltered(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query()
            ->with(['subStrategy.strategy', 'owners', 'targets.result'])
            ->when($filters['level'] ?? null, fn ($q, $v) => $q->where('level', $v))
            ->when($filters['year'] ?? null, fn ($q, $v) => $q->where('year', $v))
            ->when($filters['year_type'] ?? null, fn ($q, $v) => $q->where('year_type', $v))
            ->when($filters['sub_strategy_id'] ?? null, fn ($q, $v) => $q->where('sub_strategy_id', $v))
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($w) use ($v) {
                    $w->where('name', 'like', "%{$v}%")->orWhere('code', 'like', "%{$v}%");
                });
            })
            ->orderBy('level')
            ->orderBy('orderby')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function loadFull(KpiIndicator $indicator): KpiIndicator
    {
        return $indicator->load([
            'subStrategy.strategy',
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

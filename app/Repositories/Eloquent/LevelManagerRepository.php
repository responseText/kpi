<?php

namespace App\Repositories\Eloquent;

use App\Models\KpiLevelManager;
use App\Repositories\Contracts\LevelManagerRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class LevelManagerRepository extends BaseRepository implements LevelManagerRepositoryInterface
{
    protected function makeModel(): Model
    {
        return new KpiLevelManager;
    }

    public function allWithUser(?int $year = null): Collection
    {
        return $this->query()
            ->with('user.employee')
            // ปีที่ระบุ + รายการ "ทุกปี" (year = null) ซึ่งใช้ได้กับทุกปี
            ->when($year !== null, fn ($q) => $q->where(
                fn ($w) => $w->where('year', $year)->orWhereNull('year')
            ))
            ->orderBy('level')
            ->orderBy('role')
            ->get();
    }

    public function availableYears(): Collection
    {
        return $this->query()
            ->whereNotNull('year')
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');
    }
}

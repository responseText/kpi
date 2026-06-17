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
        return new KpiLevelManager();
    }

    public function allWithUser(): Collection
    {
        return $this->query()
            ->with('user.employee')
            ->orderBy('level')
            ->orderBy('role')
            ->get();
    }
}

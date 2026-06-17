<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface LevelManagerRepositoryInterface extends RepositoryInterface
{
    /** ทั้งหมด จัดกลุ่มตามระดับ พร้อมข้อมูล user */
    public function allWithUser(): Collection;
}

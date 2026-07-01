<?php

namespace App\Services\Import;

use App\Services\Import\Contracts\ImportType;
use App\Services\Import\Types\CategoryImport;
use App\Services\Import\Types\IndicatorImport;
use App\Services\Import\Types\MainImport;
use App\Services\Import\Types\StrategyImport;
use App\Services\Import\Types\SubStrategyImport;
use App\Services\Import\Types\TargetImport;

/**
 * ทะเบียนประเภทข้อมูลที่นำเข้าได้ — ลำดับใน $map คือลำดับการแสดง/การนำเข้า (1→6)
 */
class ImportRegistry
{
    /** @var array<string,class-string<ImportType>> */
    private array $map = [
        'strategies'     => StrategyImport::class,
        'sub-strategies' => SubStrategyImport::class,
        'categories'     => CategoryImport::class,
        'mains'          => MainImport::class,
        'indicators'     => IndicatorImport::class,
        'targets'        => TargetImport::class,
    ];

    public function has(string $key): bool
    {
        return isset($this->map[$key]);
    }

    public function get(string $key): ImportType
    {
        return app($this->map[$key]);
    }

    /**
     * ประเภททั้งหมดเรียงตามลำดับการนำเข้า
     *
     * @return array<int,ImportType>
     */
    public function all(): array
    {
        return array_map(fn (string $class) => app($class), array_values($this->map));
    }
}

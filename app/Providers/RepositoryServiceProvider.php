<?php

namespace App\Providers;

use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Repositories\Contracts\IndicatorRepositoryInterface;
use App\Repositories\Contracts\LevelManagerRepositoryInterface;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Repositories\Contracts\ResultRepositoryInterface;
use App\Repositories\Contracts\StrategyRepositoryInterface;
use App\Repositories\Contracts\SubStrategyRepositoryInterface;
use App\Repositories\Contracts\TargetRepositoryInterface;
use App\Repositories\Contracts\UnitRepositoryInterface;
use App\Repositories\Eloquent\DashboardRepository;
use App\Repositories\Eloquent\IndicatorRepository;
use App\Repositories\Eloquent\LevelManagerRepository;
use App\Repositories\Eloquent\PermissionRepository;
use App\Repositories\Eloquent\ResultRepository;
use App\Repositories\Eloquent\StrategyRepository;
use App\Repositories\Eloquent\SubStrategyRepository;
use App\Repositories\Eloquent\TargetRepository;
use App\Repositories\Eloquent\UnitRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        StrategyRepositoryInterface::class => StrategyRepository::class,
        SubStrategyRepositoryInterface::class => SubStrategyRepository::class,
        IndicatorRepositoryInterface::class => IndicatorRepository::class,
        TargetRepositoryInterface::class => TargetRepository::class,
        ResultRepositoryInterface::class => ResultRepository::class,
        LevelManagerRepositoryInterface::class => LevelManagerRepository::class,
        PermissionRepositoryInterface::class => PermissionRepository::class,
        DashboardRepositoryInterface::class => DashboardRepository::class,
        UnitRepositoryInterface::class => UnitRepository::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}

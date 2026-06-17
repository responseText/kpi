<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ยุทธศาสตร์ (รายปี พ.ศ.)
 */
class KpiStrategy extends Model
{
    use SoftDeletes;

    protected $table = 'kpi_strategies';

    protected $fillable = [
        'year', 'code', 'name', 'description', 'orderby', 'status',
    ];

    protected $casts = [
        'year' => 'integer',
    ];

    public function subStrategies(): HasMany
    {
        return $this->hasMany(KpiSubStrategy::class, 'strategy_id')->orderBy('orderby');
    }

    public function scopeEnabled($query)
    {
        return $query->where('status', 'enable');
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }
}

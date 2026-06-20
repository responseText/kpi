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

    /** ระดับตัวชี้วัดของยุทธศาสตร์ — ใช้ชุดเดียวกับตัวชี้วัด (hospital/province/ministry) */
    public const LEVELS = KpiIndicator::LEVELS;

    protected $fillable = [
        'year', 'level', 'code', 'name', 'description', 'orderby', 'status',
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

    public function scopeLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    public function getLevelLabelAttribute(): string
    {
        return self::LEVELS[$this->level] ?? $this->level;
    }
}

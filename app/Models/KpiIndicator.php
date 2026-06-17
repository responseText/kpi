<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ตัวชี้วัด (KPI)
 */
class KpiIndicator extends Model
{
    use SoftDeletes;

    protected $table = 'kpi_indicators';

    /** ระดับตัวชี้วัด */
    public const LEVEL_HOSPITAL = 'hospital';
    public const LEVEL_PROVINCE = 'province';
    public const LEVEL_MINISTRY = 'ministry';

    public const LEVELS = [
        self::LEVEL_HOSPITAL => 'โรงพยาบาลทองแสนขัน',
        self::LEVEL_PROVINCE => 'จังหวัด',
        self::LEVEL_MINISTRY => 'กระทรวง',
    ];

    /** แบบปี */
    public const YEAR_BUDDHIST = 'buddhist';
    public const YEAR_FISCAL = 'fiscal';

    public const YEAR_TYPES = [
        self::YEAR_BUDDHIST => 'ปี พ.ศ.',
        self::YEAR_FISCAL => 'ปีงบประมาณ',
    ];

    /** รูปแบบการเก็บผลงาน */
    public const PERIOD_ANNUAL = 'annual';
    public const PERIOD_QUARTERLY = 'quarterly';

    public const PERIOD_TYPES = [
        self::PERIOD_ANNUAL => 'รายปี (12 เดือน)',
        self::PERIOD_QUARTERLY => 'รายไตรมาส (ไตรมาสละ 3 เดือน)',
    ];

    protected $fillable = [
        'sub_strategy_id', 'level', 'code', 'name', 'year_type', 'year',
        'period_type', 'unit', 'description', 'orderby', 'status',
    ];

    protected $casts = [
        'year' => 'integer',
    ];

    public function subStrategy(): BelongsTo
    {
        return $this->belongsTo(KpiSubStrategy::class, 'sub_strategy_id');
    }

    /** ยุทธศาสตร์ (ผ่านกลยุทธ์) */
    public function strategy(): HasManyThrough
    {
        return $this->hasManyThrough(
            KpiStrategy::class,
            KpiSubStrategy::class,
            'id',            // kpi_sub_strategies.id
            'id',            // kpi_strategies.id
            'sub_strategy_id', // kpi_indicators.sub_strategy_id
            'strategy_id'    // kpi_sub_strategies.strategy_id
        );
    }

    /** ผู้รับผิดชอบตัวชี้วัด (อ้างอิง users) */
    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'kpi_indicator_owners', 'indicator_id', 'user_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function targets(): HasMany
    {
        return $this->hasMany(KpiTarget::class, 'indicator_id')->orderBy('period_no');
    }

    public function results(): HasMany
    {
        return $this->hasMany(KpiResult::class, 'indicator_id');
    }

    public function scopeEnabled($query)
    {
        return $query->where('status', 'enable');
    }

    public function scopeLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    public function getLevelLabelAttribute(): string
    {
        return self::LEVELS[$this->level] ?? $this->level;
    }

    public function getYearTypeLabelAttribute(): string
    {
        return self::YEAR_TYPES[$this->year_type] ?? $this->year_type;
    }

    public function getPeriodTypeLabelAttribute(): string
    {
        return self::PERIOD_TYPES[$this->period_type] ?? $this->period_type;
    }
}

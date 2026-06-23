<?php

namespace App\Models;

use App\Support\MeasurementType;
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
        'kpi_main_id', 'sub_strategy_id', 'level', 'code', 'name', 'year_type', 'year',
        'period_type', 'unit', 'measurement_type', 'numerator_label',
        'denominator_label', 'formula', 'factor', 'description', 'orderby', 'status',
    ];

    protected $casts = [
        'year' => 'integer',
        'factor' => 'decimal:4',
    ];

    /** KPI หลัก ที่ตัวชี้วัดนี้สังกัด (โครงสร้างใหม่: หมวด KPI → KPI หลัก → ตัวชี้วัด) */
    public function main(): BelongsTo
    {
        return $this->belongsTo(KpiMain::class, 'kpi_main_id');
    }

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

    /** จำนวนช่วงที่กำหนดค่าเป้าหมายแล้ว (ต้องโหลดความสัมพันธ์ targets มาก่อน) */
    public function definedTargetsCount(): int
    {
        return $this->targets->filter(fn ($t) => $t->isDefined())->count();
    }

    /** ยังไม่ได้กำหนดค่าเป้าหมายเลยสักช่วง → ห้ามบันทึกผลงาน */
    public function hasNoTargetDefined(): bool
    {
        return $this->definedTargetsCount() === 0;
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

    /** เมทาดาทาประเภทการวัด (null ถ้ายังไม่ระบุ) */
    public function getMeasurementMetaAttribute(): ?array
    {
        return MeasurementType::meta($this->measurement_type);
    }

    /** ชื่อประเภทการวัด เช่น "ร้อยละ (Percent)" */
    public function getMeasurementTypeLabelAttribute(): ?string
    {
        return MeasurementType::label($this->measurement_type);
    }

    /** ชื่อกลุ่ม KPI ของประเภทการวัด เช่น "เชิงประสิทธิภาพ (Efficiency)" */
    public function getMeasurementGroupLabelAttribute(): ?string
    {
        return MeasurementType::groupLabel($this->measurement_type);
    }

    /**
     * สูตรสำหรับแสดงผล:
     *  - ประเภทที่กรอกสูตร/เกณฑ์เอง (LEVEL/RANKING/INDEX) → ใช้ค่าที่กรอก
     *  - RATE → แทนค่า K ลงในสูตร (A/B)×K
     *  - ที่เหลือ → สูตรมาตรฐานของประเภท
     */
    public function getFormulaDisplayAttribute(): ?string
    {
        $meta = $this->measurement_meta;
        if ($meta === null) {
            return null;
        }

        if ($meta['requires_formula']) {
            return $this->formula ?: $meta['formula'];
        }

        if ($meta['requires_factor'] && $this->factor !== null) {
            $k = rtrim(rtrim(number_format((float) $this->factor, 4, '.', ','), '0'), '.');

            return "(A/B)×{$k}";
        }

        return $meta['formula'];
    }

    /** ตัวชี้วัดนี้บันทึกผลด้วยค่า ตัวตั้ง (A)/ตัวหาร (B) แล้วคำนวณผลอัตโนมัติหรือไม่ (percent/rate/average/ratio) */
    public function usesNumeratorDenominator(): bool
    {
        $meta = $this->measurement_meta;

        return $meta !== null && $meta['requires_a'] && $meta['requires_b'];
    }

    /**
     * คำนวณค่าผลงานจาก ตัวตั้ง (A) และ ตัวหาร (B) ตามสูตรของประเภทการวัด
     * คืน null ถ้าไม่ใช่ประเภทที่คำนวณจาก A/B หรือข้อมูลไม่พอ (B ว่าง/เป็น 0)
     */
    public function computeResultValue($numerator, $denominator): ?float
    {
        if (! $this->usesNumeratorDenominator()) {
            return null;
        }

        if ($numerator === null || $numerator === '' || $denominator === null || $denominator === '') {
            return null;
        }

        $a = (float) $numerator;
        $b = (float) $denominator;
        if ($b == 0.0) {
            return null;
        }

        return match ($this->measurement_type) {
            MeasurementType::PERCENT => round($a / $b * 100, 4),
            MeasurementType::RATE => round($a / $b * (float) ($this->factor ?? 0), 4),
            MeasurementType::AVERAGE, MeasurementType::RATIO => round($a / $b, 4),
            default => null,
        };
    }
}

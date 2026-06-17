<?php

namespace App\Models;

use App\Services\KpiEvaluator;
use App\Services\PeriodCalculator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * ค่าเป้าหมายรายช่วงเวลาของตัวชี้วัด
 */
class KpiTarget extends Model
{
    protected $table = 'kpi_targets';

    protected $fillable = [
        'indicator_id', 'period_no', 'period_label', 'start_date', 'end_date',
        'operator', 'target_value', 'target_text',
    ];

    protected $casts = [
        'period_no' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'target_value' => 'decimal:2',
    ];

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(KpiIndicator::class, 'indicator_id');
    }

    public function result(): HasOne
    {
        return $this->hasOne(KpiResult::class, 'target_id');
    }

    public function getOperatorSymbolAttribute(): string
    {
        return KpiEvaluator::SYMBOLS[$this->operator] ?? $this->operator;
    }

    public function getThaiRangeAttribute(): string
    {
        if (! $this->start_date || ! $this->end_date) {
            return '';
        }

        return PeriodCalculator::thaiRange($this->start_date, $this->end_date);
    }
}

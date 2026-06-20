<?php

namespace App\Models;

use App\Services\KpiEvaluator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ผลงานที่ทำได้ตามช่วงเวลา
 */
class KpiResult extends Model
{
    use SoftDeletes;

    protected $table = 'kpi_results';

    public const STATUS_LABELS = [
        KpiEvaluator::STATUS_PASS => 'ผ่าน',
        KpiEvaluator::STATUS_FAIL => 'ไม่ผ่าน',
        KpiEvaluator::STATUS_PENDING => 'รอบันทึก',
    ];

    protected $fillable = [
        'target_id', 'indicator_id', 'result_value', 'numerator_value',
        'denominator_value', 'result_text', 'pass_status', 'note',
        'recorded_by', 'recorded_at',
    ];

    protected $casts = [
        'result_value' => 'decimal:2',
        'numerator_value' => 'decimal:4',
        'denominator_value' => 'decimal:4',
        'recorded_at' => 'datetime',
    ];

    public function target(): BelongsTo
    {
        return $this->belongsTo(KpiTarget::class, 'target_id');
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(KpiIndicator::class, 'indicator_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getPassStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->pass_status] ?? $this->pass_status;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * กลยุทธ์ (อยู่ภายใต้ยุทธศาสตร์เดียว) — มีผู้ตรวจสอบอย่างน้อย 1 คน
 */
class KpiSubStrategy extends Model
{
    use SoftDeletes;

    protected $table = 'kpi_sub_strategies';

    protected $fillable = [
        'strategy_id', 'code', 'name', 'description', 'orderby', 'status',
    ];

    public function strategy(): BelongsTo
    {
        return $this->belongsTo(KpiStrategy::class, 'strategy_id');
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(KpiIndicator::class, 'sub_strategy_id')->orderBy('orderby');
    }

    /** ผู้ตรวจสอบกลยุทธ์ (อ้างอิง users) */
    public function reviewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'kpi_sub_strategy_reviewers', 'sub_strategy_id', 'user_id')
            ->withTimestamps();
    }

    public function scopeEnabled($query)
    {
        return $query->where('status', 'enable');
    }
}

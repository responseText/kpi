<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * KPI หลัก — อยู่ภายใต้หมวด KPI เดียว; ตัวชี้วัดอยู่ภายใต้ KPI หลัก
 */
class KpiMain extends Model
{
    use SoftDeletes;

    protected $table = 'kpi_mains';

    protected $fillable = [
        'category_id', 'code', 'name', 'description', 'orderby', 'status',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(KpiCategory::class, 'category_id');
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(KpiIndicator::class, 'kpi_main_id')->orderBy('orderby');
    }

    public function scopeEnabled($query)
    {
        return $query->where('status', 'enable');
    }
}

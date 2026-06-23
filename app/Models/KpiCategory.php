<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * หมวด KPI — อยู่ภายใต้กลยุทธ์ (ไม่บังคับ)
 * โครงสร้าง: ยุทธศาสตร์ → กลยุทธ์ → หมวด KPI → KPI หลัก → ตัวชี้วัด
 */
class KpiCategory extends Model
{
    use SoftDeletes;

    protected $table = 'kpi_categories';

    protected $fillable = [
        'sub_strategy_id', 'code', 'name', 'description', 'orderby', 'status',
    ];

    public function subStrategy(): BelongsTo
    {
        return $this->belongsTo(KpiSubStrategy::class, 'sub_strategy_id');
    }

    public function mains(): HasMany
    {
        return $this->hasMany(KpiMain::class, 'category_id')->orderBy('orderby');
    }

    /** ตัวชี้วัดทั้งหมดในหมวดนี้ (ผ่าน KPI หลัก) */
    public function indicators(): HasManyThrough
    {
        return $this->hasManyThrough(
            KpiIndicator::class,
            KpiMain::class,
            'category_id',  // kpi_mains.category_id
            'kpi_main_id',  // kpi_indicators.kpi_main_id
            'id',           // kpi_categories.id
            'id'            // kpi_mains.id
        );
    }

    public function scopeEnabled($query)
    {
        return $query->where('status', 'enable');
    }
}

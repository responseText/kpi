<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * หน่วยวัดของตัวชี้วัด (master) จัดกลุ่มตามหลักการบริหารผลงาน (Performance Measurement)
 * กลุ่ม KPI 5 ประเภทเป็นค่าคงที่ในระบบ — ผู้ดูแลระบบสูงสุดจัดการเฉพาะ "หน่วยวัด" ในแต่ละกลุ่ม
 */
class KpiUnit extends Model
{
    use SoftDeletes;

    protected $table = 'kpi_units';

    /** กลุ่ม KPI (group_code) */
    public const GROUP_QUANTITY = 'quantity';       // เชิงปริมาณ

    public const GROUP_QUALITY = 'quality';         // เชิงคุณภาพ

    public const GROUP_TIME = 'time';               // เชิงเวลา

    public const GROUP_COST = 'cost';               // เชิงต้นทุน

    public const GROUP_EFFICIENCY = 'efficiency';   // เชิงประสิทธิภาพ

    /** กลุ่ม KPI ทั้งหมด (รหัส => ชื่อแสดงผล) — เรียงตามลำดับหลักการ */
    public const GROUPS = [
        self::GROUP_QUANTITY => 'เชิงปริมาณ (Quantity)',
        self::GROUP_QUALITY => 'เชิงคุณภาพ (Quality)',
        self::GROUP_TIME => 'เชิงเวลา (Time)',
        self::GROUP_COST => 'เชิงต้นทุน (Cost)',
        self::GROUP_EFFICIENCY => 'เชิงประสิทธิภาพ (Efficiency)',
    ];

    protected $fillable = [
        'group_code', 'name', 'description', 'orderby', 'status',
    ];

    protected $casts = [
        'orderby' => 'integer',
    ];

    public function scopeEnabled($query)
    {
        return $query->where('status', 'enable');
    }

    public function scopeGroup($query, string $groupCode)
    {
        return $query->where('group_code', $groupCode);
    }

    public function getGroupLabelAttribute(): string
    {
        return self::GROUPS[$this->group_code] ?? $this->group_code;
    }

    /**
     * หน่วยวัดที่เปิดใช้งาน จัดกลุ่มตาม group_code (เรียงตามลำดับใน GROUPS)
     * ใช้สร้าง <optgroup> ในฟอร์มตัวชี้วัด
     *
     * @return Collection<string, Collection<int, KpiUnit>>
     */
    public static function groupedEnabled(): Collection
    {
        $byGroup = static::query()
            ->enabled()
            ->orderBy('orderby')
            ->orderBy('name')
            ->get()
            ->groupBy('group_code');

        return collect(array_keys(self::GROUPS))
            ->mapWithKeys(fn (string $code) => [$code => $byGroup->get($code, collect())])
            ->filter(fn (Collection $units) => $units->isNotEmpty());
    }
}

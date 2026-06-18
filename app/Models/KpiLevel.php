<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ระดับสิทธิ์การใช้งานของระบบ KPI (บทบาทผู้ใช้)
 */
class KpiLevel extends Model
{
    protected $table = 'kpi_level';

    /** รหัสบทบาท */
    public const SUPER_ADMIN = 'super_admin';            // ผู้ดูแลระบบสูงสุด
    public const ADMIN_ALL = 'indicator_admin_all';      // ผู้ดูแลตัวชี้วัดทั้งหมด (ทุกระดับ)
    public const ADMIN_HOSPITAL = 'indicator_admin_hospital';
    public const ADMIN_PROVINCE = 'indicator_admin_province';
    public const ADMIN_MINISTRY = 'indicator_admin_ministry';
    public const OWNER = 'indicator_owner';              // ผู้รับผิดชอบตัวชี้วัด

    /** ขอบเขต (scope) */
    public const SCOPE_ALL = 'all';

    /** บทบาทกลุ่มผู้ดูแลตัวชี้วัด */
    public const INDICATOR_ADMINS = [
        self::ADMIN_ALL,
        self::ADMIN_HOSPITAL,
        self::ADMIN_PROVINCE,
        self::ADMIN_MINISTRY,
    ];

    protected $fillable = [
        'code', 'name', 'scope', 'description', 'orderby', 'status',
    ];

    protected $casts = [
        'orderby' => 'integer',
    ];

    /** การมอบหมายบทบาทนี้ให้ผู้ใช้ (ผ่าน users_on_level) */
    public function assignments(): HasMany
    {
        return $this->hasMany(UserOnLevel::class, 'level_id');
    }

    public function scopeEnabled($query)
    {
        return $query->where('status', 'enable');
    }

    /** เป็นบทบาทผู้ดูแลระบบสูงสุดหรือไม่ */
    public function isSuperAdmin(): bool
    {
        return $this->code === self::SUPER_ADMIN;
    }
}

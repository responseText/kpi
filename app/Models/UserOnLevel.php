<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * บทบาท/ระดับสิทธิ์ของผู้ใช้ แยกตามระบบ (รองรับหลายระบบบนฐานข้อมูล coretsk)
 */
class UserOnLevel extends Model
{
    protected $table = 'users_on_level';

    protected $fillable = [
        'user_id', 'alias_system', 'level_id', 'is_super_admin',
    ];

    protected $casts = [
        'level_id' => 'integer',
        'is_super_admin' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** ระดับสิทธิ์ของระบบ KPI (เมื่อ alias_system='kpi') */
    public function level(): BelongsTo
    {
        return $this->belongsTo(KpiLevel::class, 'level_id');
    }
}

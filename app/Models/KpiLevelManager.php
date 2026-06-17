<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ผู้รับผิดชอบ/ผู้กำหนดตัวชี้วัดในแต่ละระดับ
 */
class KpiLevelManager extends Model
{
    protected $table = 'kpi_level_managers';

    public const ROLE_RESPONSIBLE = 'responsible';   // ผู้รับผิดชอบระดับ
    public const ROLE_DEFINER = 'definer';           // ผู้กำหนดตัวชี้วัดระดับ

    public const ROLES = [
        self::ROLE_RESPONSIBLE => 'ผู้รับผิดชอบระดับ',
        self::ROLE_DEFINER => 'ผู้กำหนดตัวชี้วัดระดับ',
    ];

    protected $fillable = [
        'level', 'user_id', 'role', 'year',
    ];

    protected $casts = [
        'year' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getLevelLabelAttribute(): string
    {
        return KpiIndicator::LEVELS[$this->level] ?? $this->level;
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }
}

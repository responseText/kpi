<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ข้อมูลพื้นฐานของเจ้าหน้าที่ (ตาราง employee เดิมในฐานข้อมูล coretsk)
 */
class Employee extends Model
{
    use SoftDeletes;

    protected $table = 'employee';

    protected $guarded = ['id'];

    public function prefix(): BelongsTo
    {
        return $this->belongsTo(Prefix::class, 'prefix_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function subdivision(): BelongsTo
    {
        return $this->belongsTo(Subdivision::class, 'subdivision_id');
    }

    /** ชื่อ-นามสกุล พร้อมคำนำหน้า */
    public function getFullNameAttribute(): string
    {
        $prefix = $this->prefix?->name ?? '';

        return trim($prefix . $this->fname . ' ' . $this->lname);
    }
}

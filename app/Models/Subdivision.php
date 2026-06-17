<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** ฝ่ายงาน/งานย่อย (ตาราง subdivision เดิม) */
class Subdivision extends Model
{
    protected $table = 'subdivision';
    protected $guarded = ['id'];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id');
    }
}

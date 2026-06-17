<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** กลุ่มงาน (ตาราง division เดิม) */
class Division extends Model
{
    protected $table = 'division';
    protected $guarded = ['id'];

    public function subdivisions(): HasMany
    {
        return $this->hasMany(Subdivision::class, 'division_id');
    }
}

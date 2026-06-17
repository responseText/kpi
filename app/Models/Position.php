<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** ตำแหน่ง (ตาราง positions เดิม) */
class Position extends Model
{
    protected $table = 'positions';
    protected $guarded = ['id'];
}

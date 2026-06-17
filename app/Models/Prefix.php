<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** คำนำหน้าชื่อ (ตาราง prefix เดิม) */
class Prefix extends Model
{
    protected $table = 'prefix';
    protected $guarded = ['id'];
}

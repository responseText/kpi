<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * สิทธิ์ของผู้ใช้ต่อเมนู (action flags)
 */
class UserOnMenu extends Model
{
    protected $table = 'users_on_menu';

    protected $fillable = [
        'user_id', 'menu_id', 'can_view', 'can_create', 'can_edit', 'can_delete',
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_create' => 'boolean',
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

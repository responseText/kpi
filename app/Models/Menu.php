<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * เมนู/สิทธิ์การใช้งานกลาง (ใช้ร่วมหลายระบบ) — ระบบ KPI ใช้ system='kpi'
 */
class Menu extends Model
{
    protected $table = 'menus';

    protected $fillable = [
        'system', 'code', 'name', 'parent_id', 'route', 'icon', 'orderby', 'status',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('orderby');
    }

    public function userPermissions(): HasMany
    {
        return $this->hasMany(UserOnMenu::class, 'menu_id');
    }

    public function scopeSystem($query, string $system = 'kpi')
    {
        return $query->where('system', $system);
    }

    public function scopeEnabled($query)
    {
        return $query->where('status', 'enable');
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'employee_id',
        'division_id',
        'subdivision_id',
        'password',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** แคชสิทธิ์ที่โหลดแล้วในรอบ request เดียว */
    protected ?array $permissionCache = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** ข้อมูลเจ้าหน้าที่ (employee.id อ้างผ่าน users.employee_id เป็น varchar) */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /** แถวบทบาทของผู้ใช้ในระบบ KPI (1 ผู้ใช้มีได้หลายบทบาท — เก็บที่ users_on_level แยกตามระบบ) */
    public function kpiLevelRows(): HasMany
    {
        return $this->hasMany(UserOnLevel::class, 'user_id')->where('alias_system', 'kpi');
    }

    /** เป็นผู้ดูแลระบบสูงสุดของระบบ KPI หรือไม่ (แถวใดแถวหนึ่งเป็น super admin) */
    public function getIsSuperAdminAttribute(): bool
    {
        return $this->kpiLevelRows->contains(fn (UserOnLevel $r) => $r->is_super_admin);
    }

    /** ระดับสิทธิ์ KPI ทั้งหมดของผู้ใช้ (KpiLevel) */
    public function kpiLevels(): Collection
    {
        return $this->kpiLevelRows->map->level->filter()->values();
    }

    /** id ของระดับสิทธิ์ KPI ทั้งหมด (ไม่รวมแถว super admin ที่ไม่มี level) */
    public function kpiLevelIds(): array
    {
        return $this->kpiLevelRows->pluck('level_id')->filter()->unique()->values()->all();
    }

    /** สิทธิ์เมนูทั้งหมดของผู้ใช้ */
    public function menuPermissions(): HasMany
    {
        return $this->hasMany(UserOnMenu::class, 'user_id');
    }

    /** ชื่อที่ใช้แสดงผล (ใช้ชื่อเจ้าหน้าที่ก่อน แล้วค่อย username) */
    public function getDisplayNameAttribute(): string
    {
        $emp = $this->employee;
        if ($emp && ($emp->fname || $emp->lname)) {
            return trim($emp->full_name);
        }

        return $this->name;
    }

    /**
     * โหลดสิทธิ์ทั้งหมดของผู้ใช้แบบ key by menu code (แคชไว้)
     *
     * @return array<string, \App\Models\UserOnMenu>
     */
    public function loadedPermissions(): array
    {
        if ($this->permissionCache !== null) {
            return $this->permissionCache;
        }

        $rows = $this->menuPermissions()->with('menu')->get();

        $map = [];
        foreach ($rows as $row) {
            if ($row->menu) {
                $map[$row->menu->code] = $row;
            }
        }

        return $this->permissionCache = $map;
    }

    /** ตรวจสิทธิ์ต่อเมนู+action เช่น can('kpi.indicator', 'edit') */
    public function canMenu(string $menuCode, string $action = 'view'): bool
    {
        // ผู้ดูแลระบบสูงสุดผ่านทุก action ทุกเมนูโดยอัตโนมัติ
        if ($this->is_super_admin) {
            return true;
        }

        $perm = $this->loadedPermissions()[$menuCode] ?? null;
        if (! $perm) {
            return false;
        }

        return (bool) ($perm->{'can_' . $action} ?? false);
    }

    /** มีสิทธิ์ดูเมนูนี้หรือไม่ (ใช้สร้างแถบนำทาง) */
    public function hasMenu(string $menuCode): bool
    {
        return $this->canMenu($menuCode, 'view');
    }

    /** สิทธิ์ระดับบนสุดของระบบ KPI: ผู้ดูแลระบบสูงสุด หรือผู้ดูแลตัวชี้วัดทั้งหมด */
    public function isTopAdmin(): bool
    {
        return $this->is_super_admin || in_array(KpiLevel::ADMIN_ALL, $this->levelCodes(), true);
    }

    /** มีสิทธิ์เข้าใช้เมนู "สิทธิ์ผู้ใช้งาน" เพื่อกำหนดสิทธิ์ให้ผู้อื่น */
    public function canManagePermissions(): bool
    {
        return $this->isTopAdmin();
    }

    /** มีสิทธิ์เข้าใช้เมนู "ผู้รับผิดชอบระดับ" (เฉพาะผู้ดูแลระบบสูงสุด/ผู้ดูแลตัวชี้วัดทั้งหมด) */
    public function canManageLevelManagers(): bool
    {
        return $this->isTopAdmin();
    }

    /** รหัสบทบาททั้งหมดในระบบ KPI (รวม super_admin ถ้ามี) */
    public function levelCodes(): array
    {
        $codes = $this->kpiLevels()->pluck('code')->all();

        if ($this->is_super_admin) {
            $codes[] = KpiLevel::SUPER_ADMIN;
        }

        return array_values(array_unique($codes));
    }

    /** เป็นผู้ดูแลตัวชี้วัด (รายระดับหรือทั้งหมด) อย่างน้อยหนึ่งบทบาทหรือไม่ */
    public function isIndicatorAdmin(): bool
    {
        return (bool) array_intersect($this->levelCodes(), KpiLevel::INDICATOR_ADMINS);
    }

    /** ขอบเขตของบทบาทผู้ดูแลตัวชี้วัดทั้งหมด: ชุดของ all|hospital|province|ministry */
    public function indicatorAdminScopes(): array
    {
        return $this->kpiLevels()
            ->filter(fn (KpiLevel $l) => in_array($l->code, KpiLevel::INDICATOR_ADMINS, true))
            ->pluck('scope')->filter()->unique()->values()->all();
    }

    /** จัดการข้อมูลตัวชี้วัดระดับนี้ได้หรือไม่ (super admin / ผู้ดูแลทั้งหมด / ผู้ดูแลระดับที่ครอบคลุม) */
    public function canManageIndicatorLevel(string $level): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        $scopes = $this->indicatorAdminScopes();

        return in_array(KpiLevel::SCOPE_ALL, $scopes, true) || in_array($level, $scopes, true);
    }

    /** จัดการตัวชี้วัดได้ทุกระดับหรือไม่ (ผู้ดูแลระบบสูงสุด หรือผู้ดูแลตัวชี้วัดทั้งหมด) */
    public function canManageAllIndicatorLevels(): bool
    {
        return $this->is_super_admin
            || in_array(KpiLevel::SCOPE_ALL, $this->indicatorAdminScopes(), true);
    }

    /** ระดับตัวชี้วัดที่ผู้ใช้เป็นผู้ดูแลระดับนั้นโดยเฉพาะ (hospital/province/ministry — ไม่รวม 'all') */
    public function manageableIndicatorLevels(): array
    {
        return array_values(array_filter(
            $this->indicatorAdminScopes(),
            fn (string $scope) => $scope !== KpiLevel::SCOPE_ALL
        ));
    }

    /**
     * เป็นผู้ดูแลตัวชี้วัดหรือไม่ (ผู้ดูแลระบบสูงสุด / ผู้ดูแลตัวชี้วัดทั้งหมด / ผู้ดูแลรายระดับ)
     * ใช้เป็นด่านเข้าเมนู "ตัวชี้วัด" และ "กำหนดค่าเป้าหมาย"
     */
    public function isIndicatorManager(): bool
    {
        return $this->is_super_admin || $this->isIndicatorAdmin();
    }

    /** เป็นผู้รับผิดชอบ (owner) ของตัวชี้วัดนี้หรือไม่ */
    public function isOwnerOf(KpiIndicator $indicator): bool
    {
        return $indicator->owners()->whereKey($this->getKey())->exists();
    }

    /**
     * มีสิทธิ์บันทึกผลของตัวชี้วัดนี้หรือไม่
     * - ผู้ดูแลระบบสูงสุด / ผู้ดูแลตัวชี้วัดทั้งหมด / ผู้ดูแลระดับที่ตรงกับระดับตัวชี้วัด
     * - หรือเป็นผู้รับผิดชอบของตัวชี้วัดนั้น
     */
    public function canRecordResultFor(KpiIndicator $indicator): bool
    {
        return $this->canManageIndicatorLevel($indicator->level) || $this->isOwnerOf($indicator);
    }

    /** เห็น/บันทึกผลตัวชี้วัดได้ทุกตัวหรือไม่ (ผู้ดูแลระบบสูงสุด หรือผู้ดูแลตัวชี้วัดทั้งหมด) */
    public function canRecordAllIndicators(): bool
    {
        return $this->canManageAllIndicatorLevels();
    }

    /** ระดับตัวชี้วัดที่ผู้ใช้เป็นผู้ดูแลระดับนั้น (hospital/province/ministry — ไม่รวม 'all') */
    public function recordableAdminLevels(): array
    {
        return $this->manageableIndicatorLevels();
    }

    /** id ตัวชี้วัดทั้งหมดที่ผู้ใช้เป็นผู้รับผิดชอบ */
    public function ownedIndicatorIds(): array
    {
        return KpiIndicator::query()
            ->whereHas('owners', fn ($q) => $q->whereKey($this->getKey()))
            ->pluck('id')->all();
    }
}

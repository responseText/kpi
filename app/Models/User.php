<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        'is_super_admin',
        'kpi_level_id',
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
            'is_super_admin' => 'boolean',
        ];
    }

    /** ข้อมูลเจ้าหน้าที่ (employee.id อ้างผ่าน users.employee_id เป็น varchar) */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /** บทบาท/ระดับสิทธิ์ในระบบ KPI */
    public function kpiLevel(): BelongsTo
    {
        return $this->belongsTo(KpiLevel::class, 'kpi_level_id');
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

    /** รหัสบทบาทในระบบ KPI (super_admin / indicator_admin_* / indicator_owner / null) */
    public function levelCode(): ?string
    {
        if ($this->is_super_admin) {
            return KpiLevel::SUPER_ADMIN;
        }

        return $this->kpiLevel?->code;
    }

    /** เป็นผู้ดูแลตัวชี้วัด (รายระดับหรือทั้งหมด) หรือไม่ */
    public function isIndicatorAdmin(): bool
    {
        return in_array($this->kpiLevel?->code, KpiLevel::INDICATOR_ADMINS, true);
    }

    /** ขอบเขตของผู้ดูแลตัวชี้วัด: all|hospital|province|ministry|null */
    public function indicatorAdminScope(): ?string
    {
        return $this->isIndicatorAdmin() ? $this->kpiLevel?->scope : null;
    }

    /** จัดการข้อมูลตัวชี้วัดระดับนี้ได้หรือไม่ (super admin / ผู้ดูแลทั้งหมด / ผู้ดูแลระดับตรงกัน) */
    public function canManageIndicatorLevel(string $level): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        $scope = $this->indicatorAdminScope();

        return $scope === KpiLevel::SCOPE_ALL || $scope === $level;
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
}

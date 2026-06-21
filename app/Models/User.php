<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
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
     * @return array<string, UserOnMenu>
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

        return (bool) ($perm->{'can_'.$action} ?? false);
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

    /**
     * มีสิทธิ์เข้าใช้เมนู "จัดการผู้ใช้งาน" (เปลี่ยนรหัสผ่าน/สถานะ/ระดับของผู้ใช้อื่น)
     * เฉพาะผู้ดูแลระบบสูงสุดเท่านั้น
     */
    public function canManageUsers(): bool
    {
        return $this->is_super_admin;
    }

    /**
     * มีสิทธิ์เข้าใช้เมนู "จัดการหน่วยวัด KPI" (master)
     * เฉพาะผู้ดูแลระบบสูงสุดเท่านั้น
     */
    public function canManageUnits(): bool
    {
        return $this->is_super_admin;
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

    /** ขอบเขตของบทบาทผู้ดูแลตัวชี้วัด: ชุดของ all|hospital|province|ministry */
    public function indicatorAdminScopes(): array
    {
        return array_keys($this->indicatorAdminScopeYears());
    }

    /**
     * แผนที่ขอบเขตผู้ดูแลตัวชี้วัด → ชุดปี พ.ศ. ที่รับผิดชอบ
     * คืน [scope => [year, ...]] โดยค่า null ในชุด = "ทุกปี"
     * เช่น ['hospital' => [2569, 2570], 'province' => [null]] ; ผู้ดูแลทั้งหมด → ['all' => [null]]
     *
     * @return array<string, array<int|null>>
     */
    public function indicatorAdminScopeYears(): array
    {
        $map = [];

        foreach ($this->kpiLevelRows as $row) {
            $level = $row->level;
            if (! $level || ! in_array($level->code, KpiLevel::INDICATOR_ADMINS, true)) {
                continue;
            }

            // ผู้ดูแลทั้งหมด (scope=all) ครอบคลุมทุกปีเสมอ (ไม่ผูกปี)
            $year = $level->code === KpiLevel::ADMIN_ALL
                ? null
                : ($row->year !== null ? (int) $row->year : null);

            $map[$level->scope][] = $year;
        }

        foreach ($map as $scope => $years) {
            $map[$scope] = $this->uniqueYears($years);
        }

        return $map;
    }

    /** ลบค่าซ้ำในชุดปี โดยถือ null ("ทุกปี") เป็นค่าหนึ่ง */
    private function uniqueYears(array $years): array
    {
        $seen = [];
        $out = [];
        foreach ($years as $y) {
            $key = $y === null ? 'all' : (string) $y;
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $out[] = $y;
            }
        }

        return $out;
    }

    /**
     * จัดการ/เข้าถึงข้อมูลตัวชี้วัดระดับนี้ (และปีนี้) ได้หรือไม่
     * - super admin / ผู้ดูแลทั้งหมด → ทุกระดับ ทุกปี
     * - ผู้ดูแลรายระดับ → เฉพาะระดับของตน และเฉพาะปีที่รับผิดชอบ (null ในบทบาท = ทุกปี)
     *
     * @param  int|null  $year  ปี พ.ศ. ของข้อมูล (null = ไม่จำกัดปี เช่นเช็คว่าเป็นผู้ดูแลระดับนี้บ้างไหม)
     */
    public function canManageIndicatorLevel(string $level, ?int $year = null): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        $map = $this->indicatorAdminScopeYears();

        if (array_key_exists(KpiLevel::SCOPE_ALL, $map)) {
            return true;
        }

        if (! array_key_exists($level, $map)) {
            return false;
        }

        if ($year === null) {
            return true;
        }

        $years = $map[$level];

        return in_array(null, $years, true) || in_array($year, $years, true);
    }

    /**
     * จัดการข้อมูล (เพิ่ม/แก้ไข/ลบ) ในเมนูสายตัวชี้วัดได้หรือไม่
     * แยกคนละแกน:
     *   - "ขอบเขต" คุมด้วยบทบาทระดับ+ปี (canManageIndicatorLevel) — ผู้ดูแลทั้งหมดครอบคลุมทุกระดับทุกปี
     *   - "ทำ action ได้ไหม" คุมด้วยการกำหนดสิทธิ์เมนู (users_on_menu can_create/edit/delete)
     * ถ้าไม่ได้กำหนดสิทธิ์ action → ดูได้อย่างเดียว (เมธอดนี้คืน false)
     * ผู้ดูแลระบบสูงสุดผ่านทุก action ทุกระดับทุกปีเสมอ
     *
     * @param  string|null  $level  ระดับของข้อมูล (null = ตรวจเฉพาะสิทธิ์ action เช่นตอนเปิดฟอร์มสร้าง)
     * @param  int|null  $year  ปี พ.ศ. ของข้อมูล (ใช้ร่วมกับ level เพื่อจำกัดตามปีที่รับผิดชอบ)
     */
    public function canManageIndicatorData(string $menuCode, string $action, ?string $level = null, ?int $year = null): bool
    {
        if (! $this->canMenu($menuCode, $action)) {
            return false;
        }

        return $level === null || $this->canManageIndicatorLevel($level, $year);
    }

    /** จัดการตัวชี้วัดได้ทุกระดับหรือไม่ (ผู้ดูแลระบบสูงสุด หรือผู้ดูแลตัวชี้วัดทั้งหมด) */
    public function canManageAllIndicatorLevels(): bool
    {
        return $this->is_super_admin
            || in_array(KpiLevel::SCOPE_ALL, $this->indicatorAdminScopes(), true);
    }

    /**
     * ระดับตัวชี้วัดที่ผู้ใช้เป็นผู้ดูแลรายระดับ (hospital/province/ministry — ไม่รวม 'all')
     * ถ้าระบุปี จะคืนเฉพาะระดับที่รับผิดชอบในปีนั้น (null ในบทบาท = ทุกปี)
     */
    public function manageableIndicatorLevels(?int $year = null): array
    {
        $levels = [];

        foreach ($this->indicatorAdminScopeYears() as $scope => $years) {
            if ($scope === KpiLevel::SCOPE_ALL) {
                continue;
            }
            if ($year === null || in_array(null, $years, true) || in_array($year, $years, true)) {
                $levels[] = $scope;
            }
        }

        return array_values(array_unique($levels));
    }

    /**
     * ปี พ.ศ. ที่รับผิดชอบของแต่ละบทบาท (key by level_id) สำหรับ pre-fill ฟอร์มกำหนดสิทธิ์
     * ค่า null ในชุด = "ทุกปี"
     *
     * @return array<int, array<int|null>>
     */
    public function kpiLevelYearMap(): array
    {
        $map = [];

        foreach ($this->kpiLevelRows as $row) {
            if ($row->is_super_admin || ! $row->level_id) {
                continue;
            }
            $map[$row->level_id][] = $row->year !== null ? (int) $row->year : null;
        }

        return $map;
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
        return $this->canManageIndicatorLevel($indicator->level, (int) $indicator->year)
            || $this->isOwnerOf($indicator);
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

    /**
     * เข้าถึงเมนู "บันทึกผลงาน" ได้หรือไม่ (ใช้ทั้งแถบนำทางและด่านเข้าหน้า)
     * - ผู้ดูแลระบบสูงสุด / ผู้ดูแลตัวชี้วัด (ทุกระดับ/ทั้งหมด/รายระดับ)
     * - ผู้รับผิดชอบ (owner) ของตัวชี้วัดอย่างน้อยหนึ่งตัว
     * - หรือได้รับสิทธิ์เมนูบันทึกผลโดยตรงจากผู้ดูแล
     * การบันทึกผลของตัวชี้วัดแต่ละตัวยังคุมด้วย canRecordResultFor() อีกชั้นหนึ่ง
     */
    public function canAccessResults(): bool
    {
        return $this->isIndicatorManager()
            || $this->canMenu('kpi.result', 'view')
            || count($this->ownedIndicatorIds()) > 0;
    }
}

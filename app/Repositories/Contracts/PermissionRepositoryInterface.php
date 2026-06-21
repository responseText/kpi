<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface PermissionRepositoryInterface
{
    /** รายชื่อผู้ใช้ (ค้นหา + แบ่งหน้า) พร้อมจำนวนเมนูที่มีสิทธิ์ */
    public function paginateUsers(?string $search, int $perPage = 30): LengthAwarePaginator;

    /** เมนูทั้งหมดของระบบ (เรียงลำดับ) */
    public function menus(string $system = 'kpi'): Collection;

    /** ระดับสิทธิ์ที่กำหนดให้ผู้ใช้ผ่าน UI ได้ (ไม่รวมผู้ดูแลระบบสูงสุด) */
    public function assignableLevels(): Collection;

    /** ปี พ.ศ. ให้เลือกเป็น "ปีที่รับผิดชอบ" (จากข้อมูลที่มี + ช่วงปีปัจจุบัน) เรียงมากไปน้อย */
    public function assignableYears(): array;

    /**
     * กำหนดบทบาท/ระดับสิทธิ์ KPI ของผู้ใช้ (รองรับหลายบทบาท, เก็บที่ users_on_level)
     * บทบาทผู้ดูแลรายระดับ (รพ./จังหวัด/กระทรวง) ผูกกับ "ปีที่รับผิดชอบ" ได้หลายปี
     *
     * @param  array<int>  $levelIds  id ของบทบาทที่เลือก
     * @param  array<int, array<int|string>>  $levelYears  ปีที่เลือกของแต่ละบทบาท (key=level_id) — 'all'/ว่าง = ทุกปี
     */
    public function setUserKpiLevels(int $userId, array $levelIds, array $levelYears = []): void;

    /** สิทธิ์ทั้งหมดของผู้ใช้คนหนึ่ง key by menu_id */
    public function permissionsForUser(int $userId): Collection;

    /**
     * บันทึกสิทธิ์ของผู้ใช้ (แทนที่ทั้งชุด)
     *
     * @param  array<int, array{can_view:bool,can_create:bool,can_edit:bool,can_delete:bool}>  $rows  key = menu_id
     */
    public function syncUserPermissions(int $userId, array $rows): void;

    /** รายชื่อผู้ใช้ที่เปิดใช้งาน (สำหรับเลือกในฟอร์ม) */
    public function selectableUsers(): Collection;
}

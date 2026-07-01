<?php

namespace App\Services\Import\Contracts;

use App\Services\Import\ColumnDef;
use App\Services\Import\ImportResult;
use Illuminate\Support\Collection;

/**
 * ประเภทข้อมูลหนึ่งที่นำเข้าได้ (ยุทธศาสตร์/กลยุทธ์/หมวด KPI/KPI หลัก/ตัวชี้วัด/ค่าเป้าหมาย)
 */
interface ImportType
{
    /** คีย์ใน URL/registry เช่น 'strategies' */
    public function key(): string;

    /** ชื่อแสดงผล เช่น 'ยุทธศาสตร์' */
    public function label(): string;

    /** คำอธิบายสั้น ๆ ของประเภทนี้ */
    public function description(): string;

    /** ลำดับการนำเข้าที่แนะนำ (1 = นำเข้าก่อน) */
    public function order(): int;

    /** ไอคอน (x-icon name) */
    public function icon(): string;

    /**
     * คำแนะนำเพิ่มเติม (แสดงเป็น bullet ในชีตคำแนะนำและหน้าเว็บ)
     *
     * @return array<int,string>
     */
    public function instructions(): array;

    /**
     * นิยามคอลัมน์ของเทมเพลต (แหล่งความจริงเดียวของทั้งเทมเพลตและการอ่าน)
     *
     * @return array<int,ColumnDef>
     */
    public function columns(): array;

    /**
     * นำเข้าข้อมูล — ตรวจทุกแถวก่อน ถ้ามี error จะไม่บันทึกอะไรเลย (all-or-nothing)
     *
     * @param  Collection<int,array<string,mixed>>  $rows  แต่ละแถวมีคีย์ '__row' (เลขแถวในไฟล์) + คีย์คอลัมน์
     */
    public function import(Collection $rows): ImportResult;
}

<?php

namespace App\Services\Import;

use App\Models\KpiIndicator;
use App\Models\User;
use App\Services\Import\Contracts\ImportType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * ฐานร่วมของ importer ทุกประเภท — ตัวช่วยอ่านค่า/ตรวจสอบ/ค้นหาพ่อแม่/แปลงค่า
 * และ ColumnDef ที่ใช้ซ้ำหลายเทมเพลต (ปี/ระดับ/สถานะ/รหัส/ลำดับ/รายละเอียด)
 */
abstract class AbstractImport implements ImportType
{
    public function icon(): string
    {
        return 'import';
    }

    public function instructions(): array
    {
        return [];
    }

    /**
     * โครงการนำเข้าแบบ all-or-nothing:
     *   1) เตรียม+ตรวจสอบทุกแถว (prepareRow) เก็บข้อผิดพลาดรายแถว
     *   2) ถ้ามี error แม้แถวเดียว → ไม่บันทึกอะไรเลย
     *   3) ไม่มี error → บันทึกทั้งหมดใน transaction (persist)
     */
    public function import(Collection $rows): ImportResult
    {
        $result = new ImportResult();
        $result->rowsRead = $rows->count();

        $prepared = [];
        $seen = [];

        foreach ($rows as $row) {
            $rowNo = (int) ($row['__row'] ?? 0);
            $prep = $this->prepareRow($row);

            if (! empty($prep['errors'])) {
                $result->addError($rowNo, $prep['errors']);

                continue;
            }

            $dedup = $prep['dedup'] ?? null;
            if ($dedup !== null) {
                if (isset($seen[$dedup])) {
                    $result->addError($rowNo, 'รายการนี้ซ้ำกับแถวก่อนหน้าในไฟล์เดียวกัน (แถว ' . $seen[$dedup] . ')');

                    continue;
                }
                $seen[$dedup] = $rowNo;
            }

            $prepared[] = $prep['payload'];
        }

        if ($result->hasErrors()) {
            return $result;
        }

        DB::transaction(function () use ($prepared, $result) {
            foreach ($prepared as $payload) {
                $this->persist($payload, $result);
            }
        });

        return $result;
    }

    /**
     * เตรียมและตรวจสอบหนึ่งแถว
     *
     * @return array{errors:array<int,string>,payload?:array<string,mixed>,dedup?:string|null}
     */
    abstract protected function prepareRow(array $row): array;

    /** บันทึกหนึ่ง payload (เรียกภายใน transaction) และอัปเดตตัวนับใน $result */
    abstract protected function persist(array $payload, ImportResult $result): void;

    // ---- อ่านค่าจากแถว -------------------------------------------------

    /** ค่าข้อความ (trim) หรือ null ถ้าว่าง */
    protected function str(array $row, string $key): ?string
    {
        $v = $row[$key] ?? null;
        if ($v === null) {
            return null;
        }
        $v = trim((string) $v);

        return $v === '' ? null : $v;
    }

    /** ค่าจำนวนเต็ม (null ถ้าว่าง/ไม่ใช่ตัวเลข) */
    protected function int(array $row, string $key): ?int
    {
        $v = $this->str($row, $key);

        return ($v !== null && is_numeric($v)) ? (int) $v : null;
    }

    // ---- แปลงค่าโค้ด (รับโค้ดเป็นหลัก, ยอมรับป้ายไทยเป็น fallback) -----------

    protected function normalizeLevel(?string $v): ?string
    {
        if ($v === null) {
            return null;
        }
        if (array_key_exists($v, KpiIndicator::LEVELS)) {
            return $v;                                   // โค้ดอยู่แล้ว
        }
        $byLabel = array_flip(KpiIndicator::LEVELS);     // ป้ายไทย → โค้ด

        return $byLabel[$v] ?? $v;                       // ไม่รู้จัก → คืนค่าเดิมให้ validator ปฏิเสธ
    }

    protected function normalizeStatus(?string $v): ?string
    {
        if ($v === null) {
            return 'enable';
        }
        $map = ['เปิด' => 'enable', 'เปิดใช้งาน' => 'enable', 'ปิด' => 'disable', 'ปิดใช้งาน' => 'disable'];

        return $map[$v] ?? $v;
    }

    // ---- ตรวจสอบ -------------------------------------------------------

    /**
     * ตรวจสอบข้อมูลด้วยกฎ Laravel — คืน array ของข้อความ error (ว่าง = ผ่าน)
     *
     * @return array<int,string>
     */
    protected function validate(array $data, array $rules, array $attributes = [], array $messages = []): array
    {
        $v = Validator::make($data, $rules, $messages, $attributes);

        return $v->fails() ? $v->errors()->all() : [];
    }

    // ---- ค้นหาพ่อแม่ ----------------------------------------------------

    /**
     * ค้นหารายการพ่อแม่จาก query ที่สโคปไว้แล้ว ด้วย "รหัสหรือชื่อ" (รหัสก่อน, แล้วชื่อ)
     * คืน [model|null, errorString|null] — error เมื่อไม่พบ/พบซ้ำ
     *
     * @return array{0:?Model,1:?string}
     */
    protected function resolveRef(Builder $scoped, ?string $ref, string $label): array
    {
        if ($ref === null) {
            return [null, "ไม่ได้ระบุ{$label}"];
        }

        $byCode = (clone $scoped)->where('code', $ref)->get();
        if ($byCode->count() === 1) {
            return [$byCode->first(), null];
        }
        if ($byCode->count() > 1) {
            return [null, "พบ{$label}รหัส \"{$ref}\" ซ้ำกันมากกว่า 1 รายการ — กรุณาทำให้รหัสไม่ซ้ำภายในปี/ระดับเดียวกัน"];
        }

        $byName = (clone $scoped)->where('name', $ref)->get();
        if ($byName->count() === 1) {
            return [$byName->first(), null];
        }
        if ($byName->count() > 1) {
            return [null, "พบ{$label}ชื่อ \"{$ref}\" ซ้ำกัน — กรุณาระบุด้วยรหัสแทน"];
        }

        return [null, "ไม่พบ{$label} \"{$ref}\" (ตรวจสอบปี/ระดับ และนำเข้า{$label}ให้เรียบร้อยก่อน)"];
    }

    // ---- ผู้ใช้ (owners/reviewers) -------------------------------------

    /**
     * แปลงรายชื่อผู้ใช้ (username คั่นด้วย , ; เว้นวรรค) → [userIds, ชื่อที่หาไม่พบ]
     *
     * @return array{0:array<int,int>,1:array<int,string>}
     */
    protected function resolveUsers(?string $csv): array
    {
        if ($csv === null) {
            return [[], []];
        }

        $names = collect(preg_split('/[,;]+/', $csv))
            ->map(fn ($n) => trim($n))
            ->filter()
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            return [[], []];
        }

        $found = User::query()->whereIn('name', $names->all())->pluck('id', 'name');
        $ids = [];
        $missing = [];
        foreach ($names as $name) {
            if ($found->has($name)) {
                $ids[] = (int) $found->get($name);
            } else {
                $missing[] = $name;
            }
        }

        return [$ids, $missing];
    }

    // ---- ColumnDef ที่ใช้ซ้ำ -------------------------------------------

    protected function yearCol(): ColumnDef
    {
        return new ColumnDef(
            key: 'year', header: 'ปี (พ.ศ.)', required: true,
            note: 'ปีพุทธศักราชของข้อมูล เช่น 2569', example: '2569',
            allowed: 'ตัวเลขปี พ.ศ. (2500–2700)', width: 12,
        );
    }

    protected function levelCol(): ColumnDef
    {
        return new ColumnDef(
            key: 'level', header: 'ระดับ', required: true,
            note: 'ระดับของตัวชี้วัด', example: 'hospital',
            options: array_keys(KpiIndicator::LEVELS),
            allowed: 'hospital = โรงพยาบาลทองแสนขัน, province = จังหวัด, ministry = กระทรวง', width: 14,
        );
    }

    protected function statusCol(): ColumnDef
    {
        return new ColumnDef(
            key: 'status', header: 'สถานะ', required: false,
            note: 'สถานะการใช้งาน (เว้นว่าง = เปิด)', example: 'enable',
            options: ['enable', 'disable'],
            allowed: 'enable = เปิดใช้งาน, disable = ปิด', width: 12,
        );
    }

    protected function codeCol(string $note = 'รหัสอ้างอิง (ไม่บังคับ แต่แนะนำให้กรอกและไม่ซ้ำกัน เพื่อใช้เชื่อมโยงชั้นถัดไปและอัปเดตซ้ำ)', string $example = 'S1'): ColumnDef
    {
        return new ColumnDef(
            key: 'code', header: 'รหัส', required: false,
            note: $note, example: $example,
            allowed: 'ข้อความไม่เกิน 50 ตัวอักษร', width: 14,
        );
    }

    protected function orderbyCol(): ColumnDef
    {
        return new ColumnDef(
            key: 'orderby', header: 'ลำดับ', required: false,
            note: 'ลำดับการแสดง (ตัวเลข; เว้นว่าง = 0)', example: '1',
            allowed: 'ตัวเลขจำนวนเต็ม', width: 10,
        );
    }

    protected function descriptionCol(): ColumnDef
    {
        return new ColumnDef(
            key: 'description', header: 'รายละเอียด', required: false,
            note: 'คำอธิบายเพิ่มเติม (ไม่บังคับ)', example: '', width: 40,
        );
    }
}

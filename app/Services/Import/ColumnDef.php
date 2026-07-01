<?php

namespace App\Services\Import;

/**
 * นิยามคอลัมน์หนึ่งของเทมเพลตนำเข้า — เป็นแหล่งความจริงเดียว
 * ใช้ทั้งสร้างเทมเพลต (หัวคอลัมน์/คำอธิบาย/dropdown) และอ่านไฟล์ (จับหัวคอลัมน์ → key)
 */
class ColumnDef
{
    /**
     * @param  string             $key      คีย์ภายใน (ใช้อ้างใน importer)
     * @param  string             $header   หัวคอลัมน์ในไฟล์ Excel (ต้องตรงกันเป๊ะ)
     * @param  bool               $required คอลัมน์บังคับกรอกหรือไม่
     * @param  string             $note     คำอธิบายวิธีกรอก (แสดงในชีตคำแนะนำ)
     * @param  string             $example  ตัวอย่างค่า (แสดงในชีตคำแนะนำ)
     * @param  array<int,string>|null $options  รายการค่าที่อนุญาต (สร้าง dropdown) — โค้ดจริง
     * @param  string             $allowed  คำอธิบายค่าที่อนุญาตแบบอ่านง่าย (แสดงในชีตคำแนะนำ)
     * @param  int                $width    ความกว้างคอลัมน์ในไฟล์ Excel
     */
    public function __construct(
        public readonly string $key,
        public readonly string $header,
        public readonly bool $required = false,
        public readonly string $note = '',
        public readonly string $example = '',
        public readonly ?array $options = null,
        public readonly string $allowed = '',
        public readonly int $width = 18,
    ) {}
}

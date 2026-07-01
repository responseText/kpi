<?php

namespace App\Services\Import;

/**
 * ผลลัพธ์ของการนำเข้าหนึ่งครั้ง — ใช้แสดงสรุป/ข้อผิดพลาดให้ผู้ใช้
 */
class ImportResult
{
    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    /** @var array<int,array{row:int,messages:array<int,string>}> ข้อผิดพลาดรายแถว */
    public array $errors = [];

    /** ข้อผิดพลาดระดับไฟล์ (เช่น ไฟล์ไม่ตรงเทมเพลต) — ถ้ามีจะถือว่านำเข้าไม่สำเร็จทั้งไฟล์ */
    public ?string $fatal = null;

    /** จำนวนแถวที่อ่านได้ (ไม่นับแถวว่าง) */
    public int $rowsRead = 0;

    /** @param string|array<int,string> $messages */
    public function addError(int $row, string|array $messages): void
    {
        $messages = is_array($messages) ? array_values(array_filter($messages)) : [$messages];
        if ($messages === []) {
            return;
        }
        $this->errors[] = ['row' => $row, 'messages' => $messages];
    }

    public function addFatal(string $message): void
    {
        $this->fatal = $message;
    }

    public function hasErrors(): bool
    {
        return $this->fatal !== null || $this->errors !== [];
    }

    public function success(): bool
    {
        return ! $this->hasErrors();
    }

    /** จำนวนรายการที่บันทึกจริง (สร้าง + อัปเดต) */
    public function total(): int
    {
        return $this->created + $this->updated;
    }

    /** แปลงเป็น array สำหรับเก็บใน session (flash) */
    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'rowsRead' => $this->rowsRead,
            'errors' => $this->errors,
            'fatal' => $this->fatal,
            'success' => $this->success(),
        ];
    }
}

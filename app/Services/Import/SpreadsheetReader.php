<?php

namespace App\Services\Import;

use App\Services\Import\Contracts\ImportType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;

/**
 * อ่านไฟล์ Excel/CSV ที่อัปโหลด → คืนรายการแถว (assoc array ตาม key คอลัมน์)
 * จับหัวคอลัมน์ (แถวแรกของชีต "ข้อมูล") กับ ColumnDef::$header
 */
class SpreadsheetReader
{
    /**
     * @return Collection<int,array<string,mixed>>  แต่ละแถวมี '__row' (เลขแถวในไฟล์) + คีย์คอลัมน์
     */
    public function read(UploadedFile $file, ImportType $type, ImportResult $result): Collection
    {
        try {
            $spreadsheet = $this->load($file);
        } catch (\Throwable $e) {
            $result->addFatal('เปิดไฟล์ไม่ได้: ' . $e->getMessage());

            return collect();
        }

        $sheet = $spreadsheet->getSheetByName('ข้อมูล') ?? $spreadsheet->getActiveSheet();
        $matrix = $sheet->toArray('', true, true, false);   // 0-based numeric arrays, ค่าที่แสดงจริง

        if (count($matrix) < 1) {
            $result->addFatal('ไฟล์ว่างเปล่า — ไม่พบหัวคอลัมน์');

            return collect();
        }

        // แถวแรก = หัวคอลัมน์ → map ข้อความหัว → index คอลัมน์
        $headerByText = [];
        foreach ($matrix[0] as $idx => $text) {
            $text = trim((string) $text);
            if ($text !== '') {
                $headerByText[$text] = $idx;
            }
        }

        // map key คอลัมน์ของเรา → index ในไฟล์ (ตามข้อความหัว)
        $colMap = [];
        $missing = [];
        foreach ($type->columns() as $col) {
            if (array_key_exists($col->header, $headerByText)) {
                $colMap[$col->key] = $headerByText[$col->header];
            } elseif ($col->required) {
                $missing[] = $col->header;
            }
        }

        if ($missing !== []) {
            $result->addFatal('ไฟล์ไม่ตรงกับเทมเพลต — ไม่พบคอลัมน์บังคับ: ' . implode(', ', $missing)
                . ' (กรุณาดาวน์โหลดเทมเพลตล่าสุดและกรอกในชีต "ข้อมูล")');

            return collect();
        }

        $rows = collect();
        $count = count($matrix);
        for ($r = 1; $r < $count; $r++) {
            $raw = $matrix[$r];
            $assoc = ['__row' => $r + 1];   // เลขแถวจริงในไฟล์ (1-based)
            $hasAny = false;

            foreach ($colMap as $key => $idx) {
                $val = $raw[$idx] ?? null;
                if (is_string($val)) {
                    $val = trim($val);
                }
                if ($val !== null && $val !== '') {
                    $hasAny = true;
                }
                $assoc[$key] = ($val === '' ? null : $val);
            }

            if ($hasAny) {
                $rows->push($assoc);
            }
        }

        $result->rowsRead = $rows->count();

        return $rows;
    }

    private function load(UploadedFile $file): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if ($ext === 'csv') {
            $reader = new CsvReader();
            $reader->setInputEncoding('UTF-8');
            $reader->setDelimiter(',');

            return $reader->load($file->getRealPath());
        }

        return IOFactory::load($file->getRealPath());
    }
}

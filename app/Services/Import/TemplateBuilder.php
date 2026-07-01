<?php

namespace App\Services\Import;

use App\Services\Import\Contracts\ImportType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * สร้างไฟล์เทมเพลต .xlsx ของประเภทข้อมูลหนึ่ง:
 *   - ชีต "คำแนะนำ" : อธิบายวิธีกรอก + เงื่อนไข + ค่าที่อนุญาต + ตัวอย่าง
 *   - ชีต "ข้อมูล"  : หัวคอลัมน์ (จัดสไตล์+freeze) + dropdown บังคับค่า (ไม่มีข้อมูลตัวอย่างเพื่อกันนำเข้าผิด)
 */
class TemplateBuilder
{
    private const HEADER_BG = 'FF4F46E5';        // indigo-600

    private const HEADER_REQUIRED_BG = 'FFB91C1C'; // red-700 (คอลัมน์บังคับ)

    private const TITLE_BG = 'FFEEF2FF';         // indigo-50

    private const TABLE_HEAD_BG = 'FF1E293B';    // slate-800

    public function build(ImportType $type): Spreadsheet
    {
        $ss = new Spreadsheet();
        $ss->getProperties()
            ->setCreator('ระบบตัวชี้วัด KPI รพ.ทองแสนขัน')
            ->setTitle('เทมเพลตนำเข้า: ' . $type->label());

        $this->buildInstructionSheet($ss->getActiveSheet(), $type);
        $this->buildDataSheet($ss->createSheet(), $type);

        $ss->setActiveSheetIndex(0);   // เปิดที่ชีตคำแนะนำก่อน

        return $ss;
    }

    private function buildInstructionSheet($sheet, ImportType $type): void
    {
        $sheet->setTitle('คำแนะนำ');
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(10);
        $sheet->getColumnDimension('C')->setWidth(52);
        $sheet->getColumnDimension('D')->setWidth(26);
        $sheet->getColumnDimension('E')->setWidth(40);
        $sheet->getDefaultRowDimension()->setRowHeight(-1);

        // หัวเรื่อง
        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'เทมเพลตนำเข้า: ' . $type->label());
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::TITLE_BG);
        $sheet->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $row = 2;
        $sheet->mergeCells("A{$row}:E{$row}");
        $sheet->setCellValue("A{$row}", $type->description());
        $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("A{$row}")->getFont()->setItalic(true);
        $row += 2;

        // ข้อกำหนด/คำแนะนำ
        $sheet->setCellValue("A{$row}", 'ข้อกำหนดและวิธีกรอก');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
        $row++;

        $baseNotes = [
            'กรอกข้อมูลในชีต "ข้อมูล" (แท็บด้านล่าง) — 1 แถว = 1 รายการ เริ่มจากแถวที่ 2',
            'ห้ามแก้ไข/ลบ/สลับลำดับหัวคอลัมน์ในแถวที่ 1 ของชีต "ข้อมูล"',
            'คอลัมน์ที่ทำเครื่องหมาย ✔ (บังคับ) ต้องกรอกทุกแถว',
            'ลำดับการนำเข้า: 1) ยุทธศาสตร์ → 2) กลยุทธ์ → 3) หมวด KPI → 4) KPI หลัก → 5) ตัวชี้วัด → 6) ค่าเป้าหมาย (นำเข้าให้ครบของชั้นบนก่อน)',
            'ปี (year) ใช้ปี พ.ศ. เช่น 2569',
            'ถ้ามีข้อผิดพลาดแม้แถวเดียว ระบบจะไม่บันทึกทั้งไฟล์ และแจ้งเลขแถวที่ผิดให้แก้ไข — นำเข้าไฟล์เดิมซ้ำได้ (ระบบจะอัปเดตของเดิม ไม่สร้างซ้ำ)',
        ];
        foreach (array_merge($baseNotes, $type->instructions()) as $note) {
            $sheet->setCellValue("A{$row}", '•');
            $sheet->getStyle("A{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            $sheet->mergeCells("B{$row}:E{$row}");
            $sheet->setCellValue("B{$row}", $note);
            $sheet->getStyle("B{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $row++;
        }
        $row++;

        // ตารางอธิบายคอลัมน์
        $headers = ['คอลัมน์', 'บังคับ', 'คำอธิบาย', 'ตัวอย่าง', 'ค่าที่อนุญาต'];
        foreach ($headers as $i => $h) {
            $cell = Coordinate::stringFromColumnIndex($i + 1) . $row;
            $sheet->setCellValue($cell, $h);
            $sheet->getStyle($cell)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::TABLE_HEAD_BG);
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $headerRow = $row;
        $row++;

        foreach ($type->columns() as $col) {
            $sheet->setCellValue("A{$row}", $col->header);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $sheet->setCellValue("B{$row}", $col->required ? '✔' : '');
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue("C{$row}", $col->note);
            $sheet->setCellValue("D{$row}", $col->example);
            $sheet->setCellValue("E{$row}", $col->allowed);
            foreach (['C', 'D', 'E'] as $c) {
                $sheet->getStyle("{$c}{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            }
            $row++;
        }

        // เส้นขอบตาราง
        $sheet->getStyle("A{$headerRow}:E" . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->setSelectedCell('A1');
    }

    private function buildDataSheet($sheet, ImportType $type): void
    {
        $sheet->setTitle('ข้อมูล');
        $columns = $type->columns();

        foreach ($columns as $i => $col) {
            $colIndex = $i + 1;
            $letter = Coordinate::stringFromColumnIndex($colIndex);

            // หัวคอลัมน์
            $cell = $letter . '1';
            $sheet->setCellValue($cell, $col->header);
            $sheet->getStyle($cell)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($col->required ? self::HEADER_REQUIRED_BG : self::HEADER_BG);
            $sheet->getStyle($cell)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            $sheet->getColumnDimension($letter)->setWidth($col->width);

            // dropdown บังคับค่า (โค้ดจริง) — ใส่ให้ 200 แถวแรก
            if ($col->options) {
                $list = '"' . implode(',', $col->options) . '"';
                for ($r = 2; $r <= 201; $r++) {
                    $dv = $sheet->getCell($letter . $r)->getDataValidation();
                    $dv->setType(DataValidation::TYPE_LIST);
                    $dv->setErrorStyle(DataValidation::STYLE_STOP);
                    $dv->setAllowBlank(true);
                    $dv->setShowInputMessage(true);
                    $dv->setShowErrorMessage(true);
                    $dv->setShowDropDown(true);
                    $dv->setErrorTitle('ค่าไม่ถูกต้อง');
                    $dv->setError('กรุณาเลือกค่าจากรายการที่กำหนดเท่านั้น');
                    $dv->setPromptTitle($col->header);
                    $dv->setPrompt($col->allowed ?: 'เลือกจากรายการ');
                    $dv->setFormula1($list);
                }
            }
        }

        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->freezePane('A2');                                   // ตรึงแถวหัว
        $sheet->setSelectedCell('A2');
    }
}

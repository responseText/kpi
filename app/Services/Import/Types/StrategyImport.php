<?php

namespace App\Services\Import\Types;

use App\Models\KpiStrategy;
use App\Services\Import\AbstractImport;
use App\Services\Import\ColumnDef;
use App\Services\Import\ImportResult;

/**
 * นำเข้า "ยุทธศาสตร์" (kpi_strategies) — ชั้นบนสุด ไม่มีพ่อแม่
 */
class StrategyImport extends AbstractImport
{
    public function key(): string
    {
        return 'strategies';
    }

    public function label(): string
    {
        return 'ยุทธศาสตร์';
    }

    public function description(): string
    {
        return 'นำเข้ายุทธศาสตร์รายปีตามระดับ (โรงพยาบาล/จังหวัด/กระทรวง) — เป็นชั้นบนสุดของโครงสร้าง ต้องนำเข้าก่อนกลยุทธ์';
    }

    public function order(): int
    {
        return 1;
    }

    public function icon(): string
    {
        return 'strategy';
    }

    public function instructions(): array
    {
        return [
            'ชื่อยุทธศาสตร์ห้ามซ้ำภายในปีและระดับเดียวกัน (ต่างปีหรือต่างระดับใช้ชื่อซ้ำได้)',
            'แนะนำให้กรอก "รหัส" และไม่ซ้ำกัน เพื่อใช้อ้างอิงตอนนำเข้ากลยุทธ์ และใช้จับคู่เวลาแก้ไฟล์แล้วนำเข้าซ้ำ',
        ];
    }

    public function columns(): array
    {
        return [
            $this->yearCol(),
            $this->levelCol(),
            $this->codeCol(example: 'S1'),
            new ColumnDef(
                key: 'name', header: 'ชื่อยุทธศาสตร์', required: true,
                note: 'ชื่อยุทธศาสตร์ (ห้ามซ้ำในปี+ระดับเดียวกัน)',
                example: 'พัฒนาระบบบริการสุขภาพ', width: 45,
            ),
            $this->descriptionCol(),
            $this->orderbyCol(),
            $this->statusCol(),
        ];
    }

    protected function prepareRow(array $row): array
    {
        $year   = $this->int($row, 'year');
        $level  = $this->normalizeLevel($this->str($row, 'level'));
        $code   = $this->str($row, 'code');
        $name   = $this->str($row, 'name');
        $status = $this->normalizeStatus($this->str($row, 'status'));

        $errors = $this->validate(
            ['year' => $year, 'level' => $level, 'code' => $code, 'name' => $name, 'status' => $status],
            [
                'year' => ['required', 'integer', 'min:2500', 'max:2700'],
                'level' => ['required', 'in:hospital,province,ministry'],
                'code' => ['nullable', 'string', 'max:50'],
                'name' => ['required', 'string', 'max:500'],
                'status' => ['required', 'in:enable,disable'],
            ],
            ['year' => 'ปี', 'level' => 'ระดับ', 'code' => 'รหัส', 'name' => 'ชื่อยุทธศาสตร์', 'status' => 'สถานะ'],
        );

        if ($errors) {
            return ['errors' => $errors];
        }

        // ชื่อต้องไม่ซ้ำในปี+ระดับเดียวกัน (ยกเว้นรายการเดียวกับที่กำลังอัปเดตด้วยรหัส)
        $target = $code !== null
            ? KpiStrategy::where('year', $year)->where('level', $level)->where('code', $code)->first()
            : null;
        $byName = KpiStrategy::where('year', $year)->where('level', $level)->where('name', $name)->first();
        if ($byName && (! $target || $byName->id !== $target->id)) {
            return ['errors' => ["มีชื่อยุทธศาสตร์ \"{$name}\" อยู่แล้วในปี {$year} ระดับ {$level}"]];
        }

        $payload = compact('year', 'level', 'code', 'name', 'status') + [
            'description' => $this->str($row, 'description'),
            'orderby' => $this->int($row, 'orderby') ?? 0,
        ];

        $dedup = $code !== null ? "C:{$year}:{$level}:{$code}" : "N:{$year}:{$level}:{$name}";

        return ['errors' => [], 'payload' => $payload, 'dedup' => $dedup];
    }

    protected function persist(array $p, ImportResult $result): void
    {
        $keys = $p['code'] !== null
            ? ['year' => $p['year'], 'level' => $p['level'], 'code' => $p['code']]
            : ['year' => $p['year'], 'level' => $p['level'], 'name' => $p['name']];

        $model = KpiStrategy::updateOrCreate($keys, [
            'code' => $p['code'],
            'name' => $p['name'],
            'description' => $p['description'],
            'orderby' => $p['orderby'],
            'status' => $p['status'],
        ]);

        $model->wasRecentlyCreated ? $result->created++ : $result->updated++;
    }
}

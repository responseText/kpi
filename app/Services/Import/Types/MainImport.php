<?php

namespace App\Services\Import\Types;

use App\Models\KpiCategory;
use App\Models\KpiMain;
use App\Services\Import\AbstractImport;
use App\Services\Import\ColumnDef;
use App\Services\Import\ImportResult;

/**
 * นำเข้า "KPI หลัก" (kpi_mains) — อยู่ใต้หมวด KPI
 */
class MainImport extends AbstractImport
{
    public function key(): string
    {
        return 'mains';
    }

    public function label(): string
    {
        return 'KPI หลัก';
    }

    public function description(): string
    {
        return 'นำเข้า KPI หลักภายใต้หมวด KPI — ต้องนำเข้าหมวด KPI ก่อน';
    }

    public function order(): int
    {
        return 4;
    }

    public function icon(): string
    {
        return 'main';
    }

    public function instructions(): array
    {
        return [
            'คอลัมน์ "หมวด KPI (รหัส/ชื่อ)" ใช้จับคู่หมวด KPI แม่ภายในปี+ระดับที่ระบุ (จับคู่หมวดที่ยังไม่ผูกกลยุทธ์ได้ด้วย)',
        ];
    }

    public function columns(): array
    {
        return [
            $this->yearCol(),
            $this->levelCol(),
            new ColumnDef(
                key: 'category_ref', header: 'หมวด KPI (รหัส/ชื่อ)', required: true,
                note: 'รหัสหรือชื่อหมวด KPI แม่ (ต้องนำเข้าหมวด KPI ก่อน)',
                example: 'C1', width: 32,
            ),
            $this->codeCol(example: 'M1'),
            new ColumnDef(
                key: 'name', header: 'ชื่อ KPI หลัก', required: true,
                note: 'ชื่อ KPI หลัก', example: 'อัตราการฝากครรภ์คุณภาพ', width: 45,
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
        $ref    = $this->str($row, 'category_ref');

        $errors = $this->validate(
            ['year' => $year, 'level' => $level, 'name' => $name, 'code' => $code, 'status' => $status, 'category_ref' => $ref],
            [
                'year' => ['required', 'integer', 'min:2500', 'max:2700'],
                'level' => ['required', 'in:hospital,province,ministry'],
                'category_ref' => ['required', 'string'],
                'code' => ['nullable', 'string', 'max:50'],
                'name' => ['required', 'string', 'max:500'],
                'status' => ['required', 'in:enable,disable'],
            ],
            ['year' => 'ปี', 'level' => 'ระดับ', 'category_ref' => 'หมวด KPI', 'name' => 'ชื่อ KPI หลัก', 'status' => 'สถานะ'],
        );

        if ($errors) {
            return ['errors' => $errors];
        }

        [$category, $refErr] = $this->resolveRef(
            KpiCategory::where(function ($q) use ($year, $level) {
                $q->whereHas('subStrategy.strategy', fn ($s) => $s->where('year', $year)->where('level', $level))
                    ->orWhereNull('sub_strategy_id');
            }),
            $ref, 'หมวด KPI'
        );
        if ($refErr) {
            return ['errors' => [$refErr]];
        }

        $target = $code !== null
            ? KpiMain::where('category_id', $category->id)->where('code', $code)->first()
            : null;
        $byName = KpiMain::where('category_id', $category->id)->where('name', $name)->first();
        if ($byName && (! $target || $byName->id !== $target->id)) {
            return ['errors' => ["มีชื่อ KPI หลัก \"{$name}\" อยู่แล้วภายใต้หมวด KPI เดียวกัน"]];
        }

        $payload = [
            'category_id' => $category->id,
            'code' => $code,
            'name' => $name,
            'description' => $this->str($row, 'description'),
            'orderby' => $this->int($row, 'orderby') ?? 0,
            'status' => $status,
        ];

        $dedup = $code !== null ? "C:{$category->id}:{$code}" : "N:{$category->id}:{$name}";

        return ['errors' => [], 'payload' => $payload, 'dedup' => $dedup];
    }

    protected function persist(array $p, ImportResult $result): void
    {
        $keys = $p['code'] !== null
            ? ['category_id' => $p['category_id'], 'code' => $p['code']]
            : ['category_id' => $p['category_id'], 'name' => $p['name']];

        $model = KpiMain::updateOrCreate($keys, [
            'code' => $p['code'],
            'name' => $p['name'],
            'description' => $p['description'],
            'orderby' => $p['orderby'],
            'status' => $p['status'],
        ]);

        $model->wasRecentlyCreated ? $result->created++ : $result->updated++;
    }
}

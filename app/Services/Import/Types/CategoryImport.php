<?php

namespace App\Services\Import\Types;

use App\Models\KpiCategory;
use App\Models\KpiSubStrategy;
use App\Services\Import\AbstractImport;
use App\Services\Import\ColumnDef;
use App\Services\Import\ImportResult;

/**
 * นำเข้า "หมวด KPI" (kpi_categories) — อยู่ใต้กลยุทธ์ (ไม่บังคับ; เว้นว่าง = ยังไม่ผูก)
 */
class CategoryImport extends AbstractImport
{
    public function key(): string
    {
        return 'categories';
    }

    public function label(): string
    {
        return 'หมวด KPI';
    }

    public function description(): string
    {
        return 'นำเข้าหมวด KPI ภายใต้กลยุทธ์ — ต้องนำเข้ากลยุทธ์ก่อน (เว้นคอลัมน์กลยุทธ์ว่างได้ถ้ายังไม่ผูก)';
    }

    public function order(): int
    {
        return 3;
    }

    public function icon(): string
    {
        return 'category';
    }

    public function instructions(): array
    {
        return [
            'คอลัมน์ "กลยุทธ์ (รหัส/ชื่อ)" ใช้จับคู่กลยุทธ์แม่ภายในปี+ระดับที่ระบุ — เว้นว่างได้ถ้ายังไม่ต้องการผูกกลยุทธ์',
            'ปี/ระดับ ใช้เพื่อค้นหากลยุทธ์แม่เท่านั้น (หมวด KPI สืบทอดปี/ระดับจากกลยุทธ์)',
        ];
    }

    public function columns(): array
    {
        return [
            $this->yearCol(),
            $this->levelCol(),
            new ColumnDef(
                key: 'sub_strategy_ref', header: 'กลยุทธ์ (รหัส/ชื่อ)', required: false,
                note: 'รหัสหรือชื่อกลยุทธ์แม่ (เว้นว่าง = ยังไม่ผูกกลยุทธ์)',
                example: 'S1.1', width: 32,
            ),
            $this->codeCol(example: 'C1'),
            new ColumnDef(
                key: 'name', header: 'ชื่อหมวด KPI', required: true,
                note: 'ชื่อหมวด KPI', example: 'หมวดบริการปฐมภูมิ', width: 45,
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
        $ref    = $this->str($row, 'sub_strategy_ref');

        $errors = $this->validate(
            ['year' => $year, 'level' => $level, 'name' => $name, 'code' => $code, 'status' => $status],
            [
                'year' => ['required', 'integer', 'min:2500', 'max:2700'],
                'level' => ['required', 'in:hospital,province,ministry'],
                'code' => ['nullable', 'string', 'max:50'],
                'name' => ['required', 'string', 'max:500'],
                'status' => ['required', 'in:enable,disable'],
            ],
            ['year' => 'ปี', 'level' => 'ระดับ', 'name' => 'ชื่อหมวด KPI', 'status' => 'สถานะ'],
        );

        if ($errors) {
            return ['errors' => $errors];
        }

        $subStrategyId = null;
        if ($ref !== null) {
            [$sub, $refErr] = $this->resolveRef(
                KpiSubStrategy::whereHas('strategy', fn ($q) => $q->where('year', $year)->where('level', $level)),
                $ref, 'กลยุทธ์'
            );
            if ($refErr) {
                return ['errors' => [$refErr]];
            }
            $subStrategyId = $sub->id;
        }

        // กันชื่อซ้ำภายใต้กลยุทธ์แม่เดียวกัน
        $base = KpiCategory::query()
            ->when($subStrategyId !== null, fn ($q) => $q->where('sub_strategy_id', $subStrategyId), fn ($q) => $q->whereNull('sub_strategy_id'));
        $target = $code !== null ? (clone $base)->where('code', $code)->first() : null;
        $byName = (clone $base)->where('name', $name)->first();
        if ($byName && (! $target || $byName->id !== $target->id)) {
            return ['errors' => ["มีชื่อหมวด KPI \"{$name}\" อยู่แล้วภายใต้กลยุทธ์เดียวกัน"]];
        }

        $payload = [
            'sub_strategy_id' => $subStrategyId,
            'code' => $code,
            'name' => $name,
            'description' => $this->str($row, 'description'),
            'orderby' => $this->int($row, 'orderby') ?? 0,
            'status' => $status,
        ];

        $parentKey = $subStrategyId ?? 'null';
        $dedup = $code !== null ? "C:{$parentKey}:{$code}" : "N:{$parentKey}:{$name}";

        return ['errors' => [], 'payload' => $payload, 'dedup' => $dedup];
    }

    protected function persist(array $p, ImportResult $result): void
    {
        $keys = ['sub_strategy_id' => $p['sub_strategy_id']];
        $keys[$p['code'] !== null ? 'code' : 'name'] = $p['code'] !== null ? $p['code'] : $p['name'];

        $model = KpiCategory::updateOrCreate($keys, [
            'code' => $p['code'],
            'name' => $p['name'],
            'description' => $p['description'],
            'orderby' => $p['orderby'],
            'status' => $p['status'],
        ]);

        $model->wasRecentlyCreated ? $result->created++ : $result->updated++;
    }
}

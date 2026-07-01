<?php

namespace App\Services\Import\Types;

use App\Models\KpiStrategy;
use App\Models\KpiSubStrategy;
use App\Services\Import\AbstractImport;
use App\Services\Import\ColumnDef;
use App\Services\Import\ImportResult;

/**
 * นำเข้า "กลยุทธ์" (kpi_sub_strategies) — อยู่ใต้ยุทธศาสตร์
 */
class SubStrategyImport extends AbstractImport
{
    public function key(): string
    {
        return 'sub-strategies';
    }

    public function label(): string
    {
        return 'กลยุทธ์';
    }

    public function description(): string
    {
        return 'นำเข้ากลยุทธ์ภายใต้ยุทธศาสตร์ — ต้องนำเข้ายุทธศาสตร์ของปี/ระดับนั้นก่อน';
    }

    public function order(): int
    {
        return 2;
    }

    public function icon(): string
    {
        return 'sub_strategy';
    }

    public function instructions(): array
    {
        return [
            'คอลัมน์ "ยุทธศาสตร์ (รหัส/ชื่อ)" ให้กรอกรหัสหรือชื่อยุทธศาสตร์ที่นำเข้าไว้แล้ว (ในปีและระดับเดียวกัน)',
            '"ผู้ตรวจสอบ" ไม่บังคับ — กรอก username ของผู้ใช้ (คั่นหลายคนด้วยเครื่องหมาย ,) เว้นว่างได้แล้วไปกำหนดในระบบภายหลัง',
        ];
    }

    public function columns(): array
    {
        return [
            $this->yearCol(),
            $this->levelCol(),
            new ColumnDef(
                key: 'strategy_ref', header: 'ยุทธศาสตร์ (รหัส/ชื่อ)', required: true,
                note: 'รหัสหรือชื่อยุทธศาสตร์แม่ (ต้องนำเข้ายุทธศาสตร์ก่อน)',
                example: 'S1', width: 32,
            ),
            $this->codeCol(example: 'S1.1'),
            new ColumnDef(
                key: 'name', header: 'ชื่อกลยุทธ์', required: true,
                note: 'ชื่อกลยุทธ์ (ไม่ควรซ้ำภายใต้ยุทธศาสตร์เดียวกัน)',
                example: 'เพิ่มการเข้าถึงบริการ', width: 45,
            ),
            $this->descriptionCol(),
            $this->orderbyCol(),
            $this->statusCol(),
            new ColumnDef(
                key: 'reviewers', header: 'ผู้ตรวจสอบ (username)', required: false,
                note: 'username ของผู้ตรวจสอบ คั่นหลายคนด้วย , (ไม่บังคับ)',
                example: 'somchai, somsri', allowed: 'username ที่มีอยู่ในระบบ', width: 26,
            ),
        ];
    }

    protected function prepareRow(array $row): array
    {
        $year   = $this->int($row, 'year');
        $level  = $this->normalizeLevel($this->str($row, 'level'));
        $code   = $this->str($row, 'code');
        $name   = $this->str($row, 'name');
        $status = $this->normalizeStatus($this->str($row, 'status'));
        $ref    = $this->str($row, 'strategy_ref');

        $errors = $this->validate(
            ['year' => $year, 'level' => $level, 'name' => $name, 'code' => $code, 'status' => $status, 'strategy_ref' => $ref],
            [
                'year' => ['required', 'integer', 'min:2500', 'max:2700'],
                'level' => ['required', 'in:hospital,province,ministry'],
                'strategy_ref' => ['required', 'string'],
                'code' => ['nullable', 'string', 'max:50'],
                'name' => ['required', 'string', 'max:500'],
                'status' => ['required', 'in:enable,disable'],
            ],
            ['year' => 'ปี', 'level' => 'ระดับ', 'strategy_ref' => 'ยุทธศาสตร์', 'name' => 'ชื่อกลยุทธ์', 'status' => 'สถานะ'],
        );

        if ($errors) {
            return ['errors' => $errors];
        }

        [$strategy, $refErr] = $this->resolveRef(
            KpiStrategy::where('year', $year)->where('level', $level),
            $ref, 'ยุทธศาสตร์'
        );
        if ($refErr) {
            return ['errors' => [$refErr]];
        }

        // ผู้ตรวจสอบ
        [$reviewerIds, $missing] = $this->resolveUsers($this->str($row, 'reviewers'));
        if ($missing) {
            return ['errors' => ['ไม่พบ username ผู้ตรวจสอบ: ' . implode(', ', $missing)]];
        }

        // กันชื่อซ้ำภายใต้ยุทธศาสตร์เดียวกัน
        $target = $code !== null
            ? KpiSubStrategy::where('strategy_id', $strategy->id)->where('code', $code)->first()
            : null;
        $byName = KpiSubStrategy::where('strategy_id', $strategy->id)->where('name', $name)->first();
        if ($byName && (! $target || $byName->id !== $target->id)) {
            return ['errors' => ["มีชื่อกลยุทธ์ \"{$name}\" อยู่แล้วภายใต้ยุทธศาสตร์นี้"]];
        }

        $payload = [
            'strategy_id' => $strategy->id,
            'code' => $code,
            'name' => $name,
            'description' => $this->str($row, 'description'),
            'orderby' => $this->int($row, 'orderby') ?? 0,
            'status' => $status,
            'reviewer_ids' => $reviewerIds,
            'sync_reviewers' => $this->str($row, 'reviewers') !== null,
        ];

        $dedup = $code !== null ? "C:{$strategy->id}:{$code}" : "N:{$strategy->id}:{$name}";

        return ['errors' => [], 'payload' => $payload, 'dedup' => $dedup];
    }

    protected function persist(array $p, ImportResult $result): void
    {
        $keys = $p['code'] !== null
            ? ['strategy_id' => $p['strategy_id'], 'code' => $p['code']]
            : ['strategy_id' => $p['strategy_id'], 'name' => $p['name']];

        $model = KpiSubStrategy::updateOrCreate($keys, [
            'code' => $p['code'],
            'name' => $p['name'],
            'description' => $p['description'],
            'orderby' => $p['orderby'],
            'status' => $p['status'],
        ]);

        if ($p['sync_reviewers']) {
            $model->reviewers()->sync($p['reviewer_ids']);
        }

        $model->wasRecentlyCreated ? $result->created++ : $result->updated++;
    }
}

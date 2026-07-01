<?php

namespace App\Services\Import\Types;

use App\Models\KpiIndicator;
use App\Repositories\Contracts\TargetRepositoryInterface;
use App\Services\Import\AbstractImport;
use App\Services\Import\ColumnDef;
use App\Services\Import\ImportResult;
use App\Services\KpiEvaluator;
use App\Services\PeriodCalculator;

/**
 * นำเข้า "การกำหนดค่าเป้าหมาย" (kpi_targets) — อัปเดตค่าเป้าหมายรายช่วงของตัวชี้วัดที่มีอยู่แล้ว
 * (ไม่สร้างตัวชี้วัดใหม่)
 */
class TargetImport extends AbstractImport
{
    public function __construct(
        private readonly TargetRepositoryInterface $targets,
    ) {}

    public function key(): string
    {
        return 'targets';
    }

    public function label(): string
    {
        return 'การกำหนดค่าเป้าหมาย';
    }

    public function description(): string
    {
        return 'กำหนดค่าเป้าหมายรายช่วงให้ตัวชี้วัดที่นำเข้าไว้แล้ว — ต้องนำเข้าตัวชี้วัดก่อน';
    }

    public function order(): int
    {
        return 6;
    }

    public function icon(): string
    {
        return 'target';
    }

    public function instructions(): array
    {
        return [
            'คอลัมน์ "ตัวชี้วัด (รหัส/ชื่อ)" ใช้จับคู่ตัวชี้วัดภายในปี+ระดับที่ระบุ (ต้องนำเข้าตัวชี้วัดก่อน)',
            'ช่วง (period_no): เว้นว่าง = ใช้กับทุกช่วงของตัวชี้วัดนั้น, 0 = รายปี, 1–4 = ไตรมาสที่ 1–4',
            'ตัวชี้วัดแบบรายปีมีเฉพาะช่วง 0; แบบรายไตรมาสมีช่วง 1–4',
            'เกณฑ์ที่เป็นตัวเลข (>, ≥, <, ≤, =, ≠) ต้องกรอก "ค่าเป้าหมาย"; เกณฑ์ passfail (ผ่าน/ไม่ผ่าน) ไม่ต้องกรอกค่าเป้าหมาย',
        ];
    }

    public function columns(): array
    {
        $opAllowed = collect(KpiEvaluator::LABELS)->map(fn ($l, $c) => "{$c} = {$l}")->implode(', ');

        return [
            $this->yearCol(),
            $this->levelCol(),
            new ColumnDef(
                key: 'indicator_ref', header: 'ตัวชี้วัด (รหัส/ชื่อ)', required: true,
                note: 'รหัสหรือชื่อตัวชี้วัด (ต้องนำเข้าตัวชี้วัดก่อน)', example: 'KPI-001', width: 36,
            ),
            new ColumnDef(
                key: 'period_no', header: 'ช่วง (period_no)', required: false,
                note: 'เว้นว่าง = ทุกช่วง / 0 = รายปี / 1–4 = ไตรมาส', example: '',
                allowed: 'ว่าง, 0, 1, 2, 3, 4', width: 16,
            ),
            new ColumnDef(
                key: 'operator', header: 'เกณฑ์', required: true,
                note: 'เกณฑ์การประเมินผลเทียบเป้าหมาย', example: 'gte',
                options: array_keys(KpiEvaluator::LABELS), allowed: $opAllowed, width: 14,
            ),
            new ColumnDef(
                key: 'target_value', header: 'ค่าเป้าหมาย', required: false,
                note: 'ค่าเป้าหมาย (ตัวเลข) — บังคับเมื่อเกณฑ์เป็นตัวเลข', example: '80',
                allowed: 'ตัวเลข', width: 14,
            ),
            new ColumnDef(
                key: 'target_text', header: 'หมายเหตุเป้าหมาย', required: false,
                note: 'ข้อความเป้าหมายเพิ่มเติม (ไม่บังคับ)', example: '', width: 26,
            ),
        ];
    }

    protected function prepareRow(array $row): array
    {
        $year     = $this->int($row, 'year');
        $level    = $this->normalizeLevel($this->str($row, 'level'));
        $ref      = $this->str($row, 'indicator_ref');
        $operator = $this->str($row, 'operator');
        $value    = $this->str($row, 'target_value');
        $text     = $this->str($row, 'target_text');
        $periodRaw = $this->str($row, 'period_no');

        $errors = $this->validate(
            ['year' => $year, 'level' => $level, 'indicator_ref' => $ref, 'operator' => $operator, 'target_value' => $value, 'target_text' => $text],
            [
                'year' => ['required', 'integer', 'min:2500', 'max:2700'],
                'level' => ['required', 'in:hospital,province,ministry'],
                'indicator_ref' => ['required', 'string'],
                'operator' => ['required', 'in:gt,gte,lt,lte,ne,eq,passfail'],
                'target_value' => ['nullable', 'numeric'],
                'target_text' => ['nullable', 'string', 'max:191'],
            ],
            ['year' => 'ปี', 'level' => 'ระดับ', 'indicator_ref' => 'ตัวชี้วัด', 'operator' => 'เกณฑ์', 'target_value' => 'ค่าเป้าหมาย'],
        );

        if ($errors) {
            return ['errors' => $errors];
        }

        // เกณฑ์ตัวเลขต้องมีค่าเป้าหมาย
        if ($operator !== 'passfail' && ($value === null || $value === '')) {
            return ['errors' => ['กรุณาระบุค่าเป้าหมายสำหรับเกณฑ์ที่เป็นตัวเลข']];
        }

        [$indicator, $refErr] = $this->resolveRef(
            KpiIndicator::where('year', $year)->where('level', $level),
            $ref, 'ตัวชี้วัด'
        );
        if ($refErr) {
            return ['errors' => [$refErr]];
        }

        $validPeriods = PeriodCalculator::periodNumbers($indicator->period_type);
        $periodNo = null;
        if ($periodRaw !== null) {
            if (! is_numeric($periodRaw) || ! in_array((int) $periodRaw, $validPeriods, true)) {
                return ['errors' => ['ช่วง (period_no) "' . $periodRaw . '" ไม่ถูกต้องสำหรับตัวชี้วัดนี้ (ใช้ได้: ' . implode(', ', $validPeriods) . ')']];
            }
            $periodNo = (int) $periodRaw;
        }

        $payload = [
            'indicator_id' => $indicator->id,
            'period_no' => $periodNo,           // null = ทุกช่วง
            'operator' => $operator,
            'target_value' => ($operator === 'passfail' || $value === null) ? null : (float) $value,
            'target_text' => $text,
        ];

        $dedup = $payload['indicator_id'] . ':' . ($periodNo ?? 'all');

        return ['errors' => [], 'payload' => $payload, 'dedup' => $dedup];
    }

    protected function persist(array $p, ImportResult $result): void
    {
        $indicator = KpiIndicator::find($p['indicator_id']);
        if (! $indicator) {
            return;
        }

        // กันกรณีช่วงเวลายังไม่ถูกสร้าง
        $this->targets->syncPeriods($indicator);

        $query = $indicator->targets();
        if ($p['period_no'] !== null) {
            $query->where('period_no', $p['period_no']);
        }

        foreach ($query->get() as $target) {
            $target->operator = $p['operator'];
            $target->target_value = $p['target_value'];
            $target->target_text = $p['target_text'];
            $target->save();
        }

        $result->updated++;
    }
}

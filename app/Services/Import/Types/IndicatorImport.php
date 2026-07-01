<?php

namespace App\Services\Import\Types;

use App\Models\KpiIndicator;
use App\Models\KpiMain;
use App\Repositories\Contracts\IndicatorRepositoryInterface;
use App\Repositories\Contracts\TargetRepositoryInterface;
use App\Services\Import\AbstractImport;
use App\Services\Import\ColumnDef;
use App\Services\Import\ImportResult;
use App\Support\MeasurementType;

/**
 * นำเข้า "ตัวชี้วัด" (kpi_indicators) — อยู่ใต้ KPI หลัก
 * รองรับแยกนำเข้าแต่ละระดับได้ (คอลัมน์ระดับเป็นของตัวชี้วัดเอง)
 * เมื่อนำเข้าแล้วระบบจะสร้างช่วงเวลาเป้าหมาย (รายปี/รายไตรมาส) ให้อัตโนมัติ
 */
class IndicatorImport extends AbstractImport
{
    public function __construct(
        private readonly TargetRepositoryInterface $targets,
        private readonly IndicatorRepositoryInterface $indicators,
    ) {}

    public function key(): string
    {
        return 'indicators';
    }

    public function label(): string
    {
        return 'ตัวชี้วัด';
    }

    public function description(): string
    {
        return 'นำเข้าตัวชี้วัดภายใต้ KPI หลัก (แยกนำเข้าแต่ละระดับได้) — ต้องนำเข้า KPI หลักก่อน; ระบบจะสร้างช่วงค่าเป้าหมายให้อัตโนมัติ';
    }

    public function order(): int
    {
        return 5;
    }

    public function icon(): string
    {
        return 'indicator';
    }

    public function instructions(): array
    {
        return [
            'คอลัมน์ "KPI หลัก (รหัส/ชื่อ)" ใช้จับคู่ KPI หลักแม่ภายในปี+ระดับที่ระบุ',
            'ประเภทการวัดที่ต้องมีตัวตั้ง/ตัวหาร (percent, rate, average, ratio) ให้กรอก "นิยามตัวตั้ง (A)" และ "นิยามตัวหาร (B)"; rate ต้องกรอก "ค่าคงที่ K" ด้วย',
            'ประเภท level, ranking, index ให้กรอก "สูตร/เกณฑ์" — ฟิลด์ที่ไม่เกี่ยวกับประเภทการวัดที่เลือกจะถูกล้างให้อัตโนมัติ',
            '"ผู้รับผิดชอบ" ไม่บังคับ — กรอก username คั่นด้วย , เว้นว่างได้แล้วไปกำหนดในระบบภายหลัง',
            'หน่วยวัด (unit) กรอกเป็นข้อความ เช่น ร้อยละ, ครั้ง, วัน (ดูรายการได้จากเมนูหน่วยวัด KPI)',
        ];
    }

    public function columns(): array
    {
        $mtAllowed = collect(MeasurementType::META)
            ->map(fn ($m, $code) => "{$code} = {$m['label']}")->implode(', ');

        return [
            $this->yearCol(),
            $this->levelCol(),
            new ColumnDef(
                key: 'main_ref', header: 'KPI หลัก (รหัส/ชื่อ)', required: true,
                note: 'รหัสหรือชื่อ KPI หลักแม่ (ต้องนำเข้า KPI หลักก่อน)', example: 'M1', width: 32,
            ),
            $this->codeCol(example: 'KPI-001'),
            new ColumnDef(
                key: 'name', header: 'ชื่อตัวชี้วัด', required: true,
                note: 'ชื่อตัวชี้วัด', example: 'ร้อยละหญิงตั้งครรภ์ฝากครรภ์ก่อน 12 สัปดาห์', width: 45,
            ),
            new ColumnDef(
                key: 'year_type', header: 'แบบปี', required: true,
                note: 'รูปแบบปีของการเก็บข้อมูล', example: 'fiscal',
                options: array_keys(KpiIndicator::YEAR_TYPES),
                allowed: 'buddhist = ปี พ.ศ. (ม.ค.–ธ.ค.), fiscal = ปีงบประมาณ (ต.ค.–ก.ย.)', width: 14,
            ),
            new ColumnDef(
                key: 'period_type', header: 'รูปแบบการเก็บผล', required: true,
                note: 'เก็บผลรายปีหรือรายไตรมาส', example: 'annual',
                options: array_keys(KpiIndicator::PERIOD_TYPES),
                allowed: 'annual = รายปี (12 เดือน), quarterly = รายไตรมาส (4 ช่วง)', width: 16,
            ),
            new ColumnDef(
                key: 'unit', header: 'หน่วยวัด', required: true,
                note: 'หน่วยวัดเป็นข้อความ', example: 'ร้อยละ', allowed: 'ข้อความไม่เกิน 50 ตัวอักษร', width: 14,
            ),
            new ColumnDef(
                key: 'measurement_type', header: 'ประเภทการวัด', required: true,
                note: 'ประเภทการวัด/การคำนวณ', example: 'percent',
                options: MeasurementType::keys(), allowed: $mtAllowed, width: 16,
            ),
            new ColumnDef(
                key: 'numerator_label', header: 'นิยามตัวตั้ง (A)', required: false,
                note: 'บังคับสำหรับ percent/rate/average/ratio', example: 'จำนวนหญิงตั้งครรภ์ที่ฝากครรภ์ก่อน 12 สัปดาห์', width: 30,
            ),
            new ColumnDef(
                key: 'denominator_label', header: 'นิยามตัวหาร (B)', required: false,
                note: 'บังคับสำหรับ percent/rate/average/ratio', example: 'จำนวนหญิงตั้งครรภ์ทั้งหมด', width: 30,
            ),
            new ColumnDef(
                key: 'formula', header: 'สูตร/เกณฑ์', required: false,
                note: 'บังคับสำหรับ level/ranking/index (กรอกเกณฑ์/สูตรเอง)', example: '', width: 30,
            ),
            new ColumnDef(
                key: 'factor', header: 'ค่าคงที่ K', required: false,
                note: 'บังคับสำหรับ rate เช่น 1000, 100000', example: '1000',
                allowed: 'ตัวเลข', width: 12,
            ),
            $this->descriptionCol(),
            $this->orderbyCol(),
            $this->statusCol(),
            new ColumnDef(
                key: 'owners', header: 'ผู้รับผิดชอบ (username)', required: false,
                note: 'username ของผู้รับผิดชอบ คั่นหลายคนด้วย , (ไม่บังคับ)',
                example: 'somchai', allowed: 'username ที่มีอยู่ในระบบ', width: 24,
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
        $ref    = $this->str($row, 'main_ref');
        $type   = $this->str($row, 'measurement_type');

        // ล้างฟิลด์ที่ไม่เกี่ยวกับประเภทการวัดที่เลือก (เหมือน IndicatorRequest::prepareForValidation)
        $numerator   = MeasurementType::usesField($type, 'a') ? $this->str($row, 'numerator_label') : null;
        $denominator = MeasurementType::usesField($type, 'b') ? $this->str($row, 'denominator_label') : null;
        $formula     = MeasurementType::usesField($type, 'formula') ? $this->str($row, 'formula') : null;
        $factor      = MeasurementType::usesField($type, 'factor') ? $this->str($row, 'factor') : null;

        $needA = implode(',', MeasurementType::typesRequiring('a'));
        $needB = implode(',', MeasurementType::typesRequiring('b'));
        $needF = implode(',', MeasurementType::typesRequiring('formula'));
        $needK = implode(',', MeasurementType::typesRequiring('factor'));

        $errors = $this->validate(
            [
                'year' => $year, 'level' => $level, 'code' => $code, 'name' => $name,
                'year_type' => $this->str($row, 'year_type'), 'period_type' => $this->str($row, 'period_type'),
                'unit' => $this->str($row, 'unit'), 'measurement_type' => $type,
                'numerator_label' => $numerator, 'denominator_label' => $denominator,
                'formula' => $formula, 'factor' => $factor, 'status' => $status, 'main_ref' => $ref,
            ],
            [
                'year' => ['required', 'integer', 'min:2500', 'max:2700'],
                'level' => ['required', 'in:hospital,province,ministry'],
                'main_ref' => ['required', 'string'],
                'code' => ['nullable', 'string', 'max:50'],
                'name' => ['required', 'string', 'max:500'],
                'year_type' => ['required', 'in:buddhist,fiscal'],
                'period_type' => ['required', 'in:annual,quarterly'],
                'unit' => ['required', 'string', 'max:50'],
                'measurement_type' => ['required', \Illuminate\Validation\Rule::in(MeasurementType::keys())],
                'numerator_label' => ['nullable', 'string', 'max:255', "required_if:measurement_type,{$needA}"],
                'denominator_label' => ['nullable', 'string', 'max:255', "required_if:measurement_type,{$needB}"],
                'formula' => ['nullable', 'string', 'max:500', "required_if:measurement_type,{$needF}"],
                'factor' => ['nullable', 'numeric', "required_if:measurement_type,{$needK}"],
                'status' => ['required', 'in:enable,disable'],
            ],
            [
                'year' => 'ปี', 'level' => 'ระดับ', 'main_ref' => 'KPI หลัก', 'name' => 'ชื่อตัวชี้วัด',
                'year_type' => 'แบบปี', 'period_type' => 'รูปแบบการเก็บผล', 'unit' => 'หน่วยวัด',
                'measurement_type' => 'ประเภทการวัด', 'numerator_label' => 'นิยามตัวตั้ง (A)',
                'denominator_label' => 'นิยามตัวหาร (B)', 'formula' => 'สูตร/เกณฑ์', 'factor' => 'ค่าคงที่ K',
            ],
        );

        if ($errors) {
            return ['errors' => $errors];
        }

        [$main, $refErr] = $this->resolveRef(
            KpiMain::where(function ($q) use ($year, $level) {
                $q->whereHas('category.subStrategy.strategy', fn ($s) => $s->where('year', $year)->where('level', $level))
                    ->orWhereHas('category', fn ($c) => $c->whereNull('sub_strategy_id'));
            }),
            $ref, 'KPI หลัก'
        );
        if ($refErr) {
            return ['errors' => [$refErr]];
        }

        // ปีต้องตรงกับปีของยุทธศาสตร์ที่ KPI หลักนี้สังกัด (ถ้าผูกกลยุทธ์ไว้)
        $strategyYear = $main->category?->subStrategy?->strategy?->year;
        if ($strategyYear !== null && (int) $strategyYear !== $year) {
            return ['errors' => ["ปีของตัวชี้วัด ({$year}) ไม่ตรงกับปีของยุทธศาสตร์ที่ KPI หลักนี้สังกัด ({$strategyYear})"]];
        }

        $target = $code !== null
            ? KpiIndicator::where('kpi_main_id', $main->id)->where('code', $code)->first()
            : null;
        $byName = KpiIndicator::where('kpi_main_id', $main->id)->where('name', $name)->first();
        if ($byName && (! $target || $byName->id !== $target->id)) {
            return ['errors' => ["มีชื่อตัวชี้วัด \"{$name}\" อยู่แล้วภายใต้ KPI หลักเดียวกัน"]];
        }

        [$ownerIds, $missing] = $this->resolveUsers($this->str($row, 'owners'));
        if ($missing) {
            return ['errors' => ['ไม่พบ username ผู้รับผิดชอบ: ' . implode(', ', $missing)]];
        }

        $payload = [
            'kpi_main_id' => $main->id,
            'year' => $year,
            'level' => $level,
            'code' => $code,
            'name' => $name,
            'year_type' => $this->str($row, 'year_type'),
            'period_type' => $this->str($row, 'period_type'),
            'unit' => $this->str($row, 'unit'),
            'measurement_type' => $type,
            'numerator_label' => $numerator,
            'denominator_label' => $denominator,
            'formula' => $formula,
            'factor' => ($factor === null ? null : (float) $factor),
            'description' => $this->str($row, 'description'),
            'orderby' => $this->int($row, 'orderby') ?? 0,
            'status' => $status,
            'owner_ids' => $ownerIds,
            'sync_owners' => $this->str($row, 'owners') !== null,
        ];

        $dedup = $code !== null ? "C:{$main->id}:{$code}" : "N:{$main->id}:{$name}";

        return ['errors' => [], 'payload' => $payload, 'dedup' => $dedup];
    }

    protected function persist(array $p, ImportResult $result): void
    {
        $keys = $p['code'] !== null
            ? ['kpi_main_id' => $p['kpi_main_id'], 'code' => $p['code']]
            : ['kpi_main_id' => $p['kpi_main_id'], 'name' => $p['name']];

        $model = KpiIndicator::updateOrCreate($keys, [
            'code' => $p['code'],
            'name' => $p['name'],
            'level' => $p['level'],
            'year' => $p['year'],
            'year_type' => $p['year_type'],
            'period_type' => $p['period_type'],
            'unit' => $p['unit'],
            'measurement_type' => $p['measurement_type'],
            'numerator_label' => $p['numerator_label'],
            'denominator_label' => $p['denominator_label'],
            'formula' => $p['formula'],
            'factor' => $p['factor'],
            'description' => $p['description'],
            'orderby' => $p['orderby'],
            'status' => $p['status'],
        ]);

        if ($p['sync_owners']) {
            $this->indicators->syncOwners($model, $p['owner_ids']);
        }

        // สร้าง/ปรับช่วงเวลาเป้าหมายตามรูปแบบ (รายปี/รายไตรมาส)
        $this->targets->syncPeriods($model);

        $model->wasRecentlyCreated ? $result->created++ : $result->updated++;
    }
}

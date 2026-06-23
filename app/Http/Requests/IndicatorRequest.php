<?php

namespace App\Http\Requests;

use App\Support\MeasurementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class IndicatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // ประเภทที่ "บังคับ" ให้ระบุแต่ละฟิลด์ (อิงเมทาดาทาของ MeasurementType เพื่อไม่ให้หลุดจากกัน)
        $needA = implode(',', MeasurementType::typesRequiring('a'));
        $needB = implode(',', MeasurementType::typesRequiring('b'));
        $needFormula = implode(',', MeasurementType::typesRequiring('formula'));
        $needFactor = implode(',', MeasurementType::typesRequiring('factor'));

        return [
            'kpi_main_id' => ['required', 'integer', 'exists:kpi_mains,id'],
            'level' => ['required', 'in:hospital,province,ministry'],
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:500'],
            'year_type' => ['required', 'in:buddhist,fiscal'],
            'year' => ['required', 'integer', 'min:2500', 'max:2700'],
            'period_type' => ['required', 'in:annual,quarterly'],
            'unit' => ['nullable', 'string', 'max:50'],
            'measurement_type' => ['nullable', Rule::in(MeasurementType::keys())],
            'numerator_label' => ['nullable', 'string', 'max:255', "required_if:measurement_type,{$needA}"],
            'denominator_label' => ['nullable', 'string', 'max:255', "required_if:measurement_type,{$needB}"],
            'formula' => ['nullable', 'string', 'max:500', "required_if:measurement_type,{$needFormula}"],
            'factor' => ['nullable', 'numeric', "required_if:measurement_type,{$needFactor}"],
            'description' => ['nullable', 'string'],
            'orderby' => ['nullable', 'integer'],
            'status' => ['required', 'in:enable,disable'],
            'owners' => ['required', 'array', 'min:1'],
            'owners.*' => ['integer', 'exists:users,id'],
            'primary_owner' => ['nullable', 'integer'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $primary = $this->input('primary_owner');
            $owners = (array) $this->input('owners', []);
            if ($primary && ! in_array((int) $primary, array_map('intval', $owners), true)) {
                $v->errors()->add('primary_owner', 'ผู้รับผิดชอบหลักต้องเป็นหนึ่งในผู้รับผิดชอบที่เลือก');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'kpi_main_id' => 'KPI หลัก',
            'level' => 'ระดับ',
            'name' => 'ชื่อตัวชี้วัด',
            'year_type' => 'แบบปี',
            'year' => 'ปี',
            'period_type' => 'รูปแบบการเก็บผลงาน',
            'measurement_type' => 'ประเภทการวัด',
            'numerator_label' => 'นิยามตัวตั้ง (A)',
            'denominator_label' => 'นิยามตัวหาร (B)',
            'formula' => 'สูตร/เกณฑ์การคำนวณ',
            'factor' => 'ค่าคงที่ K',
            'owners' => 'ผู้รับผิดชอบ',
        ];
    }

    public function messages(): array
    {
        return [
            'owners.required' => 'ต้องเลือกผู้รับผิดชอบอย่างน้อย 1 คน',
            'owners.min' => 'ต้องเลือกผู้รับผิดชอบอย่างน้อย 1 คน',
        ];
    }

    protected function prepareForValidation(): void
    {
        $type = $this->input('measurement_type') ?: null;

        // ล้างค่าฟิลด์ที่ไม่เกี่ยวข้องกับประเภทการวัดที่เลือก (กันข้อมูลค้างจากการสลับประเภทในฟอร์ม)
        $clear = [];
        foreach (['a' => 'numerator_label', 'b' => 'denominator_label', 'formula' => 'formula', 'factor' => 'factor'] as $field => $column) {
            if (! MeasurementType::usesField($type, $field)) {
                $clear[$column] = null;
            }
        }

        $this->merge(array_merge([
            'measurement_type' => $type,
            'orderby' => $this->orderby ?: 0,
            'status' => $this->status ?: 'enable',
        ], $clear));
    }
}

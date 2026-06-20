<?php

namespace App\Http\Requests;

use App\Models\KpiUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ตรวจสิทธิ์ผ่าน middleware (เฉพาะผู้ดูแลระบบสูงสุด) แล้ว
    }

    public function rules(): array
    {
        return [
            'group_code' => ['required', Rule::in(array_keys(KpiUnit::GROUPS))],
            // ชื่อหน่วยวัดห้ามซ้ำทั้งระบบ (ค่าที่เก็บใน dropdown ต้องไม่กำกวม)
            // มองข้ามแถวที่ถูกลบแบบ soft delete — ชื่อที่เคยลบสามารถนำกลับมาสร้างใหม่ได้
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('kpi_units', 'name')
                    ->whereNull('deleted_at')
                    ->ignore($this->route('unit')),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'orderby' => ['nullable', 'integer'],
            'status' => ['required', 'in:enable,disable'],
        ];
    }

    public function attributes(): array
    {
        return [
            'group_code' => 'กลุ่ม KPI',
            'name' => 'หน่วยวัด',
            'description' => 'คำอธิบาย',
            'orderby' => 'ลำดับ',
            'status' => 'สถานะ',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'มีหน่วยวัดนี้อยู่แล้วในระบบ',
            'group_code.in' => 'กลุ่ม KPI ไม่ถูกต้อง',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'orderby' => $this->orderby ?: 0,
            'status' => $this->status ?: 'enable',
        ]);
    }
}

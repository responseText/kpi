<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StrategyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ตรวจสิทธิ์ผ่าน middleware 'menu:' แล้ว
    }

    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2500', 'max:2700'],
            'level' => ['required', 'in:hospital,province,ministry'],
            'code' => ['nullable', 'string', 'max:50'],
            // ชื่อยุทธศาสตร์ห้ามซ้ำภายในปีและระดับเดียวกัน — ต่างระดับ/ต่างปี ซ้ำได้
            'name' => [
                'required', 'string', 'max:500',
                Rule::unique('kpi_strategies', 'name')
                    ->where(fn ($q) => $q
                        ->where('year', $this->input('year'))
                        ->where('level', $this->input('level'))
                        ->whereNull('deleted_at'))
                    ->ignore($this->route('strategy')),
            ],
            'description' => ['nullable', 'string'],
            'orderby' => ['nullable', 'integer'],
            'status' => ['required', 'in:enable,disable'],
        ];
    }

    public function attributes(): array
    {
        return [
            'year' => 'ปี พ.ศ.',
            'level' => 'ระดับตัวชี้วัด',
            'code' => 'รหัสยุทธศาสตร์',
            'name' => 'ชื่อยุทธศาสตร์',
            'description' => 'รายละเอียด',
            'orderby' => 'ลำดับ',
            'status' => 'สถานะ',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'มีชื่อยุทธศาสตร์นี้อยู่แล้วในปีและระดับตัวชี้วัดเดียวกัน (ชื่อซ้ำได้เฉพาะเมื่ออยู่คนละระดับ)',
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

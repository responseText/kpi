<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'orderby' => ['nullable', 'integer'],
            'status' => ['required', 'in:enable,disable'],
        ];
    }

    public function attributes(): array
    {
        return [
            'year' => 'ปี พ.ศ.',
            'code' => 'รหัสยุทธศาสตร์',
            'name' => 'ชื่อยุทธศาสตร์',
            'description' => 'รายละเอียด',
            'orderby' => 'ลำดับ',
            'status' => 'สถานะ',
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

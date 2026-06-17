<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubStrategyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'strategy_id' => ['required', 'integer', 'exists:kpi_strategies,id'],
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'orderby' => ['nullable', 'integer'],
            'status' => ['required', 'in:enable,disable'],
            'reviewers' => ['required', 'array', 'min:1'],
            'reviewers.*' => ['integer', 'exists:users,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'strategy_id' => 'ยุทธศาสตร์',
            'name' => 'ชื่อกลยุทธ์',
            'reviewers' => 'ผู้ตรวจสอบ',
        ];
    }

    public function messages(): array
    {
        return [
            'reviewers.required' => 'ต้องเลือกผู้ตรวจสอบอย่างน้อย 1 คน',
            'reviewers.min' => 'ต้องเลือกผู้ตรวจสอบอย่างน้อย 1 คน',
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

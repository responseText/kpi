<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sub_strategy_id' => ['nullable', 'integer', 'exists:kpi_sub_strategies,id'],
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
            'sub_strategy_id' => 'กลยุทธ์',
            'name' => 'ชื่อหมวด KPI',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sub_strategy_id' => $this->sub_strategy_id ?: null,
            'orderby' => $this->orderby ?: 0,
            'status' => $this->status ?: 'enable',
        ]);
    }
}

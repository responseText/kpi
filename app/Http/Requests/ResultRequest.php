<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'results' => ['required', 'array', 'min:1'],
            'results.*.result_value' => ['nullable', 'numeric'],
            'results.*.numerator_value' => ['nullable', 'numeric'],
            'results.*.denominator_value' => ['nullable', 'numeric'],
            'results.*.result_text' => ['nullable', 'in:pass,fail'],
            'results.*.note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

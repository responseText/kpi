<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LevelManagerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'level' => ['required', 'in:hospital,province,ministry'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['required', 'in:responsible,definer'],
            'year' => ['nullable', 'integer', 'min:2500', 'max:2700'],
        ];
    }

    public function attributes(): array
    {
        return [
            'level' => 'ระดับ',
            'user_id' => 'ผู้ใช้',
            'role' => 'บทบาท',
            'year' => 'ปี',
        ];
    }
}

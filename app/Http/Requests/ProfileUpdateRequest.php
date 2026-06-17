<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * แก้ไขข้อมูลส่วนตัวของผู้ใช้ที่ล็อกอินอยู่
 */
class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'nullable', 'email', 'max:191',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'อีเมล',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'อีเมลนี้ถูกใช้งานแล้ว',
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
        ];
    }
}

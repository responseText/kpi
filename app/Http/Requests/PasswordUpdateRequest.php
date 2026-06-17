<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * เปลี่ยนรหัสผ่านของตัวเอง — ต้องยืนยันรหัสผ่านปัจจุบันก่อน
 */
class PasswordUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    public function attributes(): array
    {
        return [
            'current_password' => 'รหัสผ่านปัจจุบัน',
            'password' => 'รหัสผ่านใหม่',
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.current_password' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง',
            'password.confirmed' => 'การยืนยันรหัสผ่านใหม่ไม่ตรงกัน',
            'password.min' => 'รหัสผ่านใหม่ต้องมีอย่างน้อย :min ตัวอักษร',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'targets' => ['required', 'array', 'min:1'],
            'targets.*.operator' => ['required', 'in:gt,gte,lt,lte,ne,eq,passfail'],
            'targets.*.target_value' => ['nullable', 'numeric'],
            'targets.*.target_text' => ['nullable', 'string', 'max:191'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            foreach ((array) $this->input('targets', []) as $periodNo => $row) {
                $op = $row['operator'] ?? null;
                // เกณฑ์ตัวเลขต้องมีค่าเป้าหมาย
                if ($op && $op !== 'passfail' && ($row['target_value'] ?? '') === '') {
                    $v->errors()->add("targets.$periodNo.target_value", 'กรุณาระบุค่าเป้าหมาย');
                }
            }
        });
    }
}

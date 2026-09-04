<?php

namespace Modules\Workflow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApprovalPolicyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'entity_type' => ['nullable', 'string', 'max:80'],
            'steps' => ['nullable'],
            'is_active' => ['boolean'],
        ];
    }
}

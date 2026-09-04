<?php

namespace Modules\Workflow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAutomationRuleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'event' => ['nullable', 'string', 'max:120'],
            'conditions' => ['nullable'],
            'actions' => ['nullable'],
            'is_active' => ['boolean'],
        ];
    }
}

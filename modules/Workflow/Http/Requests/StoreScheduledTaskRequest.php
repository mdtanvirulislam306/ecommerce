<?php

namespace Modules\Workflow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduledTaskRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'cron' => ['required', 'string', 'max:80'],
            'handler' => ['nullable', 'string', 'max:120'],
            'is_active' => ['boolean'],
        ];
    }
}

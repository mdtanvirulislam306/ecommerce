<?php

namespace Modules\Workflow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkflowLogRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'source' => ['nullable', 'string', 'max:80'],
            'message' => ['required', 'string', 'max:500'],
            'level' => ['required', 'string', 'max:20'],
            'context' => ['nullable'],
        ];
    }
}

<?php

namespace Modules\Platform\Http\Requests;

use App\Core\Module\ModuleManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_platform_admin;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => strtolower(trim($this->input('code')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/', Rule::unique('plans', 'code')],
            'description' => ['nullable', 'string', 'max:2000'],
            'price_monthly' => ['required', 'integer', 'min:0', 'max:100000000'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
            'module_codes' => ['array'],
            'module_codes.*' => ['string', Rule::in(array_keys(app(ModuleManager::class)->all()))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => 'Use lowercase letters, numbers and dashes only, like "growth" or "pro-yearly".',
            'code.unique' => 'Another plan already uses this code.',
            'module_codes.*.in' => 'One of the chosen modules does not exist.',
        ];
    }
}

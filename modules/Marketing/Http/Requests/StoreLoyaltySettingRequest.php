<?php

namespace Modules\Marketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoyaltySettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'settings' => $this->input('settings') ?: null,
            'is_active' => $this->boolean('is_active', true),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'points_per_currency' => ['required', 'numeric', 'min:0'],
            'redemption_rate' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'settings' => ['nullable', 'array'],
        ];
    }
}

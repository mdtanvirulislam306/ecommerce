<?php

namespace Modules\Billing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_platform_admin;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price_monthly' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'module_codes' => ['array'],
            'module_codes.*' => ['string', 'max:60'],
        ];
    }
}

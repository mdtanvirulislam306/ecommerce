<?php

namespace Modules\Ecommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCheckoutSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'guest_checkout' => $this->boolean('guest_checkout'),
            'require_phone' => $this->boolean('require_phone'),
            'payment_cod_enabled' => $this->boolean('payment_cod_enabled'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'guest_checkout' => ['boolean'],
            'require_phone' => ['boolean'],
            'payment_cod_enabled' => ['boolean'],
        ];
    }
}

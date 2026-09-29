<?php

namespace Modules\Ecommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShippingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'shipping_enabled' => $this->boolean('shipping_enabled'),
            'free_shipping_threshold' => $this->input('free_shipping_threshold') ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'shipping_enabled' => ['boolean'],
            'zones' => ['required_if:shipping_enabled,true', 'array', 'max:20'],
            'zones.*.name' => ['required', 'string', 'max:60', 'distinct:ignore_case'],
            'zones.*.rate' => ['required', 'numeric', 'min:0', 'max:100000'],
            'free_shipping_threshold' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'zones.required_if' => 'Add at least one delivery area.',
            'zones.*.name.required' => 'Give this area a name.',
            'zones.*.name.distinct' => 'Each area needs a different name.',
            'zones.*.rate.required' => 'Set a delivery charge.',
        ];
    }
}

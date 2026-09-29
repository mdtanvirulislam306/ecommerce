<?php

namespace Modules\Ecommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Ecommerce\Services\PaymentSettingService;

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
            'payment_online_enabled' => $this->boolean('payment_online_enabled'),
            'sslcommerz_sandbox' => $this->boolean('sslcommerz_sandbox'),
            'sslcommerz_store_id' => trim((string) $this->input('sslcommerz_store_id')) ?: null,
            'sslcommerz_store_password' => $this->input('sslcommerz_store_password') ?: null,
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
            'payment_online_enabled' => ['boolean'],
            'sslcommerz_sandbox' => ['boolean'],
            'sslcommerz_store_id' => ['nullable', 'required_if_accepted:payment_online_enabled', 'string', 'max:100'],
            'sslcommerz_store_password' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->boolean('payment_cod_enabled') && ! $this->boolean('payment_online_enabled')) {
                    $validator->errors()->add('payment_cod_enabled', 'Keep at least one way for customers to pay.');
                }

                if ($this->boolean('payment_online_enabled')
                    && blank($this->input('sslcommerz_store_password'))
                    && ! app(PaymentSettingService::class)->hasStorePassword()) {
                    $validator->errors()->add('sslcommerz_store_password', 'Enter the store password from your SSLCommerz merchant panel.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sslcommerz_store_id.required_if_accepted' => 'Enter the store ID from your SSLCommerz merchant panel.',
        ];
    }
}

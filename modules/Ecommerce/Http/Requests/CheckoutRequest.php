<?php

namespace Modules\Ecommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Ecommerce\Enums\PaymentMethod;
use Modules\Ecommerce\Services\DeliveryRateService;
use Modules\Ecommerce\Services\PaymentSettingService;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'customer_email' => $this->input('customer_email') ?: null,
            'customer_phone' => $this->input('customer_phone') ?: null,
            'notes' => $this->input('notes') ?: null,
            'delivery_zone' => $this->input('delivery_zone') ?: null,
            'payment_method' => $this->input('payment_method')
                ?: (app(PaymentSettingService::class)->enabledMethodCodes()[0] ?? PaymentMethod::Cod->value),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $delivery = app(DeliveryRateService::class);
        $payments = app(PaymentSettingService::class);
        $zoneCodes = array_column($delivery->zones(), 'code');

        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => [
                Rule::requiredIf(fn () => $payments->requiresPhone() || $this->input('payment_method') === PaymentMethod::Online->value),
                'nullable',
                'string',
                'max:40',
            ],
            'shipping_address' => ['required', 'string', 'max:1000'],
            'delivery_zone' => $delivery->enabled()
                ? [count($zoneCodes) > 1 ? 'required' : 'nullable', Rule::in($zoneCodes)]
                : ['nullable'],
            'payment_method' => ['required', Rule::in($payments->enabledMethodCodes())],
            'notes' => ['nullable', 'string', 'max:2000'],
            'address_line' => ['nullable', 'string', 'max:1000'],
            'district' => ['nullable', 'string', 'max:80'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_phone.required' => 'Please add a phone number so we can reach you about delivery.',
            'delivery_zone.required' => 'Please choose a delivery area.',
            'delivery_zone.in' => 'Please choose a delivery area.',
            'payment_method.in' => 'Please choose how you would like to pay.',
        ];
    }
}

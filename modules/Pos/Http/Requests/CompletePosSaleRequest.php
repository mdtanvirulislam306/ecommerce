<?php

namespace Modules\Pos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Pos\Enums\PosPaymentMethod;

class CompletePosSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->map(fn ($item) => [
                ...$item,
                'product_variant_id' => ($item['product_variant_id'] ?? null) ?: null,
                'discount_percent' => $item['discount_percent'] ?? 0,
            ])
            ->all();

        $paymentMethod = $this->input('payment_method') ?: PosPaymentMethod::Cash->value;

        $this->merge([
            'pos_register_id' => $this->input('pos_register_id') ?: null,
            'customer_id' => $this->input('customer_id') ?: null,
            'customer_name' => $this->input('customer_name') ?: 'Walk-in',
            'payment_method' => $paymentMethod,
            'payment_reference' => $paymentMethod === PosPaymentMethod::Cash->value
                ? null
                : ($this->input('payment_reference') ?: null),
            'cart_discount_amount' => $this->input('cart_discount_amount') ?: 0,
            'notes' => $this->input('notes') ?: null,
            'items' => $items,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $requiresReference = $this->input('payment_method')
            && $this->input('payment_method') !== PosPaymentMethod::Cash->value;

        return [
            'pos_register_id' => ['nullable', 'integer', 'exists:pos_registers,id'],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where('is_active', true)],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', Rule::enum(PosPaymentMethod::class)],
            'payment_reference' => [$requiresReference ? 'required' : 'nullable', 'string', 'max:120'],
            'amount_tendered' => ['nullable', 'numeric', 'min:0'],
            'cart_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}

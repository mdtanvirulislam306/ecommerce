<?php

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Sales\Enums\SalesOrderStatus;

class StoreSalesOrderRequest extends FormRequest
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
            ])
            ->all();

        $this->merge([
            'customer_group_id' => $this->input('customer_group_id') ?: null,
            'warehouse_id' => $this->input('warehouse_id') ?: null,
            'customer_email' => $this->input('customer_email') ?: null,
            'customer_phone' => $this->input('customer_phone') ?: null,
            'items' => $items,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'customer_group_id' => ['nullable', 'integer', 'exists:customer_groups,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::enum(SalesOrderStatus::class)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ];
    }
}

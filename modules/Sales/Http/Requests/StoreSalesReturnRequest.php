<?php

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesReturnRequest extends FormRequest
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
                'product_id' => ($item['product_id'] ?? null) ?: null,
                'product_variant_id' => ($item['product_variant_id'] ?? null) ?: null,
            ])
            ->all();

        $this->merge([
            'sales_invoice_id' => $this->input('sales_invoice_id') ?: null,
            'sales_order_id' => $this->input('sales_order_id') ?: null,
            'warehouse_id' => $this->input('warehouse_id') ?: null,
            'confirm' => filter_var($this->input('confirm'), FILTER_VALIDATE_BOOLEAN),
            'items' => $items,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sales_invoice_id' => ['nullable', 'integer', 'exists:sales_invoices,id', 'required_without:sales_order_id'],
            'sales_order_id' => ['nullable', 'integer', 'exists:sales_orders,id', 'required_without:sales_invoice_id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'confirm' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.sku' => ['nullable', 'string', 'max:80'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}

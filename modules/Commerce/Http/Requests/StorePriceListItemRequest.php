<?php

namespace Modules\Commerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Commerce\Models\PriceList;
use Modules\Commerce\Models\PriceListItem;

class StorePriceListItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'product_variant_id' => $this->input('product_variant_id') ?: null,
            'min_quantity' => $this->input('min_quantity') ?? 1,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var PriceList $priceList */
        $priceList = $this->route('priceList');

        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'min_quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var PriceList $priceList */
            $priceList = $this->route('priceList');
            $productId = $this->input('product_id');
            $variantId = $this->input('product_variant_id');
            $minQty = $this->input('min_quantity');

            $exists = PriceListItem::query()
                ->where('price_list_id', $priceList->id)
                ->where('min_quantity', $minQty)
                ->when(
                    $variantId,
                    fn ($q) => $q->where('product_variant_id', $variantId),
                    fn ($q) => $q->where('product_id', $productId)->whereNull('product_variant_id'),
                )
                ->exists();

            if ($exists) {
                $validator->errors()->add('price', 'This product already has a price for this quantity tier.');
            }
        });
    }
}

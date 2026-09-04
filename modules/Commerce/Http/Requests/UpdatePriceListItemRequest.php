<?php

namespace Modules\Commerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Commerce\Models\PriceList;
use Modules\Commerce\Models\PriceListItem;

class UpdatePriceListItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'price' => ['required', 'numeric', 'min:0'],
            'min_quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var PriceList $priceList */
            $priceList = $this->route('priceList');
            /** @var PriceListItem $item */
            $item = $this->route('item');
            $minQty = (int) $this->input('min_quantity');

            if ($item->min_quantity === $minQty) {
                return;
            }

            $exists = PriceListItem::query()
                ->where('price_list_id', $priceList->id)
                ->where('id', '!=', $item->id)
                ->where('min_quantity', $minQty)
                ->when(
                    $item->product_variant_id,
                    fn ($query) => $query->where('product_variant_id', $item->product_variant_id),
                    fn ($query) => $query->where('product_id', $item->product_id)->whereNull('product_variant_id'),
                )
                ->exists();

            if ($exists) {
                $validator->errors()->add('min_quantity', 'This product already has a price for this quantity tier.');
            }
        });
    }
}

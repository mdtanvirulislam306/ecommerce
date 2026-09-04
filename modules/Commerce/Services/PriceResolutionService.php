<?php

namespace Modules\Commerce\Services;

use App\Core\Contracts\PriceResolver;
use App\Core\Support\Service;
use Modules\Commerce\Models\CustomerGroup;
use Modules\Commerce\Models\PriceList;
use Modules\Commerce\Models\PriceListItem;

class PriceResolutionService extends Service implements PriceResolver
{
    public function resolve(
        int $productId,
        int $quantity = 1,
        ?int $productVariantId = null,
        ?int $customerGroupId = null,
        ?int $priceListId = null,
    ): array {
        $quantity = max(1, $quantity);

        $customerGroup = null;
        if ($customerGroupId) {
            $customerGroup = CustomerGroup::query()->find($customerGroupId);
            if ($customerGroup && $priceListId === null) {
                $priceListId = $customerGroup->price_list_id;
            }
        }

        $priceList = $this->resolvePriceList($priceListId);

        if ($priceList === null) {
            return $this->unresolved(
                customerGroup: $customerGroup,
                message: 'No active price list found.',
            );
        }

        $item = $this->findBestItem($priceList->id, $productId, $productVariantId, $quantity);

        if ($item === null) {
            return $this->unresolved(
                customerGroup: $customerGroup,
                priceList: $priceList,
                message: 'No matching price tier for this product and quantity.',
            );
        }

        return [
            'resolved' => true,
            'price' => (string) $item->price,
            'currency' => $priceList->currency,
            'price_list_id' => $priceList->id,
            'price_list_name' => $priceList->name,
            'price_list_item_id' => $item->id,
            'min_quantity' => $item->min_quantity,
            'customer_group_id' => $customerGroup?->id,
            'customer_group_name' => $customerGroup?->name,
            'message' => null,
        ];
    }

    private function resolvePriceList(?int $priceListId): ?PriceList
    {
        if ($priceListId) {
            return PriceList::query()
                ->where('id', $priceListId)
                ->where('is_active', true)
                ->first();
        }

        return PriceList::query()
            ->where('is_active', true)
            ->where('is_default', true)
            ->first()
            ?? PriceList::query()->where('is_active', true)->orderBy('sort_order')->first();
    }

    private function findBestItem(
        int $priceListId,
        int $productId,
        ?int $productVariantId,
        int $quantity,
    ): ?PriceListItem {
        $query = PriceListItem::query()
            ->where('price_list_id', $priceListId)
            ->where('min_quantity', '<=', $quantity)
            ->orderByDesc('min_quantity');

        if ($productVariantId) {
            $variantMatch = (clone $query)
                ->where('product_variant_id', $productVariantId)
                ->first();

            if ($variantMatch) {
                return $variantMatch;
            }
        }

        return $query
            ->where('product_id', $productId)
            ->whereNull('product_variant_id')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function unresolved(
        ?CustomerGroup $customerGroup = null,
        ?PriceList $priceList = null,
        ?string $message = null,
    ): array {
        return [
            'resolved' => false,
            'price' => null,
            'currency' => $priceList?->currency,
            'price_list_id' => $priceList?->id,
            'price_list_name' => $priceList?->name,
            'price_list_item_id' => null,
            'min_quantity' => null,
            'customer_group_id' => $customerGroup?->id,
            'customer_group_name' => $customerGroup?->name,
            'message' => $message,
        ];
    }
}

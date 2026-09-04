<?php

namespace App\Core\Contracts;

interface PriceResolver
{
    /**
     * Resolve unit price from price lists (customer group → list → tier by quantity).
     *
     * @return array{
     *     resolved: bool,
     *     price: ?string,
     *     currency: ?string,
     *     price_list_id: ?int,
     *     price_list_name: ?string,
     *     price_list_item_id: ?int,
     *     min_quantity: ?int,
     *     customer_group_id: ?int,
     *     customer_group_name: ?string,
     *     message: ?string
     * }
     */
    public function resolve(
        int $productId,
        int $quantity = 1,
        ?int $productVariantId = null,
        ?int $customerGroupId = null,
        ?int $priceListId = null,
    ): array;
}

<?php

namespace App\Core\Contracts;

interface StockAvailability
{
    /**
     * Available quantity for a sellable SKU at a warehouse (or all warehouses when null).
     *
     * @return array{
     *     available: string,
     *     on_hand: string,
     *     reserved: string,
     *     warehouse_id: ?int,
     *     product_id: int,
     *     product_variant_id: ?int
     * }
     */
    public function available(
        int $productId,
        ?int $productVariantId = null,
        ?int $warehouseId = null,
    ): array;

    /**
     * Whether the requested quantity can be fulfilled.
     */
    public function canFulfill(
        int $productId,
        int|float $quantity,
        ?int $productVariantId = null,
        ?int $warehouseId = null,
    ): bool;

    /**
     * Reduce on-hand stock for a sale/fulfillment (writes a stock movement).
     */
    public function fulfill(
        int $productId,
        float $quantity,
        ?int $productVariantId = null,
        ?int $warehouseId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null,
    ): void;

    /**
     * Return stock after a cancelled sale (writes a stock movement).
     */
    public function restock(
        int $productId,
        float $quantity,
        ?int $productVariantId = null,
        ?int $warehouseId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null,
    ): void;

    /**
     * Increase on-hand from a purchase receive (writes a stock movement).
     */
    public function receive(
        int $productId,
        float $quantity,
        ?int $productVariantId = null,
        ?int $warehouseId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null,
    ): void;
}

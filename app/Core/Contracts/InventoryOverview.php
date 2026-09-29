<?php

namespace App\Core\Contracts;

interface InventoryOverview
{
    /**
     * Warehouse and stock figures, using the same rules as the Inventory overview.
     *
     * @return array{
     *     warehouses: int,
     *     skus_tracked: int,
     *     on_hand_total: string,
     *     low_stock: int,
     *     out_of_stock: int,
     *     movements_today: int
     * }
     */
    public function snapshot(): array;

    /**
     * Stock rows at or below their reorder point, excluding zero on-hand.
     *
     * @return list<array{
     *     id: int,
     *     product_name: string,
     *     sku: ?string,
     *     warehouse_name: string,
     *     on_hand: string,
     *     reorder_point: string
     * }>
     */
    public function lowStockItems(int $limit = 6): array;
}

<?php

namespace App\Core\Support;

use App\Core\Contracts\InventoryOverview;

/**
 * Used when the Inventory module is not registered. Keeps the owner dashboard renderable.
 */
final class EmptyInventoryOverview implements InventoryOverview
{
    public function snapshot(): array
    {
        return [
            'warehouses' => 0,
            'skus_tracked' => 0,
            'on_hand_total' => '0.0000',
            'low_stock' => 0,
            'out_of_stock' => 0,
            'movements_today' => 0,
        ];
    }

    public function lowStockItems(int $limit = 6): array
    {
        return [];
    }
}

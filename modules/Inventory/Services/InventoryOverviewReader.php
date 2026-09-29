<?php

namespace Modules\Inventory\Services;

use App\Core\Contracts\InventoryOverview;

/**
 * Adapts Inventory stock stats for cross-module readers.
 */
final class InventoryOverviewReader implements InventoryOverview
{
    public function __construct(private readonly StockService $stock) {}

    public function snapshot(): array
    {
        return $this->stock->overviewStats();
    }

    public function lowStockItems(int $limit = 6): array
    {
        $limit = max(1, min($limit, 10));

        return collect($this->stock->listLevels(stockFilter: 'low', perPage: 10)->items())
            ->take($limit)
            ->map(fn (array $level): array => [
                'id' => $level['id'],
                'product_name' => $level['product_name'],
                'sku' => $level['sku'],
                'warehouse_name' => $level['warehouse_name'],
                'on_hand' => $this->quantity($level['on_hand']),
                'reorder_point' => $this->quantity($level['reorder_point']),
            ])
            ->values()
            ->all();
    }

    private function quantity(mixed $value): string
    {
        $trimmed = rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');

        return $trimmed === '' ? '0' : $trimmed;
    }
}

<?php

namespace App\Core\Support;

use App\Core\Contracts\SalesOverview;

/**
 * Used when the Sales module is not registered. Keeps the owner dashboard renderable.
 */
final class EmptySalesOverview implements SalesOverview
{
    public function snapshot(): array
    {
        return [
            'draft' => 0,
            'pending' => 0,
            'confirmed' => 0,
            'cancelled' => 0,
            'revenue' => '0.00',
            'orders' => 0,
        ];
    }

    public function recentOrders(int $limit = 6): array
    {
        return [];
    }

    public function recentOpenOrders(int $limit = 6): array
    {
        return [];
    }

    public function unpaidInvoiceCount(): ?int
    {
        return null;
    }

    public function quotationConversionRate(): ?string
    {
        return null;
    }
}

<?php

namespace App\Core\Support;

use App\Core\Contracts\SalesManagerOverview;

/**
 * Used when the Sales module is not registered or is disabled for the shop.
 */
final class EmptySalesManagerOverview implements SalesManagerOverview
{
    public function snapshot(): array
    {
        return [
            'orders' => [
                'draft' => 0,
                'pending' => 0,
                'confirmed' => 0,
                'cancelled' => 0,
                'open' => 0,
            ],
            'revenue' => '0.00',
            'invoices' => [
                'count' => 0,
                'outstanding' => '0.00',
                'due' => 0,
                'partial' => 0,
                'overdue' => 0,
                'paid' => 0,
            ],
            'conversion' => [
                'total' => 0,
                'converted' => 0,
                'rate' => null,
            ],
        ];
    }

    public function recentOrders(int $limit = 6): array
    {
        return [];
    }

    public function unpaidInvoices(int $limit = 6): array
    {
        return [];
    }
}

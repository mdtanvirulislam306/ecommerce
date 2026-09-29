<?php

namespace Modules\Sales\Services;

use App\Core\Contracts\SalesOverview;

/**
 * Adapts the Sales order overview for cross-module readers.
 */
final class SalesOverviewReader implements SalesOverview
{
    public function __construct(private readonly SalesOrderService $orders) {}

    /**
     * Open orders are draft, pending, and confirmed. Cancelled stays on the Sales overview card only.
     */
    public function snapshot(): array
    {
        $stats = $this->orders->overviewStats();

        return [
            ...$stats,
            'orders' => $stats['draft'] + $stats['pending'] + $stats['confirmed'],
        ];
    }

    public function recentOrders(int $limit = 6): array
    {
        $limit = max(1, min($limit, 10));

        return array_values(array_slice(
            $this->orders->listPaginated(perPage: 10)->items(),
            0,
            $limit,
        ));
    }
}

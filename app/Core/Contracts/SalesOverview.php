<?php

namespace App\Core\Contracts;

interface SalesOverview
{
    /**
     * Confirmed revenue plus open order counts from the Sales overview.
     * `orders` is draft, pending, and confirmed. Cancelled orders are excluded.
     *
     * @return array{
     *     draft: int,
     *     pending: int,
     *     confirmed: int,
     *     cancelled: int,
     *     revenue: string,
     *     orders: int
     * }
     */
    public function snapshot(): array;

    /**
     * Latest orders in the Sales list shape.
     *
     * @return list<array<string, mixed>>
     */
    public function recentOrders(int $limit = 6): array;
}

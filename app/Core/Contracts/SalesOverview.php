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

    /**
     * Latest draft, pending, and confirmed orders. Cancelled orders are excluded.
     *
     * @return list<array{
     *     id: int,
     *     number: string,
     *     customer_name: string,
     *     status: string,
     *     status_label: string,
     *     currency: string,
     *     grand_total: string,
     *     created_at: string|null
     * }>
     */
    public function recentOpenOrders(int $limit = 6): array;

    /**
     * Outstanding invoice balance from the Sales report (`sum(amount_due)`).
     * Null when Sales does not expose receivables.
     */
    public function unpaidReceivables(): ?string;

    /**
     * Share of quotations linked to an order by convert-to-order, from 0 to 1.
     * Null when there are no quotations.
     */
    public function quotationConversion(): ?float;
}

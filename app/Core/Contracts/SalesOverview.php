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
     * Latest draft and pending orders. Confirmed and cancelled are excluded.
     *
     * Snapshot `orders` still includes confirmed for the owner dashboard.
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
     * Due, partial, and overdue invoice count from the Sales report.
     * Null when Sales does not expose invoice totals.
     */
    public function unpaidInvoiceCount(): ?int;

    /**
     * Quotations linked to an order, as a whole-number percent such as "42%".
     * Null when there are no quotations.
     */
    public function quotationConversionRate(): ?string;
}

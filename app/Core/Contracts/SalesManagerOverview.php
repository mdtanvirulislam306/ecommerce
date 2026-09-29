<?php

namespace App\Core\Contracts;

interface SalesManagerOverview
{
    /**
     * Sales Manager KPIs from existing Sales services.
     *
     * Orders `open` is draft + pending + confirmed. Cancelled is reported separately.
     * Revenue is confirmed sales-order grand totals (`SalesOrderService::overviewStats`).
     * Invoice outstanding is the Sales report sum of `amount_due`.
     * Conversion counts quotations with `sales_order_id` set by convert-to-order.
     *
     * @return array{
     *     orders: array{draft: int, pending: int, confirmed: int, cancelled: int, open: int},
     *     revenue: string,
     *     invoices: array{count: int, outstanding: string, due: int, partial: int, overdue: int, paid: int},
     *     conversion: array{total: int, converted: int, rate: string|null}
     * }
     */
    public function snapshot(): array;

    /**
     * Latest orders in the Sales list shape, including cancelled.
     *
     * @return list<array<string, mixed>>
     */
    public function recentOrders(int $limit = 6): array;

    /**
     * Due, partial, and overdue invoices from the Sales invoice picker.
     *
     * @return list<array{id: int, number: string, customer_name: string, amount_due: string, currency: string}>
     */
    public function unpaidInvoices(int $limit = 6): array;
}

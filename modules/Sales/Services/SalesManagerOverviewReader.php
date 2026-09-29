<?php

namespace Modules\Sales\Services;

use App\Core\Contracts\SalesManagerOverview;

/**
 * Sales Manager KPIs composed from Sales order, report, quotation, and invoice services.
 */
final class SalesManagerOverviewReader implements SalesManagerOverview
{
    public function __construct(
        private readonly SalesOrderService $orders,
        private readonly SalesReportService $reports,
        private readonly QuotationService $quotations,
        private readonly InvoiceService $invoices,
    ) {}

    public function snapshot(): array
    {
        $orderStats = $this->orders->overviewStats();
        $invoiceStats = $this->reports->overview()['invoices'];

        return [
            'orders' => [
                'draft' => $orderStats['draft'],
                'pending' => $orderStats['pending'],
                'confirmed' => $orderStats['confirmed'],
                'cancelled' => $orderStats['cancelled'],
                'open' => $orderStats['draft'] + $orderStats['pending'] + $orderStats['confirmed'],
            ],
            'revenue' => $orderStats['revenue'],
            'invoices' => [
                'count' => $invoiceStats['due'] + $invoiceStats['partial'] + $invoiceStats['overdue'],
                'outstanding' => $invoiceStats['outstanding'],
                'due' => $invoiceStats['due'],
                'partial' => $invoiceStats['partial'],
                'overdue' => $invoiceStats['overdue'],
                'paid' => $invoiceStats['paid'],
            ],
            'conversion' => $this->quotations->conversionStats(),
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

    public function unpaidInvoices(int $limit = 6): array
    {
        $limit = max(1, min($limit, 10));

        return array_values(array_slice(
            $this->invoices->openInvoicesForSelect(),
            0,
            $limit,
        ));
    }
}

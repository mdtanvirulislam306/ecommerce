<?php

namespace App\Core\Services;

use App\Core\Contracts\SalesManagerOverview;
use App\Core\Module\ModuleManager;
use App\Core\Support\EmptySalesManagerOverview;
use App\Core\Support\Service;
use Illuminate\Support\Facades\Route;

/**
 * Sales Manager overview. Reads Sales through SalesManagerOverview only.
 *
 * There is no separate Sales Manager role yet. Any verified shop user may open
 * this page. Sales CRUD stays behind the `module:sales` gate. When Sales is
 * off, this payload is zeros and empty lists so the page still renders.
 */
final class SalesManagerDashboardService extends Service
{
    private const RECENT_ORDER_LIMIT = 6;

    private const UNPAID_INVOICE_LIMIT = 6;

    public function __construct(
        private readonly ModuleManager $modules,
        private readonly SalesManagerOverview $sales,
    ) {}

    /**
     * Inertia props for `SalesManagerDashboard`.
     *
     * `kpis.revenue` is confirmed sales-order grand totals from `overviewStats`
     * (no date window; Sales does not define a narrower period).
     * `kpis.orders.open` excludes cancelled.
     * `kpis.unpaid_invoices.outstanding` is the Sales report sum of invoice `amount_due`.
     * `kpis.unpaid_invoices.count` is due + partial + overdue.
     * `kpis.conversion.rate` is quotations with `sales_order_id` / all quotations, or null.
     *
     * @return array{
     *     role: string,
     *     available: bool,
     *     kpis: array{
     *         orders: array{draft: int, pending: int, confirmed: int, cancelled: int, open: int},
     *         revenue: string,
     *         unpaid_invoices: array{count: int, outstanding: string, due: int, partial: int, overdue: int, paid: int},
     *         conversion: array{total: int, converted: int, rate: string|null}
     *     },
     *     recentOrders: list<array<string, mixed>>,
     *     unpaidInvoices: list<array<string, mixed>>,
     *     quickLinks: list<array{label: string, description: string, route: string}>
     * }
     */
    public function overview(): array
    {
        $salesEnabled = $this->modules->enabled('sales');
        $snapshot = $salesEnabled
            ? $this->sales->snapshot()
            : (new EmptySalesManagerOverview)->snapshot();

        return [
            'role' => 'sales_manager',
            'available' => $salesEnabled,
            'kpis' => [
                'orders' => $snapshot['orders'],
                'revenue' => $snapshot['revenue'],
                'unpaid_invoices' => $snapshot['invoices'],
                'conversion' => $snapshot['conversion'],
            ],
            'recentOrders' => $salesEnabled
                ? $this->sales->recentOrders(self::RECENT_ORDER_LIMIT)
                : [],
            'unpaidInvoices' => $salesEnabled
                ? $this->sales->unpaidInvoices(self::UNPAID_INVOICE_LIMIT)
                : [],
            'quickLinks' => $this->quickLinks($salesEnabled),
        ];
    }

    /**
     * @return list<array{label: string, description: string, route: string}>
     */
    private function quickLinks(bool $salesEnabled): array
    {
        if (! $salesEnabled) {
            return [];
        }

        $candidates = [
            [
                'label' => 'Sales overview',
                'description' => 'Order counts and confirmed revenue',
                'route' => 'sales.overview',
            ],
            [
                'label' => 'Orders',
                'description' => 'Open and confirmed sales orders',
                'route' => 'sales.orders.all',
            ],
            [
                'label' => 'Invoices',
                'description' => 'Unpaid and overdue invoices',
                'route' => 'sales.invoices.all',
            ],
            [
                'label' => 'Quotations',
                'description' => 'Quotes and conversion to orders',
                'route' => 'sales.quotations.all',
            ],
            [
                'label' => 'Sales reports',
                'description' => 'Quotations, invoices, and collections',
                'route' => 'sales.reports.index',
            ],
        ];

        $links = [];

        foreach ($candidates as $candidate) {
            if (! Route::has($candidate['route'])) {
                continue;
            }

            $links[] = $candidate;
        }

        return $links;
    }
}

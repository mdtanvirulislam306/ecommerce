<?php

namespace App\Core\Services;

use App\Core\Contracts\SalesOverview;
use App\Core\Module\ModuleManager;
use App\Core\Support\EmptySalesOverview;
use App\Core\Support\Service;
use Illuminate\Support\Facades\Route;

/**
 * Sales Manager overview for the shared admin dashboard.
 *
 * Open orders are draft and pending only. Confirmed stays on confirmed_orders
 * and revenue, matching SalesOrderService::overviewStats(). forOwner is unchanged.
 */
final class SalesManagerDashboardService extends Service
{
    private const RECENT_ORDER_LIMIT = 6;

    public function __construct(
        private readonly ModuleManager $modules,
        private readonly SalesOverview $sales,
    ) {}

    /**
     * Inertia props for Dashboard when `role` is `sales_manager`.
     *
     * `kpis.revenue` is SalesOverview snapshot revenue (`"1234.56"`).
     * `kpis.open_orders` is draft + pending. Confirmed and cancelled are excluded.
     * `kpis.confirmed_orders` is the overviewStats confirmed count.
     * `kpis.unpaid_invoices` is the Sales report due + partial + overdue count,
     * or null when Sales is off.
     * `kpis.quotation_conversion_rate` is a percent string such as `"42%"`,
     * or null when there are no quotations or Sales is off.
     * `recentOpenOrders` are draft and pending only.
     *
     * @return array{
     *     role: string,
     *     kpis: array{
     *         open_orders: int,
     *         draft_orders: int,
     *         pending_orders: int,
     *         confirmed_orders: int,
     *         revenue: string,
     *         unpaid_invoices: int|null,
     *         quotation_conversion_rate: string|null
     *     },
     *     modulesAvailable: array{sales: bool},
     *     recentOpenOrders: list<array{
     *         id: int,
     *         number: string,
     *         customer_name: string,
     *         status: string,
     *         status_label: string,
     *         currency: string,
     *         grand_total: string,
     *         created_at: string|null
     *     }>,
     *     quickLinks: list<array{label: string, description: string, route: string}>
     * }
     */
    public function overview(): array
    {
        $salesEnabled = $this->modules->enabled('sales');
        $snapshot = $salesEnabled
            ? $this->sales->snapshot()
            : (new EmptySalesOverview)->snapshot();

        return [
            'role' => 'sales_manager',
            'kpis' => [
                'open_orders' => $snapshot['draft'] + $snapshot['pending'],
                'draft_orders' => $snapshot['draft'],
                'pending_orders' => $snapshot['pending'],
                'confirmed_orders' => $snapshot['confirmed'],
                'revenue' => $snapshot['revenue'],
                'unpaid_invoices' => $salesEnabled ? $this->sales->unpaidInvoiceCount() : null,
                'quotation_conversion_rate' => $salesEnabled ? $this->sales->quotationConversionRate() : null,
            ],
            'modulesAvailable' => [
                'sales' => $salesEnabled,
            ],
            'recentOpenOrders' => $salesEnabled
                ? $this->sales->recentOpenOrders(self::RECENT_ORDER_LIMIT)
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

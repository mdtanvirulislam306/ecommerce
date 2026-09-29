<?php

namespace App\Core\Services;

use App\Core\Contracts\SalesOverview;
use App\Core\Module\ModuleManager;
use App\Core\Support\EmptySalesOverview;
use App\Core\Support\Service;
use Illuminate\Support\Facades\Route;

/**
 * Sales Manager overview for the admin dashboard.
 *
 * Uses SalesOverview for confirmed revenue, open-order counts, and recent open
 * orders. Outstanding AR and quotation conversion are included only because
 * Sales already exposes them on that same reader. forOwner stays untouched.
 */
final class SalesManagerDashboardService extends Service
{
    private const RECENT_ORDER_LIMIT = 6;

    public function __construct(
        private readonly ModuleManager $modules,
        private readonly SalesOverview $sales,
    ) {}

    /**
     * Inertia props for the Dashboard page when `role` is `sales_manager`.
     *
     * `kpis.revenue` and `kpis.open_orders` match SalesOverview::snapshot()
     * (`overviewStats` confirmed totals; open orders exclude cancelled).
     * `kpis.unpaid_ar` is the Sales report sum of invoice `amount_due`, or null
     * when Sales is off.
     * `kpis.quotation_conversion` is converted quotations / all quotations
     * (0 to 1, four decimal places), or null when Sales is off or there are
     * no quotations.
     * `recentOrders` are open orders only.
     *
     * @return array{
     *     role: string,
     *     kpis: array{
     *         revenue: string,
     *         open_orders: int,
     *         draft: int,
     *         pending: int,
     *         confirmed: int,
     *         unpaid_ar: string|null,
     *         quotation_conversion: float|null
     *     },
     *     modulesAvailable: array{sales: bool, inventory: bool, catalog: bool},
     *     recentOrders: list<array{
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
                'revenue' => $snapshot['revenue'],
                'open_orders' => $snapshot['orders'],
                'draft' => $snapshot['draft'],
                'pending' => $snapshot['pending'],
                'confirmed' => $snapshot['confirmed'],
                'unpaid_ar' => $salesEnabled ? $this->sales->unpaidReceivables() : null,
                'quotation_conversion' => $salesEnabled ? $this->sales->quotationConversion() : null,
            ],
            'modulesAvailable' => [
                'sales' => $salesEnabled,
                'inventory' => $this->modules->enabled('inventory'),
                'catalog' => $this->modules->enabled('catalog'),
            ],
            'recentOrders' => $salesEnabled
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

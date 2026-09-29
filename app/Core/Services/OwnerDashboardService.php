<?php

namespace App\Core\Services;

use App\Core\Contracts\InventoryOverview;
use App\Core\Contracts\SalesOverview;
use App\Core\Module\ModuleManager;
use App\Core\Support\EmptyInventoryOverview;
use App\Core\Support\EmptySalesOverview;
use App\Core\Support\Service;
use Illuminate\Support\Facades\Route;

/**
 * Owner overview for the admin home.
 *
 * Sales Manager uses SalesManagerDashboardService. Inventory, Accountant, and
 * Ecommerce dashboards are later roles. Give them their own services instead
 * of branching forOwner.
 */
final class OwnerDashboardService extends Service
{
    private const RECENT_ORDER_LIMIT = 6;

    private const LOW_STOCK_LIMIT = 6;

    public function __construct(
        private readonly ModuleManager $modules,
        private readonly SalesOverview $sales,
        private readonly InventoryOverview $inventory,
    ) {}

    /**
     * @return array{
     *     role: string,
     *     kpis: array{
     *         revenue: string,
     *         orders: int,
     *         pending_orders: int,
     *         low_stock: int,
     *         out_of_stock: int,
     *         modules: int
     *     },
     *     modulesAvailable: array{sales: bool, inventory: bool, catalog: bool},
     *     recentOrders: list<array<string, mixed>>,
     *     lowStockItems: list<array<string, mixed>>,
     *     quickLinks: list<array{label: string, description: string, route: string}>
     * }
     */
    public function forOwner(): array
    {
        $salesEnabled = $this->modules->enabled('sales');
        $inventoryEnabled = $this->modules->enabled('inventory');

        $sales = $salesEnabled
            ? $this->sales->snapshot()
            : (new EmptySalesOverview)->snapshot();
        $inventory = $inventoryEnabled
            ? $this->inventory->snapshot()
            : (new EmptyInventoryOverview)->snapshot();

        return [
            'role' => 'owner',
            'kpis' => [
                'revenue' => $sales['revenue'],
                'orders' => $sales['orders'],
                'pending_orders' => $sales['pending'],
                'low_stock' => $inventory['low_stock'],
                'out_of_stock' => $inventory['out_of_stock'],
                'modules' => count($this->modules->enabledCodes()),
            ],
            'modulesAvailable' => [
                'sales' => $salesEnabled,
                'inventory' => $inventoryEnabled,
                'catalog' => $this->modules->enabled('catalog'),
            ],
            'recentOrders' => $salesEnabled
                ? $this->sales->recentOrders(self::RECENT_ORDER_LIMIT)
                : [],
            'lowStockItems' => $inventoryEnabled
                ? $this->inventory->lowStockItems(self::LOW_STOCK_LIMIT)
                : [],
            'quickLinks' => $this->quickLinks(),
        ];
    }

    /**
     * @return list<array{label: string, description: string, route: string}>
     */
    private function quickLinks(): array
    {
        $candidates = [
            [
                'module' => 'sales',
                'label' => 'Sales',
                'description' => 'Orders and confirmed revenue',
                'route' => 'sales.overview',
            ],
            [
                'module' => 'inventory',
                'label' => 'Low stock',
                'description' => 'Items at or below reorder point',
                'route' => 'inventory.low-stock.index',
            ],
            [
                'module' => 'catalog',
                'label' => 'Catalog',
                'description' => 'Products, categories, and brands',
                'route' => 'products.overview',
            ],
            [
                'module' => 'inventory',
                'label' => 'Inventory',
                'description' => 'Warehouses and on-hand stock',
                'route' => 'inventory.overview',
            ],
        ];

        $links = [];

        foreach ($candidates as $candidate) {
            if (! $this->modules->enabled($candidate['module']) || ! Route::has($candidate['route'])) {
                continue;
            }

            $links[] = [
                'label' => $candidate['label'],
                'description' => $candidate['description'],
                'route' => $candidate['route'],
            ];
        }

        return $links;
    }
}

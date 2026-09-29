<?php

namespace Tests\Feature;

use App\Core\Tenant\TenantContext;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Modules\Catalog\Models\Product;
use Modules\Inventory\Models\StockLevel;
use Modules\Inventory\Models\Warehouse;
use Modules\Sales\Models\SalesOrder;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guests_are_redirected_from_the_dashboard(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_authenticated_user_can_view_the_dashboard(): void
    {
        $this->assertTrue(Route::has('sales.overview'));

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->has('auth.user')
            ->where('auth.user.name', $user->name)
            ->where('role', 'owner')
            ->has('kpis')
            ->has('recentOrders')
            ->has('lowStockItems')
            ->has('quickLinks')
        );
    }

    public function test_owner_dashboard_shows_empty_sales_and_stock_states(): void
    {
        $tenant = $this->defaultTenant();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->get($this->dashboardUrl($tenant));

        $response->assertOk();
        $response->assertInertia(function (AssertableInertia $page) {
            $enabledModules = $page->toArray()['props']['enabledModules'];

            $page->component('Dashboard')
                ->where('kpis.revenue', '0.00')
                ->where('kpis.orders', 0)
                ->where('kpis.pending_orders', 0)
                ->where('kpis.low_stock', 0)
                ->where('kpis.out_of_stock', 0)
                ->where('kpis.modules', count($enabledModules))
                ->has('recentOrders', 0)
                ->has('lowStockItems', 0)
                ->where('modulesAvailable.sales', true)
                ->where('modulesAvailable.inventory', true)
                ->where('quickLinks', fn ($links) => collect($links)->contains('route', 'sales.overview')
                    && collect($links)->contains('route', 'inventory.low-stock.index')
                    && collect($links)->contains('route', 'products.overview'));

            return true;
        });
    }

    public function test_owner_dashboard_counts_confirmed_revenue_and_low_stock(): void
    {
        $tenant = $this->defaultTenant();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->makeOrder($tenant, 'SO-100', 'confirmed', '200.00', 'Asha Rahman', now()->subHour());
        $this->makeOrder($tenant, 'SO-DRAFT', 'draft', '75.00', 'Draft Buyer', now());
        $this->makeStock($tenant, 'Green Tea', 'TEA-1', '3', '10');
        $this->makeStock($tenant, 'Coffee Beans', 'COF-1', '0', '5');
        $this->makeStock($tenant, 'Rice', 'RICE-1', '40', '5');

        $response = $this->actingAs($user)->get($this->dashboardUrl($tenant));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('kpis.revenue', '200.00')
            ->where('kpis.orders', 2)
            ->where('kpis.pending_orders', 0)
            ->where('kpis.low_stock', 1)
            ->where('kpis.out_of_stock', 1)
            ->has('recentOrders', 2)
            ->where('recentOrders.0.number', 'SO-DRAFT')
            ->where('recentOrders.0.customer_name', 'Draft Buyer')
            ->where('recentOrders.0.status', 'draft')
            ->where('recentOrders.1.number', 'SO-100')
            ->where('recentOrders.1.status', 'confirmed')
            ->has('lowStockItems', 1)
            ->where('lowStockItems.0.product_name', 'Green Tea')
            ->where('lowStockItems.0.sku', 'TEA-1')
            ->where('lowStockItems.0.on_hand', '3')
            ->where('lowStockItems.0.reorder_point', '10')
            ->where('lowStockItems', fn ($items) => ! collect($items)->contains('product_name', 'Coffee Beans')
                && ! collect($items)->contains('product_name', 'Rice'))
        );
    }

    public function test_owner_dashboard_excludes_other_tenant_orders_and_stock(): void
    {
        $tenant = $this->defaultTenant();
        $other = Tenant::query()->create([
            'name' => 'Other Shop',
            'slug' => 'other-shop',
            'status' => 'active',
        ]);
        TenantDomain::query()->create([
            'tenant_id' => $other->id,
            'domain' => 'other-shop.test',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->makeOrder($tenant, 'SO-HOME', 'confirmed', '10.00', 'Home Buyer', now());
        $this->makeOrder($other, 'SO-OTHER', 'confirmed', '9999.00', 'Other Buyer', now());
        $this->makeStock($tenant, 'Home Soap', 'SOAP-HOME', '2', '8');
        $this->makeStock($other, 'Secret Soap', 'SOAP-SECRET', '1', '9');

        $response = $this->actingAs($user)->get($this->dashboardUrl($tenant));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('kpis.revenue', '10.00')
            ->where('kpis.orders', 1)
            ->where('kpis.low_stock', 1)
            ->has('recentOrders', 1)
            ->where('recentOrders.0.number', 'SO-HOME')
            ->where('recentOrders', fn ($orders) => ! collect($orders)->contains('number', 'SO-OTHER'))
            ->has('lowStockItems', 1)
            ->where('lowStockItems.0.product_name', 'Home Soap')
            ->where('lowStockItems', fn ($items) => ! collect($items)->contains('product_name', 'Secret Soap'))
        );
    }

    private function defaultTenant(): Tenant
    {
        return Tenant::query()->where('slug', 'default')->firstOrFail();
    }

    private function dashboardUrl(Tenant $tenant): string
    {
        $domain = TenantDomain::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->value('domain');

        return 'http://'.$domain.'/admin/dashboard';
    }

    private function useTenant(Tenant $tenant): void
    {
        app(TenantContext::class)->set($tenant);
    }

    private function makeOrder(
        Tenant $tenant,
        string $number,
        string $status,
        string $total,
        string $customer,
        \DateTimeInterface $createdAt,
    ): SalesOrder {
        $this->useTenant($tenant);

        $order = SalesOrder::query()->create([
            'number' => $number,
            'status' => $status,
            'customer_name' => $customer,
            'currency' => 'BDT',
            'subtotal' => $total,
            'grand_total' => $total,
            'amount_paid' => '0',
            'amount_due' => $total,
        ]);

        $order->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        return $order;
    }

    private function makeStock(
        Tenant $tenant,
        string $name,
        string $sku,
        string $onHand,
        string $reorderPoint,
    ): void {
        $this->useTenant($tenant);

        $product = Product::query()->create([
            'type' => 'simple',
            'name' => $name,
            'slug' => str($name.' '.$sku)->slug()->toString(),
            'sku' => $sku,
            'status' => 'active',
            'publication_status' => 'not_published',
        ]);

        $warehouse = Warehouse::query()->create([
            'name' => $name.' Warehouse',
            'code' => $sku,
            'is_active' => true,
        ]);

        StockLevel::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'on_hand' => $onHand,
            'reserved' => '0',
            'reorder_point' => $reorderPoint,
        ]);
    }
}

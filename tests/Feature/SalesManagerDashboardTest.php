<?php

namespace Tests\Feature;

use App\Core\Services\SalesManagerDashboardService;
use App\Core\Tenant\TenantContext;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\TenantModuleOverride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Modules\Sales\Models\SalesInvoice;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesQuotation;
use Modules\Settings\Models\Role;
use Tests\TestCase;

class SalesManagerDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_sales_manager_dashboard_is_empty_when_the_shop_has_no_sales_activity(): void
    {
        $tenant = $this->defaultTenant();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($user, $tenant, 'sales-manager', 'Sales Manager');

        $response = $this->actingAs($user)->get($this->dashboardUrl($tenant));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->where('role', 'sales_manager')
            ->missing('lowStockItems')
            ->missing('available')
            ->missing('recentOrders')
            ->where('kpis', fn ($kpis) => $kpis->keys()->sort()->values()->all() === [
                'confirmed_orders',
                'draft_orders',
                'open_orders',
                'pending_orders',
                'quotation_conversion_rate',
                'revenue',
                'unpaid_invoices',
            ])
            ->where('kpis.revenue', '0.00')
            ->where('kpis.open_orders', 0)
            ->where('kpis.draft_orders', 0)
            ->where('kpis.pending_orders', 0)
            ->where('kpis.confirmed_orders', 0)
            ->where('kpis.unpaid_invoices', 0)
            ->where('kpis.quotation_conversion_rate', null)
            ->where('modulesAvailable', ['sales' => true])
            ->has('recentOpenOrders', 0)
            ->where('quickLinks', fn ($links) => collect($links)->contains('route', 'sales.overview')
                && collect($links)->contains('route', 'sales.invoices.all')
                && collect($links)->contains('route', 'sales.quotations.all'))
        );
    }

    public function test_users_without_the_sales_manager_role_keep_the_owner_payload(): void
    {
        $tenant = $this->defaultTenant();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($user, $tenant, 'cashier', 'Cashier');
        $this->makeOrder($tenant, 'SO-CONF', 'confirmed', '200.00', 'Confirmed Buyer', now());

        $response = $this->actingAs($user)->get($this->dashboardUrl($tenant));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->where('role', 'owner')
            ->where('kpis.revenue', '200.00')
            ->where('kpis.orders', 1)
            ->missing('kpis.open_orders')
            ->has('lowStockItems')
        );
    }

    public function test_open_orders_exclude_cancelled_and_revenue_includes_only_confirmed_totals(): void
    {
        $tenant = $this->defaultTenant();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($user, $tenant, 'sales-manager', 'Sales Manager');

        $this->makeOrder($tenant, 'SO-CONF', 'confirmed', '200.00', 'Confirmed Buyer', now()->subHours(4));
        $this->makeOrder($tenant, 'SO-CONF-2', 'confirmed', '10.00', 'Second Confirmed', now()->subHours(3));
        $this->makeOrder($tenant, 'SO-DRAFT', 'draft', '75.00', 'Draft Buyer', now()->subHours(2));
        $this->makeOrder($tenant, 'SO-PEND', 'pending', '40.00', 'Pending Buyer', now()->subHour());
        $this->makeOrder($tenant, 'SO-CANCEL', 'cancelled', '500.00', 'Cancelled Buyer', now());

        $response = $this->actingAs($user)->get($this->dashboardUrl($tenant));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('kpis.draft_orders', 1)
            ->where('kpis.pending_orders', 1)
            ->where('kpis.confirmed_orders', 2)
            ->where('kpis.open_orders', 2)
            ->where('kpis.revenue', '210.00')
            ->missing('kpis.cancelled')
            ->has('recentOpenOrders', 2)
            ->where('recentOpenOrders', function ($orders) {
                $first = collect($orders->first());

                return $orders->count() === 2
                    && $first->get('number') === 'SO-PEND'
                    && $first->get('status') === 'pending'
                    && $first->get('status_label') === 'Pending'
                    && $first->get('customer_name') === 'Pending Buyer'
                    && $first->keys()->sort()->values()->all() === [
                        'created_at',
                        'currency',
                        'customer_name',
                        'grand_total',
                        'id',
                        'number',
                        'status',
                        'status_label',
                    ]
                    && $orders->contains('number', 'SO-DRAFT')
                    && $orders->every(fn ($order) => in_array($order['status'], ['draft', 'pending'], true))
                    && ! $orders->contains('number', 'SO-CONF')
                    && ! $orders->contains('number', 'SO-CONF-2')
                    && ! $orders->contains('number', 'SO-CANCEL');
            })
        );
    }

    public function test_unpaid_ar_is_the_sales_report_amount_due_total(): void
    {
        $tenant = $this->defaultTenant();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($user, $tenant, 'sales_manager', 'Sales Manager Underscore');

        $this->makeInvoice($tenant, 'INV-DUE', 'due', '25.00', '25.00', 'Due Buyer', now()->addDays(10));
        $this->makeInvoice($tenant, 'INV-PART', 'partial', '100.00', '40.00', 'Partial Buyer', now()->addDays(10));
        $this->makeInvoice($tenant, 'INV-LATE', 'overdue', '10.00', '10.00', 'Late Buyer', now()->subDays(3));
        $this->makeInvoice($tenant, 'INV-PAID', 'paid', '500.00', '0.00', 'Paid Buyer', now()->addDays(10));

        $response = $this->actingAs($user)->get($this->dashboardUrl($tenant));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('role', 'sales_manager')
            ->where('kpis.unpaid_invoices', 3)
        );
    }

    public function test_quotation_conversion_counts_only_quotations_linked_to_an_order(): void
    {
        $tenant = $this->defaultTenant();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($user, $tenant, 'sales-manager', 'Sales Manager');

        $convertedOrder = $this->makeOrder($tenant, 'SO-FROM-QUOTE', 'draft', '30.00', 'Quoted Buyer', now());
        $this->makeQuotation($tenant, 'QT-CONVERTED', 'accepted', $convertedOrder->id);
        $this->makeQuotation($tenant, 'QT-ACCEPTED', 'accepted', null);
        $this->makeQuotation($tenant, 'QT-DRAFT', 'draft', null);

        $response = $this->actingAs($user)->get($this->dashboardUrl($tenant));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('kpis.quotation_conversion_rate', '33%')
            ->where('kpis.revenue', '0.00')
            ->where('kpis.open_orders', 1)
            ->where('kpis.draft_orders', 1)
            ->where('kpis.confirmed_orders', 0)
            ->where('recentOpenOrders.0.status', 'draft')
        );
    }

    public function test_sales_manager_dashboard_excludes_other_tenant_orders_invoices_and_quotations(): void
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
        $this->assignRole($user, $tenant, 'sales-manager', 'Sales Manager');

        $homeOrder = $this->makeOrder($tenant, 'SO-HOME', 'confirmed', '10.00', 'Home Buyer', now());
        $this->makeOrder($tenant, 'SO-HOME-DRAFT', 'draft', '5.00', 'Home Draft', now()->subMinute());
        $this->makeOrder($tenant, 'SO-HOME-CANCEL', 'cancelled', '99.00', 'Home Cancel', now());
        $this->makeInvoice($tenant, 'INV-HOME', 'due', '15.00', '15.00', 'Home Invoice', now()->addWeek());
        $this->makeInvoice($tenant, 'INV-HOME-PAID', 'paid', '400.00', '0.00', 'Home Paid', now()->addWeek());
        $this->makeQuotation($tenant, 'QT-HOME', 'accepted', $homeOrder->id);
        $this->makeQuotation($tenant, 'QT-HOME-OPEN', 'sent', null);

        $otherOrder = $this->makeOrder($other, 'SO-OTHER', 'confirmed', '9999.00', 'Other Buyer', now());
        $this->makeInvoice($other, 'INV-OTHER', 'due', '500.00', '500.00', 'Other Invoice', now()->addWeek());
        $this->makeQuotation($other, 'QT-OTHER-1', 'accepted', $otherOrder->id);
        $this->makeQuotation($other, 'QT-OTHER-2', 'accepted', $otherOrder->id);
        $this->makeQuotation($other, 'QT-OTHER-3', 'accepted', $otherOrder->id);
        $this->makeQuotation($other, 'QT-OTHER-4', 'accepted', $otherOrder->id);

        $response = $this->actingAs($user)->get($this->dashboardUrl($tenant));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('kpis.revenue', '10.00')
            ->where('kpis.open_orders', 1)
            ->where('kpis.confirmed_orders', 1)
            ->where('kpis.draft_orders', 1)
            ->where('kpis.unpaid_invoices', 1)
            ->where('kpis.quotation_conversion_rate', '50%')
            ->where('recentOpenOrders', fn ($orders) => $orders->contains('number', 'SO-HOME-DRAFT')
                && ! $orders->contains('number', 'SO-HOME')
                && ! $orders->contains('number', 'SO-HOME-CANCEL')
                && ! $orders->contains('number', 'SO-OTHER'))
        );
    }

    public function test_disabled_sales_module_uses_the_sales_module_gate(): void
    {
        $tenant = $this->defaultTenant();
        TenantModuleOverride::query()->create([
            'tenant_id' => $tenant->id,
            'module_code' => 'sales',
            'enabled' => false,
        ]);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($user, $tenant, 'sales-manager', 'Sales Manager');
        $order = $this->makeOrder($tenant, 'SO-HIDDEN', 'confirmed', '500.00', 'Hidden Buyer', now());
        $this->makeInvoice($tenant, 'INV-HIDDEN', 'due', '80.00', '80.00', 'Hidden Invoice', now()->addWeek());
        $this->makeQuotation($tenant, 'QT-HIDDEN', 'accepted', $order->id);

        $response = $this->actingAs($user)->get($this->dashboardUrl($tenant));

        $response->assertForbidden();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Billing/Upgrade', false)
            ->where('module.code', 'sales')
        );
    }

    public function test_sales_manager_overview_is_empty_when_sales_is_disabled(): void
    {
        $tenant = $this->defaultTenant();
        TenantModuleOverride::query()->create([
            'tenant_id' => $tenant->id,
            'module_code' => 'sales',
            'enabled' => false,
        ]);
        $this->useTenant($tenant);
        $order = $this->makeOrder($tenant, 'SO-HIDDEN', 'draft', '500.00', 'Hidden Buyer', now());
        $this->makeQuotation($tenant, 'QT-HIDDEN', 'accepted', $order->id);

        $overview = app(SalesManagerDashboardService::class)->overview();

        $this->assertSame('sales_manager', $overview['role']);
        $this->assertSame(0, $overview['kpis']['open_orders']);
        $this->assertSame(0, $overview['kpis']['draft_orders']);
        $this->assertSame(0, $overview['kpis']['pending_orders']);
        $this->assertSame(0, $overview['kpis']['confirmed_orders']);
        $this->assertSame('0.00', $overview['kpis']['revenue']);
        $this->assertNull($overview['kpis']['unpaid_invoices']);
        $this->assertNull($overview['kpis']['quotation_conversion_rate']);
        $this->assertSame(['sales' => false], $overview['modulesAvailable']);
        $this->assertSame([], $overview['recentOpenOrders']);
        $this->assertSame([], $overview['quickLinks']);
    }

    public function test_owner_dashboard_still_renders_when_sales_is_disabled(): void
    {
        $tenant = $this->defaultTenant();
        TenantModuleOverride::query()->create([
            'tenant_id' => $tenant->id,
            'module_code' => 'sales',
            'enabled' => false,
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->makeOrder($tenant, 'SO-HIDDEN', 'confirmed', '500.00', 'Hidden Buyer', now());

        $response = $this->actingAs($user)->get($this->dashboardUrl($tenant));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->where('role', 'owner')
            ->where('kpis.revenue', '0.00')
            ->where('kpis.orders', 0)
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

    private function assignRole(User $user, Tenant $tenant, string $slug, string $name): void
    {
        $this->useTenant($tenant);

        $role = Role::query()->create([
            'name' => $name,
            'slug' => $slug,
        ]);

        $user->roles()->attach($role->id);
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

    private function makeInvoice(
        Tenant $tenant,
        string $number,
        string $status,
        string $grandTotal,
        string $amountDue,
        string $customer,
        \DateTimeInterface $dueDate,
    ): SalesInvoice {
        $this->useTenant($tenant);

        return SalesInvoice::query()->create([
            'number' => $number,
            'status' => $status,
            'customer_name' => $customer,
            'currency' => 'BDT',
            'subtotal' => $grandTotal,
            'grand_total' => $grandTotal,
            'amount_paid' => number_format((float) $grandTotal - (float) $amountDue, 2, '.', ''),
            'amount_due' => $amountDue,
            'due_date' => $dueDate->format('Y-m-d'),
        ]);
    }

    private function makeQuotation(
        Tenant $tenant,
        string $number,
        string $status,
        ?int $salesOrderId,
    ): SalesQuotation {
        $this->useTenant($tenant);

        return SalesQuotation::query()->create([
            'number' => $number,
            'status' => $status,
            'customer_name' => $number.' Customer',
            'currency' => 'BDT',
            'subtotal' => '30.00',
            'grand_total' => '30.00',
            'sales_order_id' => $salesOrderId,
        ]);
    }
}

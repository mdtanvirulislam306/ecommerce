<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Core\Tenant\TenantContext;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Billing\Services\PlanService;
use Modules\Crm\Models\Customer;
use Tests\TestCase;

class CrmCustomerLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));

        $this->user = User::factory()->create();
    }

    public function test_sales_order_create_lists_active_crm_customers(): void
    {
        $customer = Customer::query()->create([
            'code' => 'CUS-00010',
            'name' => 'Acme Traders',
            'email' => 'buyer@acme.example',
            'is_active' => true,
        ]);

        Customer::query()->create([
            'code' => 'CUS-00011',
            'name' => 'Closed Shop',
            'is_active' => false,
        ]);

        $this->actingAs($this->user)
            ->get(route('sales.orders.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('customers', 1)
                ->where('customers.0.id', $customer->id)
                ->where('customers.0.name', 'Acme Traders')
            );
    }

    public function test_sales_order_snapshots_selected_customer(): void
    {
        $customer = Customer::query()->create([
            'code' => 'CUS-00020',
            'name' => 'Retail Buyer',
            'email' => 'retail@example.com',
            'phone' => '01700001111',
            'is_active' => true,
        ]);
        $productId = $this->createPricedProduct();

        $this->actingAs($this->user)
            ->post(route('sales.orders.store'), [
                'customer_id' => $customer->id,
                'status' => 'draft',
                'items' => [
                    ['product_id' => $productId, 'quantity' => 1],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('sales_orders', [
            'customer_id' => $customer->id,
            'customer_name' => 'Retail Buyer',
            'customer_email' => 'retail@example.com',
            'customer_phone' => '01700001111',
        ]);
    }

    public function test_sales_order_rejects_inactive_customer(): void
    {
        $customer = Customer::query()->create([
            'code' => 'CUS-00021',
            'name' => 'Inactive Buyer',
            'is_active' => false,
        ]);
        $productId = $this->createPricedProduct();

        $this->actingAs($this->user)
            ->from(route('sales.orders.create'))
            ->post(route('sales.orders.store'), [
                'customer_id' => $customer->id,
                'status' => 'draft',
                'items' => [
                    ['product_id' => $productId, 'quantity' => 1],
                ],
            ])
            ->assertRedirect(route('sales.orders.create'))
            ->assertSessionHasErrors('customer_id');
    }

    public function test_walk_in_sales_order_omits_customer_id(): void
    {
        $productId = $this->createPricedProduct();

        $this->actingAs($this->user)
            ->post(route('sales.orders.store'), [
                'customer_name' => 'Walk-in Cash',
                'status' => 'draft',
                'items' => [
                    ['product_id' => $productId, 'quantity' => 1],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('sales_orders', [
            'customer_name' => 'Walk-in Cash',
            'customer_id' => null,
        ]);
    }

    public function test_pos_terminal_lists_crm_customers(): void
    {
        Customer::query()->create([
            'code' => 'CUS-00030',
            'name' => 'POS Regular',
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->get(route('pos.terminal'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('customers', 1));
    }

    public function test_storefront_checkout_creates_and_links_crm_customer(): void
    {
        $productId = $this->createPricedProduct(published: true, withStock: true);

        $this->post(route('shop.cart.add'), [
            'product_id' => $productId,
            'quantity' => 1,
        ])->assertRedirect();

        $this->post(route('shop.checkout.store'), [
            'customer_name' => 'Web Buyer',
            'customer_email' => 'web@example.com',
            'customer_phone' => '01711112222',
            'shipping_address' => 'Dhaka',
        ])->assertRedirect(route('shop.index'));

        $this->assertDatabaseHas('customers', [
            'name' => 'Web Buyer',
            'email' => 'web@example.com',
            'phone' => '01711112222',
        ]);

        $customer = Customer::query()->where('email', 'web@example.com')->first();

        $this->assertNotNull($customer);
        $this->assertDatabaseHas('online_orders', [
            'customer_id' => $customer->id,
            'customer_name' => 'Web Buyer',
            'customer_email' => 'web@example.com',
        ]);
    }

    public function test_storefront_checkout_reuses_existing_customer_by_email(): void
    {
        $customer = Customer::query()->create([
            'code' => 'CUS-00040',
            'name' => 'Existing Web',
            'email' => 'reuse@example.com',
            'is_active' => true,
        ]);
        $productId = $this->createPricedProduct(published: true, withStock: true);

        $this->post(route('shop.cart.add'), [
            'product_id' => $productId,
            'quantity' => 1,
        ])->assertRedirect();

        $this->post(route('shop.checkout.store'), [
            'customer_name' => 'Existing Web',
            'customer_email' => 'reuse@example.com',
            'shipping_address' => 'Chittagong',
        ])->assertRedirect(route('shop.index'));

        $this->assertSame(1, Customer::query()->where('email', 'reuse@example.com')->count());
        $this->assertDatabaseHas('online_orders', [
            'customer_id' => $customer->id,
        ]);
    }

    private function createPricedProduct(bool $published = false, bool $withStock = false): int
    {
        $tenantId = app(TenantContext::class)->id();

        $productId = (int) DB::table('products')->insertGetId([
            'tenant_id' => $tenantId,
            'type' => 'simple',
            'name' => 'Link Tea',
            'slug' => 'link-tea-'.uniqid(),
            'sku' => 'LINK-'.uniqid(),
            'status' => 'active',
            'publication_status' => $published ? 'published' : 'not_published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $listId = (int) DB::table('price_lists')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Default',
            'code' => 'DEF-'.uniqid(),
            'currency' => 'BDT',
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('price_list_items')->insert([
            'tenant_id' => $tenantId,
            'price_list_id' => $listId,
            'product_id' => $productId,
            'price' => 100,
            'min_quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($withStock) {
            $warehouseId = (int) DB::table('warehouses')->insertGetId([
                'tenant_id' => $tenantId,
                'name' => 'Main',
                'code' => 'WH-'.uniqid(),
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('stock_levels')->insert([
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'on_hand' => 50,
                'reserved' => 0,
                'reorder_point' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $productId;
    }
}

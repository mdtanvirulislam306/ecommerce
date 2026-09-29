<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Core\Tenant\TenantContext;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Modules\Billing\Services\PlanService;
use Modules\Ecommerce\Enums\OnlineOrderStatus;
use Modules\Ecommerce\Enums\PaymentMethod;
use Modules\Ecommerce\Models\OnlineOrder;
use Tests\TestCase;

class OnlineOrderAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));
    }

    public function test_checkout_gives_the_customer_a_private_order_link(): void
    {
        $productId = $this->createStockedProduct();

        $this->post(route('shop.cart.add'), ['product_id' => $productId, 'quantity' => 1])->assertRedirect();
        $this->post(route('shop.checkout.store'), [
            'customer_name' => 'Web Buyer',
            'customer_phone' => '01711112222',
            'shipping_address' => 'Dhaka',
        ])->assertSessionHas('order_placed.tracking_url');

        $order = OnlineOrder::query()->sole();

        $this->assertSame(40, strlen($order->access_token));
        $this->get(session('order_placed')['tracking_url'])
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Ecommerce/Shop/Order', false)
                ->where('order.number', $order->number)
            );
    }

    public function test_order_cannot_be_opened_with_a_wrong_token(): void
    {
        $this->createOrder(Str::random(40));

        $this->get(route('shop.orders.show', Str::random(40)))->assertNotFound();
    }

    public function test_order_cannot_be_opened_by_its_sequential_id(): void
    {
        $order = $this->createOrder(Str::random(40));

        $this->get('/shop/orders/'.$order->id)->assertNotFound();
        $this->get('/shop/thanks/'.$order->id)->assertNotFound();
    }

    public function test_order_of_another_shop_is_not_visible_on_this_shop(): void
    {
        $defaultShop = app(TenantContext::class)->get();
        $otherShop = Tenant::query()->create(['name' => 'Other', 'slug' => 'other', 'status' => Tenant::STATUS_ACTIVE]);
        app(TenantContext::class)->set($otherShop);
        $foreignOrder = $this->createOrder(Str::random(40));
        app(TenantContext::class)->set($defaultShop);

        $this->get(route('shop.orders.show', $foreignOrder->access_token))->assertNotFound();
    }

    private function createOrder(string $token): OnlineOrder
    {
        return OnlineOrder::query()->create([
            'number' => 'WEB-'.now()->format('Ymd').'-'.Str::random(4),
            'access_token' => $token,
            'status' => OnlineOrderStatus::Pending,
            'customer_name' => 'Buyer',
            'shipping_address' => 'Dhaka',
            'payment_method' => PaymentMethod::Cod,
            'currency' => 'BDT',
            'subtotal' => 100,
            'grand_total' => 100,
        ]);
    }

    private function createStockedProduct(): int
    {
        $tenantId = app(TenantContext::class)->id();

        $productId = (int) DB::table('products')->insertGetId([
            'tenant_id' => $tenantId,
            'type' => 'simple',
            'name' => 'Link Tea',
            'slug' => 'link-tea',
            'sku' => 'LINK-1',
            'status' => 'active',
            'publication_status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $listId = (int) DB::table('price_lists')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Default',
            'code' => 'DEF',
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

        $warehouseId = (int) DB::table('warehouses')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Main',
            'code' => 'MAIN',
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

        return $productId;
    }
}

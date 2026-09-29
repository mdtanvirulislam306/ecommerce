<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Core\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Billing\Services\PlanService;
use Modules\Ecommerce\Models\EcommerceCoupon;
use Modules\Ecommerce\Models\OnlineOrder;
use Modules\Ecommerce\Services\StoreSettingService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CheckoutPricingTest extends TestCase
{
    use RefreshDatabase;

    private const CHECKOUT = [
        'customer_name' => 'Web Buyer',
        'customer_phone' => '01711112222',
        'shipping_address' => 'House 1, Dhanmondi',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));
        app(StoreSettingService::class)->putMany([
            'shipping_enabled' => '1',
            'delivery_zones' => json_encode([
                ['name' => 'Inside Dhaka', 'rate' => 60],
                ['name' => 'Outside Dhaka', 'rate' => 120],
            ]),
            'free_shipping_threshold' => '',
        ]);
    }

    public function test_order_total_includes_the_charge_of_the_chosen_delivery_area(): void
    {
        $this->addToCart(price: 500, quantity: 2);

        $this->post(route('shop.checkout.store'), [...self::CHECKOUT, 'delivery_zone' => 'outside-dhaka'])
            ->assertSessionHasNoErrors();

        $order = OnlineOrder::query()->sole();
        $this->assertSame('Outside Dhaka', $order->delivery_zone);
        $this->assertEquals(1000, $order->subtotal);
        $this->assertEquals(120, $order->shipping_fee);
        $this->assertEquals(1120, $order->grand_total);
    }

    public function test_customer_must_choose_an_area_when_the_shop_has_several(): void
    {
        $this->addToCart(price: 500);

        $this->post(route('shop.checkout.store'), self::CHECKOUT)
            ->assertSessionHasErrors(['delivery_zone' => 'Please choose a delivery area.']);

        $this->assertDatabaseCount('online_orders', 0);
    }

    public function test_delivery_is_free_once_the_discounted_order_reaches_the_threshold(): void
    {
        app(StoreSettingService::class)->put('free_shipping_threshold', '900');
        $this->createCoupon(['code' => 'TEN', 'type' => 'percentage', 'value' => 10]);
        $this->addToCart(price: 1000);
        $this->post(route('shop.cart.coupon.apply'), ['coupon' => 'ten']);

        $this->post(route('shop.checkout.store'), [...self::CHECKOUT, 'delivery_zone' => 'inside-dhaka']);

        $order = OnlineOrder::query()->sole();
        $this->assertEquals(100, $order->discount_total);
        $this->assertEquals(0, $order->shipping_fee);
        $this->assertEquals(900, $order->grand_total);
    }

    public function test_threshold_is_checked_after_the_coupon_discount(): void
    {
        app(StoreSettingService::class)->put('free_shipping_threshold', '1000');
        $this->createCoupon(['code' => 'TEN', 'type' => 'percentage', 'value' => 10]);
        $this->addToCart(price: 1000);
        $this->post(route('shop.cart.coupon.apply'), ['coupon' => 'TEN']);

        $this->post(route('shop.checkout.store'), [...self::CHECKOUT, 'delivery_zone' => 'inside-dhaka']);

        $this->assertEquals(960, OnlineOrder::query()->sole()->grand_total);
    }

    public function test_no_charge_and_no_area_needed_when_delivery_charges_are_off(): void
    {
        app(StoreSettingService::class)->put('shipping_enabled', '0');
        $this->addToCart(price: 500);

        $this->post(route('shop.checkout.store'), self::CHECKOUT)->assertSessionHasNoErrors();

        $order = OnlineOrder::query()->sole();
        $this->assertNull($order->delivery_zone);
        $this->assertEquals(500, $order->grand_total);
    }

    public function test_applied_coupon_is_redeemed_once_and_stored_on_the_order(): void
    {
        $coupon = $this->createCoupon(['code' => 'SAVE50', 'type' => 'fixed', 'value' => 50]);
        $this->addToCart(price: 500);

        $this->post(route('shop.cart.coupon.apply'), ['coupon' => 'save50'])->assertSessionHasNoErrors();
        $this->post(route('shop.checkout.store'), [...self::CHECKOUT, 'delivery_zone' => 'inside-dhaka']);

        $order = OnlineOrder::query()->sole();
        $this->assertSame('SAVE50', $order->coupon_code);
        $this->assertEquals(50, $order->discount_total);
        $this->assertEquals(510, $order->grand_total);
        $this->assertSame(1, $coupon->fresh()->used_count);
    }

    public function test_fixed_coupon_never_discounts_more_than_the_order(): void
    {
        $this->createCoupon(['code' => 'BIG', 'type' => 'fixed', 'value' => 5000]);
        $this->addToCart(price: 300);
        $this->post(route('shop.cart.coupon.apply'), ['coupon' => 'BIG']);

        $this->post(route('shop.checkout.store'), [...self::CHECKOUT, 'delivery_zone' => 'inside-dhaka']);

        $order = OnlineOrder::query()->sole();
        $this->assertEquals(300, $order->discount_total);
        $this->assertEquals(60, $order->grand_total);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function unusableCoupons(): array
    {
        return [
            'inactive' => [['is_active' => false], 'This coupon code is not valid.'],
            'not started' => [['starts_at' => Carbon::now()->addDay()], 'This coupon is not active yet.'],
            'expired' => [['ends_at' => Carbon::now()->subDay()], 'This coupon has expired.'],
            'used up' => [['usage_limit' => 3, 'used_count' => 3], 'This coupon has reached its usage limit.'],
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    #[DataProvider('unusableCoupons')]
    public function test_unusable_coupon_is_rejected_with_a_reason(array $attributes, string $message): void
    {
        $this->createCoupon(['code' => 'NOPE', ...$attributes]);
        $this->addToCart(price: 500);

        $this->post(route('shop.cart.coupon.apply'), ['coupon' => 'NOPE'])
            ->assertSessionHasErrors(['coupon' => $message]);
    }

    public function test_unknown_coupon_is_rejected(): void
    {
        $this->addToCart(price: 500);

        $this->post(route('shop.cart.coupon.apply'), ['coupon' => 'MISSING'])
            ->assertSessionHasErrors(['coupon' => 'This coupon code is not valid.']);
    }

    public function test_checkout_is_stopped_when_the_coupon_ran_out_after_it_was_applied(): void
    {
        $coupon = $this->createCoupon(['code' => 'LAST', 'usage_limit' => 1]);
        $this->addToCart(price: 500);
        $this->post(route('shop.cart.coupon.apply'), ['coupon' => 'LAST']);
        $coupon->update(['used_count' => 1]);

        $this->post(route('shop.checkout.store'), [...self::CHECKOUT, 'delivery_zone' => 'inside-dhaka'])
            ->assertSessionHasErrors(['coupon' => 'This coupon has reached its usage limit.']);

        $this->assertDatabaseCount('online_orders', 0);
    }

    public function test_removed_coupon_is_not_applied(): void
    {
        $this->createCoupon(['code' => 'SAVE50', 'type' => 'fixed', 'value' => 50]);
        $this->addToCart(price: 500);
        $this->post(route('shop.cart.coupon.apply'), ['coupon' => 'SAVE50']);

        $this->delete(route('shop.cart.coupon.remove'));
        $this->post(route('shop.checkout.store'), [...self::CHECKOUT, 'delivery_zone' => 'inside-dhaka']);

        $this->assertEquals(560, OnlineOrder::query()->sole()->grand_total);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createCoupon(array $attributes): EcommerceCoupon
    {
        return EcommerceCoupon::query()->create([
            'name' => 'Promo',
            'type' => 'fixed',
            'value' => 50,
            'is_active' => true,
            ...$attributes,
        ]);
    }

    private function addToCart(float $price, int $quantity = 1): void
    {
        $tenantId = app(TenantContext::class)->id();
        $timestamps = ['created_at' => now(), 'updated_at' => now()];

        $productId = (int) DB::table('products')->insertGetId([
            'tenant_id' => $tenantId,
            'type' => 'simple',
            'name' => 'Tea',
            'slug' => 'tea',
            'sku' => 'TEA-1',
            'status' => 'active',
            'publication_status' => 'published',
            ...$timestamps,
        ]);
        $listId = (int) DB::table('price_lists')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Retail',
            'code' => 'RETAIL',
            'currency' => 'BDT',
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 1,
            ...$timestamps,
        ]);
        DB::table('price_list_items')->insert([
            'tenant_id' => $tenantId,
            'price_list_id' => $listId,
            'product_id' => $productId,
            'price' => $price,
            'min_quantity' => 1,
            ...$timestamps,
        ]);
        $warehouseId = (int) DB::table('warehouses')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Main',
            'code' => 'MAIN',
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 1,
            ...$timestamps,
        ]);
        DB::table('stock_levels')->insert([
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'on_hand' => 50,
            'reserved' => 0,
            'reorder_point' => 0,
            ...$timestamps,
        ]);

        $this->post(route('shop.cart.add'), ['product_id' => $productId, 'quantity' => $quantity])->assertRedirect();
    }
}

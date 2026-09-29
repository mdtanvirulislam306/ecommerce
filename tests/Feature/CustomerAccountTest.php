<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Core\Tenant\TenantContext;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Modules\Billing\Services\PlanService;
use Modules\Ecommerce\Enums\OnlineOrderStatus;
use Modules\Ecommerce\Enums\PaymentMethod;
use Modules\Ecommerce\Models\CustomerAccount;
use Modules\Ecommerce\Models\OnlineOrder;
use Modules\Ecommerce\Notifications\ResetCustomerPasswordNotification;
use Modules\Ecommerce\Services\StoreSettingService;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    private const CHECKOUT = [
        'customer_name' => 'Rafi Ahmed',
        'customer_phone' => '01711112222',
        'shipping_address' => "House 7, Road 3, Mirpur\nDistrict: Dhaka",
        'address_line' => 'House 7, Road 3, Mirpur',
        'district' => 'Dhaka',
        'payment_method' => 'cod',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));
        app(StoreSettingService::class)->put('shipping_enabled', '0');
    }

    public function test_shopper_can_register_and_is_signed_in_with_a_linked_crm_customer(): void
    {
        $this->post(route('shop.account.register.store'), [
            'name' => 'Rafi Ahmed',
            'email' => 'Rafi@Example.com',
            'phone' => '01711112222',
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
        ])->assertRedirect(route('shop.account.index'));

        $account = CustomerAccount::query()->sole();
        $this->assertSame('rafi@example.com', $account->email);
        $this->assertNotNull($account->customer);
        $this->assertSame('rafi@example.com', $account->customer->email);
        $this->assertAuthenticatedAs($account, 'customer');
    }

    public function test_email_already_registered_in_this_shop_is_rejected(): void
    {
        CustomerAccount::factory()->create(['email' => 'rafi@example.com']);

        $this->post(route('shop.account.register.store'), [
            'name' => 'Someone Else',
            'email' => 'rafi@example.com',
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
        ])->assertSessionHasErrors('email');

        $this->assertSame(1, CustomerAccount::query()->count());
    }

    public function test_customer_can_sign_in_and_sign_out(): void
    {
        $account = CustomerAccount::factory()->create(['email' => 'rafi@example.com']);

        $this->post(route('shop.account.login.store'), ['email' => 'RAFI@example.com', 'password' => 'password'])
            ->assertRedirect(route('shop.account.index'));
        $this->assertAuthenticatedAs($account, 'customer');
        $this->assertNotNull($account->fresh()->last_login_at);

        $this->post(route('shop.account.logout'))->assertRedirect(route('shop.index'));
        $this->assertGuest('customer');
    }

    public function test_wrong_password_does_not_sign_in(): void
    {
        CustomerAccount::factory()->create(['email' => 'rafi@example.com']);

        $this->post(route('shop.account.login.store'), ['email' => 'rafi@example.com', 'password' => 'not-it'])
            ->assertSessionHasErrors('email');

        $this->assertGuest('customer');
    }

    public function test_account_registered_at_another_shop_cannot_sign_in_here(): void
    {
        $thisShop = app(TenantContext::class)->get();
        $otherShop = Tenant::query()->create(['name' => 'Other', 'slug' => 'other', 'status' => Tenant::STATUS_ACTIVE]);
        app(TenantContext::class)->set($otherShop);
        CustomerAccount::factory()->create(['email' => 'rafi@example.com']);
        app(TenantContext::class)->set($thisShop);

        $this->post(route('shop.account.login.store'), ['email' => 'rafi@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest('customer');
    }

    public function test_account_page_requires_a_signed_in_customer(): void
    {
        $this->get(route('shop.account.index'))->assertRedirect(route('shop.account.login'));

        $this->actingAs(User::factory()->create())
            ->get(route('shop.account.index'))
            ->assertRedirect(route('shop.account.login'));
    }

    public function test_order_history_only_lists_orders_placed_from_the_account(): void
    {
        $account = CustomerAccount::factory()->create(['email' => 'rafi@example.com']);
        $mine = $this->createOrder(['customer_account_id' => $account->id, 'grand_total' => 250]);
        $this->createOrder(['customer_email' => 'rafi@example.com']);
        $this->createOrder(['customer_account_id' => CustomerAccount::factory()->create()->id]);

        $this->actingAs($account, 'customer')
            ->get(route('shop.account.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Ecommerce/Shop/Account/Index', false)
                ->where('stats.orders', 1)
                ->where('stats.spent', '250.00')
                ->has('orders.data', 1)
                ->where('orders.data.0.number', $mine->number)
                ->where('orders.data.0.tracking_url', route('shop.orders.show', $mine->access_token))
            );
    }

    public function test_signed_in_checkout_links_the_order_and_remembers_the_address(): void
    {
        $account = CustomerAccount::factory()->create(['email' => 'rafi@example.com', 'phone' => null]);
        $this->actingAs($account, 'customer');
        $this->addToCart();

        $this->post(route('shop.checkout.store'), self::CHECKOUT)->assertSessionHas('order_placed');

        $order = OnlineOrder::query()->sole();
        $account->refresh();
        $this->assertSame($account->id, $order->customer_account_id);
        $this->assertSame($account->customer_id, $order->customer_id);
        $this->assertSame('House 7, Road 3, Mirpur', $account->default_address);
        $this->assertSame('Dhaka', $account->default_district);
        $this->assertSame('01711112222', $account->phone);

        $this->get(route('shop.orders.show', $order->access_token))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('ownsOrder', true));
    }

    public function test_checkout_does_not_overwrite_a_saved_address(): void
    {
        $account = CustomerAccount::factory()->withSavedAddress()->create();
        $savedAddress = $account->default_address;
        $this->actingAs($account, 'customer');
        $this->addToCart();

        $this->post(route('shop.checkout.store'), self::CHECKOUT)->assertSessionHas('order_placed');

        $this->assertSame($savedAddress, $account->fresh()->default_address);
    }

    public function test_guest_is_sent_to_sign_in_when_the_shop_requires_an_account(): void
    {
        app(StoreSettingService::class)->put('guest_checkout', '0');
        $this->addToCart();

        $this->post(route('shop.checkout.store'), self::CHECKOUT)
            ->assertRedirect(route('shop.account.login'));
        $this->assertSame(0, OnlineOrder::query()->count());

        CustomerAccount::factory()->create(['email' => 'rafi@example.com']);
        $this->post(route('shop.account.login.store'), ['email' => 'rafi@example.com', 'password' => 'password'])
            ->assertRedirect(route('shop.checkout'));
    }

    public function test_customer_can_update_details(): void
    {
        $account = CustomerAccount::factory()->create();

        $this->actingAs($account, 'customer')
            ->put(route('shop.account.profile.update'), [
                'name' => 'Rafi Ahmed',
                'email' => 'new@example.com',
                'phone' => '01899998888',
                'default_district' => 'Sylhet',
                'default_address' => 'Zindabazar',
            ])
            ->assertSessionHasNoErrors();

        $account->refresh();
        $this->assertSame('new@example.com', $account->email);
        $this->assertSame('Sylhet', $account->default_district);
        $this->assertSame('Zindabazar', $account->default_address);
    }

    public function test_password_change_requires_the_current_password(): void
    {
        $account = CustomerAccount::factory()->create();
        $this->actingAs($account, 'customer');

        $this->put(route('shop.account.password.change'), [
            'current_password' => 'wrong',
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('password', $account->fresh()->password));

        $this->put(route('shop.account.password.change'), [
            'current_password' => 'password',
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('brand-new-pass', $account->fresh()->password));
    }

    public function test_forgotten_password_can_be_reset_from_the_emailed_link(): void
    {
        Notification::fake();
        $account = CustomerAccount::factory()->create(['email' => 'rafi@example.com']);

        $this->post(route('shop.account.password.email'), ['email' => 'rafi@example.com'])->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($account, ResetCustomerPasswordNotification::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->post(route('shop.account.password.update'), [
            'token' => $token,
            'email' => 'rafi@example.com',
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertRedirect(route('shop.account.login'));

        $this->assertTrue(Hash::check('brand-new-pass', $account->fresh()->password));
    }

    public function test_reset_request_for_an_unknown_email_gets_the_same_reply(): void
    {
        Notification::fake();

        $this->post(route('shop.account.password.email'), ['email' => 'nobody@example.com'])
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_staff_session_is_untouched_while_a_customer_is_signed_in(): void
    {
        $owner = User::factory()->create(['email' => 'owner@shop.test']);
        $account = CustomerAccount::factory()->create(['email' => 'rafi@example.com']);

        $this->actingAs($owner)
            ->actingAs($account, 'customer')
            ->get(route('shop.account.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.user.email', 'owner@shop.test')
                ->where('shopCustomer.email', 'rafi@example.com')
            );

        $this->post(route('shop.account.logout'));

        $this->assertGuest('customer');
        $this->assertAuthenticatedAs($owner, 'web');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createOrder(array $attributes = []): OnlineOrder
    {
        return OnlineOrder::query()->create([
            'number' => 'WEB-'.Str::random(6),
            'access_token' => Str::random(40),
            'status' => OnlineOrderStatus::Pending,
            'customer_name' => 'Buyer',
            'shipping_address' => 'Dhaka',
            'payment_method' => PaymentMethod::Cod,
            'currency' => 'BDT',
            'subtotal' => 100,
            'grand_total' => 100,
            ...$attributes,
        ]);
    }

    private function addToCart(): void
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
            'price' => 300,
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

        $this->post(route('shop.cart.add'), ['product_id' => $productId, 'quantity' => 1])->assertRedirect();
    }
}

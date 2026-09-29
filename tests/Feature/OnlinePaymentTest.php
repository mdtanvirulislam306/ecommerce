<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Core\Tenant\TenantContext;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia;
use Modules\Billing\Services\PlanService;
use Modules\Ecommerce\Enums\PaymentMethod;
use Modules\Ecommerce\Enums\PaymentStatus;
use Modules\Ecommerce\Mail\NewOnlineOrderMail;
use Modules\Ecommerce\Models\OnlineOrder;
use Modules\Ecommerce\Models\StoreSetting;
use Modules\Ecommerce\Services\PaymentSettingService;
use Modules\Ecommerce\Services\StoreSettingService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OnlinePaymentTest extends TestCase
{
    use RefreshDatabase;

    private const GATEWAY_URL = 'https://sandbox.sslcommerz.com/EasyCheckOut/testcde123';

    private const CHECKOUT = [
        'customer_name' => 'Nusrat Jahan',
        'customer_email' => 'nusrat@example.com',
        'customer_phone' => '01711112222',
        'shipping_address' => 'House 1, Dhanmondi',
        'payment_method' => 'online',
    ];

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));
        app(StoreSettingService::class)->put('shipping_enabled', '0');
        $this->owner = User::factory()->create(['email' => 'owner@shop.test']);
        $this->connectSslCommerz();
        Mail::fake();
    }

    public function test_online_checkout_sends_the_shopper_to_the_sslcommerz_payment_page(): void
    {
        $this->fakeGateway();
        $this->addToCart(price: 500);

        $this->post(route('shop.checkout.store'), self::CHECKOUT)->assertRedirect(self::GATEWAY_URL);

        $order = OnlineOrder::query()->sole();
        $this->assertSame(PaymentMethod::Online, $order->payment_method);
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
        $this->assertStringStartsWith($order->number.'-', $order->payment_transaction_id);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/gwprocess/v4/api.php')
            && $request['store_id'] === 'testbox'
            && $request['store_passwd'] === 'qwerty'
            && $request['total_amount'] === '500.00'
            && $request['tran_id'] === $order->payment_transaction_id
            && $request['value_a'] === $order->access_token
            && $request['ipn_url'] === route('shop.payments.sslcommerz.ipn'));
        Mail::assertNothingOutgoing();
    }

    public function test_verified_payment_marks_the_order_paid_and_tells_the_shop_once(): void
    {
        $order = $this->placeOnlineOrder();
        $this->fakeGateway(validation: $this->validPayment($order));

        $this->post(route('shop.payments.sslcommerz.success'), ['val_id' => 'VAL123', 'value_a' => $order->access_token])
            ->assertRedirect(route('shop.orders.show', ['token' => $order->access_token, 'payment' => 'success']));
        $this->post(route('shop.payments.sslcommerz.ipn'), ['val_id' => 'VAL123', 'status' => 'VALID'])->assertOk();

        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame('VAL123', $order->payment_validation_id);
        $this->assertSame('BKASH-BKash', $order->payment_card_type);
        $this->assertNotNull($order->paid_at);
        Mail::assertQueued(NewOnlineOrderMail::class, 1);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function untrustworthyPayments(): array
    {
        return [
            'amount differs' => [['currency_amount' => '10.00']],
            'currency differs' => [['currency_type' => 'USD']],
            'not a valid payment' => [['status' => 'INVALID_TRANSACTION']],
            'another order\'s transaction' => [['tran_id' => 'WEB-000999-ABCDEF']],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('untrustworthyPayments')]
    public function test_payment_is_not_accepted_unless_sslcommerz_confirms_it_for_this_order(array $overrides): void
    {
        $order = $this->placeOnlineOrder();
        $this->fakeGateway(validation: [...$this->validPayment($order), ...$overrides]);

        $this->post(route('shop.payments.sslcommerz.success'), ['val_id' => 'VAL123', 'value_a' => $order->access_token])
            ->assertRedirect(route('shop.orders.show', ['token' => $order->access_token, 'payment' => 'unconfirmed']));

        $this->assertSame(PaymentStatus::Pending, $order->fresh()->payment_status);
        Mail::assertNotQueued(NewOnlineOrderMail::class);
    }

    public function test_failed_payment_lets_the_shopper_retry_or_switch_to_cash_on_delivery(): void
    {
        $order = $this->placeOnlineOrder();

        $this->post(route('shop.payments.sslcommerz.fail'), ['value_a' => $order->access_token, 'tran_id' => $order->payment_transaction_id])
            ->assertRedirect(route('shop.orders.show', ['token' => $order->access_token, 'payment' => 'failed']));
        $this->assertSame(PaymentStatus::Failed, $order->fresh()->payment_status);

        $this->get(route('shop.orders.show', $order->access_token))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Ecommerce/Shop/Order', false)
                ->where('payment.can_pay_online', true)
                ->where('payment.can_pay_on_delivery', true)
            );

        $this->post(route('shop.orders.cash-on-delivery', $order->access_token))->assertRedirect();

        $order->refresh();
        $this->assertSame(PaymentMethod::Cod, $order->payment_method);
        $this->assertSame(PaymentStatus::Unpaid, $order->payment_status);
        Mail::assertQueued(NewOnlineOrderMail::class, 1);
    }

    public function test_late_callback_from_an_abandoned_attempt_does_not_fail_the_current_one(): void
    {
        $order = $this->placeOnlineOrder();

        $this->post(route('shop.payments.sslcommerz.cancel'), ['value_a' => $order->access_token, 'tran_id' => $order->number.'-OLD000']);

        $this->assertSame(PaymentStatus::Pending, $order->fresh()->payment_status);
    }

    public function test_gateway_outage_keeps_the_order_and_offers_a_retry(): void
    {
        $this->fakeGateway(session: ['status' => 'FAILED', 'failedreason' => 'Store is de-active']);
        $this->addToCart(price: 500);

        $this->post(route('shop.checkout.store'), self::CHECKOUT);

        $order = OnlineOrder::query()->sole();
        $this->assertTrue(session()->missing('errors'));
        $this->get(route('shop.orders.show', ['token' => $order->access_token, 'payment' => 'unavailable']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('payment.notice', 'unavailable')
                ->where('payment.can_pay_online', true)
            );
    }

    public function test_online_payment_needs_a_phone_number(): void
    {
        $this->addToCart(price: 500);

        $this->post(route('shop.checkout.store'), [...self::CHECKOUT, 'customer_phone' => ''])
            ->assertSessionHasErrors('customer_phone');

        $this->assertDatabaseCount('online_orders', 0);
    }

    public function test_orders_below_the_gateway_minimum_cannot_be_paid_online(): void
    {
        $this->addToCart(price: 5);

        $this->post(route('shop.checkout.store'), self::CHECKOUT)->assertSessionHasErrors('payment_method');

        $this->assertDatabaseCount('online_orders', 0);
    }

    public function test_switched_off_payment_method_is_refused_at_checkout(): void
    {
        app(StoreSettingService::class)->put('payment_cod_enabled', '0');
        $this->addToCart(price: 500);

        $this->post(route('shop.checkout.store'), [...self::CHECKOUT, 'payment_method' => 'cod'])
            ->assertSessionHasErrors(['payment_method' => 'Please choose how you would like to pay.']);
    }

    public function test_store_password_is_stored_encrypted_and_never_sent_to_the_browser(): void
    {
        $stored = StoreSetting::query()->where('key', 'sslcommerz_store_password')->value('value');

        $this->assertNotSame('qwerty', $stored);
        $this->actingAs($this->owner)
            ->get(route('ecommerce.checkout.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Ecommerce/Checkout/Index', false)
                ->where('settings.has_store_password', true)
                ->missing('settings.sslcommerz_store_password')
            );
    }

    public function test_saving_without_a_new_password_keeps_the_saved_one(): void
    {
        $this->actingAs($this->owner)
            ->put(route('ecommerce.checkout.update'), $this->settingsPayload(['sslcommerz_store_password' => '']))
            ->assertSessionHasNoErrors();

        $this->assertSame('qwerty', app(PaymentSettingService::class)->sslcommerzCredentials()['store_password']);
    }

    public function test_turning_on_online_payment_requires_merchant_credentials(): void
    {
        StoreSetting::query()->whereIn('key', ['sslcommerz_store_id', 'sslcommerz_store_password'])->delete();

        $this->actingAs($this->owner)
            ->put(route('ecommerce.checkout.update'), $this->settingsPayload(['sslcommerz_store_id' => '', 'sslcommerz_store_password' => '']))
            ->assertSessionHasErrors(['sslcommerz_store_id', 'sslcommerz_store_password']);
    }

    public function test_at_least_one_payment_method_must_stay_on(): void
    {
        $this->actingAs($this->owner)
            ->put(route('ecommerce.checkout.update'), $this->settingsPayload(['payment_cod_enabled' => false, 'payment_online_enabled' => false]))
            ->assertSessionHasErrors(['payment_cod_enabled' => 'Keep at least one way for customers to pay.']);
    }

    private function connectSslCommerz(): void
    {
        app(PaymentSettingService::class)->save([
            'guest_checkout' => true,
            'require_phone' => false,
            'payment_cod_enabled' => true,
            'payment_online_enabled' => true,
            'sslcommerz_store_id' => 'testbox',
            'sslcommerz_store_password' => 'qwerty',
            'sslcommerz_sandbox' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function settingsPayload(array $overrides = []): array
    {
        return [
            'guest_checkout' => true,
            'require_phone' => false,
            'payment_cod_enabled' => true,
            'payment_online_enabled' => true,
            'sslcommerz_store_id' => 'testbox',
            'sslcommerz_store_password' => '',
            'sslcommerz_sandbox' => true,
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $session
     * @param  array<string, mixed>  $validation
     */
    private function fakeGateway(array $session = [], array $validation = []): void
    {
        Http::fake(array_filter([
            'sandbox.sslcommerz.com/validator/*' => $validation ? Http::response($validation) : null,
            'sandbox.sslcommerz.com/gwprocess/*' => Http::response($session ?: [
                'status' => 'SUCCESS',
                'sessionkey' => 'SESSION123',
                'GatewayPageURL' => self::GATEWAY_URL,
            ]),
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayment(OnlineOrder $order): array
    {
        return [
            'status' => 'VALID',
            'tran_id' => $order->payment_transaction_id,
            'val_id' => 'VAL123',
            'amount' => '500.00',
            'currency_type' => 'BDT',
            'currency_amount' => '500.00',
            'bank_tran_id' => 'BANK789',
            'card_type' => 'BKASH-BKash',
            'value_a' => $order->access_token,
        ];
    }

    private function placeOnlineOrder(): OnlineOrder
    {
        $this->fakeGateway();
        $this->addToCart(price: 500);
        $this->post(route('shop.checkout.store'), self::CHECKOUT)->assertRedirect(self::GATEWAY_URL);

        return OnlineOrder::query()->sole();
    }

    private function addToCart(float $price): void
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

        $this->post(route('shop.cart.add'), ['product_id' => $productId, 'quantity' => 1])->assertRedirect();
    }
}

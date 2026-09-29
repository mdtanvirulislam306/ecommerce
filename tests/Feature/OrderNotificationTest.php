<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Core\Support\BulkSmsBdSender;
use App\Core\Tenant\TenantContext;
use App\Jobs\SendSmsMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;
use Modules\Billing\Services\PlanService;
use Modules\Ecommerce\Mail\NewOnlineOrderMail;
use Modules\Ecommerce\Mail\OnlineOrderCustomerMail;
use Modules\Ecommerce\Models\OnlineOrder;
use Modules\Ecommerce\Services\OrderNotificationService;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class OrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    private const CHECKOUT = [
        'customer_name' => 'Nusrat Jahan',
        'customer_email' => 'nusrat@example.com',
        'customer_phone' => '01711112222',
        'shipping_address' => 'House 1, Dhanmondi',
    ];

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));
        $this->owner = User::factory()->create(['email' => 'owner@shop.test']);
        Mail::fake();
    }

    public function test_placing_an_order_emails_the_customer_and_alerts_the_owner(): void
    {
        $this->placeOrder();

        $order = OnlineOrder::query()->sole();
        Mail::assertQueued(OnlineOrderCustomerMail::class, fn (OnlineOrderCustomerMail $mail) => $mail->hasTo('nusrat@example.com')
            && $mail->event === OrderNotificationService::EVENT_PLACED
            && $mail->order['tracking_url'] === route('shop.orders.show', $order->access_token));
        Mail::assertQueued(NewOnlineOrderMail::class, fn (NewOnlineOrderMail $mail) => $mail->hasTo('owner@shop.test'));
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->owner->id,
            'title' => "New order {$order->number}",
        ]);
    }

    public function test_customer_without_an_email_address_is_not_emailed(): void
    {
        $this->placeOrder(['customer_email' => null]);

        Mail::assertNotQueued(OnlineOrderCustomerMail::class);
        Mail::assertQueued(NewOnlineOrderMail::class);
    }

    public function test_texts_are_sent_to_the_customer_and_the_team_when_sms_is_enabled(): void
    {
        Queue::fake();
        app(OrderNotificationService::class)->savePreferences([
            'notify_customer_email' => true,
            'notify_customer_sms' => true,
            'notify_staff_email' => true,
            'notify_staff_sms' => true,
            'staff_notification_phone' => '01899990000',
        ]);

        $this->placeOrder();

        Queue::assertPushed(SendSmsMessage::class, fn (SendSmsMessage $job) => $job->phone === '01711112222'
            && str_contains($job->message, OnlineOrder::query()->sole()->number));
        Queue::assertPushed(SendSmsMessage::class, fn (SendSmsMessage $job) => $job->phone === '01899990000');
    }

    public function test_switched_off_channels_send_nothing(): void
    {
        Queue::fake();
        app(OrderNotificationService::class)->savePreferences([]);

        $this->placeOrder();

        Mail::assertNothingQueued();
        Queue::assertNotPushed(SendSmsMessage::class);
    }

    public function test_confirming_an_order_tells_the_customer(): void
    {
        $this->placeOrder();
        $order = OnlineOrder::query()->sole();

        $this->actingAs($this->owner)
            ->post(route('ecommerce.online-orders.confirm', $order))
            ->assertSessionHasNoErrors();

        Mail::assertQueued(OnlineOrderCustomerMail::class, fn (OnlineOrderCustomerMail $mail) => $mail->event === OrderNotificationService::EVENT_CONFIRMED);
    }

    public function test_settings_page_shows_where_team_alerts_go(): void
    {
        $this->actingAs($this->owner)
            ->get(route('ecommerce.notifications.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Ecommerce/Notifications/Index', false)
                ->where('ownerEmail', 'owner@shop.test')
                ->where('smsLive', false)
            );
    }

    public function test_owner_saves_notification_settings(): void
    {
        $this->actingAs($this->owner)
            ->put(route('ecommerce.notifications.update'), [
                'notify_customer_email' => false,
                'notify_customer_sms' => true,
                'notify_staff_email' => true,
                'notify_staff_sms' => false,
                'staff_notification_email' => 'orders@shop.test',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame([
            'notify_customer_email' => false,
            'notify_customer_sms' => true,
            'notify_staff_email' => true,
            'notify_staff_sms' => false,
            'staff_notification_email' => 'orders@shop.test',
            'staff_notification_phone' => '',
        ], app(OrderNotificationService::class)->preferences());
    }

    public function test_team_sms_needs_a_mobile_number(): void
    {
        $this->actingAs($this->owner)
            ->put(route('ecommerce.notifications.update'), ['notify_staff_sms' => true])
            ->assertSessionHasErrors(['staff_notification_phone' => 'Add the mobile number that should receive new-order texts.']);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function emailPreviews(): array
    {
        return [
            'order received' => ['customer-placed', 'Thanks for your order!'],
            'confirmed' => ['customer-confirmed', 'Your order is confirmed'],
            'cancelled' => ['customer-cancelled', 'Your order was cancelled'],
            'team alert' => ['staff-placed', 'Nusrat Jahan'],
        ];
    }

    #[DataProvider('emailPreviews')]
    public function test_email_preview_renders_a_sample_order_before_the_first_real_order(string $template, string $expectedText): void
    {
        $this->actingAs($this->owner)
            ->get(route('ecommerce.notifications.preview', $template))
            ->assertOk()
            ->assertSee($expectedText)
            ->assertSee('Premium Assam Tea 500g');
    }

    public function test_plain_text_versions_of_the_emails_render(): void
    {
        $notifications = app(OrderNotificationService::class);
        $snapshot = $notifications->snapshot($notifications->sampleOrder());
        $shop = $notifications->shop();

        (new NewOnlineOrderMail($snapshot, $shop))->assertSeeInText($snapshot['number']);
        foreach ([OrderNotificationService::EVENT_PLACED, OrderNotificationService::EVENT_CONFIRMED, OrderNotificationService::EVENT_CANCELLED] as $event) {
            (new OnlineOrderCustomerMail($snapshot, $shop, $event))->assertSeeInText($snapshot['number']);
        }
    }

    public function test_bulksmsbd_sends_the_number_in_international_format(): void
    {
        Http::fake(['*' => Http::response(['response_code' => 202])]);

        (new BulkSmsBdSender('key', 'SHOP', 'https://sms.test/api'))->send('01711-112222', 'Hello');

        Http::assertSent(fn (Request $request) => $request['number'] === '8801711112222' && $request['message'] === 'Hello');
    }

    public function test_bulksmsbd_rejection_is_raised_so_the_job_retries(): void
    {
        Http::fake(['*' => Http::response(['response_code' => 1007, 'error_message' => 'Balance insufficient'])]);

        $this->expectException(RuntimeException::class);

        (new BulkSmsBdSender('key', 'SHOP', 'https://sms.test/api'))->send('01711112222', 'Hello');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function placeOrder(array $overrides = []): void
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
            'price' => 500,
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
        $this->post(route('shop.checkout.store'), [...self::CHECKOUT, ...$overrides])->assertSessionHasNoErrors();
    }
}

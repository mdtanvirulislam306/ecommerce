<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Billing\Services\PlanService;
use Modules\Sales\Enums\SalesDeliveryStatus;
use Modules\Sales\Enums\SalesPaymentStatus;
use Modules\Sales\Models\SalesOrder;
use Tests\TestCase;

class SalesOrderStatusTrackingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));

        $this->user = User::factory()->create();
    }

    public function test_creating_order_sets_delivery_and_payment_and_logs_them(): void
    {
        $productId = $this->createPricedProduct();

        $this->actingAs($this->user)
            ->post(route('sales.orders.store'), [
                'customer_name' => 'Status Buyer',
                'status' => 'draft',
                'items' => [
                    ['product_id' => $productId, 'quantity' => 1],
                ],
            ])
            ->assertRedirect();

        $order = SalesOrder::query()->where('customer_name', 'Status Buyer')->firstOrFail();

        $this->assertSame(SalesDeliveryStatus::Pending, $order->delivery_status);
        $this->assertSame(SalesPaymentStatus::Unpaid, $order->payment_status);
        $this->assertDatabaseCount('sales_order_status_logs', 3);

        $this->actingAs($this->user)
            ->get(route('sales.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('order.delivery_status', 'pending')
                ->where('order.payment_status', 'unpaid')
                ->has('order.status_logs', 3)
            );
    }

    public function test_delivery_status_can_be_updated_and_is_logged(): void
    {
        $order = $this->createDraftOrder();

        $this->actingAs($this->user)
            ->post(route('sales.orders.delivery-status', $order), [
                'delivery_status' => 'processing',
                'note' => 'Packed at warehouse',
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(SalesDeliveryStatus::Processing, $order->delivery_status);

        $this->assertDatabaseHas('sales_order_status_logs', [
            'sales_order_id' => $order->id,
            'field' => 'delivery_status',
            'from_value' => 'pending',
            'to_value' => 'processing',
            'note' => 'Packed at warehouse',
        ]);
    }

    public function test_payment_status_can_be_updated_manually(): void
    {
        $order = $this->createDraftOrder();

        $this->actingAs($this->user)
            ->post(route('sales.orders.payment-status', $order), [
                'payment_status' => 'paid',
                'note' => 'Cash collected',
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(SalesPaymentStatus::Paid, $order->payment_status);

        $this->assertDatabaseHas('sales_order_status_logs', [
            'sales_order_id' => $order->id,
            'field' => 'payment_status',
            'to_value' => 'paid',
        ]);
    }

    public function test_invalid_delivery_transition_is_rejected(): void
    {
        $order = $this->createDraftOrder();
        $order->update(['delivery_status' => SalesDeliveryStatus::Delivered]);

        $this->actingAs($this->user)
            ->from(route('sales.orders.show', $order))
            ->post(route('sales.orders.delivery-status', $order), [
                'delivery_status' => 'shipped',
            ])
            ->assertRedirect(route('sales.orders.show', $order))
            ->assertSessionHasErrors('delivery_status');
    }

    public function test_orders_export_csv_includes_filtered_rows(): void
    {
        $this->createDraftOrder();

        SalesOrder::query()->latest('id')->firstOrFail()->update([
            'customer_name' => 'Export Alpha',
            'number' => 'SO-ALPHA-1',
        ]);

        $productId = $this->createPricedProduct();
        $this->actingAs($this->user)
            ->post(route('sales.orders.store'), [
                'customer_name' => 'Export Zeta',
                'status' => 'draft',
                'items' => [
                    ['product_id' => $productId, 'quantity' => 1],
                ],
            ])
            ->assertRedirect();

        $response = $this->actingAs($this->user)
            ->get(route('sales.orders.export', ['format' => 'csv', 'search' => 'Alpha']));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Export Alpha', $csv);
        $this->assertStringContainsString('SO-ALPHA-1', $csv);
        $this->assertStringNotContainsString('Export Zeta', $csv);
    }

    public function test_orders_export_print_view_renders(): void
    {
        $order = $this->createDraftOrder();
        $order->update(['customer_name' => 'Printable Order']);

        $this->actingAs($this->user)
            ->get(route('sales.orders.export', ['format' => 'pdf']))
            ->assertOk()
            ->assertSee('Sales Orders')
            ->assertSee('Printable Order');
    }

    public function test_guests_are_redirected_from_orders_export(): void
    {
        $this->get(route('sales.orders.export', ['format' => 'csv']))
            ->assertRedirect(route('login', absolute: false));
    }

    public function test_orders_export_rejects_unknown_format(): void
    {
        $this->actingAs($this->user)
            ->get(route('sales.orders.export', ['format' => 'xlsx']))
            ->assertSessionHasErrors('format');
    }

    private function createDraftOrder(): SalesOrder
    {
        $productId = $this->createPricedProduct();

        $this->actingAs($this->user)
            ->post(route('sales.orders.store'), [
                'customer_name' => 'Track Buyer',
                'status' => 'draft',
                'items' => [
                    ['product_id' => $productId, 'quantity' => 1],
                ],
            ])
            ->assertRedirect();

        return SalesOrder::query()->latest('id')->firstOrFail();
    }

    private function createPricedProduct(): int
    {
        $productId = (int) DB::table('products')->insertGetId([
            'type' => 'simple',
            'name' => 'Tracked Item',
            'slug' => 'tracked-'.uniqid(),
            'sku' => 'TRK-'.uniqid(),
            'status' => 'active',
            'publication_status' => 'not_published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $listId = (int) DB::table('price_lists')->insertGetId([
            'name' => 'Retail',
            'code' => 'retail-'.uniqid(),
            'currency' => 'BDT',
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('price_list_items')->insert([
            'price_list_id' => $listId,
            'product_id' => $productId,
            'price' => 100,
            'min_quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $productId;
    }
}

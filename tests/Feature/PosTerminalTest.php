<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Billing\Services\PlanService;
use Modules\Pos\Models\PosOrder;
use Tests\TestCase;

class PosTerminalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));

        $this->user = User::factory()->create();
    }

    public function test_terminal_page_includes_categories_and_products(): void
    {
        $this->actingAs($this->user)
            ->get(route('pos.terminal'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Pos/Terminal/Index')
                ->has('categories')
                ->has('products')
                ->has('paymentMethods')
                ->has('register')
                ->has('stats')
            );
    }

    public function test_completing_sale_stays_on_terminal_with_receipt_flash(): void
    {
        $productId = $this->createSellableProduct();

        $this->actingAs($this->user)
            ->post(route('pos.terminal.complete'), [
                'customer_name' => 'Cash Buyer',
                'amount_tendered' => 200,
                'items' => [
                    ['product_id' => $productId, 'quantity' => 1],
                ],
            ])
            ->assertRedirect(route('pos.terminal'))
            ->assertSessionHas('receipt')
            ->assertSessionHas('success');

        $order = PosOrder::query()->where('customer_name', 'Cash Buyer')->first();
        $this->assertNotNull($order);
        $this->assertSame('completed', $order->status->value);

        $receipt = session('receipt');
        $this->assertIsArray($receipt);
        $this->assertSame($order->number, $receipt['number']);
        $this->assertNotEmpty($receipt['items']);
    }

    public function test_guests_cannot_open_terminal(): void
    {
        $this->get(route('pos.terminal'))
            ->assertRedirect(route('login', absolute: false));
    }

    public function test_sale_supports_line_discount_and_card_payment(): void
    {
        $productId = $this->createSellableProduct();

        $this->actingAs($this->user)
            ->post(route('pos.terminal.complete'), [
                'customer_name' => 'Card Buyer',
                'payment_method' => 'card',
                'items' => [
                    [
                        'product_id' => $productId,
                        'quantity' => 2,
                        'discount_percent' => 10,
                    ],
                ],
            ])
            ->assertRedirect(route('pos.terminal'))
            ->assertSessionHas('receipt');

        $order = PosOrder::query()->where('customer_name', 'Card Buyer')->first();
        $this->assertNotNull($order);
        $this->assertSame('card', $order->payment_method);
        $this->assertSame('200.0000', (string) $order->subtotal);
        $this->assertSame('20.0000', (string) $order->discount_total);
        $this->assertSame('180.0000', (string) $order->grand_total);
        $this->assertSame('180.0000', (string) $order->amount_tendered);
        $this->assertSame('0.0000', (string) $order->change_due);

        $this->assertDatabaseHas('pos_order_items', [
            'pos_order_id' => $order->id,
            'discount_percent' => 10,
        ]);
    }

    public function test_mobile_payment_is_accepted(): void
    {
        $productId = $this->createSellableProduct();

        $this->actingAs($this->user)
            ->post(route('pos.terminal.complete'), [
                'customer_name' => 'bKash Buyer',
                'payment_method' => 'mobile',
                'items' => [
                    ['product_id' => $productId, 'quantity' => 1],
                ],
            ])
            ->assertRedirect(route('pos.terminal'));

        $this->assertDatabaseHas('pos_orders', [
            'customer_name' => 'bKash Buyer',
            'payment_method' => 'mobile',
            'grand_total' => 100,
        ]);
    }

    private function createSellableProduct(): int
    {
        $productId = (int) DB::table('products')->insertGetId([
            'type' => 'simple',
            'name' => 'POS Snack',
            'slug' => 'pos-snack-'.uniqid(),
            'sku' => 'POS-'.uniqid(),
            'barcode' => '890'.random_int(1000000, 9999999),
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

        $warehouseId = (int) DB::table('warehouses')->insertGetId([
            'name' => 'Main',
            'code' => 'WH-'.uniqid(),
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('stock_levels')->insert([
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

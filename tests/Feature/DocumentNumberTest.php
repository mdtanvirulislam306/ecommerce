<?php

namespace Tests\Feature;

use App\Core\Support\DocumentNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Enums\OnlineOrderStatus;
use Modules\Ecommerce\Enums\PaymentMethod;
use Modules\Ecommerce\Models\OnlineOrder;
use Tests\TestCase;

class DocumentNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_number_of_the_day_starts_at_one(): void
    {
        $this->travelTo('2026-09-29 10:00');
        $this->createOrder('WEB-20260928-0041');

        $this->assertSame('WEB-20260929-0001', DocumentNumber::next(OnlineOrder::query(), 'WEB'));
    }

    public function test_numbering_continues_after_a_deleted_order(): void
    {
        $this->travelTo('2026-09-29 10:00');
        $first = $this->createOrder('WEB-20260929-0001');
        $this->createOrder('WEB-20260929-0002');
        $first->delete();

        $this->assertSame('WEB-20260929-0003', DocumentNumber::next(OnlineOrder::query(), 'WEB'));
    }

    public function test_numbering_goes_past_the_padding_width(): void
    {
        $this->travelTo('2026-09-29 10:00');
        $this->createOrder('WEB-20260929-9999');
        $this->createOrder('WEB-20260929-10000');

        $this->assertSame('WEB-20260929-10001', DocumentNumber::next(OnlineOrder::query(), 'WEB'));
    }

    private function createOrder(string $number): OnlineOrder
    {
        return OnlineOrder::query()->create([
            'number' => $number,
            'status' => OnlineOrderStatus::Pending,
            'customer_name' => 'Buyer',
            'shipping_address' => 'Dhaka',
            'payment_method' => PaymentMethod::Cod,
            'currency' => 'BDT',
            'subtotal' => 100,
            'grand_total' => 100,
        ]);
    }
}

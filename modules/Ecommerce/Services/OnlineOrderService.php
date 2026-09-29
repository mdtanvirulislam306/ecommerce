<?php

namespace Modules\Ecommerce\Services;

use App\Core\Contracts\StockAvailability;
use App\Core\Events\OnlineOrderCancelled;
use App\Core\Events\OnlineOrderConfirmed;
use App\Core\Events\OnlineOrderPlaced;
use App\Core\Support\DocumentNumber;
use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Crm\Services\CustomerService;
use Modules\Ecommerce\Enums\OnlineOrderStatus;
use Modules\Ecommerce\Enums\PaymentMethod;
use Modules\Ecommerce\Enums\PaymentStatus;
use Modules\Ecommerce\Models\CustomerAccount;
use Modules\Ecommerce\Models\OnlineOrder;
use Modules\Ecommerce\Models\OnlineOrderItem;

class OnlineOrderService extends Service
{
    public function __construct(
        private readonly StockAvailability $stock,
        private readonly CartService $cart,
        private readonly CustomerService $customers,
        private readonly StorefrontCouponService $coupons,
        private readonly DeliveryRateService $delivery,
    ) {}

    /**
     * @return array{pending: int, confirmed: int, cancelled: int, revenue: string}
     */
    public function overviewStats(): array
    {
        $counts = OnlineOrder::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'pending' => (int) ($counts[OnlineOrderStatus::Pending->value] ?? 0),
            'confirmed' => (int) ($counts[OnlineOrderStatus::Confirmed->value] ?? 0),
            'cancelled' => (int) ($counts[OnlineOrderStatus::Cancelled->value] ?? 0),
            'revenue' => number_format(
                (float) OnlineOrder::query()
                    ->where('status', OnlineOrderStatus::Confirmed->value)
                    ->sum('grand_total'),
                2,
                '.',
                '',
            ),
        ];
    }

    public function listPaginated(?string $search = null, ?OnlineOrderStatus $status = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return OnlineOrder::query()
            ->withCount('items')
            ->when($status, fn ($query, $status) => $query->where('status', $status->value))
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            }))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (OnlineOrder $order) => $this->formatList($order));
    }

    /**
     * @param  array{
     *     customer_name: string,
     *     customer_email?: string|null,
     *     customer_phone?: string|null,
     *     shipping_address: string,
     *     delivery_zone?: string|null,
     *     payment_method?: string,
     *     notes?: string|null
     * }  $checkout
     */
    public function checkoutFromCart(array $checkout, ?CustomerAccount $account = null): OnlineOrder
    {
        $cart = $this->cart->detailed();

        if ($cart['items'] === []) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty.',
            ]);
        }

        foreach ($cart['items'] as $item) {
            if (! $item['can_fulfill']) {
                throw ValidationException::withMessages([
                    'cart' => "Insufficient stock for {$item['name']}.",
                ]);
            }
        }

        return DB::transaction(function () use ($checkout, $cart, $account) {
            $subtotal = (float) $cart['subtotal'];
            $discount = 0.0;
            $couponCode = null;

            if ($code = $this->cart->couponCode()) {
                $applied = $this->coupons->resolve($code, $subtotal, lockForRedeem: true);
                $discount = $applied['discount'];
                $couponCode = $applied['coupon']->code;
                $this->coupons->redeem($applied['coupon']);
            }

            $delivery = $this->delivery->quote($checkout['delivery_zone'] ?? null, $subtotal - $discount);
            $grandTotal = round($subtotal - $discount + $delivery['fee'], 2);
            $paymentMethod = PaymentMethod::tryFrom($checkout['payment_method'] ?? '') ?? PaymentMethod::Cod;

            if ($paymentMethod === PaymentMethod::Online && $grandTotal < PaymentSettingService::MINIMUM_ONLINE_AMOUNT) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Online payment needs an order of at least ৳'.(int) PaymentSettingService::MINIMUM_ONLINE_AMOUNT.'. Please choose cash on delivery.',
                ]);
            }

            $warehouseId = DB::table('warehouses')->where('is_default', true)->value('id')
                ?? DB::table('warehouses')->orderBy('id')->value('id');

            $customer = $account?->customer ?? $this->customers->matchOrCreateFromContact([
                'name' => $checkout['customer_name'],
                'email' => $checkout['customer_email'] ?? null,
                'phone' => $checkout['customer_phone'] ?? null,
                'address' => $checkout['shipping_address'] ?? null,
            ]);

            if ($account !== null && $account->customer_id === null) {
                $account->update(['customer_id' => $customer->id]);
            }

            $order = OnlineOrder::query()->create([
                'number' => DocumentNumber::next(OnlineOrder::query(), 'WEB'),
                'access_token' => Str::random(40),
                'status' => OnlineOrderStatus::Pending,
                'customer_id' => $customer->id,
                'customer_account_id' => $account?->id,
                'customer_name' => $checkout['customer_name'],
                'customer_email' => $checkout['customer_email'] ?? null,
                'customer_phone' => $checkout['customer_phone'] ?? null,
                'shipping_address' => $checkout['shipping_address'],
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentMethod === PaymentMethod::Online ? PaymentStatus::Pending : PaymentStatus::Unpaid,
                'currency' => $cart['currency'],
                'subtotal' => $subtotal,
                'coupon_code' => $couponCode,
                'discount_total' => $discount,
                'delivery_zone' => $delivery['zone'],
                'shipping_fee' => $delivery['fee'],
                'grand_total' => $grandTotal,
                'notes' => $checkout['notes'] ?? null,
                'warehouse_id' => $warehouseId,
            ]);

            foreach ($cart['items'] as $index => $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'],
                    'sku' => $item['sku'],
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['line_total'],
                    'currency' => $item['currency'],
                    'sort_order' => $index,
                ]);
            }

            $this->cart->clear();

            if ($paymentMethod === PaymentMethod::Cod) {
                event(new OnlineOrderPlaced(orderId: $order->id, orderNumber: $order->number));
            }

            return $order->fresh(['items']);
        });
    }

    public function confirm(OnlineOrder $order): OnlineOrder
    {
        return DB::transaction(function () use ($order) {
            $order = OnlineOrder::query()->whereKey($order->id)->lockForUpdate()->with('items')->firstOrFail();

            if (! $order->status->canTransitionTo(OnlineOrderStatus::Confirmed)) {
                throw ValidationException::withMessages([
                    'status' => "Cannot confirm a {$order->status->label()} order.",
                ]);
            }

            foreach ($order->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                if (! $this->stock->canFulfill($item->product_id, (float) $item->quantity, $item->product_variant_id, $order->warehouse_id)) {
                    throw ValidationException::withMessages([
                        'stock' => "Insufficient stock for {$item->name}.",
                    ]);
                }
            }

            foreach ($order->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                $this->stock->fulfill(
                    productId: $item->product_id,
                    quantity: (float) $item->quantity,
                    productVariantId: $item->product_variant_id,
                    warehouseId: $order->warehouse_id,
                    referenceType: OnlineOrder::class,
                    referenceId: $order->id,
                );
            }

            $order->update([
                'status' => OnlineOrderStatus::Confirmed,
                'confirmed_at' => now(),
            ]);

            $order = $order->fresh(['items']);

            event(new OnlineOrderConfirmed(
                orderId: $order->id,
                orderNumber: $order->number,
                amount: (string) $order->grand_total,
                currency: $order->currency,
                customerName: $order->customer_name,
            ));

            return $order;
        });
    }

    public function cancel(OnlineOrder $order): OnlineOrder
    {
        return DB::transaction(function () use ($order) {
            $order = OnlineOrder::query()->whereKey($order->id)->lockForUpdate()->with('items')->firstOrFail();

            if (! $order->status->canTransitionTo(OnlineOrderStatus::Cancelled)) {
                throw ValidationException::withMessages([
                    'status' => "Cannot cancel a {$order->status->label()} order.",
                ]);
            }

            $wasConfirmed = $order->status === OnlineOrderStatus::Confirmed;

            if ($wasConfirmed) {
                foreach ($order->items as $item) {
                    if (! $item->product_id) {
                        continue;
                    }

                    $this->stock->restock(
                        productId: $item->product_id,
                        quantity: (float) $item->quantity,
                        productVariantId: $item->product_variant_id,
                        warehouseId: $order->warehouse_id,
                        referenceType: OnlineOrder::class,
                        referenceId: $order->id,
                    );
                }
            }

            $order->update([
                'status' => OnlineOrderStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            $order = $order->fresh(['items']);

            event(new OnlineOrderCancelled(
                orderId: $order->id,
                orderNumber: $order->number,
                amount: (string) $order->grand_total,
                currency: $order->currency,
                wasConfirmed: $wasConfirmed,
                customerName: $order->customer_name,
            ));

            return $order;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function formatDetail(OnlineOrder $order): array
    {
        $order->loadMissing('items');

        return [
            ...$this->formatList($order),
            'customer_id' => $order->customer_id,
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,
            'shipping_address' => $order->shipping_address,
            'subtotal' => (string) $order->subtotal,
            'coupon_code' => $order->coupon_code,
            'discount_total' => (string) $order->discount_total,
            'delivery_zone' => $order->delivery_zone,
            'shipping_fee' => (string) $order->shipping_fee,
            'payment_method' => $order->payment_method->value,
            'payment_method_label' => $order->payment_method->label(),
            'payment_transaction_id' => $order->payment_transaction_id,
            'payment_bank_transaction_id' => $order->payment_bank_transaction_id,
            'payment_card_type' => $order->payment_card_type,
            'paid_at' => $order->paid_at?->toIso8601String(),
            'notes' => $order->notes,
            'confirmed_at' => $order->confirmed_at?->toIso8601String(),
            'cancelled_at' => $order->cancelled_at?->toIso8601String(),
            'tracking_url' => $order->access_token ? route('shop.orders.show', $order->access_token) : null,
            'can_confirm' => $order->status->canTransitionTo(OnlineOrderStatus::Confirmed),
            'can_cancel' => $order->status->canTransitionTo(OnlineOrderStatus::Cancelled),
            'items' => $order->items->map(fn (OnlineOrderItem $item) => [
                'id' => $item->id,
                'sku' => $item->sku,
                'name' => $item->name,
                'quantity' => (string) $item->quantity,
                'unit_price' => (string) $item->unit_price,
                'line_total' => (string) $item->line_total,
                'currency' => $item->currency,
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatList(OnlineOrder $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'payment_status' => $order->payment_status->value,
            'payment_status_label' => $order->payment_status->label(),
            'customer_name' => $order->customer_name,
            'customer_id' => $order->customer_id,
            'currency' => $order->currency,
            'grand_total' => (string) $order->grand_total,
            'items_count' => $order->items_count ?? $order->items()->count(),
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }
}

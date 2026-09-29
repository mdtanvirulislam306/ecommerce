<?php

namespace Modules\Ecommerce\Services;

use App\Core\Support\Service;
use App\Jobs\SendSmsMessage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Ecommerce\Enums\OnlineOrderStatus;
use Modules\Ecommerce\Enums\PaymentMethod;
use Modules\Ecommerce\Enums\PaymentStatus;
use Modules\Ecommerce\Mail\NewOnlineOrderMail;
use Modules\Ecommerce\Mail\OnlineOrderCustomerMail;
use Modules\Ecommerce\Models\OnlineOrder;
use Modules\Ecommerce\Models\OnlineOrderItem;
use Modules\Notifications\Models\UserNotification;

/**
 * Emails, texts and in-app alerts for storefront orders. Messages are built from a snapshot taken
 * during the request, because queue workers run without a shop (tenant) context.
 */
class OrderNotificationService extends Service
{
    public const EVENT_PLACED = 'placed';

    public const EVENT_CONFIRMED = 'confirmed';

    public const EVENT_CANCELLED = 'cancelled';

    private const DEFAULTS = [
        'notify_customer_email' => '1',
        'notify_customer_sms' => '0',
        'notify_staff_email' => '1',
        'notify_staff_sms' => '0',
        'staff_notification_email' => '',
        'staff_notification_phone' => '',
    ];

    public function __construct(private readonly StoreSettingService $settings) {}

    /**
     * @return array{
     *     notify_customer_email: bool,
     *     notify_customer_sms: bool,
     *     notify_staff_email: bool,
     *     notify_staff_sms: bool,
     *     staff_notification_email: string,
     *     staff_notification_phone: string
     * }
     */
    public function preferences(): array
    {
        $stored = $this->settings->getMany(array_keys(self::DEFAULTS), self::DEFAULTS);

        return [
            'notify_customer_email' => $stored['notify_customer_email'] === '1',
            'notify_customer_sms' => $stored['notify_customer_sms'] === '1',
            'notify_staff_email' => $stored['notify_staff_email'] === '1',
            'notify_staff_sms' => $stored['notify_staff_sms'] === '1',
            'staff_notification_email' => (string) $stored['staff_notification_email'],
            'staff_notification_phone' => (string) $stored['staff_notification_phone'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function savePreferences(array $data): void
    {
        $this->settings->putMany([
            'notify_customer_email' => ! empty($data['notify_customer_email']) ? '1' : '0',
            'notify_customer_sms' => ! empty($data['notify_customer_sms']) ? '1' : '0',
            'notify_staff_email' => ! empty($data['notify_staff_email']) ? '1' : '0',
            'notify_staff_sms' => ! empty($data['notify_staff_sms']) ? '1' : '0',
            'staff_notification_email' => trim((string) ($data['staff_notification_email'] ?? '')),
            'staff_notification_phone' => trim((string) ($data['staff_notification_phone'] ?? '')),
        ]);
    }

    /**
     * Email address used for new-order alerts: the configured one, or the shop owner's.
     */
    public function staffEmail(): ?string
    {
        $configured = $this->preferences()['staff_notification_email'];

        return $configured !== '' ? $configured : $this->owners()->first()?->email;
    }

    public function orderPlaced(OnlineOrder $order): void
    {
        $preferences = $this->preferences();
        $snapshot = $this->snapshot($order);
        $shop = $this->shop();

        $this->notifyCustomer($order, $snapshot, $shop, self::EVENT_PLACED, $preferences);

        if ($preferences['notify_staff_email'] && ($staffEmail = $this->staffEmail())) {
            Mail::to($staffEmail)->send((new NewOnlineOrderMail($snapshot, $shop))->afterCommit());
        }

        if ($preferences['notify_staff_sms'] && $preferences['staff_notification_phone'] !== '') {
            SendSmsMessage::dispatch(
                $preferences['staff_notification_phone'],
                "New order {$snapshot['number']}: {$snapshot['totals']['grand_total_plain']} from {$snapshot['customer_name']}"
                    .($snapshot['customer_phone'] ? " ({$snapshot['customer_phone']})" : '')
                    .($snapshot['is_paid'] ? ', paid online.' : '.'),
            )->afterCommit();
        }

        foreach ($this->owners() as $owner) {
            UserNotification::query()->create([
                'user_id' => $owner->id,
                'title' => "New order {$snapshot['number']}",
                'body' => "{$snapshot['customer_name']} ordered {$snapshot['items_count']} item(s) for {$snapshot['totals']['grand_total']}.",
                'channel' => 'in_app',
            ]);
        }
    }

    public function orderConfirmed(OnlineOrder $order): void
    {
        $this->notifyCustomer($order, $this->snapshot($order), $this->shop(), self::EVENT_CONFIRMED, $this->preferences());
    }

    public function orderCancelled(OnlineOrder $order): void
    {
        $this->notifyCustomer($order, $this->snapshot($order), $this->shop(), self::EVENT_CANCELLED, $this->preferences());
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(OnlineOrder $order): array
    {
        $order->loadMissing('items');
        $currency = $order->currency ?: 'BDT';

        return [
            'number' => $order->number,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'placed_at' => $order->created_at?->format('j M Y, g:i A'),
            'customer_name' => $order->customer_name,
            'customer_first_name' => Str::before(trim($order->customer_name), ' '),
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,
            'shipping_address' => $order->shipping_address,
            'delivery_zone' => $order->delivery_zone,
            'payment_method' => $order->payment_method->label(),
            'is_cash_on_delivery' => $order->payment_method === PaymentMethod::Cod,
            'is_paid' => $order->payment_status === PaymentStatus::Paid,
            'payment_reference' => $order->payment_transaction_id,
            'notes' => $order->notes,
            'items_count' => (int) $order->items->sum(fn ($item) => (float) $item->quantity),
            'items' => $order->items->map(fn ($item) => [
                'name' => $item->name,
                'sku' => $item->sku,
                'quantity' => rtrim(rtrim(number_format((float) $item->quantity, 2, '.', ''), '0'), '.'),
                'unit_price' => $this->money((float) $item->unit_price, $currency),
                'line_total' => $this->money((float) $item->line_total, $currency),
            ])->all(),
            'totals' => [
                'subtotal' => $this->money((float) $order->subtotal, $currency),
                'coupon_code' => $order->coupon_code,
                'discount' => (float) $order->discount_total > 0 ? $this->money((float) $order->discount_total, $currency) : null,
                'shipping' => (float) $order->shipping_fee > 0 ? $this->money((float) $order->shipping_fee, $currency) : null,
                'grand_total' => $this->money((float) $order->grand_total, $currency),
                'grand_total_plain' => $this->money((float) $order->grand_total, $currency, plain: true),
            ],
            'tracking_url' => $order->access_token ? route('shop.orders.show', $order->access_token) : null,
            'admin_url' => route('ecommerce.online-orders.show', $order->id),
        ];
    }

    /**
     * @return array{name: string, support_email: ?string, url: string}
     */
    public function shop(): array
    {
        $stored = $this->settings->getMany(['store_name', 'support_email']);

        return [
            'name' => $stored['store_name'] ?: (string) config('app.name'),
            'support_email' => $stored['support_email'] ?: null,
            'url' => route('shop.index'),
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array<string, mixed>  $shop
     * @param  array<string, mixed>  $preferences
     */
    private function notifyCustomer(OnlineOrder $order, array $snapshot, array $shop, string $event, array $preferences): void
    {
        if ($preferences['notify_customer_email'] && $order->customer_email) {
            Mail::to($order->customer_email, $order->customer_name)
                ->send((new OnlineOrderCustomerMail($snapshot, $shop, $event))->afterCommit());
        }

        if ($preferences['notify_customer_sms'] && $order->customer_phone) {
            SendSmsMessage::dispatch($order->customer_phone, $this->customerSms($snapshot, $shop, $event))->afterCommit();
        }
    }

    /**
     * Plain ASCII keeps each text inside a single standard SMS segment where possible.
     *
     * @param  array<string, mixed>  $snapshot
     * @param  array<string, mixed>  $shop
     */
    private function customerSms(array $snapshot, array $shop, string $event): string
    {
        $track = $snapshot['tracking_url'] ? " Track: {$snapshot['tracking_url']}" : '';

        return match ($event) {
            self::EVENT_CONFIRMED => "{$shop['name']}: Your order {$snapshot['number']} is confirmed and on its way soon.{$track}",
            self::EVENT_CANCELLED => "{$shop['name']}: Your order {$snapshot['number']} has been cancelled. Contact us if this is unexpected.",
            default => "{$shop['name']}: Hi {$snapshot['customer_first_name']}, we got your order {$snapshot['number']} ({$snapshot['totals']['grand_total_plain']}).{$track}",
        };
    }

    /**
     * @return Collection<int, User>
     */
    private function owners(): Collection
    {
        return User::query()
            ->inCurrentShop()
            ->whereNotNull('tenant_id')
            ->where('is_owner', true)
            ->orderBy('id')
            ->get(['id', 'email']);
    }

    private function money(float $amount, string $currency, bool $plain = false): string
    {
        $formatted = number_format($amount, 2);

        if ($currency === 'BDT') {
            return ($plain ? 'Tk ' : '৳').$formatted;
        }

        return "{$currency} {$formatted}";
    }

    /**
     * Sample order for previewing email designs before the shop has real orders.
     */
    public function sampleOrder(): OnlineOrder
    {
        $order = new OnlineOrder([
            'number' => 'WEB-'.now()->format('Ymd').'-0001',
            'access_token' => str_repeat('a', 40),
            'status' => OnlineOrderStatus::Pending,
            'customer_name' => 'Nusrat Jahan',
            'customer_email' => 'nusrat@example.com',
            'customer_phone' => '01711-223344',
            'shipping_address' => "House 12, Road 5, Dhanmondi\nDistrict: Dhaka",
            'delivery_zone' => 'Inside Dhaka',
            'payment_method' => PaymentMethod::Cod,
            'currency' => 'BDT',
            'subtotal' => 2350,
            'coupon_code' => 'WELCOME10',
            'discount_total' => 235,
            'shipping_fee' => 60,
            'grand_total' => 2175,
        ]);
        $order->id = 0;
        $order->created_at = now();
        $order->setRelation('items', collect([
            ['name' => 'Premium Assam Tea 500g', 'sku' => 'TEA-500', 'quantity' => 2, 'unit_price' => 650, 'line_total' => 1300],
            ['name' => 'Ceramic Tea Mug', 'sku' => 'MUG-01', 'quantity' => 1, 'unit_price' => 1050, 'line_total' => 1050],
        ])->map(fn (array $item) => new OnlineOrderItem($item)));

        return $order;
    }
}

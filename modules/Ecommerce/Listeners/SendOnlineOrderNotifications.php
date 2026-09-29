<?php

namespace Modules\Ecommerce\Listeners;

use App\Core\Events\OnlineOrderCancelled;
use App\Core\Events\OnlineOrderConfirmed;
use App\Core\Events\OnlineOrderPlaced;
use Closure;
use Modules\Ecommerce\Models\OnlineOrder;
use Modules\Ecommerce\Services\OrderNotificationService;
use Throwable;

class SendOnlineOrderNotifications
{
    public function __construct(private readonly OrderNotificationService $notifications) {}

    public function handlePlaced(OnlineOrderPlaced $event): void
    {
        $this->forOrder($event->orderId, fn (OnlineOrder $order) => $this->notifications->orderPlaced($order));
    }

    public function handleConfirmed(OnlineOrderConfirmed $event): void
    {
        $this->forOrder($event->orderId, fn (OnlineOrder $order) => $this->notifications->orderConfirmed($order));
    }

    public function handleCancelled(OnlineOrderCancelled $event): void
    {
        $this->forOrder($event->orderId, fn (OnlineOrder $order) => $this->notifications->orderCancelled($order));
    }

    /**
     * A failed notification is reported but must never undo the order change that triggered it.
     *
     * @param  Closure(OnlineOrder): void  $notify
     */
    private function forOrder(int $orderId, Closure $notify): void
    {
        $order = OnlineOrder::query()->with('items')->find($orderId);

        if ($order === null) {
            return;
        }

        try {
            $notify($order);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}

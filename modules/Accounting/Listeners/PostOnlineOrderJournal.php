<?php

namespace Modules\Accounting\Listeners;

use App\Core\Events\OnlineOrderCancelled;
use App\Core\Events\OnlineOrderConfirmed;
use Modules\Accounting\Services\ChartOfAccountsService;
use Modules\Accounting\Services\JournalService;

class PostOnlineOrderJournal
{
    public function __construct(
        private readonly JournalService $journals,
        private readonly ChartOfAccountsService $accounts,
    ) {}

    public function handleConfirmed(OnlineOrderConfirmed $event): void
    {
        $this->accounts->ensureDefaults();
        $this->journals->postSalesCredit(
            orderNumber: $event->orderNumber,
            amount: $event->amount,
            currency: $event->currency,
            orderId: $event->orderId,
            customerName: $event->customerName,
            sourceType: 'online_order_confirmed',
        );
    }

    public function handleCancelled(OnlineOrderCancelled $event): void
    {
        if (! $event->wasConfirmed) {
            return;
        }

        $this->accounts->ensureDefaults();
        $this->journals->reverseSalesCredit(
            orderNumber: $event->orderNumber,
            amount: $event->amount,
            currency: $event->currency,
            orderId: $event->orderId,
            customerName: $event->customerName,
            confirmSourceType: 'online_order_confirmed',
            cancelSourceType: 'online_order_cancelled',
        );
    }
}

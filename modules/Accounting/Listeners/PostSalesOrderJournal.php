<?php

namespace Modules\Accounting\Listeners;

use App\Core\Events\SalesOrderCancelled;
use App\Core\Events\SalesOrderConfirmed;
use Modules\Accounting\Services\ChartOfAccountsService;
use Modules\Accounting\Services\JournalService;

class PostSalesOrderJournal
{
    public function __construct(
        private readonly JournalService $journals,
        private readonly ChartOfAccountsService $accounts,
    ) {}

    public function handleConfirmed(SalesOrderConfirmed $event): void
    {
        $this->accounts->ensureDefaults();
        $this->journals->postSalesCredit(
            orderNumber: $event->orderNumber,
            amount: $event->amount,
            currency: $event->currency,
            orderId: $event->orderId,
            customerName: $event->customerName,
        );
    }

    public function handleCancelled(SalesOrderCancelled $event): void
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
        );
    }
}

<?php

namespace Modules\Accounting\Listeners;

use App\Core\Events\PosSaleCancelled;
use App\Core\Events\PosSaleCompleted;
use Modules\Accounting\Services\ChartOfAccountsService;
use Modules\Accounting\Services\JournalService;

class PostPosSaleJournal
{
    public function __construct(
        private readonly JournalService $journals,
        private readonly ChartOfAccountsService $accounts,
    ) {}

    public function handleCompleted(PosSaleCompleted $event): void
    {
        $this->accounts->ensureDefaults();
        $this->journals->postCashSale(
            orderNumber: $event->orderNumber,
            amount: $event->amount,
            currency: $event->currency,
            orderId: $event->orderId,
            customerName: $event->customerName,
        );
    }

    public function handleCancelled(PosSaleCancelled $event): void
    {
        $this->accounts->ensureDefaults();
        $this->journals->reverseCashSale(
            orderNumber: $event->orderNumber,
            amount: $event->amount,
            currency: $event->currency,
            orderId: $event->orderId,
            customerName: $event->customerName,
        );
    }
}

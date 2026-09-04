<?php

namespace Modules\Accounting\Listeners;

use App\Core\Events\PurchaseGoodsReceived;
use Modules\Accounting\Services\ChartOfAccountsService;
use Modules\Accounting\Services\JournalService;

class PostPurchaseReceiveJournal
{
    public function __construct(
        private readonly JournalService $journals,
        private readonly ChartOfAccountsService $accounts,
    ) {}

    public function handle(PurchaseGoodsReceived $event): void
    {
        $this->accounts->ensureDefaults();
        $this->journals->postPurchaseReceive(
            orderNumber: $event->orderNumber,
            amount: $event->amount,
            currency: $event->currency,
            orderId: $event->orderId,
            supplierName: $event->supplierName,
        );
    }
}

<?php

namespace App\Core\Events;

class OnlineOrderCancelled
{
    public function __construct(
        public readonly int $orderId,
        public readonly string $orderNumber,
        public readonly string $amount,
        public readonly string $currency,
        public readonly bool $wasConfirmed,
        public readonly ?string $customerName = null,
    ) {}
}

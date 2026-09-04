<?php

namespace App\Core\Events;

class PosSaleCancelled
{
    public function __construct(
        public readonly int $orderId,
        public readonly string $orderNumber,
        public readonly string $amount,
        public readonly string $currency,
        public readonly ?string $customerName = null,
    ) {}
}

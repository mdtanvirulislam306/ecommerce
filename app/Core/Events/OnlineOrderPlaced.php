<?php

namespace App\Core\Events;

class OnlineOrderPlaced
{
    public function __construct(
        public readonly int $orderId,
        public readonly string $orderNumber,
    ) {}
}

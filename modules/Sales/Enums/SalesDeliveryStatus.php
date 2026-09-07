<?php

namespace Modules\Sales\Enums;

enum SalesDeliveryStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Partial = 'partial';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::Partial => 'Partial',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Transitions for order-level or line-level delivery.
     * Partial is order-level only (derived from mixed line statuses).
     */
    public function canTransitionTo(self $to): bool
    {
        if ($this === $to) {
            return false;
        }

        return match ($this) {
            self::Pending => in_array($to, [self::Processing, self::Partial, self::Shipped, self::Delivered, self::Cancelled], true),
            self::Processing => in_array($to, [self::Partial, self::Shipped, self::Delivered, self::Cancelled], true),
            self::Partial => in_array($to, [self::Processing, self::Shipped, self::Delivered, self::Cancelled], true),
            self::Shipped => in_array($to, [self::Delivered, self::Cancelled], true),
            self::Delivered => false,
            self::Cancelled => false,
        };
    }

    public function isLineApplicable(): bool
    {
        return $this !== self::Partial;
    }
}

<?php

namespace Modules\Ecommerce\Enums;

enum OnlineOrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::Pending => in_array($to, [self::Confirmed, self::Cancelled], true),
            self::Confirmed => $to === self::Cancelled,
            self::Cancelled => false,
        };
    }
}

<?php

namespace Modules\Sales\Enums;

enum SalesOrderStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::Draft => in_array($to, [self::Pending, self::Confirmed, self::Cancelled], true),
            self::Pending => in_array($to, [self::Confirmed, self::Cancelled], true),
            self::Confirmed => $to === self::Cancelled,
            self::Cancelled => false,
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Draft, self::Pending], true);
    }

    public function depletesStock(): bool
    {
        return $this === self::Confirmed;
    }
}

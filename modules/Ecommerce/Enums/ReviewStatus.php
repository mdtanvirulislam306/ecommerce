<?php

namespace Modules\Ecommerce\Enums;

enum ReviewStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::Pending => $to === self::Approved || $to === self::Rejected,
            self::Approved => $to === self::Rejected,
            self::Rejected => $to === self::Approved,
        };
    }
}

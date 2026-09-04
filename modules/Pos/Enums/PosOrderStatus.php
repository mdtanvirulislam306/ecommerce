<?php

namespace Modules\Pos\Enums;

enum PosOrderStatus: string
{
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::Completed => $to === self::Cancelled,
            self::Cancelled => false,
        };
    }
}

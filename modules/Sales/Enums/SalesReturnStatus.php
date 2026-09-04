<?php

namespace Modules\Sales\Enums;

enum SalesReturnStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Confirmed => 'Confirmed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function canConfirm(): bool
    {
        return $this === self::Draft;
    }
}
